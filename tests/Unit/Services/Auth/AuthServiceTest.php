<?php

use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

it('registers a new user and authenticates them', function () {
    $service = app(AuthService::class);

    $user = $service->register([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('test@example.com')
        ->and(Hash::check('secret', $user->password))->toBeTrue()
        ->and(Auth::check())->toBeTrue()
        ->and((string) Auth::id())->toBe((string) $user->id);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'register',
        'entity_type' => 'auth',
        'entity_id' => (string) $user->id,
        'actor_user_id' => (string) $user->id,
    ]);
});

it('logs in an active user and writes an audit log', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    app(AuthService::class)->login($user->email, 'password');

    expect(Auth::check())->toBeTrue()
        ->and((string) Auth::id())->toBe((string) $user->id);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'login',
        'entity_type' => 'auth',
        'entity_id' => (string) $user->id,
        'actor_user_id' => (string) $user->id,
    ]);
});

it('rejects invalid credentials and throws an authentication exception', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    expect(fn () => app(AuthService::class)->login($user->email, 'wrong-password'))->toThrow(
        AuthenticationException::class,
        'Credenciais inválidas.'
    );

    expect(Auth::check())->toBeFalse();
    expect($this->app['db']->table('error_logs')->count())->toBeGreaterThanOrEqual(1);
});

it('rejects inactive users and logs them out', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    expect(fn () => app(AuthService::class)->login($user->email, 'password'))->toThrow(
        AuthenticationException::class,
        'Usuário inativo.'
    );

    expect(Auth::check())->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'login_denied_inactive',
        'entity_type' => 'auth',
        'entity_id' => (string) $user->id,
    ]);
});

it('logs out the current user and writes an audit log', function () {
    $user = User::factory()->create();
    Auth::login($user);

    app(AuthService::class)->logout($user);

    expect(Auth::check())->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'logout',
        'entity_type' => 'auth',
        'entity_id' => (string) $user->id,
        'actor_user_id' => (string) $user->id,
    ]);
});
