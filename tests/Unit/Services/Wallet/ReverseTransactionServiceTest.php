<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\Wallet\AlreadyReversedException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\NotAllowedToReverseException;
use App\Exceptions\Wallet\TransactionNotFoundException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\ReverseTransactionService;
use App\Services\Wallet\TransferService;
use App\Services\Wallet\WithdrawService;
use Illuminate\Support\Str;

it('reverses a deposit by creating a reversal transaction and updating balances', function () {
    $user = User::factory()->create();

    $original = app(DepositService::class)->deposit($user, '100,00');
    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance_cents)->toBe(10_000);

    $reversal = app(ReverseTransactionService::class)->reverse($user, (string) $original->id);

    $wallet->refresh();
    $original->refresh();

    expect($wallet->balance_cents)->toBe(0)
        ->and($original->status)->toBe(TransactionStatusEnum::REVERSED)
        ->and($reversal->type)->toBe(TransactionTypeEnum::REVERSAL)
        ->and($reversal->status)->toBe(TransactionStatusEnum::POSTED)
        ->and((string) $reversal->reversal_of_id)->toBe((string) $original->id)
        ->and((string) $reversal->from_wallet_id)->toBe((string) $wallet->id)
        ->and($reversal->to_wallet_id)->toBeNull();
});

it('fails to reverse a deposit when the wallet no longer has enough balance', function () {
    $user = User::factory()->create();

    $original = app(DepositService::class)->deposit($user, '100,00');
    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    app(WithdrawService::class)->withdraw($user, '60,00');

    $wallet->refresh();
    expect($wallet->balance_cents)->toBe(4_000);

    expect(fn () => app(ReverseTransactionService::class)->reverse($user, (string) $original->id))->toThrow(
        InsufficientBalanceException::class,
        'Saldo insuficiente para reverter este depósito.'
    );

    $wallet->refresh();
    $original->refresh();

    expect($wallet->balance_cents)->toBe(4_000)
        ->and($original->status)->toBe(TransactionStatusEnum::POSTED);

    $this->assertDatabaseCount('transactions', 2); // depósito + retirada
});

it('reverses a transfer by moving balance back and creating a reversal transaction', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    $fromWallet = Wallet::factory()->for($actor)->create(['balance_cents' => 10_000]);

    $original = app(TransferService::class)->transfer($actor, $recipient->email, '50,00');

    $fromWallet->refresh();
    $toWallet = Wallet::query()->where('user_id', $recipient->id)->firstOrFail();

    expect($fromWallet->balance_cents)->toBe(5_000)
        ->and($toWallet->balance_cents)->toBe(5_000);

    $reversal = app(ReverseTransactionService::class)->reverse($actor, (string) $original->id);

    $fromWallet->refresh();
    $toWallet->refresh();
    $original->refresh();

    expect($fromWallet->balance_cents)->toBe(10_000)
        ->and($toWallet->balance_cents)->toBe(0)
        ->and($original->status)->toBe(TransactionStatusEnum::REVERSED)
        ->and($reversal->type)->toBe(TransactionTypeEnum::REVERSAL)
        ->and((string) $reversal->from_wallet_id)->toBe((string) $toWallet->id)
        ->and((string) $reversal->to_wallet_id)->toBe((string) $fromWallet->id);
});

it('fails to reverse a transfer when the recipient no longer has enough balance', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    $fromWallet = Wallet::factory()->for($actor)->create(['balance_cents' => 10_000]);

    $original = app(TransferService::class)->transfer($actor, $recipient->email, '50,00');

    $toWallet = Wallet::query()->where('user_id', $recipient->id)->firstOrFail();

    app(WithdrawService::class)->withdraw($recipient, '10,00');

    $fromWallet->refresh();
    $toWallet->refresh();

    expect($fromWallet->balance_cents)->toBe(5_000)
        ->and($toWallet->balance_cents)->toBe(4_000);

    expect(fn () => app(ReverseTransactionService::class)->reverse($actor, (string) $original->id))->toThrow(
        InsufficientBalanceException::class,
        'O destinatário não possui saldo suficiente para reverter esta transferência.'
    );

    $original->refresh();
    $fromWallet->refresh();
    $toWallet->refresh();

    expect($original->status)->toBe(TransactionStatusEnum::POSTED)
        ->and($fromWallet->balance_cents)->toBe(5_000)
        ->and($toWallet->balance_cents)->toBe(4_000);
});

it('does not allow reversing another users transaction', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $original = app(DepositService::class)->deposit($owner, '10,00');

    expect(fn () => app(ReverseTransactionService::class)->reverse($intruder, (string) $original->id))->toThrow(
        NotAllowedToReverseException::class,
        'Você não tem permissão para reverter esta transação.'
    );
});

it('throws already reversed when trying to reverse twice', function () {
    $user = User::factory()->create();

    $original = app(DepositService::class)->deposit($user, '10,00');

    app(ReverseTransactionService::class)->reverse($user, (string) $original->id);

    expect(fn () => app(ReverseTransactionService::class)->reverse($user, (string) $original->id))->toThrow(
        AlreadyReversedException::class,
        'Esta operação já foi revertida.'
    );
});

it('throws when the transaction does not exist', function () {
    $user = User::factory()->create();

    expect(fn () => app(ReverseTransactionService::class)->reverse($user, (string) Str::uuid()))->toThrow(
        TransactionNotFoundException::class,
        'Transação não encontrada.'
    );
});

it('only allows reversing deposits and transfers', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['balance_cents' => 10_000]);

    $withdrawTx = Transaction::create([
        'type' => TransactionTypeEnum::WITHDRAW,
        'status' => TransactionStatusEnum::POSTED,
        'amount_cents' => 1_000,
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'reversal_of_id' => null,
        'created_by' => $user->id,
        'description' => 'Retirada',
        'meta' => [],
    ]);

    expect(fn () => app(ReverseTransactionService::class)->reverse($user, (string) $withdrawTx->id))->toThrow(
        InvalidArgumentException::class,
        'Apenas depósitos e transferências podem ser revertidos.'
    );
});

it('returns an existing reversal transaction when present and original is still posted', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['balance_cents' => 0]);

    $original = Transaction::create([
        'type' => TransactionTypeEnum::DEPOSIT,
        'status' => TransactionStatusEnum::POSTED,
        'amount_cents' => 1_000,
        'from_wallet_id' => null,
        'to_wallet_id' => $wallet->id,
        'reversal_of_id' => null,
        'created_by' => $user->id,
        'description' => 'Depósito',
        'meta' => [],
    ]);

    $existingReversal = Transaction::create([
        'type' => TransactionTypeEnum::REVERSAL,
        'status' => TransactionStatusEnum::POSTED,
        'amount_cents' => 1_000,
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'reversal_of_id' => $original->id,
        'created_by' => $user->id,
        'description' => 'Estorno',
        'meta' => [
            'original_transaction_id' => (string) $original->id,
            'original_type' => (string) $original->type->value,
        ],
    ]);

    $result = app(ReverseTransactionService::class)->reverse($user, (string) $original->id);

    $original->refresh();

    expect((string) $result->id)->toBe((string) $existingReversal->id)
        ->and($original->status)->toBe(TransactionStatusEnum::POSTED);

    $this->assertDatabaseCount('transactions', 2);
});
