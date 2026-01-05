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
                <i class="mr-1 las la-tablets la-lg"></i> Consumption Report Manual
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <style>
        .editable-field {
            cursor: pointer;
            position: relative;
        }

        .editable-field:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .editable-field.active {
            background-color: rgba(59, 130, 246, 0.2);
        }

        .edit-mode-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 50;
            padding: 8px 16px;
            background-color: #3b82f6;
            color: white;
            border-radius: 4px;
        }
    </style>
@endpush

<div class="max-w-screen">
    @if ($editMode)
        <div class="edit-mode-indicator">
            <span class="font-bold">Edit Mode Active</span>
        </div>
    @endif

    <div class="flex flex-col w-full px-2 py-5">
        <div class="flex justify-end my-2">
            <div class="ml-2">
                <button class="btn btn-sm btn-primary" wire:loading.attr='disabled'
                    wire:click='generate_ending_balance'>Generate</button>
            </div>
            <div class="ml-2">
                {{-- <button class="btn btn-sm {{ $editMode ? 'btn-error' : 'btn-secondary' }}" wire:click="toggleEditMode">
                    {{ $editMode ? 'Exit Edit Mode' : 'Edit Quantities' }}
                </button> --}}
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
                        <input type="date" class="w-full input input-sm input-bordered" wire:model.lazy="date_to" />
                    </label>
                </div>
            </div>
            <div class="ml-2 mr-auto">
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
        <div class="flex my-2">
            <progress class="mt-2 mr-2 w-28 progress" wire:loading.inline></progress>
        </div>
        <div id="print" class="w-full overflow-auto" wire:loading.class='hidden'
            wire:target='generate_ending_balance'>
            <table class="w-full text-xs bg-white shadow-md" id="table">
                <thead class="sticky top-0 font-bold bg-gray-200">
                    <tr class="text-center uppercase">
                        <td class="w-2/12 text-xs border border-black">Source of Fund</td>
                        <td class="text-xs border border-black" colspan="2">Beg. Bal.</td>
                        <td class="text-xs border border-black" colspan="2">Total Purchases</td>
                        <td class="text-xs border border-black" colspan="3">Total Avail. For Sale</td>
                        <td class="text-xs border border-black" colspan="2">IO TRANS</td>
                        <td class="text-xs border border-black" colspan="1"></td>
                        <td class="text-xs border border-black" colspan="16">Issuances</td>
                        <td class="text-xs border border-black" colspan="2">Ending Bal.</td>
                    </tr>
                    <tr class="text-center">
                        <td class="text-xs uppercase border border-black cursor-pointer" onclick="sortTable(0)"
                            rowspan="2">
                            {{ $current_charge }} <span class="ml-1"><i class="las la-sort"></i></span></td>
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
                        <td class="text-xs border border-black" rowspan="2">Pullout</td>
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
                    @forelse ($drugs_issued as $rxi)
                        @php
                            // Use the model methods to calculate values
                            $available = $rxi->available();
                            $available_amount = $rxi->availableAmount();
                            $total_sales = $rxi->totalSales();
                            $total_cogs = $rxi->totalCOGS();
                            $total_profit = $rxi->totalProfit();
                            $ending_balance = $rxi->endingBalance();
                            $ending_balance_amount = $rxi->endingBalanceAmount();

                            // Format the drug concat string
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                            $concat = implode(' ', explode('-', $concat));
                        @endphp
                        <tr class="border border-black hover" itemcode="{{ $rxi->dmdcomb . ', ' . $rxi->dmdctr }}">
                            <td class="text-xs border border-black whitespace-nowrap">
                                {{ $concat }}
                            </td>

                            <!-- Beginning Balance - Editable -->
                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap
                                       {{ $editMode ? 'editable-field' : '' }}"
                                @if ($editMode) wire:click="initializeEditField({{ $rxi->id }}, 'beg_bal', {{ $rxi->beg_bal }})"
                                    onclick="this.classList.add('active')" @endif>
                                @if ($editMode && isset($editableFields[$rxi->id]['beg_bal']))
                                    <input type="number" class="w-full text-right input input-bordered input-xs"
                                        wire:model.defer="editableFields.{{ $rxi->id }}.beg_bal"
                                        wire:keydown.enter="saveField({{ $rxi->id }}, 'beg_bal')"
                                        wire:blur="saveField({{ $rxi->id }}, 'beg_bal')" autofocus />
                                @else
                                    {{ number_format($rxi->beg_bal) }}
                                @endif
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->beg_bal * $rxi->acquisition_cost, 2) }}
                            </td>

                            <!-- Purchased - Editable -->
                            <td class="text-xs text-right border border-black whitespace-nowrap
                                       {{ $editMode ? 'editable-field' : '' }}"
                                @if ($editMode) wire:click="initializeEditField({{ $rxi->id }}, 'purchased', {{ $rxi->purchased }})"
                                    onclick="this.classList.add('active')" @endif>
                                @if ($editMode && isset($editableFields[$rxi->id]['purchased']))
                                    <input type="number" class="w-full text-right input input-bordered input-xs"
                                        wire:model.defer="editableFields.{{ $rxi->id }}.purchased"
                                        wire:keydown.enter="saveField({{ $rxi->id }}, 'purchased')"
                                        wire:blur="saveField({{ $rxi->id }}, 'purchased')" autofocus />
                                @else
                                    {{ number_format($rxi->purchased) }}
                                @endif
                            </td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->purchased * $rxi->acquisition_cost, 2) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($available) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->acquisition_cost, 2) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($available_amount, 2) }}
                            </td>

                            <!-- IO Trans IN - Editable -->
                            <td class="text-xs text-right border border-black whitespace-nowrap
                                       {{ $editMode ? 'editable-field' : '' }}"
                                @if ($editMode) wire:click="initializeEditField({{ $rxi->id }}, 'received_iotrans', {{ $rxi->received_iotrans }})"
                                    onclick="this.classList.add('active')" @endif>
                                @if ($editMode && isset($editableFields[$rxi->id]['received_iotrans']))
                                    <input type="number" class="w-full text-right input input-bordered input-xs"
                                        wire:model.defer="editableFields.{{ $rxi->id }}.received_iotrans"
                                        wire:keydown.enter="saveField({{ $rxi->id }}, 'received_iotrans')"
                                        wire:blur="saveField({{ $rxi->id }}, 'received_iotrans')" autofocus />
                                @else
                                    {{ number_format($rxi->received_iotrans) }}
                                @endif
                            </td>

                            <!-- IO Trans OUT - Editable -->
                            <td class="text-xs text-right border border-black whitespace-nowrap
                                       {{ $editMode ? 'editable-field' : '' }}"
                                @if ($editMode) wire:click="initializeEditField({{ $rxi->id }}, 'transferred_iotrans', {{ $rxi->transferred_iotrans }})"
                                    onclick="this.classList.add('active')" @endif>
                                @if ($editMode && isset($editableFields[$rxi->id]['transferred_iotrans']))
                                    <input type="number" class="w-full text-right input input-bordered input-xs"
                                        wire:model.defer="editableFields.{{ $rxi->id }}.transferred_iotrans"
                                        wire:keydown.enter="saveField({{ $rxi->id }}, 'transferred_iotrans')"
                                        wire:blur="saveField({{ $rxi->id }}, 'transferred_iotrans')" autofocus />
                                @else
                                    {{ number_format($rxi->transferred_iotrans) }}
                                @endif
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->return_qty) }}
                            </td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->ems) }}</td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->maip) }}</td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->wholesale) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->opdpay) }}</td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->pay) }}</td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->service) }}</td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->caf) }}</td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->pcso) }}</td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->phic) }}</td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->konsulta) }}
                            </td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->pullout_qty) }}
                            </td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($rxi->issue_qty) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($rxi->dmselprice, 2) }}</td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($total_sales, 2) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($total_cogs, 2) }}
                            </td>

                            <td class="text-xs text-right border border-black whitespace-nowrap">
                                {{ number_format($total_profit, 2) }}
                            </td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($ending_balance) }}</td>

                            <td class="text-xs text-right bg-gray-100 border border-black whitespace-nowrap">
                                {{ number_format($ending_balance_amount, 2) }}</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="29" class="font-bold text-center uppercase bg-red-400 border border-black">
                                No record found!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($drugs_issued) > 0)
                    <tfoot class="bg-gray-200">
                        <tr class="font-bold text-right">
                            <td class="text-xs border border-black">TOTALS</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('beg_bal')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->beg_bal * $item->acquisition_cost;}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('purchased')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->purchased * $item->acquisition_cost;}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->available();})) }}
                            </td>
                            <td class="text-xs border border-black">-</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->availableAmount();}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('received_iotrans')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('transferred_iotrans')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('return_qty')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('ems')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('maip')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('wholesale')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('opdpay')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('pay')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('service')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('caf')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('pcso')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('phic')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('konsulta')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('pullout_qty')) }}</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum('issue_qty')) }}</td>
                            <td class="text-xs border border-black">-</td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->totalSales();}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->totalCOGS();}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->totalProfit();}),2) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->endingBalance();})) }}
                            </td>
                            <td class="text-xs border border-black">
                                {{ number_format(collect($drugs_issued)->sum(function ($item) {return $item->endingBalanceAmount();}),2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        function ExportToExcel(type, fn, dl) {
            // Temporarily hide edit controls before export
            const editControls = document.querySelectorAll('input.input-xs');
            editControls.forEach(control => {
                control.style.display = 'none';
            });

            var elt = document.getElementById('table');
            var wb = XLSX.utils.table_to_book(elt, {
                sheet: "sheet1"
            });

            // Restore edit controls
            setTimeout(() => {
                editControls.forEach(control => {
                    control.style.display = '';
                });
            }, 1000);

            return dl ?
                XLSX.write(wb, {
                    bookType: type,
                    bookSST: true,
                    type: 'base64'
                }) :
                XLSX.writeFile(wb, fn || ('Consumption Report.' + (type || 'xlsx')));
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
            // Hide the edit controls before printing
            const editControls = document.querySelectorAll('input.input-xs');
            const editModeIndicator = document.querySelector('.edit-mode-indicator');

            editControls.forEach(control => {
                control.style.display = 'none';
            });

            if (editModeIndicator) {
                editModeIndicator.style.display = 'none';
            }

            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;

            // Restore the edit controls after printing
            setTimeout(() => {
                editControls.forEach(control => {
                    control.style.display = '';
                });

                if (editModeIndicator) {
                    editModeIndicator.style.display = '';
                }
            }, 1000);
        }

        // Reset active state when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.editable-field')) {
                document.querySelectorAll('.editable-field.active').forEach(el => {
                    el.classList.remove('active');
                });
            }
        });
    </script>
@endpush
