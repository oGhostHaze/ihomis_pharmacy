<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="emerald">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}?v={{ config('app.version') }}">
    <link rel="icon" href="{{ asset('logo.png') }}?v={{ config('app.version') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <!-- Styles -->
    @livewireStyles
    <style>
        @media print {
            body {
                transform: scale(1);
                transform-origin: 0 0;
            }
        }
    </style>

    <!-- Scripts -->
</head>

<body class="font-sans antialiased">

    <div class="min-h-screen">
        <main>
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    @stack('scripts')
</body>

</html>
