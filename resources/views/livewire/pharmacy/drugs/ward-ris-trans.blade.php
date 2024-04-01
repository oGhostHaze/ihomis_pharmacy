<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> IO Transactions
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto">
    <div class="flex justify-between">
        <div class="flex space-x-2">
            @can('view-ris')
                <label class="btn btn-sm btn-primary" for="issueModal">Issue RIS</label>
                <button class="btn btn-sm btn-warning" wire:click="append">Append Last RIS</button>
                {{-- <button class="btn btn-sm btn-secondary" onclick="issue_more_ris()">Add To Last Request</button> --}}
            @endcan
        </div>
        <div>
            <div class="form-control">
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm"
                        wire:model.lazy="search" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto">
        @if ($errors->first())
            <div class="shadow-lg max-w-fit alert alert-error">
                <i class="mr-2 las la-lg la-exclamation-triangle"></i> {{ $errors->first() }}
            </div>
        @endif
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th class="w-1/12">Reference</th>
                    <th class="w-1/12">Date Issued</th>
                    <th class="w-1/12">FROM</th>
                    <th class="w-1/12">TO</th>
                    <th class="w-6/12">Item</th>
                    <th class="w-1/12">Issued QTY</th>
                    <th class="w-1/12">Fund Source</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trans as $tran)
                    <tr class="hover" wire:key="select-txt-{{ $loop->iteration . $tran->id }}">
                        <th class="text-xs cursor-pointer" wire:click="view_trans('{{ $tran->trans_no }}')">
                            <span class="text-blue-500"><i class="las la-lg la-eye"></i> {{ $tran->trans_no }}</span>
                        </th>
                        <td class="text-xs cursor-pointer"
                            wire:click="view_trans_date('{{ date('Y-m-d', strtotime($tran->created_at)) }}')">
                            <span class="text-blue-500"><i class="las la-lg la-eye"></i>
                                {{ $tran->created_at() }}</span>
                        </td>
                        <td class="text-xs">{{ $tran->location->description }}</td>
                        <td class="text-xs">{{ $tran->ward->ward_name }}</td>
                        <td class="text-xs cursor-pointer">
                            <span class="text-blue-500">
                                <i class="las la-lg la-hand-pointer"></i>
                                {{ $tran->drug->drug_concat() }}
                            </span>
                        </td>
                        <td class="text-xs">{{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}</td>
                        <td class="text-xs">
                            {{ $tran->charge->chrgdesc }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="10">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $trans->links() }}
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="issueModal" class="modal-toggle" wire:model="issueModal" />
    <div class="modal">
        <div class="relative modal-box">
            <label for="issueModal" class="absolute btn btn-sm btn-circle right-2 top-2">✕</label>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">RIS To Ward</span>
                </label>
                <select class="select select-bordered" wire:model="ward_id">
                    <option></option>
                    @foreach ($wards as $ward)
                        <option value="{{ $ward->id }}">{{ $ward->ward_name }}</option>
                    @endforeach
                </select>
                @error('ward_id')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">Fund Source</span>
                </label>
                <select class="select select-bordered" wire:model="chrgcode">
                    <option></option>
                    @foreach ($charge_codes as $charge)
                        <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                    @endforeach
                </select>
                @error('chrgcode')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">Drug/Medicine</span>
                </label>
                <label class="input-group input-group-vertical">
                    <input type="text" placeholder="Type here to search" class="input input-sm input-bordered"
                        wire:model.lazy="search_drug">
                    <select class="h-full select select-bordered" wire:model.lazy="stock_id" size="5">
                        @foreach ($drugs as $drug)
                            <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">
                                {{ implode(',', explode('_,', $drug->drug_concat)) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">QTY to issue</span>
                </label>
                <input type="number" class="input input-sm input-bordered" wire:model.lazy="issue_qty">
            </div>
            <div class="flex justify-between mt-3" x-data="{ accept: false, good: false }">
                <div class="flex flex-col">
                    <div class="form-control">
                        <label class="cursor-pointer label">
                            <input type="checkbox" class="checkbox" x-model="good" name="good" id="good"
                                value="Yes" />
                            <span class="ml-1 font-semibold label-text text-error">Verify above data</span>
                        </label>
                    </div>
                    <div class="form-control" x-show="good">
                        <label class="cursor-pointer label">
                            <input type="checkbox" class="checkbox" x-model="accept" name="accept" id="accept"
                                value="Yes" />
                            <span class="ml-1 font-semibold label-text text-error">Double check input</span>
                        </label>
                    </div>
                </div>
                <div class="flex mt-auto space-x-2">
                    <button class="mt-auto btn btn-primary" wire:click="issue_ris"
                        x-bind:disabled="!accept">Issue</button>
                    <button class="mt-auto btn btn-warning" wire:click="issue_ris(true)"
                        x-bind:disabled="!accept">Issue and Append</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="issueMoreModal" class="modal-toggle" wire:model="issueMoreModal" />
    <div class="modal">
        <div class="relative modal-box">
            <label for="issueMoreModal" class="absolute btn btn-sm btn-circle right-2 top-2">✕</label>
            <h2 class="text-lg text-center">Append to Reference #: {{ $reference_no }}</h2>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">RIS To Ward</span>
                </label>
                <select class="select select-bordered" wire:model="ward_id" disabled>
                    <option></option>
                    @foreach ($wards as $ward)
                        <option value="{{ $ward->id }}">{{ $ward->ward_name }}</option>
                    @endforeach
                </select>
                @error('ward_id')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">Fund Source</span>
                </label>
                <select class="select select-bordered" wire:model="chrgcode">
                    <option></option>
                    @foreach ($charge_codes as $charge)
                        <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                    @endforeach
                </select>
                @error('chrgcode')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">Drug/Medicine</span>
                </label>
                <label class="input-group input-group-vertical">
                    <input type="text" placeholder="Type here to search" class="input input-sm input-bordered"
                        wire:model.lazy="search_drug">
                    <select class="h-full select select-bordered" wire:model.lazy="stock_id" size="5">
                        @foreach ($drugs as $drug)
                            <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">
                                {{ implode(',', explode('_,', $drug->drug_concat)) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="w-full form-control">
                <label class="label" for="stock_id">
                    <span class="label-text">QTY to issue</span>
                </label>
                <input type="number" class="input input-sm input-bordered" wire:model.lazy="issue_qty">
            </div>
            <div class="flex justify-between mt-3" x-data="{ accept: false, good: false }">
                <div class="flex flex-col">
                    <div class="form-control">
                        <label class="cursor-pointer label">
                            <input type="checkbox" class="checkbox" x-model="good" name="good" id="good"
                                value="Yes" />
                            <span class="ml-1 font-semibold label-text text-error">Verify above data</span>
                        </label>
                    </div>
                    <div class="form-control" x-show="good">
                        <label class="cursor-pointer label">
                            <input type="checkbox" class="checkbox" x-model="accept" name="accept" id="accept"
                                value="Yes" />
                            <span class="ml-1 font-semibold label-text text-error">Double check input</span>
                        </label>
                    </div>
                </div>
                <div class="mt-auto">
                    <button class="mt-auto btn btn-primary" wire:click="issue_ris"
                        x-bind:disabled="!accept">Issue</button>
                    <button class="mt-auto btn btn-warning" wire:click="issue_ris(true)"
                        x-bind:disabled="!accept">Issue and Append</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function cancel_tx(trans_id) {
            Swal.fire({
                title: 'Are you sure you want to cancel this transaction?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                confirmButtonColor: 'red',
                html: `
                    <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                    <div class="mt-2 text-slate-500" id="inf">All items issued that have not been received will return to warehouse. <br>This process cannot be undone. Continue?</div>
                `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('cancel_tx', trans_id)
                }
            })
        }

        window.addEventListener('toggleIssue', event => {
            $('#issueModal').click();
        })

        Echo.private(`ioTrans.{{ session('pharm_location_id') }}`)
            .listen('IoTransRequestUpdated', (e) => {
                Livewire.emit('refreshComponent');
            });
    </script>
@endpush
