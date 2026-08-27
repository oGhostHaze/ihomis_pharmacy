<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-file-prescription la-lg"></i> Rx/Orders
            </li>
            <li>
                <i class="mr-1 las la-clock la-lg"></i> UDDDS (Wards)
            </li>
        </ul>
    </div>
    <div class="flex justify-center">
        <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.ward')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Wards') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.uddds') }}" :active="request()->routeIs('rx.uddds')">
            <i class="mr-1 las la-lg la-clock"></i> {{ __('UDDDS') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.opd') }}" :active="request()->routeIs('rx.opd')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Out Patient Department') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.er') }}" :active="request()->routeIs('rx.er')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Emergency Room') }}
        </x-jet-nav-link>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <p class="mb-3 text-sm text-base-content/70">Today's unit-dose orders for inpatients (ADM). UDDDS is optional; only items already enrolled appear here.</p>
    <div class="flex flex-wrap items-end gap-4">
        <div class="form-control">
            <label>
                <span class="label-text">Ward</span>
            </label>
            <select wire:model="wardcode" class="w-full select select-bordered select-sm">
                <option value="">All</option>
                @foreach ($wards as $ward)
                    <option value="{{ $ward->wardcode }}">{{ $ward->wardname }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn btn-sm btn-primary" wire:click="processSelected" wire:loading.attr="disabled">
            Batch selected
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="processWard" wire:loading.attr="disabled">
            Ready to Bill (ward)
        </button>
        <span wire:loading>
            <i class="las la-spinner la-lg animate-spin"></i>
            Processing...
        </span>
    </div>

    @if (! $udddsReady)
        <div class="mt-4 alert alert-warning">
            <span>{{ $udddsMessage }}</span>
        </div>
    @endif

    @forelse ($patients as $patient)
        <div class="mt-4 overflow-hidden border rounded-lg shadow-sm" wire:key="uddds-{{ md5($patient['enccode']) }}">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-base-200">
                <div>
                    <button type="button" class="font-semibold text-left text-primary" wire:click="view_enctr('{{ $patient['enccode'] }}')">
                        {{ $patient['name'] }}
                    </button>
                    <div class="text-xs text-base-content/60">
                        {{ $patient['hpercode'] }} · {{ $patient['wardname'] }} {{ $patient['rmname'] }}
                    </div>
                </div>
                <button type="button" class="btn btn-xs btn-success" wire:click="readyToBill('{{ $patient['enccode'] }}')">
                    Ready to Bill
                </button>
            </div>
            <table class="table w-full table-compact table-zebra">
                <thead>
                    <tr>
                        <th></th>
                        <th>Item</th>
                        <th>Fund source</th>
                        <th>Qty</th>
                        <th>Frequency</th>
                        <th>Start / End</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patient['items'] as $item)
                        <tr wire:key="uddds-item-{{ $item->docointkey }}">
                            <td>
                                <input type="checkbox" class="checkbox checkbox-xs" wire:model="selected_items"
                                    value="{{ $item->docointkey }}" />
                            </td>
                            <td class="text-xs">{{ implode('', explode('_', $item->drug_concat)) }}</td>
                            <td class="text-xs">{{ $item->chrgdesc }}</td>
                            <td class="text-xs text-right">{{ number_format($item->pchrgqty, 0) }}</td>
                            <td class="text-xs">{{ $item->frequency ?: '—' }}</td>
                            <td class="text-xs whitespace-nowrap">
                                {{ $item->uddds_start_date ? date('m/d/Y', strtotime($item->uddds_start_date)) : '' }}
                                –
                                {{ $item->uddds_end_date ? date('m/d/Y', strtotime($item->uddds_end_date)) : '' }}
                            </td>
                            <td class="text-xs">
                                @if ($item->estatus == 'U' || !$item->pcchrgcod)
                                    <span class="badge badge-xs badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-xs badge-secondary">Charged</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="p-8 mt-4 text-center border rounded-lg text-base-content/60">
            No UDDDS Basic orders for today.
        </div>
    @endforelse
</div>

@push('scripts')
    <script>
        window.addEventListener('uddds-print', function(event) {
            window.open(event.detail.url, 'udddsChargeSlips', 'width=900,height=900');
        });
    </script>
@endpush
