<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @livewireStyles
    @vite(['resources/js/app.js'])
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
