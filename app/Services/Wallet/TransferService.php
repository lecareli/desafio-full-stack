<?php

namespace App\Services\Wallet;

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\RecipientNotFoundException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class TransferService
{
    public function __construct(
        protected AuditLogger $audit,
        protected ErrorLogger $error
    ) {}

    public function transfer(User $actor, string $toEmail, string $rawAmount, ?string $description = null): Transaction
    {
        $amountCents = $this->parseAmountToCents($rawAmount);

        if($amountCents <= 0) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }

        //destinatário precisa existir
        $recipient = User::query()
            ->where('email', $toEmail)
            ->whereNull('deleted_at')
            ->first();

        if(!$recipient) {
            throw new RecipientNotFoundException('Destinatário não encontrado. Verifique o e-mail e tente novamente.');
        }

        if((string) $recipient->id === (string) $actor->id) {
            throw new InvalidArgumentException('Você não pode transferir para você mesmo.');
        }

        try
        {
            return DB::transaction(function () use ($actor, $recipient, $toEmail, $rawAmount, $amountCents, $description) {

                //Garante wallet
                $fromWallet = Wallet::firstOrCreate(
                    ['user_id' => $actor->id],
                    ['balance_cents' => 0, 'currency' => 'BRL']
                );

                $toWallet = Wallet::firstOrCreate(
                    ['user_id' => $recipient->id],
                    ['balance_cents' => 0, 'currency' => 'BRL']
                );

                //lock consistente (ordem por id) para evitar deadlock
                $walletIds = [(string) $fromWallet->id, (string) $toWallet->id];
                sort($walletIds);

                $locked = Wallet::query()
                    ->whereIn('id', $walletIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $fromWallet = $locked[$fromWallet->id];
                $toWallet = $locked[$toWallet->id];

                $beforeFrom = (int) $fromWallet->balance_cents;
                $beforeTo = (int) $toWallet->balance_cents;

                if($beforeFrom < $amountCents) {
                    throw new InsufficientBalanceException('Saldo insufuciente para realizar a transferência');
                }

                $afterFrom = $beforeFrom - $amountCents;
                $afterTo = $beforeTo + $amountCents;

                $fromWallet->update(['balance_cents' => $afterFrom]);
                $toWallet->update(['balance_cents' => $afterTo]);

                $tx = Transaction::create([
                    'type'              => TransactionTypeEnum::TRANSFER,
                    'status'            => TransactionStatusEnum::POSTED,
                    'amount_cents'      => $amountCents,
                    'from_wallet_id'    => $fromWallet->id,
                    'to_wallet_id'      => $toWallet->id,
                    'reversal_of_id'    => null,
                    'created_by'        => $actor->id,
                    'description'       => $description ?: "Transferência para {$toEmail}",
                    'meta' => [
                        'raw_amount'    => $rawAmount,
                        'to_email'      => $toEmail,
                        'from_user_id'  => (string) $actor->id,
                        'to_user_id'    => (string) $recipient->id,
                    ],
                ]);

                $this->audit->log(
                    'transfer_posted',
                    'transaction',
                    (string) $tx->id,
                    [
                        'from_wallet_id' => (string) $fromWallet->id,
                        'to_wallet_id' => (string) $toWallet->id,
                        'from_balance_cents' => $beforeFrom,
                        'to_balance_cents' => $beforeTo
                    ],
                    [
                        'transaction_id' => (string) $tx->id,
                        'amount_cents' => $amountCents,
                        'from_balance_cents' => $afterFrom,
                        'to_balance_cents' => $afterTo
                    ],
                    'Transferência realizada com sucesso.',
                    $actor
                );

                return $tx;
            });
        }
        catch(Throwable $e)
        {
            $this->error->exception(
                $e,
                ErrorLevelEnum::ERROR,
                [
                    'action' => 'transfer',
                    'from_user_id' => (string) $actor->id,
                    'to_email' => $toEmail,
                    'amount_raw' => $rawAmount,
                    'amount_cents' => $amountCents ?? null
                ],
                (string) $actor->id
            );

            throw $e;
        }
    }

    private function parseAmountToCents(string $raw): int
    {
        $v = trim($raw);
        $v = str_ireplace(['R$', ' '], '', $v);

        $hasComma = str_contains($v, ',');
        $hasDot   = str_contains($v, '.');

        if ($hasComma && $hasDot) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        elseif ($hasComma) {
            $v = str_replace(',', '.', $v);
        }

        if (!is_numeric($v)) {
            throw new InvalidArgumentException('Informe um valor válido. Ex.: 50,00');
        }

        $num = (float) $v;
        $cents = (int) round($num * 100);

        if ($cents > 9_999_999_999_99) {
            throw new InvalidArgumentException('Valor muito alto. Verifique e tente novamente.');
        }

        return $cents;
    }
}
