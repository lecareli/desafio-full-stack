<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DepositService;

it('deposits into the users wallet and creates a posted transaction', function () {
    $user = User::factory()->create();

    $transaction = app(DepositService::class)->deposit($user, '150,00');

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance_cents)->toBe(15_000)
        ->and($transaction->type)->toBe(TransactionTypeEnum::DEPOSIT)
        ->and($transaction->status)->toBe(TransactionStatusEnum::POSTED)
        ->and((string) $transaction->to_wallet_id)->toBe((string) $wallet->id)
        ->and($transaction->from_wallet_id)->toBeNull()
        ->and($transaction->created_by)->toBe($user->id)
        ->and($transaction->amount_cents)->toBe(15_000)
        ->and($transaction->meta)->toMatchArray([
            'raw_amount' => '150,00',
            'currency' => 'BRL',
        ]);

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'type' => TransactionTypeEnum::DEPOSIT->value,
        'status' => TransactionStatusEnum::POSTED->value,
        'created_by' => $user->id,
    ]);

    expect(AuditLog::query()->where('action', 'deposit_posted')->where('entity_id', (string) $transaction->id)->exists())->toBeTrue();
});

it('parses Brazilian formatted amounts', function () {
    $user = User::factory()->create();

    app(DepositService::class)->deposit($user, 'R$ 1.234,56', 'Depósito teste');

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance_cents)->toBe(123_456);
});

it('rejects non numeric amounts', function () {
    $user = User::factory()->create();

    expect(fn () => app(DepositService::class)->deposit($user, 'abc'))->toThrow(
        InvalidArgumentException::class,
        'Informe um valor válido. Ex.: 150,00'
    );
});

it('rejects zero or negative amounts', function () {
    $user = User::factory()->create();

    expect(fn () => app(DepositService::class)->deposit($user, '0'))->toThrow(
        InvalidArgumentException::class,
        'Informe um valor maior que zero.'
    );
});

it('rejects unrealistically large amounts', function () {
    $user = User::factory()->create();

    expect(fn () => app(DepositService::class)->deposit($user, '10000000000000'))->toThrow(
        InvalidArgumentException::class,
        'Valor muito alto. Verifique e tente novamente'
    );
});
