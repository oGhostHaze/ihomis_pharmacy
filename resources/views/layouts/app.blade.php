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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />

    <!-- Styles -->
    @livewireStyles

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ mix('js/turbolinks.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/gh/livewire/turbolinks@v0.1.x/dist/livewire-turbolinks.js"
        data-turbolinks-eval="false" data-turbo-eval="false" defer></script>

    @stack('head')

</head>

<body class="max-h-screen overflow-auto font-sans antialiased ">

    <div class="min-h-screen bg-gray-100">
        @livewire('navigation-menu')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="px-4 py-2 mx-auto sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <x-jet-banner />

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    @livewireScripts
    <x-livewire-alert::scripts />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>

    @stack('scripts')

    <script>
        // Pusher.logToConsole = true;


        number_format = function(number, decimals, dec_point, thousands_sep) {
            number = number.toFixed(decimals);

            var nstr = number.toString();
            nstr += '';
            x = nstr.split('.');
            x1 = x[0];
            x2 = x.length > 1 ? dec_point + x[1] : '';
            var rgx = /(\d+)(\d{3})/;

            while (rgx.test(x1))
                x1 = x1.replace(rgx, '$1' + thousands_sep + '$2');

            return x1 + x2;
        }

        Echo.private(`ioTrans.{{ session('pharm_location_id') }}`)
            .listen('IoTransNewRequest', (e) => {
                // console.log(e.requestor);
                Swal.fire({
                    icon: 'info',
                    title: 'New Request from ' + e.requestor,
                })
            });

        Echo.private(`ioTrans.{{ session('pharm_location_id') }}`)
            .listen('IoTransRequestUpdated', (e) => {
                // console.log(e.requestor);
                Swal.fire({
                    icon: 'info',
                    title: e.message,
                })
            });

        Echo.private(`encounter-view.{{ session('pharm_location_id') }}`)
            .listen('DrugOrderEvent', (e) => {
                // console.log(e.requestor);
                Swal.fire({
                    icon: 'info',
                    title: e.message,
                })
            });
    </script>
</body>

</html>
