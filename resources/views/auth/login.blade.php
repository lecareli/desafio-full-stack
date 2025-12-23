@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <h1 class="title">Entrar</h1>
    <p class="subtitle">Acesse sua conta para continuar.</p>

    {{-- Erros gerais --}}
    @if ($errors->any())
        <div class="error-box">
            <strong>Ops!</strong> Verifique seus dados e tente novamente.
        </div>
    @endif

    <form method="POST" action="{{ route('auth.login') }}">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn" type="submit" style="margin-top: 14px;">
            Entrar
        </button>
    </form>
@endsection

@section('footer')
    Ainda não tem conta?
    <a href="{{ route('auth.view.register') }}">Criar conta</a>
@endsection
