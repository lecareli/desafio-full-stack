<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service) {}

    public function registerView()
    {
        return view('auth.register');
    }

    public function loginView()
    {
        return view('auth.login');
    }

    public function register(RegisterRequest $request)
    {
        $this->service->register($request->validated());
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        try {
            $this->service->login(
                $data['email'],
                $data['password']
            );

            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        } catch (AuthenticationException $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->onlyInput('email');
        } catch (Throwable $e) {
            return back()
                ->withErrors(['email' => 'Não foi possivel realizar o login'])
                ->onlyInput('email');
        }
    }

    public function logout(Request $request)
    {
        $this->service->logout($request->user());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.view.login');
    }
}
