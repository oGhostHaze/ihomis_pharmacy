<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Requisition and Issue Slips - Pharmacy') }}
        </h2>
    </x-slot>

    <div>
        <div class="mx-auto">
            <livewire:ris-list />
        </div>
    </div>
</x-app-layout>
