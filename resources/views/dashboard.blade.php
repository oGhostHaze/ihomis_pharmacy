<x-app-layout>
    <x-slot name="header">
        <div class="text-sm breadcrumbs">
            <ul>
                <li class="font-bold">
                    <i class="las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
                </li>
                <li>
                    <i class="las la-tachometer-alt la-lg"></i> Dashboard
                </li>
            </ul>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                <x-jet-welcome />
            </div>
        </div>
    </div>
</x-app-layout>
