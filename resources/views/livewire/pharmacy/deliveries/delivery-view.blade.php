<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-truck la-lg"></i> Deliveries
            </li>
            <li>
                <i class="mr-1 las la-eye la-lg"></i> View
            </li>
            <li>
                {{ $details->si_no }}
            </li>
        </ul>
    </div>
</x-slot>

@php
    $total_qty = 0;
    $total_amount = 0.0;
@endphp

<div class="flex flex-col p-5 mx-auto mt-5 max-w-screen-2xl">
    @if ($details->delivery_type === 'RIS' && $details->po_no)
        <div class="p-4 mb-4 border-l-4 border-blue-500 rounded bg-blue-50">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        This delivery was created from RIS # <span class="font-medium">{{ $details->po_no }}</span>
                    </p>
                    <p class="mt-1 text-xs text-blue-600">
                        <a href="{{ route('ris.show', ['id' => $details->po_no]) }}"
                            class="underline hover:text-blue-800">
                            View original RIS
                        </a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($details->status == 'pending')
        <div class="p-4 mb-3 bg-white rounded-lg">
            <div class="flex justify-between">
                <div>
                    <button class="btn btn-sm btn-warning" onclick="save_lock()" wire:loading.attr="disabled">Save &
                        Lock</button>
                </div>
                @if ($details->delivery_type != 'RIS')
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="add_item()" wire:loading.attr="disabled">Add
                            Item</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
    @if ($errors->first())
        <div class="mb-3 shadow-lg alert alert-error">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-6 h-6 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif
    <div class="flex flex-col p-5 bg-white rounded-lg">
        <div class="flex justify-between w-full pb-2 border-b">
            <div class="flex flex-col w-1/2">
                <div class="flex">
                    <div class="w-36">Delivery Date:</div>
                    <div class="font-bold uppercase w-96">{{ $details->delivery_date }}</div>
                </div>
                <div class="flex">
                    <div class="w-36"> Supplier:</div>
                    <div class="font-bold uppercase w-96">{{ $details->supplier ? $details->supplier->suppname : '' }}
                    </div>
                </div>
                <div class="flex">
                    <div class="w-36"> Source of Fund:</div>
                    <div class="font-bold uppercase w-96">{{ $details->charge->chrgdesc }}</div>
                </div>
            </div>
            <div class="flex flex-col w-1/2">
                <div class="flex">
                    <div class="w-36"> {{ $details->charge_code == 'DRUMAK' ? 'Reference' : 'Purchase Order' }} #:
                    </div>
                    <div class="font-bold uppercase w-96">{{ $details->po_no }}</div>
                </div>
                <div class="flex">
                    <div class="w-36"> Sales Invoice #:</div>
                    <div class="font-bold uppercase w-96">{{ $details->si_no }}</div>
                </div>
                <div class="flex">
                    <div class="w-36"> Status:</div>
                    <div class="font-bold uppercase w-96">
                        <div class="badge @if ($details->status == 'pending') badge-ghost @else badge-success @endif">
                            {{ $details->status }}</div>
                    </div>
                </div>
            </div>
        </div>
        <table class="table w-full mt-3 table-compact">
            <thead>
                <tr>
                    <th>Lot #</th>
                    <th>Description</th>
                    <th class="text-right">QTY</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Retail Price</th>
                    <th class="text-right">Total Amount</th>
                </tr>
            </thead>
            <tbody class="border">
                @forelse ($details->items->all() as $item)
                    @php
                        $dm = $item->drug;
                        $total_qty++;
                        $total_amount += $item->total_amount;
                    @endphp
                    <tr
                        @if ($details->status == 'pending') class="cursor-pointer hover" onclick="edit_item('{{ $item->id }}', '{{ $item->lot_no }}', '{{ number_format($item->qty) }}', '{{ $item->unit_price }}', '{{ $item->retail_price }}', '{{ $item->total_amount }}', '{{ $item->expiry_date }}', '{{ $dm->drug_concat() }}')" @endif>
                        <td>{{ $item->lot_no }}</td>
                        <td>{{ $dm->drug_concat() }} (exp: {{ $item->expiry_date }})</td>
                        <td class="text-right">{{ number_format($item->qty) }}</td>
                        <td class="text-right">{{ $item->unit_price }}</td>
                        <td class="text-right">
                            @if ($item->current_price->has_compounding)
                                <span class="font-bold tooltip"
                                    data-tip="Includes {{ $item->current_price->compounding_fee }} compounding fee.">
                                    <i class="las la-question-circle"></i></span>
                            @endif
                            {{ $item->retail_price }}
                        </td>
                        <td class="text-right">{{ $item->total_amount }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="6">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="uppercase">
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $total_qty }} Item/s</td>
                    <td class="text-right"></td>
                    <td class="text-right">Total</td>
                    <td class="text-right">{{ number_format($total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>


@push('scripts')
    <script>
        function add_item() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Add Item </span>
                    <div class="w-full form-control">
                        <label class="label" for="dmdcomb">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-bordered select2" id="dmdcomb">
                            <option disabled selected>Choose drug/medicine</option>
                            @foreach ($drugs as $drug)
                                <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">{{ $drug->drug_concat() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="expiry_date">
                            <span class="label-text">Expiry Date</span>
                        </label>
                        <input id="expiry_date" type="date" value="{{ date('Y-m-d', strtotime(now() . '+1 years')) }}" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="qty">
                            <span class="label-text">QTY</span>
                        </label>
                        <input id="qty" type="number" value="1" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="unit_price">
                            <span class="label-text">Unit Cost</span>
                              </label>
                            <input id="unit_price" type="number" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="lot_no">
                            <span class="label-text">Lot No</span>
                        </label>
                        <input id="lot_no" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="px-2 form-control">
                        <label class="flex mt-3 space-x-3 cursor-pointer">
                            <input type="checkbox" id="has_compounding" class="checkbox" />
                            <span class="mr-auto label-text !justify-self-start">Highly Specialised Drugs</span>
                        </label>
                    </div>
                    <div class="w-full px-2 form-control" hidden id="compounding_div">
                        <label class="label" for="compounding_fee">
                            <span class="label-text">Compounding fee</span>
                        </label>
                        <input id="compounding_fee" type="number" step="0.01" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const dmdcomb = Swal.getHtmlContainer().querySelector('#dmdcomb');
                    const expiry_date = Swal.getHtmlContainer().querySelector('#expiry_date');
                    const qty = Swal.getHtmlContainer().querySelector('#qty');
                    const unit_price = Swal.getHtmlContainer().querySelector('#unit_price');
                    const lot_no = Swal.getHtmlContainer().querySelector('#lot_no');
                    const has_compounding = Swal.getHtmlContainer().querySelector('#has_compounding');
                    const compounding_div = Swal.getHtmlContainer().querySelector('#compounding_div');
                    const compounding_fee = Swal.getHtmlContainer().querySelector('#compounding_fee');

                    compounding_div.style.display = 'none';

                    has_compounding.addEventListener('click', function handleClick() {
                        if (has_compounding.checked) {
                            compounding_div.style.display = 'block';
                        } else {
                            compounding_div.style.display = 'none';
                        }
                    });

                    $('.select2').select2({
                        dropdownParent: $('.swal2-container'),
                        width: 'resolve',
                    });
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('dmdcomb', $('#dmdcomb').select2('val'));
                    @this.set('expiry_date', expiry_date.value);
                    @this.set('qty', qty.value);
                    @this.set('unit_price', unit_price.value);
                    @this.set('lot_no', lot_no.value);
                    @this.set('has_compounding', has_compounding.checked);
                    @this.set('compounding_fee', compounding_fee.value);

                    Livewire.emit('add_item');
                }
            });
        }

        function edit_item(item_id, item_lot_no, item_qty, item_unit_price, item_retail_price, item_total_amount,
            item_expiry_date, drug_name) {
            Swal.fire({
                html: `
        <span class="text-xl font-bold uppercase"> Edit Item: <br>` + drug_name + ` </span>

        <div class="w-full form-control">
            <label class="label">
                <span class="label-text">Current Details</span>
            </label>
            <div class="p-4 mb-4 bg-gray-100 rounded">
                <p><strong>Quantity:</strong> ` + item_qty + `</p>
                <p><strong>Unit Price:</strong> ` + item_unit_price + `</p>
                <p><strong>Current Retail Price:</strong> ` + item_retail_price + `</p>
            </div>
        </div>

        <div class="w-full form-control">
            <label class="label" for="edit_lot_no">
                <span class="label-text">Lot No</span>
            </label>
            <input id="edit_lot_no" type="text" value="` + item_lot_no + `" class="w-full input input-bordered" />
        </div>

        <div class="w-full form-control">
            <label class="label" for="edit_expiry_date">
                <span class="label-text">Expiry Date</span>
            </label>
            <input id="edit_expiry_date" type="date" value="` + item_expiry_date + `" class="w-full input input-bordered" />
        </div>

        <div class="px-2 form-control">
            <label class="flex mt-3 space-x-3 cursor-pointer">
                <input type="checkbox" id="edit_has_compounding" class="checkbox" />
                <span class="mr-auto label-text !justify-self-start">Highly Specialised Drugs</span>
            </label>
        </div>
        <div class="w-full px-2 form-control" hidden id="edit_compounding_div">
            <label class="label" for="edit_compounding_fee">
                <span class="label-text">Compounding fee</span>
            </label>
            <input id="edit_compounding_fee" type="number" step="0.01" class="w-full input input-bordered" />
        </div>`,
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: `Save Changes`,
                didOpen: () => {
                    const has_compounding = Swal.getHtmlContainer().querySelector('#edit_has_compounding');
                    const compounding_div = Swal.getHtmlContainer().querySelector('#edit_compounding_div');
                    const compounding_fee = Swal.getHtmlContainer().querySelector('#edit_compounding_fee');

                    compounding_div.style.display = 'none';

                    // Check if the current item has compounding fee
                    // You can determine this based on retail_price vs calculated price
                    const unit_price = parseFloat(item_unit_price);
                    let calculated_base_price = 0;
                    let excess = 0;

                    if (unit_price >= 10000.01) {
                        excess = unit_price - 10000;
                        calculated_base_price = unit_price + 1115 + (excess * 0.05);
                    } else if (unit_price >= 1000.01 && unit_price <= 10000.00) {
                        excess = unit_price - 1000;
                        calculated_base_price = unit_price + 215 + (excess * 0.10);
                    } else if (unit_price >= 100.01 && unit_price <= 1000.00) {
                        excess = unit_price - 100;
                        calculated_base_price = unit_price + 35 + (excess * 0.20);
                    } else if (unit_price >= 50.01 && unit_price <= 100.00) {
                        excess = unit_price - 50;
                        calculated_base_price = unit_price + 20 + (excess * 0.30);
                    } else if (unit_price >= 0.01 && unit_price <= 50.00) {
                        calculated_base_price = unit_price + (unit_price * 0.40);
                    }

                    const current_retail = parseFloat(item_retail_price);
                    if (current_retail > calculated_base_price) {
                        has_compounding.checked = true;
                        compounding_div.style.display = 'block';
                        compounding_fee.value = (current_retail - calculated_base_price).toFixed(2);
                    }

                    has_compounding.addEventListener('click', function handleClick() {
                        if (has_compounding.checked) {
                            compounding_div.style.display = 'block';
                        } else {
                            compounding_div.style.display = 'none';
                        }
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const lot_no = document.querySelector('#edit_lot_no');
                    const expiry_date = document.querySelector('#edit_expiry_date');
                    const has_compounding = document.querySelector('#edit_has_compounding');
                    const compounding_fee = document.querySelector('#edit_compounding_fee');

                    @this.set('lot_no', lot_no.value);
                    @this.set('expiry_date', expiry_date.value);
                    @this.set('has_compounding', has_compounding.checked);
                    @this.set('compounding_fee', compounding_fee ? compounding_fee.value : 0);

                    Livewire.emit('edit_item', item_id);
                }
            });
        }

        function save_lock() {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                        <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                        <div class="mt-2 text-slate-500" id="inf">All items in this delivery will be added to your current stocks and no changes can be made after. <br>This process cannot be undone. Continue?</div>
                    `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('save_lock')
                }
            })
        }
    </script>
@endpush
