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

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div class="flex space-x-3">
            <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i class="las la-lg la-file-excel"></i>
                Export</button>
            <button onclick="printMe()" class="btn btn-sm btn-primary"><i class="las la-lg la-print"></i>
                Print</button>
        </div>
        <div class="flex space-x-3">
            <div class="form-control">
                <label class="input-group">
                    <span>From</span>
                    <input type="date" class="w-full input input-sm input-bordered" wire:model.lazy="from" />
                </label>
            </div>
            <div class="form-control">
                <label class="input-group">
                    <span>To</span>
                    <input type="date" class="w-full input input-sm input-bordered" wire:model.lazy="to" />
                </label>
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
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto" id="print">
        <table class="table w-full mb-2 table-compact" id="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>PO #</th>
                    <th>SI #</th>
                    <th>Item</th>
                    <th class="text-end">QTY</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total Amount</th>
                    <th>Fund Source</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveries as $delivery)
                    @foreach ($delivery->items->all() as $item)
                        @php
                            $concat = implode('', explode('_,', $item->drug->drug_concat));
                        @endphp
                        <tr>
                            <td>{{ $delivery->delivery_date }}</td>
                            <td>{{ $delivery->po_no }}</td>
                            <td>{{ $delivery->si_no }}</td>
                            <td class="font-bold">{{ $concat }} <small>{{ $item->expiry_date }}</small></td>
                            <td class="text-end">{{ number_format($item->qty) }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_amount, 2) }}</td>
                            <td>{{ $delivery->charge->chrgdesc }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <th class="text-center" colspan="10">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@push('scripts')
    <script>
        function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('table');
            var wb = XLSX.utils.table_to_book(elt, {
                sheet: "sheet1"
            });
            return dl ?
                XLSX.write(wb, {
                    bookType: type,
                    bookSST: true,
                    type: 'base64'
                }) :
                XLSX.writeFile(wb, fn || ('DeliverySummary.' + (type || 'xlsx')));
        }

        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
