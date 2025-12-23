@extends('layouts.guest')

@section('title', 'Criar conta')

@section('content')
    <h1 class="title">Criar conta</h1>
    <p class="subtitle">Preencha seus dados para acessar sua carteira.</p>

    {{-- Erros gerais --}}
    @if ($errors->any())
        <div class="error-box">
            <strong>Ops!</strong> Verifique os campos e tente novamente.
        </div>
    @endif

    <form method="POST" action="{{ route('auth.register') }}">
        @csrf

        <div class="field">
            <label for="name">Nome</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>
            @error('name')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            @error('password')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                required>
        </div>

        <button class="btn" type="submit">
            Criar conta
        </button>
    </form>
@endsection

@section('footer')
    Já tem conta?
    <a href="{{ route('auth.view.login') }}">Entrar</a>
@endsection
