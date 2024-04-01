<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-file-prescription la-lg"></i> Rx/Orders
            </li>
            <li>
                <i class="mr-1 las la-file-invoice la-lg"></i> Pending Orders
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
            <div class="flex justify-between">
            </div>
            <div class="flex justify-end">
                <div class="ml-2">
                    <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                            class="las la-lg la-file-excel"></i> Export</button>
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
                <div class="ml-2 form-control">
                    <label class="input-group">
                        <span class="label-text">Search</span>
                        <input type="text" placeholder="Patient Name" class="w-full input input-sm input-bordered"
                            id="patient_name" />
                    </label>
                </div>
            </div>
        </div>
        <table class="table shadow-md table-fixed table-compact table-hover" id="table">
            <thead class="font-bold bg-gray-200">
                <tr>
                    <td class="text-sm uppercase cursor-pointer" onclick="sortTable(0)"># <span class="ml-1"><i
                                class="las la-sort"></i></span></td>
                    <td class="text-sm cursor-pointer" onclick="sortTable(1)">Date Ordered <span class="ml-1"><i
                                class="las la-sort"></i></span></td>
                    <td class="text-sm cursor-pointer" onclick="sortTable(2)">Hosp. # <span class="ml-1"><i
                                class="las la-sort"></i></span></td>
                    <td class="text-sm cursor-pointer" onclick="sortTable(3)">Name of Patient <span class="ml-1"><i
                                class="las la-sort"></i></span></td>
                    <td class="text-sm text-right cursor-pointer" onclick="sortTable(4)">Total Items <span
                            class="ml-1"><i class="las la-sort"></i></span></td>
                    <td class="text-sm text-right cursor-pointer" onclick="sortTable(5)">Amount <span class="ml-1"><i
                                class="las la-sort"></i></span></td>
                    <td class="text-sm text-right cursor-pointer" onclick="sortTable(5)">Entry By <span
                            class="ml-1"><i class="las la-sort"></i></span></td>
                </tr>
            </thead>
            <tbody id="admittedTable">
                @forelse ($drugs_ordered as $rxo)
                    <tr class="border border-black cursor-pointer hover:bg-gray-300"
                        wire:click="view_enctr('{{ $rxo->enccode }}')" wire:key="view_enctr-{{ $rxo->enccode }}">
                        <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                        <td class="text-sm border">{{ date('F j, Y H:i A', strtotime($rxo->dodate)) }}</td>
                        <td class="text-sm border">{{ $rxo->hpercode }}</td>
                        <td class="text-sm border">{{ $rxo->patlast . ', ' . $rxo->patfirst, ' ' . $rxo->patmiddle }}
                        </td>
                        <td class="text-sm text-right border">{{ $rxo->total_order }}</td>
                        <td class="text-sm text-right border">{{ $rxo->total_amount }}</td>
                        <td class="text-sm text-right border">
                            {{ $rxo->entryby }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                            record found!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
        $("#patient_name").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#admittedTable tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

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
                for (i = 1; i < (rows.length - 1); i++) {
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
    </script>
@endpush
