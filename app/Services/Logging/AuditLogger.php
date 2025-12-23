<?php

namespace App\Services\Logging;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\RequestContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(protected RequestContext $ctx) {}

    public function log(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        ?array $before,
        ?array $after = null,
        ?string $description = null,
        User|string|null $actor = null,
    ): AuditLog
    {
        $actorId = $this->resolveActorId($actor);

        return AuditLog::create([
            'actor_user_id' => $actorId,
            'action'        => $action,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId ? (string) $entityId : null,
            'before'        => $before,
            'after'         => $after,
            'description'   => $description,
            'ip'            => $this->ctx->ip(),
            'user_agent'    => $this->ctx->userAgent(),
            'request_id'    => $this->ctx->requestId(),
            'created_at'    => Carbon::now(),
        ]);
    }

    public function logModelChange(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        ?array $before,
        ?array $after,
        ?string $description = null,
        User|string|null $actor = null,
    ): AuditLog
    {
        return $this->log(
            $action,
            $entityType,
            $entityId,
            $before,
            $after,
            $description,
            $actor
        );
    }

    protected function resolveActorId(User|string|null $actor): ?string
    {
        if($actor instanceof User) {
            return (string) $actor->id;
        }

        if(is_string($actor) && $actor !== '') {
            return $actor;
        }

        $authUser = Auth::user();

        return $authUser ? (string) $authUser->id : null;
    }
}
