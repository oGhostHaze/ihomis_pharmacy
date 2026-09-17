<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li><i class="mr-1 las la-cog la-lg"></i> Settings</li>
            <li class="font-bold"><i class="mr-1 las la-unlink la-lg"></i> Missing Drug Prices</li>
        </ul>
    </div>
</x-slot>

<div class="min-h-screen px-4 py-6 bg-base-200 sm:px-6 lg:px-8">
    <div class="mx-auto space-y-5 max-w-7xl">
        <section class="overflow-hidden shadow-lg rounded-xl bg-base-100">
            <div class="px-6 py-6 border-b border-base-300 sm:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-warning/15 text-warning">
                                <i class="las la-unlink la-2x"></i>
                            </span>
                            <div>
                                <h1 class="text-2xl font-bold">Missing drug price links</h1>
                                <p class="mt-1 text-sm text-base-content/65">
                                    Stock records that have no valid matching entry in the drug price table.
                                </p>
                            </div>
                        </div>
                    </div>
                    <span class="gap-2 badge badge-warning badge-lg">
                        <i class="las la-shield-alt"></i> Super Admin only
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:px-8 md:grid-cols-3">
                <div class="form-control md:col-span-2">
                    <label class="label" for="missing-price-search"><span class="font-semibold label-text">Search drugs, lots, or fund codes</span></label>
                    <input id="missing-price-search" type="search" class="w-full input input-bordered"
                        wire:model.debounce.400ms="search" placeholder="Example: dapagliflozin or 60052367">
                </div>
                <div class="form-control">
                    <label class="label" for="missing-price-location"><span class="font-semibold label-text">Location</span></label>
                    <select id="missing-price-location" class="w-full select select-bordered" wire:model="location_id">
                        <option value="">All locations</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->description }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="alert alert-info shadow-sm">
            <i class="las la-info-circle la-lg"></i>
            <span>“Use matching price” is available only when drug, fund source, and expiry match. Otherwise, create a verified price manually.</span>
        </div>

        <section class="overflow-hidden shadow-lg rounded-xl bg-base-100">
            <div class="overflow-x-auto">
                <table class="table w-full table-zebra">
                    <thead class="bg-base-200">
                        <tr>
                            <th>Drug</th>
                            <th>Location / Fund</th>
                            <th>Lot / Expiry</th>
                            <th class="text-right">Balance</th>
                            <th>Broken reference</th>
                            <th class="text-right">Repair</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            <tr wire:key="missing-price-{{ $stock->id }}">
                                <td>
                                    <div class="font-semibold">{{ $stock->drug_concat }}</div>
                                    <div class="mt-1 font-mono text-xs text-base-content/55">Stock #{{ $stock->id }}</div>
                                </td>
                                <td>
                                    <div>{{ $stock->location_name ?: 'Location #' . $stock->loc_code }}</div>
                                    <div class="mt-1 text-xs text-base-content/60">{{ $stock->chrgdesc ?: $stock->chrgcode }}</div>
                                </td>
                                <td>
                                    <div class="font-mono text-sm">{{ $stock->lot_no ?: 'No lot number' }}</div>
                                    <div class="mt-1 text-xs text-base-content/60">{{ $stock->exp_date ?: 'No expiry' }}</div>
                                </td>
                                <td class="font-semibold text-right tabular-nums">{{ number_format($stock->stock_bal) }}</td>
                                <td>
                                    <span class="badge badge-error badge-outline">
                                        {{ $stock->dmdprdte ?: 'NULL' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-col items-stretch justify-end gap-2 sm:flex-row">
                                        @if ($matchingPriceIds[$stock->id])
                                            <button type="button" class="btn btn-sm btn-success"
                                                wire:click="attachLatestMatchingPrice({{ $stock->id }})"
                                                wire:loading.attr="disabled">
                                                <i class="las la-link"></i> Use matching price
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-primary"
                                            wire:click="selectForManualRepair({{ $stock->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="las la-plus-circle"></i> Create price
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="py-14 text-center">
                                        <i class="text-5xl las la-check-circle text-success"></i>
                                        <p class="mt-3 text-lg font-bold">No missing price links found</p>
                                        <p class="mt-1 text-sm text-base-content/60">Every stock record in this view has a valid price link.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->hasPages())
                <div class="px-6 py-4 border-t border-base-300">{{ $stocks->links() }}</div>
            @endif
        </section>

        @if ($selected_stock_id)
            <section class="border shadow-lg rounded-xl border-primary/30 bg-base-100" id="manual-price-repair">
                <div class="p-6 sm:p-8">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary">
                            <i class="las la-tag la-lg"></i>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold">Create and attach a price</h2>
                            <p class="mt-1 text-sm text-base-content/65">{{ $selected_drug }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-5 md:grid-cols-2">
                        <div class="form-control">
                            <label class="label" for="acquisition-cost"><span class="font-semibold label-text">Acquisition cost</span></label>
                            <input id="acquisition-cost" type="number" min="0" step="0.01" class="input input-bordered"
                                wire:model.defer="acquisition_cost" placeholder="0.00">
                            @error('acquisition_cost') <span class="mt-1 text-sm text-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label" for="selling-price"><span class="font-semibold label-text">Selling price</span></label>
                            <input id="selling-price" type="number" min="0" step="0.01" class="input input-bordered"
                                wire:model.defer="selling_price" placeholder="0.00">
                            @error('selling_price') <span class="mt-1 text-sm text-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col justify-end gap-3 mt-6 sm:flex-row">
                        <button type="button" class="btn btn-ghost" wire:click="cancelManualRepair">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="createPriceAndRepair"
                            wire:loading.attr="disabled" wire:target="createPriceAndRepair">
                            <span wire:loading.remove wire:target="createPriceAndRepair"><i class="las la-check"></i> Create and attach price</span>
                            <span wire:loading wire:target="createPriceAndRepair"><span class="loading loading-spinner loading-sm"></span> Repairing…</span>
                        </button>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
