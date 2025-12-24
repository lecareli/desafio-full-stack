<?php

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Models\ErrorLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WithdrawService;

it('withdraws from the users wallet and creates a posted transaction', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['balance_cents' => 10_000]);

    $transaction = app(WithdrawService::class)->withdraw($user, '80,00');

    $wallet->refresh();

    expect($wallet->balance_cents)->toBe(2_000)
        ->and($transaction->type)->toBe(TransactionTypeEnum::WITHDRAW)
        ->and($transaction->status)->toBe(TransactionStatusEnum::POSTED)
        ->and((string) $transaction->from_wallet_id)->toBe((string) $wallet->id)
        ->and($transaction->to_wallet_id)->toBeNull()
        ->and($transaction->created_by)->toBe($user->id)
        ->and($transaction->amount_cents)->toBe(8_000);
});

it('throws insufficient balance and logs an error', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['balance_cents' => 2_000]);

    expect(fn () => app(WithdrawService::class)->withdraw($user, '80,00'))->toThrow(
        InsufficientBalanceException::class,
        'Saldo insuficiente para realizar a retirada.'
    );

    $wallet->refresh();

    expect($wallet->balance_cents)->toBe(2_000)
        ->and(ErrorLog::query()->where('level', ErrorLevelEnum::ERROR->value)->exists())->toBeTrue();
});

it('rejects non numeric amounts', function () {
    $user = User::factory()->create();

    expect(fn () => app(WithdrawService::class)->withdraw($user, 'abc'))->toThrow(
        InvalidArgumentException::class,
        'Informe um valor válido. Ex.: 80,00'
    );
});

it('rejects zero or negative amounts', function () {
    $user = User::factory()->create();

    expect(fn () => app(WithdrawService::class)->withdraw($user, '0'))->toThrow(
        InvalidArgumentException::class,
        'Informe um valor maior que zero.'
    );
});
