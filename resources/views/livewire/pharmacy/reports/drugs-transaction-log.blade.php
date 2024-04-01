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
                <i class="mr-1 las la-history la-lg"></i> Transaction Log
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5">
        <div class="flex justify-end my-2">
            <div class="ml-2">
                <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                        class="las la-lg la-file-excel"></i> Export</button>
            </div>
            <div class="ml-2">
                <button onclick="printMe()" class="btn btn-sm btn-primary"><i class="las la-lg la-print"></i>
                    Print</button>
            </div>
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
                        <span>From</span>
                        <input type="datetime-local" class="w-full input input-sm input-bordered"
                            max="{{ $date_to }}" wire:model.lazy="date_from" />
                    </label>
                </div>
            </div>
            <div class="ml-2">
                <div class="form-control">
                    <label class="input-group">
                        <span>To</span>
                        <input type="datetime-local" class="w-full input input-sm input-bordered"
                            min="{{ $date_from }}" wire:model.lazy="date_to" />
                    </label>
                </div>
            </div>
            <div class="ml-2">
                <div class="form-control">
                    <label class="input-group">
                        <span>Fund Source</span>
                        <select class="select select-bordered select-sm" wire:model="filter_charge">
                            <option></option>
                            @foreach ($charge_codes as $charge)
                                <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">{{ $charge->chrgdesc }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
        </div>
        <div id="print" class="w-full">
            <table class="table w-full bg-white shadow-md table-sm" id="table">
                <thead class="font-bold bg-gray-200">
                    <tr class="text-center uppercase">
                        <td class="w-2/12 text-xs border border-black">Source of Fund</td>
                        <td class="text-xs border border-black" colspan="2">Beg. Bal.</td>
                        <td class="text-xs border border-black" colspan="2">Total Purchases</td>
                        <td class="text-xs border border-black" colspan="3">Total Avail. For Sale</td>
                        <td class="text-xs border border-black" colspan="15">Issuances</td>
                        <td class="text-xs border border-black" colspan="2">Ending Bal.</td>
                    </tr>
                    <tr class="text-center">
                        <td class="text-xs uppercase border border-black">{{ $current_charge }}</td>
                        <td class="text-xs border border-black">QTY.</td>
                        <td class="text-xs border border-black">Amount</td>
                        <td class="text-xs border border-black">QTY.</td>
                        <td class="text-xs border border-black">AMT.</td>
                        <td class="text-xs border border-black">QTY.</td>
                        <td class="text-xs border border-black">Unit <br> Cost</td>
                        <td class="text-xs border border-black">Total <br> Cost</td>
                        <td class="text-xs border border-black">SC/PWD</td>
                        <td class="text-xs border border-black">EMS</td>
                        <td class="text-xs border border-black">MAIP</td>
                        <td class="text-xs border border-black">W.S.</td>
                        <td class="text-xs border border-black">Pay</td>
                        <td class="text-xs border border-black">Medicare</td>
                        <td class="text-xs border border-black">Service</td>
                        <td class="text-xs border border-black">CAF</td>
                        <td class="text-xs border border-black">Gov't <br> Emp.</td>
                        <td class="text-xs border border-black">Returns</td>
                        <td class="text-xs border border-black">Issued <br> Total</td>
                        <td class="text-xs border border-black">Selling <br> Price</td>
                        <td class="text-xs border border-black">Total <br> Sales</td>
                        <td class="text-xs border border-black">COGS</td>
                        <td class="text-xs border border-black">Profit</td>
                        <td class="text-xs border border-black">QTY.</td>
                        <td class="text-xs border border-black">Amount</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr classs="border border-black">
                            <td class="text-xs border border-black">
                                <div class="flex flex-col">
                                    <div class="text-sm font-bold">{{ $log->drug->generic->gendesc }}</div>
                                    <div class="ml-10 text-xs text-slate-800">
                                        {{ $log->drug->dmdnost }}{{ $log->drug->strength->stredesc ?? '' }}
                                        {{ $log->drug->form->formdesc ?? '' }}</div>
                                </div>
                            </td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->beg_bal) }}</td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->beg_bal * $log->current_price->dmduprice, 2) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->purchased) }}
                            </td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->purchased * $log->current_price->dmduprice, 2) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->available()) }}
                            </td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->current_price->dmduprice, 2) }}</td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->available_amount(), 2) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->sc_pwd) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->ems) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->maip) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->wholesale) }}
                            </td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->pay) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->medicare) }}
                            </td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->service) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->caf) }}</td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->govt_emp) }}
                            </td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->return_qty) }}
                            </td>
                            <td class="text-xs text-right border border-black">{{ number_format($log->issue_qty) }}
                            </td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->current_price->dmselprice, 2) }}</td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->total_sales(), 2) }}
                            </td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->total_cogs(), 2) }}
                            </td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->total_profit(), 2) }}</td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->ending_balance(), 2) }}</td>
                            <td class="text-xs text-right border border-black">
                                {{ number_format($log->ending_balance() * $log->current_price->dmduprice, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="font-bold text-center uppercase bg-red-400 border border-black">
                                No
                                record found!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
