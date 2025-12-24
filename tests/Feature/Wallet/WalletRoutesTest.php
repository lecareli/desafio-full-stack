<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('requires authentication to access the wallet', function () {
    $response = $this->get(route('wallet.index'));

    $response->assertRedirect(route('auth.view.login'));
});

it('shows the wallet index for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('wallet.index'));

    $response->assertOk();
    $response->assertSee('Minha carteira');
});

it('allows deposits and records a transaction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('wallet.index'))
        ->post(route('wallet.deposit'), [
            'amount' => '150,00',
            'description' => null,
        ]);

    $response->assertRedirect(route('wallet.index'));
    $response->assertSessionHas('success', 'Depósito realizado com sucesso.');

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
    expect($wallet->balance_cents)->toBe(15_000);

    $this->assertDatabaseHas('transactions', [
        'type' => TransactionTypeEnum::DEPOSIT->value,
        'status' => TransactionStatusEnum::POSTED->value,
        'created_by' => $user->id,
        'to_wallet_id' => $wallet->id,
    ]);
});

it('allows transfers and updates both wallets', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();

    $fromWallet = Wallet::factory()->for($actor)->create(['balance_cents' => 10_000]);

    $response = $this->actingAs($actor)
        ->from(route('wallet.index'))
        ->post(route('wallet.transfer'), [
            'to_email' => $recipient->email,
            'amount' => '50,00',
            'description' => null,
        ]);

    $response->assertRedirect(route('wallet.index'));
    $response->assertSessionHas('success', 'Transferência realizada com sucesso.');

    $fromWallet->refresh();
    $toWallet = Wallet::query()->where('user_id', $recipient->id)->firstOrFail();

    expect($fromWallet->balance_cents)->toBe(5_000)
        ->and($toWallet->balance_cents)->toBe(5_000);

    $this->assertDatabaseHas('transactions', [
        'type' => TransactionTypeEnum::TRANSFER->value,
        'status' => TransactionStatusEnum::POSTED->value,
        'created_by' => $actor->id,
        'from_wallet_id' => $fromWallet->id,
        'to_wallet_id' => $toWallet->id,
    ]);
});

it('allows withdrawals and decreases the wallet balance', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['balance_cents' => 10_000]);

    $response = $this->actingAs($user)
        ->from(route('wallet.index'))
        ->post(route('wallet.withdraw'), [
            'amount' => '80,00',
            'description' => null,
        ]);

    $response->assertRedirect(route('wallet.index'));
    $response->assertSessionHas('success', 'Retirada realizada com sucesso.');

    $wallet->refresh();
    expect($wallet->balance_cents)->toBe(2_000);
});

it('allows reversing a deposit via the route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('wallet.index'))
        ->post(route('wallet.deposit'), ['amount' => '10,00']);

    $original = Transaction::query()
        ->where('created_by', $user->id)
        ->where('type', TransactionTypeEnum::DEPOSIT->value)
        ->latest('created_at')
        ->firstOrFail();

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance_cents)->toBe(1_000);

    $response = $this->actingAs($user)
        ->from(route('wallet.index'))
        ->post(route('wallet.transactions.reverse', $original->id));

    $response->assertRedirect(route('wallet.index'));
    $response->assertSessionHas('success', 'reversão realizada com sucesso.');

    $wallet->refresh();
    $original->refresh();

    expect($wallet->balance_cents)->toBe(0)
        ->and($original->status)->toBe(TransactionStatusEnum::REVERSED);

    $this->assertDatabaseHas('transactions', [
        'type' => TransactionTypeEnum::REVERSAL->value,
        'reversal_of_id' => $original->id,
        'created_by' => $user->id,
    ]);
});
