<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'reversal_of_id',
        'created_by',
        'type',
        'status',
        'amount_cents',
        'description',
        'meta',
    ];

    protected $casts = [
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
        'meta' => 'array',
    ];

    public function scopeForWallet(Builder $q, string $walletId): Builder
    {
        return $q->where(function ($w) use ($walletId) {
            $w->where('from_wallet_id', $walletId)
                ->orWhere('to_wallet_id', $walletId);
        });
    }

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
