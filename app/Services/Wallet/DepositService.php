<?php

namespace App\Services\Wallet;

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class DepositService
{
    public function __construct(
        protected AuditLogger $audit,
        protected ErrorLogger $error
    ) {}

    public function deposit(User $actor, string $rawAmount, ?string $description = null): Transaction
    {
        $amountCents = $this->parseAmountToCents($rawAmount);

        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }

        try
        {
            return DB::transaction(function () use ($actor, $amountCents, $description, $rawAmount) {

                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $actor->id],
                    ['balance_cents' => 0, 'currency' => 'BRL']
                );

                // Lock para evitar concorrência
                $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

                $before = (int) $wallet->balance_cents;
                $after = $before + $amountCents;

                $wallet->update([
                    'balance_cents' => $after,
                ]);

                $tx = Transaction::create([
                    'type'              => TransactionTypeEnum::DEPOSIT,
                    'status'            => TransactionStatusEnum::POSTED,
                    'amount_cents'      => $amountCents,
                    'from_wallet_id'    => null,
                    'to_wallet_id'      => $wallet->id,
                    'reversal_of_id'    => null,
                    'created_by'        => $actor->id,
                    'description'       => $description ?: 'Depósito',
                    'meta' => [
                        'raw_amount'    => $rawAmount,
                        'currency'      => $wallet->currency,
                    ],
                ]);

                $this->audit->log(
                    'deposit_posted',
                    'transaction',
                    (string) $tx->id,
                    [
                        'wallet_id'     => (string) $wallet->id,
                        'balance_cents' => $before,
                    ],
                    [
                        'wallet_id'     => (string) $wallet->id,
                        'balance_cents' => $after,
                        'amount_cents'  => $amountCents,
                        'transaction_id' => (string) $tx->id,
                    ],
                    'Depósito realizado com sucesso',
                    $actor
                );

                return $tx;
            });
        }
        catch (Throwable $e)
        {
            $this->error->exception(
                $e,
                ErrorLevelEnum::ERROR,
                [
                    'action'        => 'deposit',
                    'user_id'       => (string) $actor->id,
                    'amount_raw'    => $rawAmount,
                    'amount_cents'  => $amountCents ?? null,
                ],
                (string) $actor->id,
            );

            throw $e;
        }
    }

    private function parseAmountToCents(string $raw): int
    {
        $v = trim($raw);
        $v = str_ireplace(['R$', ' '], '', $v);

        $hasComma = str_contains($v, ',');
        $hasDot = str_contains($v, '.');

        // Caso BR: 1.234,56 => remiove milhares (.) e troca decimal (,)
        if ($hasComma && $hasDot) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif ($hasComma) {
            $v = str_replace(',', '.', $v);
        }

        if (! is_numeric($v)) {
            throw new InvalidArgumentException('Informe um valor válido. Ex.: 150,00');
        }

        $num = (float) $v;
        $cents = (int) round($num * 100);

        // Proteção: evita depósitos absurdos por input errado
        if ($cents > 9_999_999_999_99) {
            throw new InvalidArgumentException('Valor muito alto. Verifique e tente novamente');
        }

        return $cents;
    }
}
