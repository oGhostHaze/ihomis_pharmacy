<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li><i class="mr-1 las la-pills la-lg"></i> Drugs and Medicine</li>
            <li class="font-bold">Fund Source Reclassification</li>
        </ul>
    </div>
</x-slot>

<div class="min-h-screen px-4 py-6 bg-base-200 sm:px-6 lg:px-8">
    <div class="mx-auto space-y-6 max-w-7xl">
        <section class="overflow-hidden shadow-xl rounded-2xl bg-base-100">
            <div class="px-6 py-7 text-primary-content bg-gradient-to-r from-primary to-info sm:px-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white/20">
                                <i class="las la-exchange-alt la-2x"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold tracking-widest uppercase opacity-80">
                                    Inventory administration
                                </p>
                                <h1 class="text-2xl font-bold sm:text-3xl">Fund Source Reclassification</h1>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 opacity-90 sm:text-base">
                            Transfer all positive drug inventory from one pharmacy charge code to another while
                            preserving each medicine, location, lot, expiry date, price, and audit history.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="px-4 py-3 rounded-xl bg-white/15">
                            <div class="text-2xl font-bold">All</div>
                            <div class="text-xs opacity-80">Locations</div>
                        </div>
                        <div class="px-4 py-3 rounded-xl bg-white/15">
                            <div class="text-2xl font-bold">100%</div>
                            <div class="text-xs opacity-80">Remaining stock</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 border-b sm:px-8 border-base-300">
                <ul class="w-full steps">
                    <li class="step step-primary">Select funds</li>
                    <li class="step {{ $preview_ready || $command_output ? 'step-primary' : '' }}">Review preview</li>
                    <li class="step {{ $output_type === 'success' && !$preview_ready ? 'step-primary' : '' }}">
                        Commit transfer
                    </li>
                </ul>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="shadow-lg card bg-base-100 xl:col-span-2">
                <div class="card-body">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="badge badge-primary badge-outline">Step 1</span>
                                <h2 class="text-xl font-bold">Choose the fund sources</h2>
                            </div>
                            <p class="mt-2 text-sm text-base-content/70">
                                Only active pharmacy charge codes are available. The source and destination must be
                                different.
                            </p>
                        </div>
                        <i class="hidden text-4xl las la-random text-primary/30 sm:block"></i>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-5 md:grid-cols-2">
                        <div class="p-4 border rounded-xl border-base-300 bg-base-200/50">
                            <label class="label" for="source_chrgcode">
                                <span class="font-semibold label-text">
                                    <span class="mr-2 badge badge-error badge-sm">FROM</span>
                                    Source charge code
                                </span>
                            </label>
                            <select id="source_chrgcode"
                                class="w-full select select-bordered focus:select-error"
                                wire:model="source_chrgcode">
                                <option value="">Choose source fund</option>
                                @foreach ($chargeCodes as $chargeCode)
                                    <option value="{{ $chargeCode->chrgcode }}">
                                        {{ $chargeCode->chrgcode }} — {{ $chargeCode->chrgdesc }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-base-content/60">This fund will be left with zero stock.</p>
                            @error('source_chrgcode')
                                <span class="block mt-2 text-sm text-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="p-4 border rounded-xl border-base-300 bg-base-200/50">
                            <label class="label" for="destination_chrgcode">
                                <span class="font-semibold label-text">
                                    <span class="mr-2 badge badge-success badge-sm">TO</span>
                                    Destination charge code
                                </span>
                            </label>
                            <select id="destination_chrgcode"
                                class="w-full select select-bordered focus:select-success"
                                wire:model="destination_chrgcode">
                                <option value="">Choose destination fund</option>
                                @foreach ($chargeCodes as $chargeCode)
                                    <option value="{{ $chargeCode->chrgcode }}">
                                        {{ $chargeCode->chrgcode }} — {{ $chargeCode->chrgdesc }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-base-content/60">This fund will receive the transferred stock.</p>
                            @error('destination_chrgcode')
                                <span class="block mt-2 text-sm text-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @if ($source_chrgcode && $destination_chrgcode)
                        <div class="flex flex-col items-center justify-center gap-2 p-4 mt-4 border border-dashed sm:flex-row rounded-xl border-primary/40 bg-primary/5">
                            <span class="font-mono text-lg font-bold">{{ $source_chrgcode }}</span>
                            <i class="las la-long-arrow-alt-right la-2x text-primary"></i>
                            <span class="font-mono text-lg font-bold">{{ $destination_chrgcode }}</span>
                        </div>
                    @endif

                    <div class="justify-end mt-5 card-actions">
                        <button type="button" class="gap-2 btn btn-primary"
                            wire:click="preview" wire:loading.attr="disabled" wire:target="preview,commit">
                            <span wire:loading.remove wire:target="preview">
                                <i class="las la-search"></i> Generate preview
                            </span>
                            <span wire:loading wire:target="preview">
                                <span class="mr-2 loading loading-spinner loading-sm"></span>Checking inventory…
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            <aside class="shadow-lg card bg-base-100">
                <div class="card-body">
                    <div class="flex items-center gap-2">
                        <i class="text-2xl las la-shield-alt text-warning"></i>
                        <h2 class="text-lg font-bold">Safety checklist</h2>
                    </div>
                    <div class="mt-3 space-y-3">
                        <div class="flex gap-3">
                            <i class="mt-1 las la-check-circle text-success"></i>
                            <p class="text-sm">Create and verify a current database backup.</p>
                        </div>
                        <div class="flex gap-3">
                            <i class="mt-1 las la-check-circle text-success"></i>
                            <p class="text-sm">Place every affected pharmacy location under maintenance.</p>
                        </div>
                        <div class="flex gap-3">
                            <i class="mt-1 las la-check-circle text-success"></i>
                            <p class="text-sm">Ensure each location has exactly one active consumption period.</p>
                        </div>
                        <div class="flex gap-3">
                            <i class="mt-1 las la-check-circle text-success"></i>
                            <p class="text-sm">Review quantities, lots, expiries, and inventory value in the preview.</p>
                        </div>
                    </div>
                    <div class="mt-4 alert alert-warning">
                        <i class="las la-exclamation-triangle"></i>
                        <span class="text-xs">Committing affects all matching inventory across all locations.</span>
                    </div>
                </div>
            </aside>
        </div>

        @if ($command_output)
            <section class="overflow-hidden shadow-lg card bg-base-100">
                <div class="p-5 border-b card-title border-base-300">
                    <div class="flex flex-col w-full gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg
                                {{ $output_type === 'success' ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">
                                <i class="las {{ $output_type === 'success' ? 'la-clipboard-check' : 'la-times-circle' }} la-lg"></i>
                            </span>
                            <div>
                                <span class="badge {{ $output_type === 'success' ? 'badge-success' : 'badge-error' }}">
                                    Step 2
                                </span>
                                <h2 class="mt-1 text-xl font-bold">
                                    {{ $output_type === 'success' ? 'Inventory preview' : 'Validation failed' }}
                                </h2>
                            </div>
                        </div>
                        @if ($preview_ready)
                            <span class="gap-2 badge badge-success badge-lg">
                                <i class="las la-check"></i> Ready for review
                            </span>
                        @endif
                    </div>
                </div>
                <div class="p-0">
                    <div class="flex items-center gap-2 px-4 py-2 text-xs border-b bg-neutral text-neutral-content border-base-300">
                        <span class="w-3 h-3 rounded-full bg-error"></span>
                        <span class="w-3 h-3 rounded-full bg-warning"></span>
                        <span class="w-3 h-3 rounded-full bg-success"></span>
                        <span class="ml-2 font-mono opacity-70">reclassification preview</span>
                    </div>
                    <pre class="p-5 overflow-x-auto font-mono text-xs leading-5 whitespace-pre bg-neutral text-neutral-content max-h-96">{{ $command_output }}</pre>
                </div>
            </section>
        @endif

        @if ($preview_ready)
            <section class="overflow-hidden border-2 shadow-xl card border-error/40 bg-base-100">
                <div class="card-body">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
                        <div class="flex items-center justify-center flex-shrink-0 w-14 h-14 rounded-full bg-error/10 text-error">
                            <i class="las la-lock la-2x"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-error">Step 3</span>
                                <h2 class="text-xl font-bold">Authorize the transfer</h2>
                            </div>
                            <p class="mt-2 text-sm text-base-content/70">
                                After reviewing the preview, type the exact phrase below. The application will recheck
                                all balances and safety conditions before opening the database transaction.
                            </p>

                            <div class="p-4 mt-4 border rounded-xl border-error/30 bg-error/5">
                                <p class="text-xs font-semibold tracking-wide uppercase text-error">Confirmation phrase</p>
                                <code class="block mt-2 text-base font-bold break-all sm:text-lg">{{ $confirmationPhrase }}</code>
                            </div>

                            <div class="mt-4 form-control">
                                <label class="label" for="confirmation">
                                    <span class="font-semibold label-text">Type the phrase exactly as shown</span>
                                </label>
                                <input id="confirmation" type="text"
                                    class="w-full font-mono input input-bordered input-error"
                                    wire:model.defer="confirmation" autocomplete="off"
                                    placeholder="RECLASSIFY SOURCE TO DESTINATION" />
                                @error('confirmation')
                                    <span class="block mt-2 text-sm text-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex flex-col items-stretch justify-end gap-3 mt-5 sm:flex-row sm:items-center">
                                <span class="text-xs text-base-content/60 sm:mr-auto">
                                    <i class="mr-1 las la-database"></i>
                                    The transfer and its audit records are committed atomically.
                                </span>
                                <button type="button" class="gap-2 btn btn-error"
                                    wire:click="commit" wire:loading.attr="disabled" wire:target="preview,commit">
                                    <span wire:loading.remove wire:target="commit">
                                        <i class="las la-exchange-alt"></i> Commit system-wide transfer
                                    </span>
                                    <span wire:loading wire:target="commit">
                                        <span class="mr-2 loading loading-spinner loading-sm"></span>Processing transaction…
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="shadow-lg card bg-base-100">
            <div class="card-body">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Recent reclassifications</h2>
                        <p class="mt-1 text-sm text-base-content/60">The ten most recent committed transactions.</p>
                    </div>
                    <span class="gap-2 badge badge-ghost">
                        <i class="las la-history"></i> Audit history
                    </span>
                </div>

                <div class="mt-4 overflow-x-auto border rounded-xl border-base-300">
                    <table class="table w-full table-zebra">
                        <thead class="bg-base-200">
                            <tr>
                                <th>Reference</th>
                                <th>Executed</th>
                                <th>Transfer</th>
                                <th class="text-right">Batches</th>
                                <th class="text-right">Quantity</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $record)
                                <tr>
                                    <td>
                                        <span class="font-mono text-xs font-semibold">{{ $record->reference_no }}</span>
                                    </td>
                                    <td class="text-sm whitespace-nowrap">{{ $record->executed_at }}</td>
                                    <td>
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <span class="badge badge-error badge-outline">{{ $record->source_chrgcode }}</span>
                                            <i class="las la-long-arrow-alt-right text-primary"></i>
                                            <span class="badge badge-success badge-outline">{{ $record->destination_chrgcode }}</span>
                                        </div>
                                    </td>
                                    <td class="font-semibold text-right">{{ number_format($record->batch_count) }}</td>
                                    <td class="font-semibold text-right">{{ number_format($record->total_quantity, 2) }}</td>
                                    <td><span class="badge badge-ghost">#{{ $record->user_id }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="py-10 text-center">
                                            <i class="text-5xl las la-inbox text-base-content/20"></i>
                                            <p class="mt-2 font-semibold">No reclassification history</p>
                                            <p class="mt-1 text-sm text-base-content/60">
                                                Completed transactions will appear here.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
