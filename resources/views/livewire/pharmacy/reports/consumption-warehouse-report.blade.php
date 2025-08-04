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
                <i class="mr-1 las la-tablets la-lg"></i> Consumption Report (Warehouse)
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col w-full px-2 py-5">


        <!-- Control Panel -->
        <div class="p-4 mb-4 bg-white rounded-lg shadow">
            <!-- Date Range and Fund Source -->
            <div class="flex flex-wrap gap-3 mb-3">
                <div class="form-control">
                    <label class="input-group input-group-sm">
                        <span class="bg-gray-100">From</span>
                        <input type="date" class="flex-1 input input-sm input-bordered" wire:model.lazy="date_from"
                            {{ $processing ? 'disabled' : '' }} />
                    </label>
                </div>
                <div class="form-control">
                    <label class="input-group input-group-sm">
                        <span class="bg-gray-100">To</span>
                        <input type="date" class="flex-1 input input-sm input-bordered" wire:model.lazy="date_to"
                            {{ $processing ? 'disabled' : '' }} />
                    </label>
                </div>
                <div class="form-control">
                    <label class="input-group input-group-sm">
                        <span class="bg-gray-100">Fund Source</span>
                        <select class="flex-1 select select-sm select-bordered" wire:model="filter_charge"
                            {{ $processing ? 'disabled' : '' }}>
                            <option value="">Select Fund Source</option>
                            @foreach ($charge_codes as $charge)
                                <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">
                                    {{ $charge->chrgdesc }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <button type="button" class="btn btn-sm btn-primary" wire:click="createNewReport"
                    wire:loading.attr="disabled">
                    <i class="mr-1 las la-plus"></i> New Report
                </button>
            </div>

            <!-- Report Selection -->
            <div class="flex flex-wrap gap-3 mb-3">
                <div class="flex-1 form-control">
                    <label class="input-group input-group-sm">
                        <span class="bg-gray-100">Existing Reports</span>
                        <select class="flex-1 select select-sm select-bordered" wire:model="report_id"
                            {{ $processing ? 'disabled' : '' }}>
                            <option value="">Select Existing Report</option>
                            @foreach ($cons as $con)
                                <option value="{{ $con->id }}">
                                    {{ $loop->iteration }}.
                                    [{{ date('M d, Y', strtotime($con->consumption_from)) }}] -
                                    [{{ $con->consumption_to ? date('M d, Y', strtotime($con->consumption_to)) : 'Ongoing' }}]
                                    @if ($con->generated_status)
                                        ✓
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-2">
                @if ($report_id)
                    <!-- Generate Report Button -->
                    <button type="button" class="btn btn-sm btn-primary" wire:click="generate_ending_balance"
                        wire:loading.attr="disabled">
                        @if ($processing)
                            <span class="mr-1 loading loading-spinner loading-xs"></span>
                            Generating...
                        @else
                            <i class="mr-1 las la-cog"></i>
                            Generate Report
                        @endif
                    </button>
                @endif

                <!-- Export Options -->
                <div class="flex gap-2 ml-auto">
                    <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-success"
                        {{ count($drugs_issued) == 0 || $processing ? 'disabled' : '' }}>
                        <i class="mr-1 las la-file-excel"></i> Export
                    </button>
                    <button onclick="printMe()" class="btn btn-sm btn-primary"
                        {{ count($drugs_issued) == 0 || $processing ? 'disabled' : '' }}>
                        <i class="mr-1 las la-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
        <div id="print" class="w-full overflow-auto">
            <table class="w-full text-xs bg-white shadow-md" id="table">
                <thead class="text-xs bg-gray-200">
                    <tr>
                        <th rowspan="3" class="px-4 py-2 text-xs font-semibold text-center border border-black">
                            {{ $current_charge }}
                        </th>
                        <th class="px-4 py-2 text-xs text-center border border-black"></th>
                        <th colspan="2" rowspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            BEGINNING BALANCE
                        </th>
                        <th colspan="2" rowspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            TOTAL PURCHASES
                        </th>
                        <th rowspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            TOTAL AVAILABLE FOR SALE
                        </th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">UNIT COST</th>
                        <th rowspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            TOTAL COST AVAILABLE FOR SALE
                        </th>
                        <th colspan="7" class="px-4 py-2 text-xs text-center border border-black">ISSUANCES</th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">TOTAL ISSUANCES
                        </th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">SELLING PRICE
                        </th>
                        <th rowspan="2" colspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            TOTAL SALES
                        </th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">
                            OVERALL TOTAL ISSUANCES
                        </th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">
                            OVERALL COST OF GOODS ISSUED
                        </th>
                        <th rowspan="3" class="px-4 py-2 text-xs text-center border border-black">PROFIT</th>
                        <th rowspan="2" colspan="2" class="px-4 py-2 text-xs text-center border border-black">
                            ENDING BALANCE
                        </th>
                    </tr>
                    <tr>
                        <th class="px-4 py-2 text-xs border border-black">Item Code</th>
                        <th class="px-4 py-2 text-xs border border-black">Satellite Pharmacy</th>
                        <th class="px-4 py-2 text-xs border border-black">OPD Pharmacy</th>
                        <th class="px-4 py-2 text-xs border border-black">Cancer Unit</th>
                        <th class="px-4 py-2 text-xs border border-black">OR Pharmacy</th>
                        <th class="px-4 py-2 text-xs border border-black">NST</th>
                        <th class="px-4 py-2 text-xs border border-black">Other Clinical Areas</th>
                        <th class="px-4 py-2 text-xs border border-black">Return/Pull Out</th>
                    </tr>
                    <tr>
                        <th class="px-4 py-2 text-xs border border-black"></th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">Amount</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">Amount</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">Amount</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black whitespace-nowrap">
                            (SP, OPD, CU, <br> OR, NST)
                        </th>
                        <th class="px-4 py-2 text-xs border border-black">(Others, Return)</th>
                        <th class="px-4 py-2 text-xs border border-black">(Qty)</th>
                        <th class="px-4 py-2 text-xs border border-black">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drugs_issued as $item)
                        @php
                            $concat = implode(',', explode('_,', $item->drug_concat));
                            $for_sale = $item->total_purchases + $item->beg_bal;
                        @endphp
                        <tr>
                            <td class="px-4 py-2 border whitespace-nowrap">{{ $concat }}</td>
                            <td class="px-4 py-2 border text-end">{{ $item->dmdcomb . $item->dmdctr }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->beg_bal > 0 ? number_format($item->beg_bal) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($item->beg_bal * $item->unit_cost, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">{{ number_format($item->total_purchases) }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($item->total_purchases * $item->unit_cost, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ $for_sale > 0 ? number_format($for_sale) : '' }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($item->unit_cost, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($for_sale * $item->unit_cost, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->sat_iss > 0 ? number_format($item->sat_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->opd_iss > 0 ? number_format($item->opd_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->cu_iss > 0 ? number_format($item->cu_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->or_iss > 0 ? number_format($item->or_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->nst_iss > 0 ? number_format($item->nst_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->others_iss > 0 ? number_format($item->others_iss) : '' }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ $item->returns_pullout > 0 ? number_format($item->returns_pullout) : '' }}</td>
                            @php
                                $total_issued =
                                    $item->sat_iss +
                                    $item->opd_iss +
                                    $item->cu_iss +
                                    $item->or_iss +
                                    $item->nst_iss +
                                    $item->others_iss;

                                $overall_cost = $total_issued * $item->unit_cost;
                                $overall_sales = $total_issued * $item->unit_price;
                                $profit = $overall_sales - $overall_cost;
                                $end_bal = $for_sale - $total_issued;
                                $end_amount = $end_bal * $item->unit_cost;
                            @endphp
                            <td class="px-4 py-2 border text-end">
                                {{ $total_issued > 0 ? number_format($total_issued) : '' }}</td>
                            <td class="px-4 py-2 border text-end">{{ number_format($item->dmselprice, 2) }}</td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format(($total_issued - $item->others_iss) * $item->unit_price, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format(($item->others_iss + $item->returns_pullout) * $item->unit_price, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ $total_issued > 0 ? number_format($total_issued) : '' }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($overall_cost, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($profit, 2) }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ $end_bal > 0 ? number_format($end_bal) : '' }}
                            </td>
                            <td class="px-4 py-2 border text-end">
                                {{ number_format($end_amount, 2) }}
                            </td>
                        </tr>
                    @endforeach

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

        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("table");
            switching = true;
            // Set the sorting direction to ascending:
            dir = "asc";
            /* Make a loop that will continue until
            no switching has been done: */
            while (switching) {
                // Start by saying: no switching is done:
                switching = false;
                rows = table.rows;
                /* Loop through all table rows (except the
                first, which contains table headers): */
                for (i = 2; i < (rows.length - 1); i++) {
                    // Start by saying there should be no switching:
                    shouldSwitch = false;
                    /* Get the two elements you want to compare,
                    one from current row and one from the next: */
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];
                    /* Check if the two rows should switch place,
                    based on the direction, asc or desc: */
                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            // If so, mark as a switch and break the loop:
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            // If so, mark as a switch and break the loop:
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    /* If a switch has been marked, make the switch
                    and mark that a switch has been done: */
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    // Each time a switch is done, increase this count by 1:
                    switchcount++;
                } else {
                    /* If no switching has been done AND the direction is "asc",
                    set the direction to "desc" and run the while loop again. */
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
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
