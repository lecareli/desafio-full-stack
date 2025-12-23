@extends('layouts.app')

@section('title', 'Carteira')

@section('content')
    {{-- Flash messages --}}
    @if (session('success'))
        <div
            style="margin-top: 14px; background:#0f2a16; border: 1px solid #1f6b2e; padding: 10px 12px; border-radius: 12px; color:#b7ffd0;">
            <strong>Sucesso:</strong> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            style="margin-top: 14px; background:#2a0f13; border: 1px solid #6b1a24; padding: 10px 12px; border-radius: 12px; color:#ffb4be;">
            <strong>Erro:</strong> {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div
            style="margin-top: 14px; background:#2a0f13; border: 1px solid #6b1a24; padding: 10px 12px; border-radius: 12px; color:#ffb4be;">
            <strong>Confira os campos:</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li style="margin: 4px 0;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="margin-top: 14px;">
        <div class="grid">
            {{-- Saldo --}}
            <section class="card">
                <h1 class="title">Minha carteira</h1>
                <p class="text">Veja seu saldo e realize movimentações.</p>

                <div
                    style="margin-top: 14px; padding: 14px; border-radius: 14px; border: 1px solid #2a2a2f; background:#0f0f12;">
                    <div class="text" style="display:flex; align-items:center; justify-content:space-between; gap: 12px;">
                        <span>Saldo atual</span>

                        {{-- Espera $wallet->balance_cents e $wallet->currency --}}
                        <strong style="font-size: 18px; color:#fff;">
                            {{ $walletCurrency ?? 'BRL' }}
                            {{ number_format(($walletBalanceCents ?? 0) / 100, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="text" style="margin-top: 8px; font-size: 12px;">
                        Atualizado em: {{ $walletUpdatedAt ?? '—' }}
                    </div>
                </div>

                <div style="margin-top: 12px;" class="text">
                    Dica: todas as operações ficam registradas no seu extrato.
                </div>
            </section>

            {{-- Ações: Depósito e Transferência --}}
            <aside class="card">
                <h2 class="title">Movimentações</h2>

                {{-- Depósito --}}
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #2a2a2f;">
                    <h3 style="margin:0 0 8px; font-size: 14px; font-weight: 800;">Depósito</h3>

                    <form method="POST" action="{{ route('wallet.deposit') }}">
                        @csrf

                        <div class="field">
                            <label for="deposit_amount">Valor (R$)</label>
                            <input id="deposit_amount" name="amount" type="text" inputmode="decimal"
                                placeholder="Ex: 150,00" value="{{ old('amount') }}">
                            <div class="text" style="font-size: 12px; margin-top: 6px;">
                                O valor será somado ao seu saldo.
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Depositar</button>
                    </form>
                </div>

                {{-- Transferência --}}
                <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #2a2a2f;">
                    <h3 style="margin:0 0 8px; font-size: 14px; font-weight: 800;">Transferência</h3>

                    <form method="POST" action="{{ route('wallet.transfer') }}">
                        @csrf

                        <div class="field">
                            <label for="to_email">E-mail do destinatário</label>
                            <input id="to_email" name="to_email" type="email" autocomplete="email"
                                placeholder="destinatario@exemplo.com" value="{{ old('to_email') }}">
                        </div>

                        <div class="field">
                            <label for="transfer_amount">Valor (R$)</label>
                            <input id="transfer_amount" name="amount" type="text" inputmode="decimal"
                                placeholder="Ex: 50,00" value="{{ old('amount') }}">
                            <div class="text" style="font-size: 12px; margin-top: 6px;">
                                Será validado se você possui saldo antes de enviar.
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Transferir</button>
                    </form>
                </div>
            </aside>
        </div>

        {{-- Extrato --}}
        <section class="card" style="margin-top: 14px;">
            <div style="display:flex; align-items:flex-end; justify-content:space-between; gap: 12px;">
                <div>
                    <h2 class="title">Extrato</h2>
                    <p class="text">Movimentações recentes da sua carteira.</p>
                </div>

                <form method="GET" action="{{ route('wallet.index') }}" style="display:flex; gap: 8px; align-items:center;">
                    <input name="q" type="text" value="{{ request('q') }}" placeholder="Buscar (descrição/tipo)"
                        style="max-width: 220px;">
                    <button class="btn" type="submit">Filtrar</button>
                </form>
            </div>

            <div style="margin-top: 12px; overflow:auto; border-radius: 12px; border: 1px solid #2a2a2f;">
                <table style="width:100%; border-collapse: collapse; min-width: 720px;">
                    <thead style="background:#0f0f12;">
                        <tr>
                            <th style="text-align:left; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Data</th>
                            <th style="text-align:left; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Tipo</th>
                            <th style="text-align:left; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Descrição</th>
                            <th style="text-align:right; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Valor</th>
                            <th style="text-align:left; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Status</th>
                            <th style="text-align:right; padding: 10px 12px; font-size: 12px; color:#b8b8c0;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($transactions ?? []) as $tx)
                                            @php
                                                $type = is_object($tx->type ?? null) ? $tx->type->value : ($tx->type ?? '');
                                                $status = is_object($tx->status ?? null) ? $tx->status->value : ($tx->status ?? '');
                                                $amount = (int) ($tx->amount_cents ?? 0);
                                                $isReversed = ($status === 'reversed') || !empty($tx->reversal_of_id);
                                                $canReverse = !$isReversed && in_array($type, ['deposit', 'transfer'], true);
                                            @endphp

                                            <tr style="border-top: 1px solid #2a2a2f;">
                                                <td style="padding: 10px 12px; font-size: 13px; color:#d6d6dd;">
                                                    {{ optional($tx->created_at)->format('d/m/Y H:i') ?? '—' }}
                                                </td>

                                                <td style="padding: 10px 12px; font-size: 13px; color:#d6d6dd;">
                                                    <span
                                                        style="padding: 4px 8px; border-radius: 999px; border: 1px solid #2a2a2f; background:#131316;">
                                                        {{ strtoupper($type ?: '—') }}
                                                    </span>
                                                </td>

                                                <td style="padding: 10px 12px; font-size: 13px; color:#b8b8c0;">
                                                    {{ $tx->description ?? '—' }}
                                                </td>

                                                <td style="padding: 10px 12px; text-align:right; font-size: 13px; color:#fff;">
                                                    {{ $walletCurrency ?? 'BRL' }}
                                                    {{ number_format($amount / 100, 2, ',', '.') }}
                                                </td>

                                                <td style="padding: 10px 12px; font-size: 13px;">
                                                    @php
                                                        $badgeBg = $status === 'posted' ? '#0f2a16' : ($status === 'reversed' ? '#2a0f13' : '#202028');
                                                        $badgeBd = $status === 'posted' ? '#1f6b2e' : ($status === 'reversed' ? '#6b1a24' : '#2a2a2f');
                                                        $badgeTx = $status === 'posted' ? '#b7ffd0' : ($status === 'reversed' ? '#ffb4be' : '#d6d6dd');
                                                    @endphp
                             <span
                                                        style="padding: 4px 8px; border-radius: 999px; border: 1px solid {{ $badgeBd }}; background: {{ $badgeBg }}; color: {{ $badgeTx }};">
                                                        {{ strtoupper($status ?: '—') }}
                                                    </span>
                                                </td>

                                                <td style="padding: 10px 12px; text-align:right;">
                                                    @if ($canReverse)
                                                        <form method="POST" action="{{ route('wallet.transactions.reverse', $tx->id) }}"
                                                            style="display:inline;">
                                                            @csrf
                                                            <button class="btn" type="submit" style="padding: 8px 10px;">Reverter</button>
                                                        </form>
                                                    @else
                                                        <span class="text" style="font-size: 12px;">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 14px 12px; color:#b8b8c0; font-size: 13px;">
                                    Nenhuma movimentação encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação (se você usar paginate) --}}
            @if (!empty($transactions) && method_exists($transactions, 'links'))
                <div style="margin-top: 12px;">
                    {{ $transactions->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
