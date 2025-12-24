<?php

use App\Enums\ErrorLevelEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\RecipientNotFoundException;
use App\Models\ErrorLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\TransferService;

it('transfers between wallets and creates a posted transaction', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    $fromWallet = Wallet::factory()->for($actor)->create(['balance_cents' => 10_000]);

    $transaction = app(TransferService::class)->transfer($actor, $recipient->email, '50,00');

    $fromWallet->refresh();
    $toWallet = Wallet::query()->where('user_id', $recipient->id)->firstOrFail();

    expect($fromWallet->balance_cents)->toBe(5_000)
        ->and($toWallet->balance_cents)->toBe(5_000)
        ->and($transaction->type)->toBe(TransactionTypeEnum::TRANSFER)
        ->and($transaction->status)->toBe(TransactionStatusEnum::POSTED)
        ->and((string) $transaction->from_wallet_id)->toBe((string) $fromWallet->id)
        ->and((string) $transaction->to_wallet_id)->toBe((string) $toWallet->id)
        ->and($transaction->created_by)->toBe($actor->id)
        ->and($transaction->amount_cents)->toBe(5_000);
});

it('rejects missing recipients', function () {
    $actor = User::factory()->create();

    expect(fn () => app(TransferService::class)->transfer($actor, 'missing@example.com', '10,00'))->toThrow(
        RecipientNotFoundException::class,
        'Destinatário não encontrado. Verifique o e-mail e tente novamente.'
    );
});

it('rejects self transfer', function () {
    $actor = User::factory()->create();

    expect(fn () => app(TransferService::class)->transfer($actor, $actor->email, '10,00'))->toThrow(
        InvalidArgumentException::class,
        'Você não pode transferir para você mesmo.'
    );
});

it('rejects invalid amount format', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    expect(fn () => app(TransferService::class)->transfer($actor, $recipient->email, 'abc'))->toThrow(
        InvalidArgumentException::class,
        'Informe um valor válido. Ex.: 50,00'
    );
});

it('throws insufficient balance and logs an error', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    $fromWallet = Wallet::factory()->for($actor)->create(['balance_cents' => 4_000]);

    expect(fn () => app(TransferService::class)->transfer($actor, $recipient->email, '50,00'))->toThrow(
        InsufficientBalanceException::class,
        'Saldo insufuciente para realizar a transferência'
    );

    $this->assertDatabaseCount('transactions', 0);

    $fromWallet->refresh();

    expect($fromWallet->balance_cents)->toBe(4_000)
        ->and(ErrorLog::query()->where('level', ErrorLevelEnum::ERROR->value)->exists())->toBeTrue();
});
