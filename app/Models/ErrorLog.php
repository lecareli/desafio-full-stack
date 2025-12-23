<?php

namespace App\Models;

use App\Enums\ErrorLevelEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    use HasUuids;

    protected $table = 'error_logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'level',
        'message',
        'exception_class',
        'file',
        'line',
        'trace',
        'context',
        'route',
        'method',
        'url',
        'ip',
        'request_id',
        'created_at',
    ];

    protected $casts = [
        'level'         => ErrorLevelEnum::class,
        'context'       => 'array',
        'created_by'    => 'datetime'
    ];

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
