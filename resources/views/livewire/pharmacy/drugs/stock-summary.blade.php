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

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="flex flex-col px-5 py-5 mx-auto max-w-screen">
    <div class="flex justify-end">
        <div class="flex">
            @can('filter-stocks-location')
                <div class="ml-3 form-control">
                    <label class="label">
                        <span class="label-text">Current Location</span>
                    </label>
                    <select class="w-full max-w-xs text-sm select select-bordered select-sm select-success"
                        wire:model="location_id">
                        <option value="">All</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                        @endforeach
                    </select>
                </div>
            @endcan
            <div class="ml-3 form-control">
                <label class="label">
                    <span class="label-text">Fund Source</span>
                </label>
                <select class="text-sm select select-bordered select-sm" wire:model="selected_fund">
                    <option value="">All</option>
                    @foreach ($charges as $charge)
                        <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">
                            {{ $charge->chrgdesc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ml-3 form-control">
                <label class="label">
                    <span class="label-text">Export to .csv</span>
                </label>
                <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                        class="las la-lg la-file-excel"></i> Export</button>
            </div>
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
        <table class="table w-full table-compact" id="table">
            <thead>
                <tr>
                    <th>Source of Fund</th>
                    <th>Generic</th>
                    <th>Lot No</th>
                    <th>Expiration Date</th>
                    <th class="text-end">Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stk)
                    <tr class="cursor-pointer hover">
                        <th>{{ $stk->chrgdesc }}</th>
                        <td class="font-bold">{{ $stk->drug_concat }}</td>
                        <td>{{ $stk->lot_no }}</td>
                        <td>{{ $stk->exp_date }}</td>
                        <td class="text-end">{{ number_format($stk->stock_bal, 0) }}</td>
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
                XLSX.writeFile(wb, fn || ('Stocks summary.' + (type || 'xlsx')));
        }

        function update_reorder(dmdcomb, dmdctr, chrgcode, reorder_point) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Update Reorder Point</span>
                    <div class="w-full px-2 form-control">
                        <label class="label" for="reorder_qty">
                            <span class="label-text">Beginning Balance</span>
                        </label>
                        <input id="reorder_qty" type="number" value="` + reorder_point + `" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const reorder_qty = Swal.getHtmlContainer().querySelector('#reorder_qty');
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('update_reorder', dmdcomb, dmdctr, chrgcode, reorder_qty.value);
                }
            });
        }
    </script>
@endpush
