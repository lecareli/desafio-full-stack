<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Logging\AuditLogger;
use Illuminate\Support\Facades\Auth;

it('creates an audit log entry and resolves actor from a user instance', function () {
    $user = User::factory()->create();

    $log = app(AuditLogger::class)->log(
        action: 'test_action',
        entityType: 'test',
        entityId: '123',
        before: ['a' => 1],
        after: ['a' => 2],
        description: 'Test',
        actor: $user
    );

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and((string) $log->actor_user_id)->toBe((string) $user->id)
        ->and($log->action)->toBe('test_action')
        ->and($log->entity_type)->toBe('test')
        ->and($log->entity_id)->toBe('123')
        ->and($log->before)->toMatchArray(['a' => 1])
        ->and($log->after)->toMatchArray(['a' => 2])
        ->and($log->request_id)->not->toBeNull();
});

it('resolves actor from the authenticated user when none is provided', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $log = app(AuditLogger::class)->log(
        action: 'test_action',
        entityType: 'test',
        entityId: '123',
        before: null,
        after: null,
        description: null,
        actor: null
    );

    expect((string) $log->actor_user_id)->toBe((string) $user->id);
});

it('resolves actor from a string id when provided', function () {
    $user = User::factory()->create();

    $log = app(AuditLogger::class)->log(
        action: 'test_action',
        entityType: 'test',
        entityId: '123',
        before: null,
        after: null,
        description: null,
        actor: (string) $user->id
    );

    expect((string) $log->actor_user_id)->toBe((string) $user->id);
});
