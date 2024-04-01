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
                <i class="mr-1 las la-prescription-alt la-lg"></i> Out Patient Department
            </li>
        </ul>
    </div>
    <div class="flex justify-center">
        <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.ward')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Wards') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.opd') }}" :active="request()->routeIs('rx.opd')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Out Patient Department') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.er') }}" :active="request()->routeIs('rx.er')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Emergency Room') }}
        </x-jet-nav-link>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    {{-- <div class="flex space-x-8 justify-normal">
        <div class="ml-3 form-control">
            <label class="input-group input-group-sm">
                <span class="text-sm">Ward</span>
                <select class="p-0 pl-2 text-sm w-80 select select-bordered select-sm" wire:model="wardcode">
                    @foreach ($wards as $ward)
                        <option value="{{$ward->wardcode}}">{{$ward->wardname}} ({{$ward->wclcode}})</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div> --}}
    <div class="flex justify-end">
        <div class="w-full max-w-xs form-control ">
            <label for="patient_name">
                <span class="label-text">Patient</span>
            </label>
            <input type="text" placeholder="Patient Name" class="w-full input input-sm input-bordered"
                id="patient_name" />
        </div>
        <div class="ml-2">
            <div class="form-control">
                <label>
                    <span>Date</span>
                    <input type="date" class="w-full input input-sm input-bordered"
                        max="{{ date('Y-m-d', strtotime('+1 day')) }}" wire:model.lazy="filter_date" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-3 overflow-x-auto">
        <div wire:loading>
            <span>
                <i class="las la-spinner la-lg animate-spin"></i>
                Processing...
            </span>
        </div>
        <div wire:loading.class="hidden">
            <table class="table w-full mb-3 table-compact table-zebra">
                <thead>
                    <tr>
                        <th class="cursor-pointer" onclick="sortTable(0)">Date <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th class="cursor-pointer" onclick="sortTable(1)">Patient Name <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th class="cursor-pointer" onclick="sortTable(2)">Department <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th>Order Type</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse ($prescriptions as $rx)
                        <tr wire:key="view-enctr-{{ $rx->enccode }}-{{ $loop->iteration }}"
                            class="cursor-pointer hover clickable-row content @if ($rx->basic) has-basic @elseif ($rx->g24) has-g24 @elseif ($rx->or) has-or @else has-none @endif"
                            data-href="{{ route('dispensing.view.enctr', ['enccode' => Crypt::encrypt(str_replace(' ', '--', $rx->enccode))]) }}">
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ \Carbon\Carbon::parse($rx->opddate)->format('Y/m/d') }}</div>
                                    <div>{{ \Carbon\Carbon::parse($rx->opdtime)->format('g:i A') }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>
                                        {{ $rx->patlast . ', ' . $rx->patfirst . ' ' . $rx->patsuffix . ' ' . $rx->patmiddle }}
                                    </div>
                                    <div class="text-sm"><span
                                            class="badge badge-ghost badge-sm">{{ $rx->hpercode }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>
                                        Out Patient Department
                                    </div>
                                    <div>{{ $rx->tsdesc }}</div>
                                </div>
                            </td>
                            <td>
                                <ul class="text-sm rounded-md menu menu-horizontal bg-base-200">
                                    @if ($rx->basic)
                                        <li>
                                            <div class="tooltip" data-tip="BASIC"><i
                                                    class="las la-2g la-prescription"></i>
                                                <div class="badge badge-accent badge-xs">{{ $rx->basic }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($rx->g24)
                                        <li>
                                            <div class="tooltip" data-tip="Good For 24 Hrs"><i
                                                    class="las la-2g la-hourglass-start"></i>
                                                <div class="badge badge-error badge-xs">{{ $rx->g24 }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($rx->or)
                                        <li>
                                            <div class="tooltip" data-tip="For Operating Use"><i
                                                    class="las la-2g la-syringe"></i>
                                                <div class="badge badge-secondary badge-xs">{{ $rx->or }}</div>
                                            </div>
                                        </li>
                                    @endif
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No record found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-2">
                {{-- {{ $prescriptions->links() }} --}}
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {

            $("#patient_name").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                console.log('asdasds')
                $("#tableBody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("dataTable");
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


        $(document).ready(function($) {
            $(".clickable-row").click(function() {
                let url = $(this).data("href");
                $('<a  rel="noopener noreferrer" href="' + url + '" target="blank"></a>')[0].click();
            });
        });
    </script>
@endpush
