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

    <div style="margin-top: 14px; display:flex; flex-direction:column; gap:14px;">

        {{-- BLOCO HORIZONTAL: MINHA CARTEIRA --}}
        <section class="card"
            style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
            <div style="min-width: 240px;">
                <h1 class="title" style="margin:0;">Minha carteira</h1>
                <p class="text" style="margin:6px 0 0;">Veja seu saldo e realize movimentações.</p>
            </div>

            <div
                style="flex:1; min-width: 280px; padding: 14px; border-radius: 14px; border: 1px solid #2a2a2f; background:#0f0f12;">
                <div class="text" style="display:flex; align-items:center; justify-content:space-between; gap: 12px;">
                    <span>Saldo atual</span>

                    <strong style="font-size: 20px; color:#fff;">
                        {{ $walletCurrency ?? 'BRL' }}
                        {{ number_format(($walletBalanceCents ?? 0) / 100, 2, ',', '.') }}
                    </strong>
                </div>

                <div class="text" style="margin-top: 8px; font-size: 12px;">
                    Atualizado em: {{ $walletUpdatedAt ?? '—' }}
                </div>
            </div>
        </section>

        {{-- BLOCO HORIZONTAL: MOVIMENTAÇÕES --}}
        <section class="card">
            <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div>
                    <h2 class="title" style="margin:0;">Movimentações</h2>
                    <p class="text" style="margin:6px 0 0;">Deposite, transfira ou retire saldo.</p>
                </div>
            </div>

            {{-- Cards lado a lado --}}
            <div style="margin-top: 14px; display:flex; gap:14px; flex-wrap:wrap;">
                {{-- Depósito --}}
                <div
                    style="flex:1; min-width: 260px; padding:14px; border-radius:14px; border:1px solid #2a2a2f; background:#0f0f12;">
                    <h3 style="margin:0 0 10px; font-size: 14px; font-weight: 800;">Depósito</h3>

                    <form method="POST" action="{{ route('wallet.deposit') }}">
                        @csrf

                        <div class="field">
                            <label for="deposit_amount">Valor (R$)</label>
                            <input id="deposit_amount" name="amount" type="text" inputmode="decimal"
                                placeholder="Ex: 150,00" value="{{ old('amount') }}" style="width:100%;">
                            <div class="text" style="font-size: 12px; margin-top: 6px;">
                                O valor será somado ao seu saldo.
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Depositar</button>
                    </form>
                </div>

                {{-- Transferência --}}
                <div
                    style="flex:1; min-width: 260px; padding:14px; border-radius:14px; border:1px solid #2a2a2f; background:#0f0f12;">
                    <h3 style="margin:0 0 10px; font-size: 14px; font-weight: 800;">Transferência</h3>

                    <form method="POST" action="{{ route('wallet.transfer') }}">
                        @csrf

                        <div class="field">
                            <label for="to_email">E-mail do destinatário</label>
                            <input id="to_email" name="to_email" type="email" autocomplete="email"
                                placeholder="destinatario@exemplo.com" value="{{ old('to_email') }}" style="width:100%;">
                        </div>

                        <div class="field">
                            <label for="transfer_amount">Valor (R$)</label>
                            <input id="transfer_amount" name="amount" type="text" inputmode="decimal"
                                placeholder="Ex: 50,00" value="{{ old('amount') }}" style="width:100%;">
                            <div class="text" style="font-size: 12px; margin-top: 6px;">
                                Será validado se você possui saldo antes de enviar.
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Transferir</button>
                    </form>
                </div>

                {{-- Retirada --}}
                <div
                    style="flex:1; min-width: 260px; padding:14px; border-radius:14px; border:1px solid #2a2a2f; background:#0f0f12;">
                    <h3 style="margin:0 0 10px; font-size: 14px; font-weight: 800;">Retirada</h3>

                    <form method="POST" action="{{ route('wallet.withdraw') }}">
                        @csrf

                        <div class="field">
                            <label for="withdraw_amount">Valor (R$)</label>
                            <input id="withdraw_amount" name="amount" type="text" inputmode="decimal"
                                placeholder="Ex: 80,00" value="{{ old('amount') }}" style="width:100%;">
                            <div class="text" style="font-size: 12px; margin-top: 6px;">
                                O valor será subtraído do seu saldo.
                            </div>
                        </div>

                        <div class="field">
                            <label for="withdraw_description">Descrição (opcional)</label>
                            <input id="withdraw_description" name="description" type="text"
                                placeholder="Ex: retirada para despesas" value="{{ old('description') }}"
                                style="width:100%;">
                        </div>

                        <button class="btn btn-primary" type="submit">Retirar</button>
                    </form>
                </div>
            </div>
        </section>

        {{-- Extrato (mantido como está) --}}
        <section class="card" style="margin-top: 0;">
            <div style="display:flex; align-items:flex-end; justify-content:space-between; gap: 12px;">
                <div>
                    <h2 class="title">Extrato</h2>
                    <p class="text">Movimentações recentes da sua carteira.</p>
                </div>

                <form method="GET" action="{{ route('wallet.index') }}" style="display:flex; gap: 8px; align-items:center;">
                    <input name="q" type="text" value="{{ request('q') }}" placeholder="Buscar (descrição/tipo)"
                        style="width: 260px;">
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
                                $rawType = is_object($tx->type ?? null) ? ($tx->type->value ?? (string) $tx->type) : (string) ($tx->type ?? '');
                                $rawStatus = is_object($tx->status ?? null) ? ($tx->status->value ?? (string) $tx->status) : (string) ($tx->status ?? '');

                                $type = strtolower($rawType);
                                $status = strtolower($rawStatus);

                                $typeLabels = [
                                    'deposit' => 'Depósito',
                                    'transfer' => 'Transferência',
                                    'reversal' => 'Estorno',
                                    'withdraw' => 'Retirada',
                                ];

                                $statusLabels = [
                                    'posted' => 'Concluída',
                                    'reversed' => 'Estornada',
                                    'failed' => 'Falhou',
                                ];

                                $typeLabel = $typeLabels[$type] ?? strtoupper($rawType ?: '—');
                                $statusLabel = $statusLabels[$status] ?? strtoupper($rawStatus ?: '—');

                                $amount = (int) ($tx->amount_cents ?? 0);
                                $isReversed = $status === 'reversed' || !empty($tx->reversal_of_id);
                                $canReverse = !$isReversed && in_array($type, ['deposit', 'transfer'], true) && $status === 'posted';
                            @endphp

                            <tr style="border-top: 1px solid #2a2a2f;">
                                <td style="padding: 10px 12px; font-size: 13px; color:#d6d6dd;">
                                    {{ optional($tx->created_at)->format('d/m/Y H:i') ?? '—' }}
                                </td>

                                <td style="padding: 10px 12px; font-size: 13px; color:#d6d6dd;">
                                    <span
                                        style="padding: 4px 8px; border-radius: 999px; border: 1px solid #2a2a2f; background:#131316;">
                                        {{ $typeLabel }}
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
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <td style="padding: 10px 12px; text-align:right;">
                                    @if ($canReverse)
                                        <form method="POST" action="{{ route('wallet.transactions.reverse', $tx->id) }}"
                                            style="display:inline;" onsubmit="return confirm('Confirmar estorno desta operação?');">
                                            @csrf
                                            <button class="btn" type="submit" style="padding: 8px 10px;">
                                                Estornar
                                            </button>
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

            @if (!empty($transactions) && method_exists($transactions, 'links'))
                <div style="margin-top: 12px;">
                    {{ $transactions->links('dashboard.wallet.pagination') }}
                </div>
            @endif
        </section>

    </div>
@endsection
