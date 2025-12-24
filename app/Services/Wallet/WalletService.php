<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance_cents' => 0, 'currency' => 'BRL']
        );
    }
}
