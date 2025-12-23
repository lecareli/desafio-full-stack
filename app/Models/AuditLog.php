<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'description',
        'ip',
        'user_agent',
        'request_id',
        'created_at',
    ];

    protected $casts = [
        'before'        => 'array',
        'after'         => 'array',
        'created_at'    => 'datetime'
    ];

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
