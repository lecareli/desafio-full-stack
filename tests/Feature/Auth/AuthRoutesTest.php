<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and redirects to the wallet', function () {
    $response = $this->post(route('auth.register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
        'password_confirmation' => 'secret',
    ]);

    $response->assertRedirect(route('wallet.index'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

it('logs in a user and redirects to the wallet', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    $response = $this->post(route('auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('wallet.index'));
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    $response = $this->from(route('auth.view.login'))->post(route('auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('auth.view.login'));
    $response->assertSessionHasErrors(['email' => 'Credenciais inválidas.']);
    $this->assertGuest();
});

it('rejects inactive users', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    $response = $this->from(route('auth.view.login'))->post(route('auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('auth.view.login'));
    $response->assertSessionHasErrors(['email' => 'Usuário inativo.']);
    $this->assertGuest();
});

it('logs out and redirects to login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('auth.logout'));

    $response->assertRedirect(route('auth.view.login'));
    $this->assertGuest();
});
