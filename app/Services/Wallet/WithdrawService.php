<?php

namespace App\Services\Wallet;

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class WithdrawService
{
    public function __construct(
        protected AuditLogger $audit,
        protected ErrorLogger $error
    ) {}

    public function withdraw(User $actor, string $rawAmount, ?string $description = null): Transaction
    {
        $amountCents = $this->parseAmountToCents($rawAmount);

        if($amountCents <= 0) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }

        try
        {
            return DB::transaction(function () use ($actor, $rawAmount, $amountCents, $description) {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $actor->id],
                    ['balance_cents' => 0, 'currency' => 'BRL']
                );

                $wallet = Wallet::query()
                    ->whereKey($wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = (int) $wallet->balance_cents;

                if($before < $amountCents) {
                    throw new InsufficientBalanceException('Saldo insuficiente para realizar a retirada.');
                }

                $after = $before - $amountCents;
                $wallet->update(['balance_cents' => $after]);

                $tx = Transaction::create([
                    'type'           => TransactionTypeEnum::WITHDRAW,
                    'status'         => TransactionStatusEnum::POSTED,
                    'amount_cents'   => $amountCents,
                    'from_wallet_id' => $wallet->id,
                    'to_wallet_id'   => null,
                    'reversal_of_id' => null,
                    'created_by'     => $actor->id,
                    'description'    => $description ?: 'Retirada',
                    'meta' => [
                        'raw_amount' => $rawAmount,
                        'user_id' => (string) $actor->id,
                    ],
                ]);

                $this->audit->log(
                    'withdraw_posted',
                    'transaction',
                    (string) $tx->id,
                    [
                        'wallet_id' => (string) $wallet->id,
                        'balance_cents' => $before,
                    ],
                    [
                        'transaction_id' => (string) $tx->id,
                        'amount_cents' => $amountCents,
                        'balance_cents' => $after,
                    ],
                    'Retirada realizada com sucesso.',
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
                    'action' => 'withdraw',
                    'user_id' => (string) $actor->id,
                    'amount_raw' => $rawAmount,
                    'amount_cents' => $amountCents ?? null,
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
        } elseif ($hasComma) {
            $v = str_replace(',', '.', $v);
        }

        if (!is_numeric($v)) {
            throw new InvalidArgumentException('Informe um valor válido. Ex.: 80,00');
        }

        $num = (float) $v;
        $cents = (int) round($num * 100);

        if ($cents > 9_999_999_999_99) {
            throw new InvalidArgumentException('Valor muito alto. Verifique e tente novamente.');
        }

        return $cents;
    }
}
