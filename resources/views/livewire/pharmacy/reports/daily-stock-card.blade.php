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
                <i class="mr-1 las la-folder la-lg"></i> Stock Card
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
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
                <div class="ml-2 form-control">
                    <label class="input-group">
                        <span class="whitespace-nowrap">Fund Source</span>
                        <select class="text-sm select select-bordered select-sm" wire:model="selected_fund">
                            <option value="">N/A</option>
                            @foreach ($fund_sources as $fund)
                                <option value="{{ $fund->chrgcode }},{{ $fund->chrgdesc }}">
                                    {{ $fund->chrgdesc }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="ml-2 form-control">
                    <label class="input-group">
                        <span>Drug</span>
                        <select class="w-2/3 select select-bordered select-sm" wire:model="selected_drug">
                            <option value="">N/A</option>
                            @foreach ($drugs as $stock_item)
                                <option value="{{ $stock_item->dmdcomb }},{{ $stock_item->dmdctr }}">
                                    {{ $stock_item->drug_concat() }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>From</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_to" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div id="print" class="w-full">
            <table class="table w-full bg-white shadow-md table-sm" id="table">
                <thead class="font-bold text-center bg-gray-100">
                    <tr>
                        <td colspan="8" class="border border-black">{{ $chrgdesc }}</td>
                    </tr>
                    <tr>
                        <td class="text-sm uppercase border border-black">Drug</td>
                        <td class="text-sm uppercase border border-black">Date</td>
                        <td class="text-sm border border-black">Reference</td>
                        <td class="text-sm border border-black">Beginning Balance</td>
                        <td class="border border-black">Receipt</td>
                        <td class="border border-black">Issued</td>
                        <td class="text-sm uppercase border border-black">Balance</td>
                        <td class="text-sm border border-black">Expiry Date</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cards as $card)
                        @php
                            $ref = $card->reference;
                            $receipt = $card->rec;
                            $issued = $card->iss;
                            $balance = $card->bal;
                            $total = $ref + $receipt - $issued;
                        @endphp
                        <tr classs="border border-black">
                            <td class="text-sm border">
                                <div class="flex flex-col">
                                    <div>{{ $card->drug_concat }}</div>
                                    <div class="text-xs text-slate-600">{{ $card->charge->chrgdesc ?? '' }}</div>
                                </div>
                            </td>
                            <td class="text-sm text-right border">{{ $card->stock_date }}</td>
                            <td class="text-sm text-right border"></td>
                            <td class="text-sm text-right border">{{ number_format($card->reference) }}</td>
                            <td class="text-sm border">{{ number_format($card->rec) }}</td>
                            <td class="text-sm border">{{ number_format($card->iss) }}</td>
                            <td class="text-sm text-right border">{{ number_format($total) }}</td>
                            <td class="text-sm border">{{ $card->exp_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                                record found!</td>
                        </tr>
                    @endforelse
                </tbody>
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
