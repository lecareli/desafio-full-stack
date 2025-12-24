<?php

namespace Database\Factories;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_wallet_id' => Wallet::factory(),
            'to_wallet_id' => Wallet::factory(),
            'reversal_of_id' => null,
            'created_by' => User::factory(),
            'type' => TransactionTypeEnum::TRANSFER,
            'status' => TransactionStatusEnum::POSTED,
            'amount_cents' => fake()->numberBetween(1, 100_000),
            'description' => fake()->sentence(3),
            'meta' => [],
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn () => [
            'type' => TransactionTypeEnum::DEPOSIT,
            'from_wallet_id' => null,
            'to_wallet_id' => Wallet::factory(),
            'description' => 'Depósito',
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'type' => TransactionTypeEnum::TRANSFER,
        ]);
    }

    public function withdraw(): static
    {
        return $this->state(fn () => [
            'type' => TransactionTypeEnum::WITHDRAW,
            'from_wallet_id' => Wallet::factory(),
            'to_wallet_id' => null,
            'description' => 'Retirada',
        ]);
    }

    public function reversal(Transaction $original): static
    {
        return $this->state(fn () => [
            'type' => TransactionTypeEnum::REVERSAL,
            'status' => TransactionStatusEnum::POSTED,
            'reversal_of_id' => $original->id,
            'description' => 'Estorno',
            'meta' => [
                'original_transaction_id' => (string) $original->id,
                'original_type' => (string) $original->type->value,
            ],
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatusEnum::REVERSED,
        ]);
    }
}
