<?php

namespace App\Services\Wallet;

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\AlreadyReversedException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\NotAllowedToReverseException;
use App\Exceptions\Wallet\TransactionNotFoundException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class ReverseTransactionService
{
    public function __construct(
        protected AuditLogger $audit,
        protected ErrorLogger $error,
    ) {
    }

    public function reverse(User $actor, string $transactionId): Transaction
    {
        try
        {
            return DB::transaction(function () use ($actor, $transactionId) {
                $original = $this->getOriginalForUpdate($transactionId);

                $this->assertCanReverse($actor, $original);

                // Idempotência: se já existe reversão vinculada, retorna ela
                if ($existing = $this->findExistingReversal($original)) {
                    return $existing;
                }

                if ($this->isDeposit($original)) {
                    return $this->reverseDeposit($actor, $original);
                }

                if ($this->isTransfer($original)) {
                    return $this->reverseTransfer($actor, $original);
                }

                // Segurança extra (não deveria chegar aqui)
                throw new InvalidArgumentException('Apenas depósitos e transferências podem ser revertidos.');
            });
        }
        catch (Throwable $e)
        {
            $this->logError($e, $actor, $transactionId);
            throw $e;
        }
    }

    /* =========================================================
     * Orquestração / Guards
     * =======================================================*/

    private function getOriginalForUpdate(string $transactionId): Transaction
    {
        $original = Transaction::query()
            ->whereKey($transactionId)
            ->lockForUpdate()
            ->first();

        if (!$original) {
            throw new TransactionNotFoundException('Transação não encontrada.');
        }

        return $original;
    }

    private function assertCanReverse(User $actor, Transaction $original): void
    {
        $this->assertAllowedType($original);
        $this->assertOwner($actor, $original);
        $this->assertNotReversed($original);
    }

    private function assertAllowedType(Transaction $original): void
    {
        $type = $this->typeString($original);

        $allowed = [
            $this->enumToUpper(TransactionTypeEnum::DEPOSIT),
            $this->enumToUpper(TransactionTypeEnum::TRANSFER),
        ];

        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Apenas depósitos e transferências podem ser revertidos.');
        }
    }

    private function assertOwner(User $actor, Transaction $original): void
    {
        if ((string) $original->created_by !== (string) $actor->id) {
            throw new NotAllowedToReverseException('Você não tem permissão para reverter esta transação.');
        }
    }

    private function assertNotReversed(Transaction $original): void
    {
        $status = $this->statusString($original);

        if ($status === $this->enumToUpper(TransactionStatusEnum::REVERSED)) {
            throw new AlreadyReversedException('Esta operação já foi revertida.');
        }
    }

    private function findExistingReversal(Transaction $original): ?Transaction
    {
        return Transaction::query()
            ->where('reversal_of_id', $original->id)
            ->first();
    }

    /* =========================================================
     * Reversões (casos)
     * =======================================================
    */

    private function reverseDeposit(User $actor, Transaction $original): Transaction
    {
        $amount = (int) $original->amount_cents;

        $wallet = $this->lockWalletOrFail((string) $original->to_wallet_id);

        [$before, $after] = $this->subtractFromWallet($wallet, $amount, 'Saldo insuficiente para reverter este depósito.');

        $reversal = $this->createReversalTransaction(
            actor: $actor,
            original: $original,
            amount: $amount,
            fromWalletId: (string) $wallet->id,
            toWalletId: null,
            description: 'Estorno de depósito',
        );

        $this->markOriginalAsReversed($original);

        $this->auditDepositReversed($actor, $original, $wallet, $before, $after, $reversal);

        return $reversal;
    }

    private function reverseTransfer(User $actor, Transaction $original): Transaction
    {
        $amount = (int) $original->amount_cents;

        $fromWalletId = (string) $original->from_wallet_id; // remetente original
        $toWalletId = (string) $original->to_wallet_id;   // destinatário original

        [$fromWallet, $toWallet] = $this->lockTwoWallets($fromWalletId, $toWalletId);

        $beforeFrom = (int) $fromWallet->balance_cents;
        $beforeTo = (int) $toWallet->balance_cents;

        // quem recebeu precisa ter saldo para devolver
        if ($beforeTo < $amount) {
            throw new InsufficientBalanceException('O destinatário não possui saldo suficiente para reverter esta transferência.');
        }

        $afterTo = $beforeTo - $amount;
        $afterFrom = $beforeFrom + $amount;

        $toWallet->update(['balance_cents' => $afterTo]);
        $fromWallet->update(['balance_cents' => $afterFrom]);

        $reversal = $this->createReversalTransaction(
            actor: $actor,
            original: $original,
            amount: $amount,
            fromWalletId: (string) $toWallet->id,    // reversão inverte o fluxo
            toWalletId: (string) $fromWallet->id,
            description: 'Estorno de transferência',
        );

        $this->markOriginalAsReversed($original);

        $this->auditTransferReversed($actor, $original, $fromWalletId, $toWalletId, $beforeFrom, $beforeTo, $afterFrom, $afterTo, $reversal);

        return $reversal;
    }

    /* =========================================================
     * Persistência / Helpers de DB
     * =======================================================*/

    private function lockWalletOrFail(string $walletId): Wallet
    {
        return Wallet::query()
            ->whereKey($walletId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Lock consistente por id para evitar deadlock.
     * Retorna [fromWallet, toWallet] na ordem ORIGINAL de ids passados.
     */
    private function lockTwoWallets(string $fromWalletId, string $toWalletId): array
    {
        $ids = [$fromWalletId, $toWalletId];
        sort($ids);

        $locked = Wallet::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return [$locked[$fromWalletId], $locked[$toWalletId]];
    }

    private function subtractFromWallet(Wallet $wallet, int $amount, string $errorMessage): array
    {
        $before = (int) $wallet->balance_cents;

        if ($before < $amount) {
            throw new InsufficientBalanceException($errorMessage);
        }

        $after = $before - $amount;
        $wallet->update(['balance_cents' => $after]);

        return [$before, $after];
    }

    private function markOriginalAsReversed(Transaction $original): void
    {
        $original->update(['status' => TransactionStatusEnum::REVERSED]);
    }

    private function createReversalTransaction(
        User $actor,
        Transaction $original,
        int $amount,
        ?string $fromWalletId,
        ?string $toWalletId,
        string $description
    ): Transaction {
        return Transaction::create([
            'type' => TransactionTypeEnum::REVERSAL,
            'status' => TransactionStatusEnum::POSTED,
            'amount_cents' => $amount,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'reversal_of_id' => $original->id,
            'created_by' => $actor->id,
            'description' => $description,
            'meta' => [
                'original_transaction_id' => (string) $original->id,
                'original_type' => $this->enumToString($original->type),
            ],
        ]);
    }

    /* =========================================================
     * Auditoria / Erros
     * =======================================================*/

    private function auditDepositReversed(
        User $actor,
        Transaction $original,
        Wallet $wallet,
        int $before,
        int $after,
        Transaction $reversal
    ): void {
        $this->audit->log(
            'deposit_reversed',
            'transaction',
            (string) $original->id,
            ['wallet_id' => (string) $wallet->id, 'balance_cents' => $before],
            ['wallet_id' => (string) $wallet->id, 'balance_cents' => $after, 'reversal_id' => (string) $reversal->id],
            'Depósito revertido com sucesso.',
            $actor
        );
    }

    private function auditTransferReversed(
        User $actor,
        Transaction $original,
        string $fromWalletId,
        string $toWalletId,
        int $beforeFrom,
        int $beforeTo,
        int $afterFrom,
        int $afterTo,
        Transaction $reversal
    ): void {
        $this->audit->log(
            'transfer_reversed',
            'transaction',
            (string) $original->id,
            [
                'from_wallet_id' => $fromWalletId,
                'to_wallet_id' => $toWalletId,
                'from_balance_cents' => $beforeFrom,
                'to_balance_cents' => $beforeTo,
            ],
            [
                'reversal_id' => (string) $reversal->id,
                'from_balance_cents' => $afterFrom,
                'to_balance_cents' => $afterTo,
            ],
            'Transferência revertida com sucesso.',
            $actor
        );
    }

    private function logError(Throwable $e, User $actor, string $transactionId): void
    {
        $this->error->exception(
            $e,
            ErrorLevelEnum::ERROR,
            [
                'action' => 'reverse_transaction',
                'transaction_id' => $transactionId,
                'user_id' => (string) $actor->id,
            ],
            (string) $actor->id
        );
    }

    /* =========================================================
     * Tipos / Status / Enum utils
     * =======================================================*/

    private function isDeposit(Transaction $tx): bool
    {
        return $this->typeString($tx) === $this->enumToUpper(TransactionTypeEnum::DEPOSIT);
    }

    private function isTransfer(Transaction $tx): bool
    {
        return $this->typeString($tx) === $this->enumToUpper(TransactionTypeEnum::TRANSFER);
    }

    private function typeString(Transaction $tx): string
    {
        return strtoupper($this->enumToString($tx->type));
    }

    private function statusString(Transaction $tx): string
    {
        return strtoupper($this->enumToString($tx->status));
    }

    private function enumToUpper(mixed $value): string
    {
        return strtoupper($this->enumToString($value));
    }

    private function enumToString(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return (string) $value->name;
        }

        return (string) $value;
    }
}
