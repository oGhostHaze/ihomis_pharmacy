<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-truck la-lg"></i> Drugs and Medicine Stock Inventory
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col px-5 py-5 mx-auto max-w-screen">
    <div class="flex justify-end">
        <div class="flex">
            <div class="mt-auto">
                <button class="btn btn-sm btn-warning" onclick="pull_out()">Pull-out
                    Items</button>
            </div>
            @can('filter-stocks-location')
                <div class="ml-3 form-control">
                    <label class="label">
                        <span class="label-text">Current Location</span>
                    </label>
                    <select class="w-full max-w-xs text-sm select select-bordered select-sm select-success"
                        wire:model="location_id">
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                        @endforeach
                    </select>
                </div>
            @endcan
            <div class="ml-3 form-control">
                <label class="label">
                    <span class="label-text">Seach generic name</span>
                </label>
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm"
                        wire:model.lazy="search" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto">
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th></th>
                    <th>Source of Fund</th>
                    <th>Balance as of</th>
                    <th>Generic</th>
                    @role('warehouse')
                        <th>Cost</th>
                    @endrole
                    <th>Price</th>
                    <th>Stock Balance</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stk)
                    <tr class="cursor-pointer hover" id="tr{{ '-' . $stk->id }}"
                        onclick="check(`{{ '-' . $stk->id }}`)">
                        <td class="w-10 text-center">
                            <input type="checkbox" class="checkbox{{ '-' . $stk->id }}" wire:model="selected_items"
                                value="{{ $stk->id }}" id="checkbox{{ '-' . $stk->id }}" checked="false" />
                        </td>
                        <th>{{ $stk->chrgdesc }}</th>
                        <td>{{ $stk->updated_at }}</td>
                        <td class="font-bold">{{ $stk->drug_concat() }}</td>
                        @role('warehouse')
                            <th>{{ $stk->dmduprice }}</th>
                        @endrole
                        <td>{{ $stk->dmselprice }}</td>
                        <td>{{ $stk->balance() }}</td>
                        <td>{!! $stk->expiry() !!}</td>
                    </tr>
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
        function check(element) {
            var checkBox = document.getElementById('checkbox' + element);
            var tR = document.getElementById('tr' + element);

            if (checkBox.checked == false) {
                checkBox.click();
                tR.classList.toggle("active");
            } else {
                checkBox.click();
                tR.classList.toggle("active");
            }
        }


        function pull_out() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Pullout Selected Items </span>
                    <div class="w-full form-control">
                        <label class="label" for="pullout_date">
                            <span class="label-text">Pullout Date</span>
                        </label>
                        <input id="pullout_date" type="date" value="{{ date('Y-m-d') }}" class="w-full input input-bordered" />
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
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const pullout_date = Swal.getHtmlContainer().querySelector('#pullout_date');
                    const si_no = Swal.getHtmlContainer().querySelector('#si_no');

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('pullout_date', pullout_date.value);
                    @this.set('suppcode', suppcode.value);

                    Livewire.emit('pull_out');
                }
            });
        }
    </script>
@endpush
