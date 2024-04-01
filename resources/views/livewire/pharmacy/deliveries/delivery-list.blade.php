<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-truck la-lg"></i> Deliveries
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div>
            <button class="btn btn-sm btn-primary" onclick="new_delivery()">Add Delivery</button>
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
        <table class="table w-full mb-2 table-compact">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>PO #</th>
                    <th>SI #</th>
                    <th>Supplier</th>
                    <th>Total Items</th>
                    <th>Total Amount</th>
                    <th>Source of Fund</th>
                    <th>Delivery Type</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveries as $delivery)
                    <tr onclick="window.location='{{ route('delivery.view', [$delivery->id, true]) }}'"
                        class="cursor-pointer hover">
                        <th>{{ $delivery->id }}</th>
                        <td>{{ $delivery->delivery_date }}</td>
                        <td>{{ $delivery->po_no }}</td>
                        <td>{{ $delivery->si_no }}</td>
                        <td>{{ $delivery->supplier->suppname }}</td>
                        <td>{{ $delivery->items->sum('qty') }}</td>
                        <td>{{ $delivery->items->sum('total_amount') }}</td>
                        <td>{{ $delivery->charge->chrgdesc }}</td>
                        <td>{{ $delivery->delivery_type }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="10">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $deliveries->links() }}
    </div>
</div>

@push('scripts')
    <script>
        function new_delivery() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Add Delivery </span>
                    <div class="w-full form-control">
                        <label class="label" for="delivery_date">
                            <span class="label-text">Delivery Date</span>
                        </label>
                        <input id="delivery_date" type="date" value="{{ date('Y-m-d') }}" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="po_no">
                            <span class="label-text">Purchase Order No</span>
                        </label>
                        <input id="po_no" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="si_no">
                            <span class="label-text">Sales Invoice No</span>
                        </label>
                        <input id="si_no" type="text" class="w-full input input-bordered" />
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="suppcode">
                            <span class="label-text">Supplier</span>
                        </label>
                        <select class="select select-bordered" id="suppcode">
                            <option disabled selected>Choose supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->suppcode }}">{{ $supplier->suppname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="charge_code">
                            <span class="label-text">Source of Fund</span>
                        </label>
                        <select class="select select-bordered" id="charge_code">
                            @foreach ($charges as $charge)
                                <option value="{{ $charge->chrgcode }}">{{ $charge->chrgdesc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full form-control">
                        <label class="label" for="delivery_type">
                            <span class="label-text">Type of Delivery</span>
                        </label>
                        <select class="select select-bordered" id="delivery_type">
                            <option value="procured" selected>Procured</option>
                            <option value="donation">Donation</option>
                            <option value="received">Received</option>
                        </select>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const po_no = Swal.getHtmlContainer().querySelector('#po_no');
                    const si_no = Swal.getHtmlContainer().querySelector('#si_no');
                    const delivery_date = Swal.getHtmlContainer().querySelector('#delivery_date');
                    const suppcode = Swal.getHtmlContainer().querySelector('#suppcode');
                    const charge_code = Swal.getHtmlContainer().querySelector('#charge_code');
                    const delivery_type = Swal.getHtmlContainer().querySelector('#delivery_type');

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('po_no', po_no.value);
                    @this.set('si_no', si_no.value);
                    @this.set('delivery_date', delivery_date.value);
                    @this.set('suppcode', suppcode.value);
                    @this.set('charge_code', charge_code.value);
                    @this.set('delivery_type', delivery_type.value);

                    Livewire.emit('add_delivery');
                }
            });
        }
    </script>
@endpush
