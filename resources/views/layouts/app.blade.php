<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Home')</title>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: #0b0b0c;
            color: #f5f5f5;
            margin: 0;
        }

        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #2a2a2f;
            background: #131316;
            border-radius: 16px;
        }

        .brand {
            font-weight: 800;
            letter-spacing: .2px;
        }

        .meta {
            color: #b8b8c0;
            font-size: 13px;
        }

        .btn {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #2a2a2f;
            background: #0f0f12;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover {
            filter: brightness(1.05);
        }

        .btn-primary {
            border: none;
            background: #5b5bf7;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-top: 14px;
        }

        @media (min-width: 860px) {
            .grid {
                grid-template-columns: 1.2fr .8fr;
            }
        }

        .card {
            background: #131316;
            border: 1px solid #2a2a2f;
            border-radius: 16px;
            padding: 16px;
        }

        .title {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 800;
        }

        .text {
            margin: 0;
            color: #b8b8c0;
            font-size: 14px;
            line-height: 1.5;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px dashed #2a2a2f;
        }

        .row:first-child {
            border-top: 0;
            padding-top: 0;
        }

        a {
            color: #a8a8ff;                                                                                                             
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <header class="topbar">
            <div>
                <div class="brand">Wallet App</div>
                <div class="meta">Logado como: {{ auth()->user()->name }} ({{ auth()->user()->email }})</div>
            </div>

            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button class="btn" type="submit">Sair</button>
            </form>
        </header>

        @yield('content')
    </div>
</body>

</html>
