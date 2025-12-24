<?php

use App\Enums\ErrorLevelEnum;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\Logging\ErrorLogger;
use Illuminate\Support\Facades\Auth;

it('creates an error log entry and resolves actor from a user instance', function () {
    $user = User::factory()->create();

    $log = app(ErrorLogger::class)->log(
        level: ErrorLevelEnum::WARNING,
        message: 'Test warning',
        context: ['a' => 1],
        actor: $user
    );

    expect($log)->toBeInstanceOf(ErrorLog::class)
        ->and((string) $log->actor_user_id)->toBe((string) $user->id)
        ->and($log->level)->toBe(ErrorLevelEnum::WARNING)
        ->and($log->message)->toBe('Test warning')
        ->and($log->context)->toMatchArray(['a' => 1])
        ->and($log->request_id)->not->toBeNull();
});

it('creates an error log from an exception and resolves actor from auth', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $exception = new RuntimeException('Boom');

    $log = app(ErrorLogger::class)->exception(
        e: $exception,
        level: ErrorLevelEnum::ERROR,
        context: ['action' => 'test'],
        actor: null
    );

    expect((string) $log->actor_user_id)->toBe((string) $user->id)
        ->and($log->level)->toBe(ErrorLevelEnum::ERROR)
        ->and($log->message)->toBe('Boom')
        ->and($log->exception_class)->toBe(RuntimeException::class)
        ->and($log->trace)->not->toBeNull();
});
