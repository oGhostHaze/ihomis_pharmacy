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
                <i class="mr-1 las la-tablets la-lg"></i> Consumption Report
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col w-full px-2 py-5">
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
                        <span>Reports</span>
                        <select class="select select-bordered select-sm" wire:model="report_id">
                            <option></option>
                            @foreach ($cons as $con)
                                <option value="{{ $con->id }}">
                                    {{ $loop->iteration }}
                                    [{{ date('Y-m-d g:i A', strtotime($con->consumption_from)) }}] -
                                    [{{ $con->consumption_to ? date('Y-m-d g:i A', strtotime($con->consumption_to)) : 'Ongoing' }}]
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
            {{-- <div class="ml-2">
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
            </div> --}}
        </div>
        <div id="print" class="w-full overflow-auto">
            <table class="w-full text-xs bg-white shadow-md table-compact" id="table">
                <thead class="sticky top-0 font-bold bg-gray-200">
                    <tr class="text-center uppercase">
                        <td class="w-2/12 text-xs border border-black">Source of Fund</td>
                        <td class="text-xs border border-black" colspan="2">Beg. Bal.</td>
                        <td class="text-xs border border-black" colspan="2">Total Purchases</td>
                        <td class="text-xs border border-black" colspan="3">Total Avail. For Sale</td>
                        <td class="text-xs border border-black" colspan="2">IO TRANS</td>
                        <td class="text-xs border border-black" colspan="1"></td>
                        <td class="text-xs border border-black" colspan="15">Issuances</td>
                        <td class="text-xs border border-black" colspan="2">Ending Bal.</td>
                    </tr>
                    <tr class="text-center">
                        <td class="text-xs uppercase border border-black cursor-pointer" onclick="//sortTable(0)"
                            rowspan="2">
                            {{ $current_charge ?? '' }} <span class="ml-1"><i class="las la-sort"></i></span></td>
                        <td class="text-xs border border-black" rowspan="2">QTY.</td>
                        <td class="text-xs border border-black" rowspan="2">Amount</td>
                        <td class="text-xs border border-black" rowspan="2">QTY.</td>
                        <td class="text-xs border border-black" rowspan="2">AMT.</td>
                        <td class="text-xs border border-black" rowspan="2">QTY.</td>
                        <td class="text-xs border border-black" rowspan="2">Unit <br> Cost</td>
                        <td class="text-xs border border-black" rowspan="2">Total <br> Cost</td>
                        <td class="text-xs border border-black" rowspan="2">IN</td>
                        <td class="text-xs border border-black" rowspan="2">OUT</td>
                        <td class="text-xs border border-black" rowspan="2">Returns</td>
                        <td class="text-xs border border-black" rowspan="2">EMS</td>
                        <td class="text-xs border border-black" rowspan="2">MAIP</td>
                        <td class="text-xs border border-black" rowspan="2">W.S.</td>
                        <td class="text-xs border border-black" rowspan="2">Pay</td>
                        <td class="text-xs border border-black" rowspan="1" colspan="2">Inpatient</td>
                        <td class="text-xs border border-black" rowspan="2">CAF</td>
                        <td class="text-xs border border-black" rowspan="2">PCSO</td>
                        <td class="text-xs border border-black" rowspan="2">PHIC</td>
                        <td class="text-xs border border-black" rowspan="2">Kon. <br> Pkg.</td>
                        <td class="text-xs border border-black" rowspan="2">Issued <br> Total</td>
                        <td class="text-xs border border-black" rowspan="2">Selling <br> Price</td>
                        <td class="text-xs border border-black" rowspan="2">Total <br> Sales</td>
                        <td class="text-xs border border-black" rowspan="2">COGS</td>
                        <td class="text-xs border border-black" rowspan="2">Profit</td>
                        <td class="text-xs border border-black" rowspan="2">QTY.</td>
                        <td class="text-xs border border-black" rowspan="2">Amount</td>
                    </tr>
                    <tr class="text-center uppercase">
                        <td class="text-xs border border-black">Non-Basic</td>
                        <td class="text-xs border border-black">Basic</td>
                    </tr>
                </thead>
                <tbody>
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
