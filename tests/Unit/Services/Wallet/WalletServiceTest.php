<?php

use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;

it('creates a wallet with default values when missing', function () {
    $user = User::factory()->create();

    $wallet = app(WalletService::class)->getOrCreateWallet($user);

    expect($wallet->user_id)->toBe($user->id)
        ->and($wallet->balance_cents)->toBe(0)
        ->and($wallet->currency)->toBe('BRL');

    $this->assertDatabaseCount('wallets', 1);
});

it('returns the existing wallet and does not create a new one', function () {
    $user = User::factory()->create();
    $existing = Wallet::factory()->for($user)->create([
        'balance_cents' => 1234,
        'currency' => 'BRL',
    ]);

    $wallet = app(WalletService::class)->getOrCreateWallet($user);

    expect((string) $wallet->id)->toBe((string) $existing->id)
        ->and($wallet->balance_cents)->toBe(1234);

    $this->assertDatabaseCount('wallets', 1);
});
