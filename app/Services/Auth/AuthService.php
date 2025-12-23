<?php

namespace App\Services\Auth;

use App\Enums\ErrorLevelEnum;
use App\Models\User;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService
{
    public function __construct(
        protected AuditLogger $audit,
        protected ErrorLogger $error
    ) {}

    public function register(array $data): User
    {
        $safe = $this->safeAuthContext($data);

        try
        {
            $user = User::create([
                'is_active' => true,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password'])
            ]);

            Auth::login($user);

            $this->audit->log(
                'register',
                'auth',
                (string) $user->id,
                null,
                [
                    'user_id' => (string) $user->id,
                    'email' => $user->email,
                    'is_active' => (bool) $user->is_active
                ],
                'Usuário registrado e autenticado'
            );

            return $user;
        }
        catch(Throwable $e)
        {
            $this->error->exception(
                $e,
                ErrorLevelEnum::ERROR,
                [
                    'action' => 'register',
                    'data' => $safe
                ]
            );

            throw $e;
        }
    }

    public function login(string $email, string $password): void
    {
        try
        {
            $ok = Auth::attempt([
                'email' => $email,
                'password' => $password
            ]);

            if(!$ok) {
                $this->error->log(
                    ErrorLevelEnum::WARNING,
                    'Falha de autenticação (credenciais inválidas).',
                    [
                        'action' => 'login',
                        'email' => $email
                    ]
                );

                throw new AuthenticationException('Credenciais inválidas.');
            }

            $user = Auth::user();

            if(!$user->is_active) {
                $this->audit->log(
                    'login_denied_inactive',
                    'auth',
                    (string) $user->id,
                    null,
                    [
                        'user_id' => (string) $user->id,
                        'email' => $email
                    ],
                    'Login negado: usuário inativo.'
                );
                Auth::logout();

                throw new AuthenticationException('Usuário inativo.');
            }

            $this->audit->log(
                'login',
                'auth',
                (string) $user->id,
                null,
                [
                    'user_id' => $user->id,
                    'email' => $user->email
                ],
                'Login realizado com sucesso.'
            );
        }
        catch(AuthenticationException $e)
        {
            $this->error->log(
                ErrorLevelEnum::WARNING,
                $e->getMessage(),
                [
                    'action' => 'login',
                    'email' => $email
                ]
            );

            throw $e;
        }
        catch(Throwable $e)
        {
            $this->error->exception(
                $e,
                ErrorLevelEnum::ERROR,
                [
                    'action' => 'login',
                    'email' => $email
                ]
            );

            throw $e;
        }
    }

    public function logout(?User $user = null): void
    {
        $user ??= Auth::user();

        try
        {
            if($user) {
                $this->audit->log(
                    'logout',
                    'auth',
                    (string) $user->id,
                    null,
                    [
                        'user_id' => (string) $user->id,
                        'email' => $user->email
                    ],
                    'Logout realizado.'
                );
            }

            Auth::logout();
        }
        catch(Throwable $e)
        {
            $this->error->exception(
                $e,
                ErrorLevelEnum::ERROR,
                [
                    'action' => 'logout',
                    'user_id' => $user?->id ? (string) $user->id : null,
                ],
                $user?->id ? (string) $user->id : null,
            );
        }
    }

    /**
     * Remove dados sensíveis
    */
    private function safeAuthContext(array $data): array
    {
        return [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ];
    }
}
