@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="grid">
        <section class="card">
            <h1 class="title">Bem-vinda 👋</h1>
            <p class="text">
                Você está autenticada. A partir daqui vamos construir a carteira:
                depósito, transferência, reversão e extrato.
            </p>

            <div style="margin-top: 14px;">
                <a href="#" class="text">Ir para carteira (em breve)</a>
            </div>
        </section>

        <aside class="card">
            <h2 class="title">Próximos passos</h2>

            <div class="row">
                <span class="text">Criar Wallet automaticamente</span>
                <span class="text">⏳</span>
            </div>
            <div class="row">
                <span class="text">Depósito</span>
                <span class="text">⏳</span>
            </div>
            <div class="row">
                <span class="text">Transferência</span>
                <span class="text">⏳</span>
            </div>
            <div class="row">
                <span class="text">Reversão</span>
                <span class="text">⏳</span>
            </div>
        </aside>
    </div>
@endsection
