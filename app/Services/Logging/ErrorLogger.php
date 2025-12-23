<?php

namespace App\Services\Logging;

use App\Enums\ErrorLevelEnum;
use App\Models\ErrorLog;
use App\Models\User;
use App\Support\RequestContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ErrorLogger
{
    public function __construct(protected RequestContext $ctx) {}

    public function log(
        ErrorLevelEnum $level,
        string $message,
        array $context = [],
        User|string|null $actor = null,
    ): ErrorLog
    {
        return ErrorLog::create([
            'actor_user_id'   => $this->resolveActorId($actor),
            'level'           => $level,
            'message'         => $message,
            'exception_class' => null,
            'file'            => null,
            'line'            => null,
            'trace'           => null,
            'context'         => $context,
            'route'           => $this->ctx->routeName(),
            'method'          => $this->ctx->method(),
            'url'             => $this->ctx->url(),
            'ip'              => $this->ctx->ip(),
            'request_id'      => $this->ctx->requestId(),
            'created_at'      => Carbon::now(),
        ]);
    }

    public function exception(
        Throwable $e,
        ErrorLevelEnum $level = ErrorLevelEnum::ERROR,
        array $context = [],
        User|string|null $actor = null,
        ?string $messageOverride = null
    ): ErrorLog
    {
        $trace = $this->safeTrace($e);

        return ErrorLog::create([
            'actor_user_id'   => $this->resolveActorId($actor),
            'level'           => $level,
            'message'         => $messageOverride ?? $e->getMessage(),
            'exception_class' => get_class($e),
            'file'            => $e->getFile(),
            'line'            => $e->getLine(),
            'trace'           => $trace,
            'context'         => $context,
            'route'           => $this->ctx->routeName(),
            'method'          => $this->ctx->method(),
            'url'             => $this->ctx->url(),
            'ip'              => $this->ctx->ip(),
            'request_id'      => $this->ctx->requestId(),
            'created_at'      => Carbon::now(),
        ]);
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

    protected function safeTrace(Throwable $e): string
    {
        $trace = $e->getTraceAsString();
        return mb_substr($trace, 0, 20000);
    }
}
