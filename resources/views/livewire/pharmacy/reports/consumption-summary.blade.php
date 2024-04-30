<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-file-excel la-lg"></i> Report
            </li>
            <li>
                <i class="mr-1 las la-folder la-lg"></i> Consumption Summary
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="mx-auto max-w-screen-2xl">
    <div class="flex flex-col px-2 py-5 overflow-auto">
        <div class="flex justify-between my-2">
            <div class="flex justify-between space-x-2">
                <div class="ml-2">
                    <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                            class="las la-lg la-file-excel"></i> Export</button>
                </div>
                <div class="ml-2">
                    <button onclick="printMe()" class="btn btn-sm btn-primary"><i class="las la-lg la-print"></i>
                        Print</button>
                </div>
            </div>
            <div class="flex justify-end">
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Location</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="location_id">
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Type/Tag</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="tagging">
                                <option value="">ALL</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->tx_type }}" class="uppercase">
                                        @php
                                            $desc = '';
                                            switch ($tag->tx_type) {
                                                case 'opdpay':
                                                case 'pay':
                                                    $desc = 'Non Basic';
                                                    break;

                                                case 'service':
                                                    $desc = 'Basic';
                                                    break;

                                                default:
                                                    $desc = $tag->tx_type;
                                                    break;
                                            }
                                        @endphp
                                        {{ $desc }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Date</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div id="print" class="w-full p-3 bg-white">
            <table class="table w-full table-xs table-compact" id="table">
                <thead class="font-bold text-center bg-gray-100">
                    <tr>
                        <td class="text-sm text-right uppercase">#</td>
                        <td class="text-sm text-left uppercase">Patient</td>
                        <td class="text-sm text-left">Prescribing Department</td>
                        <td class="text-sm text-left">Type/Tagging</td>
                        <td class="text-sm">Rx</td>
                        <td class="text-sm">L.I.</td>
                        <td class="text-sm text-right">Amount</td>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total_rx = 0;
                        $total_li = 0;
                        $total_amount = 0;
                    @endphp
                    @forelse ($transactions as $txn)
                        @php
                            $tag = '';
                            switch ($txn->transaction_type) {
                                case 'opdpay':
                                case 'pay':
                                    $tag = 'Non Basic';
                                    break;

                                case 'service':
                                    $tag = 'Basic';
                                    break;

                                default:
                                    $tag = $txn->transaction_type;
                                    break;
                            }
                            $total_rx++;
                            $total_li += $txn->line_item;
                            $total_amount += $txn->amount;
                        @endphp
                        <tr classs="border border-black">
                            <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                            <td class="text-sm border">
                                {{ $txn->patlast . ', ' . $txn->patfirst }} <span
                                    class="text-xs">({{ $txn->hpercode }})</span></td>
                            <td class="text-sm border">
                                {{ $txn->prescribing_department ?? $txn->tsdesc . ' [' . $txn->toecode . ']' }}</td>
                            <td class="text-sm uppercase border">{{ $tag }}</td>
                            <td class="text-sm text-center border">{{ $txn->rx ?? '' }}</td>
                            <td class="text-sm text-center border">{{ $txn->line_item }}</td>
                            <td class="text-sm text-right border">{{ number_format($txn->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                                record found!</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr classs="border border-black bg-gray-100 font-bold">
                        <td class="text-sm text-right border"></td>
                        <td class="text-sm text-right border"></td>
                        <td class="text-sm text-right border"></td>
                        <td class="text-sm text-right border"></td>
                        <td class="text-sm font-bold text-center border">{{ $total_rx }}</td>
                        <td class="text-sm font-bold text-center border">{{ $total_li }}</td>
                        <td class="text-sm font-bold text-right border">{{ number_format($total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-2">
            {{-- {{ $drugs_ordered->links() }} --}}
        </div>
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="my-modal" class="modal-toggle" wire:loading.attr="checked" />
    <div class="modal">
        <div class="modal-box">
            <div>
                <span>
                    <i class="las la-spinner la-lg animate-spin"></i>
                    Processing...
                </span>
            </div>
        </div>
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
                XLSX.writeFile(wb, fn || ('Ward Consumption Report.' + (type || 'xlsx')));
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
