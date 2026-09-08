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

<div class="flex flex-col px-4 py-6 mx-auto max-w-screen-2xl sm:px-6">
    <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-base-content">UDDDS ward queue</h1>
            <p class="mt-1 text-sm text-base-content/70">Unit-dose orders and eligible enrollments for {{ $displayDate }}.</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-base-content/70" aria-label="Queue summary">
            <span><strong class="font-semibold text-base-content">{{ $eligibleCount }}</strong> eligible</span>
            <span class="w-px h-4 bg-base-300" aria-hidden="true"></span>
            <span><strong class="font-semibold text-base-content">{{ $billableCount }}</strong> ready to bill</span>
            <span class="w-px h-4 bg-base-300" aria-hidden="true"></span>
            <span><strong class="font-semibold text-base-content">{{ $issuedCount }}</strong> issued</span>
        </div>
    </div>

    <div class="flex flex-col gap-3 pb-5 border-b border-base-300 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="form-control">
                <span class="pb-1 text-xs font-medium label-text">Service date</span>
                <div class="flex gap-2">
                    <input type="date" wire:model="selected_date" class="w-full input input-bordered input-sm"
                        aria-label="Service date" />
                    @if (! $isToday)
                        <button type="button" class="btn btn-sm btn-outline btn-primary" wire:click="showToday">
                            Today
                        </button>
                    @endif
                </div>
            </label>
            <label class="form-control">
                <span class="pb-1 text-xs font-medium label-text">Ward</span>
                <select wire:model="wardcode" class="w-full select select-bordered select-sm">
                    <option value="">All wards</option>
                    @foreach ($wards as $ward)
                        <option value="{{ $ward->wardcode }}">{{ $ward->wardname }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-xs text-base-content/60" wire:loading>
                <i class="las la-spinner la-lg animate-spin"></i> Updating…
            </span>
            <button type="button" class="btn btn-sm btn-outline" wire:click="processSelected" wire:loading.attr="disabled"
                @if (empty($selected_items)) disabled @endif>
                Batch selected
            </button>
            <button type="button" class="btn btn-sm btn-success" wire:click="processWard" wire:loading.attr="disabled"
                @if (! $hasBillableItems) disabled @endif>
                Ready to bill ward
            </button>
        </div>
    </div>

    @if (! $udddsReady)
        <div class="mt-4 alert alert-warning">
            <span>{{ $udddsMessage }}</span>
        </div>
    @endif

    @forelse ($patients as $patient)
        <section class="mt-4 overflow-hidden border border-base-300 bg-base-100" wire:key="uddds-{{ md5($patient['enccode']) }}">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-base-200">
                <div>
                    <button type="button" class="font-semibold text-left text-primary" wire:click="view_enctr('{{ $patient['enccode'] }}')">
                        {{ $patient['name'] }}
                    </button>
                    <div class="text-xs text-base-content/60">
                        {{ $patient['hpercode'] }} · {{ $patient['wardname'] }} {{ $patient['rmname'] }}
                    </div>
                </div>
                <button type="button" class="btn btn-xs btn-success" wire:click="readyToBill('{{ $patient['enccode'] }}')"
                    @if (empty($patient['keys'])) disabled @endif>
                    Ready to Bill
                </button>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead class="bg-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-700">
                    <tr class="border-b border-slate-200">
                        <th scope="col" class="w-12 px-3 py-3"><span class="sr-only">Select</span></th>
                        <th scope="col" class="px-3 py-3">Item</th>
                        <th scope="col" class="px-3 py-3">Fund source</th>
                        <th scope="col" class="px-3 py-3 text-right">Qty</th>
                        <th scope="col" class="px-3 py-3">Frequency</th>
                        <th scope="col" class="px-3 py-3">Start / End</th>
                        <th scope="col" class="px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patient['items'] as $item)
                        <tr class="border-b border-slate-200 bg-white transition-colors last:border-b-0 hover:bg-slate-50"
                            wire:key="uddds-item-{{ $item->docointkey }}">
                            <td class="px-3 py-3">
                                <input type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-emerald-600 accent-emerald-600 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                    aria-label="Select {{ implode('', explode('_', $item->drug_concat)) }}" wire:model="selected_items"
                                    value="{{ $item->docointkey }}" @if (! $item->is_actionable) disabled @endif />
                            </td>
                            <td class="px-3 py-3 text-xs font-medium text-slate-800">{{ implode('', explode('_', $item->drug_concat)) }}</td>
                            <td class="px-3 py-3 text-xs text-slate-600">{{ $item->chrgdesc }}</td>
                            <td class="px-3 py-3 text-right text-xs tabular-nums text-slate-700">{{ number_format($item->pchrgqty, 0) }}</td>
                            <td class="px-3 py-3 text-xs text-slate-600">{{ $item->frequency ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs tabular-nums text-slate-600 whitespace-nowrap">
                                {{ $item->uddds_start_date ? date('m/d/Y', strtotime($item->uddds_start_date)) : '' }}
                                –
                                {{ $item->uddds_end_date ? date('m/d/Y', strtotime($item->uddds_end_date)) : '' }}
                            </td>
                            <td class="px-3 py-3 text-xs">
                                @if (!empty($item->is_source_issued_for_date))
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Issued</span>
                                @elseif (empty($item->uddds_source_docointkey))
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800">Eligible</span>
                                @elseif ($item->estatus === 'S' || (float) $item->qtyissued > 0)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Issued</span>
                                @elseif ($item->estatus == 'U' || !$item->pcchrgcod)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Pending</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Charged</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>
    @empty
        <div class="p-10 mt-6 text-center border rounded-lg border-base-300 text-base-content/60">
            <div class="font-medium text-base-content">No UDDDS orders found</div>
            <div class="mt-1 text-sm">There are no eligible or generated Basic orders for {{ $displayDate }} in this ward.</div>
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
