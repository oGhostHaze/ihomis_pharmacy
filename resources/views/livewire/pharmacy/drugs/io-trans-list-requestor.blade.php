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
        @can('request-drugs')
            <div class="flex space-x-2">
                <button class="btn btn-sm btn-primary" onclick="add_request()" wire:loading.attr="disabled">Add
                    Request</button>
                <button class="btn btn-sm btn-secondary" onclick="add_more_request()" wire:loading.attr="disabled">Add To
                    Last Request</button>
            </div>
        @endcan
        <div class="flex space-x-2">
            <div class="ml-2">
                <div class="form-control">
                    <label class="input-group">
                        <span>Location</span>
                        <select class="text-sm select select-bordered select-sm" wire:model="filter_location_id">
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
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
                    <th class="w-1/12">Date Requested</th>
                    <th class="w-1/12">Request FROM</th>
                    <th class="w-1/12">Request TO</th>
                    <th class="w-6/12">Item Requested</th>
                    <th class="w-1/12">Requested QTY</th>
                    <th class="w-1/12">Issued QTY</th>
                    <th class="w-1/12">Fund Source</th>
                    <th class="w-1/12">Updated At</th>
                    <th>Remarks</th>
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
                        <td class="text-xs">{{ $tran->from_location ? $tran->from_location->description : '' }}</td>
                        <td class="text-xs cursor-pointer"
                            @if ($tran->trans_stat == 'Requested' and $tran->request_from == session('pharm_location_id')) @can('issue-requested-drugs') wire:click="select_request({{ $tran->id }})" @endcan @endif
                            @if ($tran->trans_stat == 'Requested' and $tran->loc_code == session('pharm_location_id')) onclick="cancel_tx({{ $tran->id }})" @endif
                            @if ($tran->trans_stat == 'Issued' and session('pharm_location_id') == $tran->loc_code) @can('receive-requested-drugs') onclick="receive_issued('{{ $tran->id }}', `{{ $tran->drug->drug_concat() }}`, '{{ number_format($tran->issued_qty) }}')" @endcan @endif>
                            <span class="text-blue-500"><i class="las la-lg la-hand-pointer"></i>
                                {{ $tran->drug->drug_concat() }}</span>
                        </td>
                        <td class="text-xs">{{ number_format($tran->requested_qty) }}</td>
                        <td class="text-xs">{{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}</td>
                        <td class="text-xs">
                            @php
                                if ($tran->trans_stat == 'Issued' or $tran->trans_stat == 'Received') {
                                    echo $tran->items->first()->charge->chrgdesc;
                                }
                            @endphp
                        </td>
                        <td class="text-xs">{!! $tran->updated_at() !!}</td>
                        <td class="text-xs">
                            {{ $tran->remarks_issue ? '[iss: ' . $tran->remarks_issue . ']' : '' }}
                            {{ $tran->remarks_cancel ? '[can: ' . $tran->remarks_cancel . ']' : '' }}
                            {{ $tran->remarks_request ? '[req: ' . $tran->remarks_request . ']' : '' }}
                            {{ $tran->remarks_received ? '[rec: ' . $tran->remarks_received . ']' : '' }}
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
    <input type="checkbox" id="issueModal" class="modal-toggle" />
    <div class="modal">
        <div class="relative modal-box">
            <label for="issueModal" class="absolute btn btn-sm btn-circle right-2 top-2">✕</label>
            @if ($selected_request)
                <span class="text-xl font-bold"> Issue Drugs/Medicine to
                    {{ $selected_request->location->description }}</span>
                <div class="w-full form-control">
                    <label class="label" for="stock_id">
                        <span class="label-text">Drug/Medicine</span>
                    </label>
                    <select class="select select-bordered" id="stock_id" wire:model.defer="chrgcode">
                        <option></option>
                        @forelse ($available_drugs as $charge)
                            @if (is_object($charge))
                                <option value="{{ $charge->chrgcode }}">{{ $charge->charge->chrgdesc }} - [avail QTY:
                                    {{ $charge->avail }}]</option>
                            @endif
                            @if (is_array($charge))
                                <option value="{{ $charge['chrgcode'] }}">{{ $charge['charge']['chrgdesc'] }} - [avail
                                    QTY: {{ $charge['avail'] }}]</option>
                            @endif
                        @empty
                            <option disabled selected>No available stock in warehouse</option>
                        @endforelse
                    </select>
                    @error('chrgcode')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
                <div class="w-full form-control">
                    <label class="label" for="requested_qty">
                        <span class="label-text">Issue QTY</span>
                    </label>
                    <input id="requested_qty" type="number" min="1"
                        max="{{ $selected_request->requested_qty }}" class="w-full input input-bordered"
                        wire:model.defer="issue_qty" />
                    <div class="flex justify-end text-red-600">
                        <label class="float-right cursor-pointer label" for="requested_qty">
                            <span class="text-xs">Requested QTY: {{ $selected_request->requested_qty }}</span>
                        </label>
                    </div>
                </div>
                <div class="w-full form-control">
                    <label class="label" for="remarks">
                        <span class="label-text">Remarks</span>
                    </label>
                    <input id="remarks" type="text" class="w-full input input-bordered"
                        wire:model.defer="remarks" />
                </div>
                <div class="flex justify-end mt-3">
                    <div>
                        <button class="btn btn-primary" onclick="issue_request()"
                            wire:loading.attr="disabled">Issue</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function issue_request() {
            Swal.fire({
                title: 'Are you sure you want to issue items for this request?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                confirmButtonColor: 'green',
                html: `
                    <div class="mt-2 text-slate-500" id="inf">You are about to issue requested items. <br>This process cannot be undone. Continue?</div>
                `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('issue_request')
                }
            })
        }

        function add_request() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Request Drugs/Medicine </span>
                    <div class="w-full form-control">
                        <label class="label" for="location_id">
                            <span class="label-text">Request FROM</span>
                        </label>
                        <select class="select select-bordered select2" id="location_id">
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @if ($location->id == 1) selected @endif>{{ $location->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="stock_id">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-bordered select2" id="stock_id">
                            <option disabled selected>Choose drug/medicine</option>
                            @foreach ($drugs as $drug)
                                <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">{{ $drug->drug_concat() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="requested_qty">
                            <span class="label-text">Request QTY</span>
                        </label>
                        <input id="requested_qty" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="remarks">
                            <span class="label-text">Remarks</span>
                        </label>
                        <input id="remarks" type="text" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const location_id = Swal.getHtmlContainer().querySelector('#location_id');
                    const stock_id = Swal.getHtmlContainer().querySelector('#stock_id');
                    const requested_qty = Swal.getHtmlContainer().querySelector('#requested_qty');
                    const remarks = Swal.getHtmlContainer().querySelector('#remarks');

                    $('.select2').select2({
                        dropdownParent: $('.swal2-container'),
                        width: 'resolve',
                        dropdownCssClass: "text-sm",
                    });

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('location_id', location_id.value);
                    @this.set('stock_id', stock_id.value);
                    @this.set('requested_qty', requested_qty.value);
                    @this.set('remarks', remarks.value);

                    Livewire.emit('add_request');
                }
            });
        }

        function add_more_request() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Request Drugs/Medicine </span>
                    <div class="w-full form-control">
                        <label class="label" for="more_stock_id">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-bordered select2" id="more_stock_id">
                            <option disabled selected>Choose drug/medicine</option>
                            @foreach ($drugs as $drug)
                                <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">{{ $drug->drug_concat() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="more_requested_qty">
                            <span class="label-text">Request QTY</span>
                        </label>
                        <input id="more_requested_qty" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="more_remarks">
                            <span class="label-text">Remarks</span>
                        </label>
                        <input id="more_remarks" type="text" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const more_stock_id = Swal.getHtmlContainer().querySelector('#more_stock_id');
                    const more_requested_qty = Swal.getHtmlContainer().querySelector('#more_requested_qty');
                    const more_remarks = Swal.getHtmlContainer().querySelector('#more_remarks');

                    $('.select2').select2({
                        dropdownParent: $('.swal2-container'),
                        width: 'resolve',
                        dropdownCssClass: "text-sm",
                    });

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('stock_id', more_stock_id.value);
                    @this.set('requested_qty', more_requested_qty.value);
                    @this.set('remarks', more_remarks.value);

                    Livewire.emit('add_more_request');
                }
            });
        }

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

        function receive_issued(trans_id, drug, issued_drug_qty) {
            Swal.fire({
                html: `
                    <span class="text-lg text-xl font-bold"> Receive Drugs/Medicine </span>
                    <div class="w-full mt-3 form-control">
                        <span class="font-bold text-7xl"> ` + issued_drug_qty + ` </span>
                        <span class="text-2xl font-medium"> ` + drug + ` </span>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Receive`,
                didOpen: () => {
                    const received_qty = Swal.getHtmlContainer().querySelector('#received_qty');
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('receive_issued', trans_id);
                }
            });
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
