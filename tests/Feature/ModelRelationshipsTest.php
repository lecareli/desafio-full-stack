<?php

use App\Models\AuditLog;
use App\Models\ErrorLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

it('defines the expected model relationships', function () {
    $user = new User;
    $wallet = new Wallet;
    $transaction = new Transaction;
    $auditLog = new AuditLog;
    $errorLog = new ErrorLog;

    expect($user->wallets())->toBeInstanceOf(HasMany::class)
        ->and($user->wallets()->getForeignKeyName())->toBe('user_id')
        ->and($user->transactionsCreated())->toBeInstanceOf(HasMany::class)
        ->and($user->transactionsCreated()->getForeignKeyName())->toBe('created_by')
        ->and($user->auditLogs())->toBeInstanceOf(HasMany::class)
        ->and($user->auditLogs()->getForeignKeyName())->toBe('actor_user_id')
        ->and($user->errorLogs())->toBeInstanceOf(HasMany::class)
        ->and($user->errorLogs()->getForeignKeyName())->toBe('actor_user_id');

    expect($wallet->user())->toBeInstanceOf(BelongsTo::class)
        ->and($wallet->user()->getForeignKeyName())->toBe('user_id')
        ->and($wallet->outgoingTransactions())->toBeInstanceOf(HasMany::class)
        ->and($wallet->outgoingTransactions()->getForeignKeyName())->toBe('from_wallet_id')
        ->and($wallet->incomingTransactions())->toBeInstanceOf(HasMany::class)
        ->and($wallet->incomingTransactions()->getForeignKeyName())->toBe('to_wallet_id');

    expect($transaction->fromWallet())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->fromWallet()->getForeignKeyName())->toBe('from_wallet_id')
        ->and($transaction->toWallet())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->toWallet()->getForeignKeyName())->toBe('to_wallet_id')
        ->and($transaction->reversalOf())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->reversalOf()->getForeignKeyName())->toBe('reversal_of_id')
        ->and($transaction->reversals())->toBeInstanceOf(HasMany::class)
        ->and($transaction->reversals()->getForeignKeyName())->toBe('reversal_of_id')
        ->and($transaction->createdBy())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->createdBy()->getForeignKeyName())->toBe('created_by');

    expect($auditLog->actorUser())->toBeInstanceOf(BelongsTo::class)
        ->and($auditLog->actorUser()->getForeignKeyName())->toBe('actor_user_id')
        ->and($auditLog->entity())->toBeInstanceOf(MorphTo::class)
        ->and($auditLog->entity()->getMorphType())->toBe('entity_type')
        ->and($auditLog->entity()->getForeignKeyName())->toBe('entity_id');

    expect($errorLog->actorUser())->toBeInstanceOf(BelongsTo::class)
        ->and($errorLog->actorUser()->getForeignKeyName())->toBe('actor_user_id');
});
