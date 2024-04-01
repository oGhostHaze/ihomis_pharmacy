<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-first-aid la-lg"></i> Emergency Purchases
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5">
    @if ($errors->first())
        <div class="mb-3 shadow-lg cursor-pointer alert alert-error" wire:click="$emit('refresh')">
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
    <div class="flex justify-between">
        <div>
            <button class="btn btn-sm btn-primary" onclick="new_ep()">Add EP</button>
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
    <div class="flex justify-center w-full mt-2 overflow-x-auto">
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>OR #</th>
                    <th>Bought From</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Purchase QTY</th>
                    <th>Total Amount</th>
                    <th>Source of Fund</th>
                    <th>Encoded by</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <th>{{ $purchase->id }}</th>
                        <td>{{ $purchase->purchase_date }}</td>
                        <td>{{ $purchase->or_no }}</td>
                        <td>{{ $purchase->pharmacy_name }}</td>
                        <td>
                            {{ $purchase->drug->drug_concat() }} (exp: {{ $purchase->expiry_date }})
                        </td>
                        <td>{{ $purchase->unit_price }}</td>
                        <td>{{ number_format($purchase->qty) }}</td>
                        <td>{{ number_format($purchase->total_amount, 2) }}</td>
                        <td>{{ $purchase->charge->chrgdesc }}</td>
                        <td>{{ $purchase->user->name }}</td>
                        <td>
                            <button
                                @if ($purchase->status == 'pending') class="btn btn-xs btn-warning tooltip" data-tip="Push to stocks"  onclick="push({{ $purchase->id }})" @elseif ($purchase->status == 'pushed') class="badge badge-sm badge-success" @else class="badge badge-sm badge-error" @endif>
                                {{ $purchase->status }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="11">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $purchases->links() }}
    </div>
</div>

@push('scripts')
    <script>
        function new_ep() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Add EP </span>
                    <div class="flex space-x-2">
                        <div class="w-full form-control">
                            <label class="label" for="purchase_date">
                                <span class="label-text">Purchase Date</span>
                            </label>
                            <input id="purchase_date" type="date" value="{{ date('Y-m-d') }}" class="w-full input input-sm input-bordered" />
                        </div>
                        <div class="w-full form-control">
                            <label class="label" for="or_no">
                                <span class="label-text">OR No</span>
                            </label>
                            <input id="or_no" type="text" class="w-full input input-sm input-bordered" />
                        </div>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="pharmacy_name">
                            <span class="label-text">Bought From <small>(Pharmacy Name)</small></span>
                        </label>
                        <input id="pharmacy_name" type="text" class="w-full input input-sm input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="charge_code">
                            <span class="label-text">Source of Fund</span>
                        </label>
                        <select class="select select-sm select-bordered" id="charge_code">
                            @foreach ($charges as $charge)
                                <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="dmdcomb">
                            <span class="label-text">Drug/Medicine</span>
                        </label>
                        <select class="select select-sm select-bordered select2" id="dmdcomb">
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
                        <input id="expiry_date" type="date" value="{{ date('Y-m-d', strtotime(now() . '+1 years')) }}" class="w-full input input-sm input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="qty">
                            <span class="label-text">QTY</span>
                        </label>
                        <input id="qty" type="number" value="1" class="w-full input input-sm input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="unit_price">
                            <span class="label-text">Unit Cost</span>
                        </label>
                        <input id="unit_price" type="number" step="0.01" class="w-full input input-sm input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="lot_no">
                            <span class="label-text">Lot No</span>
                        </label>
                        <input id="lot_no" type="text" class="w-full input input-sm input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="remarks">
                            <span class="label-text">Remarks</span>
                        </label>
                        <textarea id="remarks" type="text" class="w-full textarea textarea-bordered"></textarea>
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
                        <input id="compounding_fee" type="number" step="0.01" class="w-full input input-sm input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const purchase_date = Swal.getHtmlContainer().querySelector('#purchase_date');
                    const or_no = Swal.getHtmlContainer().querySelector('#or_no');
                    const pharmacy_name = Swal.getHtmlContainer().querySelector('#pharmacy_name');
                    const charge_code = Swal.getHtmlContainer().querySelector('#charge_code');

                    const dmdcomb = Swal.getHtmlContainer().querySelector('#dmdcomb');
                    const expiry_date = Swal.getHtmlContainer().querySelector('#expiry_date');
                    const qty = Swal.getHtmlContainer().querySelector('#qty');
                    const unit_price = Swal.getHtmlContainer().querySelector('#unit_price');
                    const lot_no = Swal.getHtmlContainer().querySelector('#lot_no');
                    const has_compounding = Swal.getHtmlContainer().querySelector('#has_compounding');
                    const compounding_div = Swal.getHtmlContainer().querySelector('#compounding_div');
                    const compounding_fee = Swal.getHtmlContainer().querySelector('#compounding_fee');
                    const remarks = Swal.getHtmlContainer().querySelector('#remarks');

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
                    @this.set('purchase_date', purchase_date.value);
                    @this.set('or_no', or_no.value);
                    @this.set('pharmacy_name', pharmacy_name.value);
                    @this.set('charge_code', charge_code.value);

                    @this.set('dmdcomb', $('#dmdcomb').select2('val'));
                    @this.set('expiry_date', expiry_date.value);
                    @this.set('qty', qty.value);
                    @this.set('unit_price', unit_price.value);
                    @this.set('lot_no', lot_no.value);
                    @this.set('has_compounding', has_compounding.checked);
                    @this.set('compounding_fee', compounding_fee.value);
                    @this.set('remarks', remarks.value);

                    Livewire.emit('new_ep');
                }
            });
        }


        function push(purchase_id) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Confirm Action </span>
                    <div class="w-full form-control">
                        <span>Confirm puush emergency purchased item to stocks? Action cannot be undone.</span>
                    </div>`,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: `Push`,
                denyButtonText: `Cancel`,
                cancelButtonText: `Close`,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('push', purchase_id);
                } else if (result.isDenied) {
                    Livewire.emit('cancel_purchase', purchase_id);
                }
            });
        }
    </script>
@endpush
