<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Autenticação')</title>

    {{-- Livewire só se você for usar em alguma tela/componente --}}
    {{-- @livewireStyles --}}
    <style>
        /* Resolve overflow de inputs com width:100% + padding/border */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: #0b0b0c;
            color: #f5f5f5;
        }

        .container {
            max-width: 420px;
            margin: 48px auto;
            padding: 0 16px;
        }

        .card {
            background: #131316;
            border: 1px solid #2a2a2f;
            border-radius: 16px;
            padding: 20px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .subtitle {
            font-size: 14px;
            color: #b8b8c0;
            margin: 0 0 18px;
        }

        .field {
            margin-bottom: 14px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: #d6d6dd;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #2a2a2f;
            background: #0f0f12;
            color: #fff;
            outline: none;
            display: block;
            width: 100%;
            max-width: 100%;
        }

        input:focus {
            border-color: #5b5bf7;
            box-shadow: 0 0 0 3px rgba(91, 91, 247, .15);
        }

        .btn {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: none;
            background: #5b5bf7;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover {
            filter: brightness(1.05);
        }

        .error-box {
            background: #2a0f13;
            border: 1px solid #6b1a24;
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 14px;
            color: #ffb4be;
        }

        .error-text {
            font-size: 12px;
            color: #ff9aa8;
            margin-top: 6px;
        }

        .muted {
            font-size: 13px;
            color: #b8b8c0;
            margin-top: 14px;
            text-align: center;
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
    <main class="container">
        <div class="card">
            @yield('content')
        </div>
        <div class="muted">
            <small>@yield('footer')</small>
        </div>
    </main>

    {{-- @livewireScripts --}}
</body>

</html>
