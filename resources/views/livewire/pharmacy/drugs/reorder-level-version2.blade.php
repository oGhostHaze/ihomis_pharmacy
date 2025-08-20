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
            @if (!$current_io)
                <div class="ml-3 form-control">
                    <label class="label">
                        <span class="label-text">Request Orders to Warehouse</span>
                    </label>
                    <button onclick="bulk_request()" class="btn btn-sm btn-secondary" wire:loading.attr="disabled">
                        <i class="mr-1 las la-lg la-mail-bulk"></i>Batch Request
                    </button>
                </div>
            @endif
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
                    <th>Generic</th>
                    <th class="text-end">Stock Balance</th>
                    <th class="text-end">30-Day Moving Average</th>
                    <th class="text-end">Reorder Level</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stk)
                    <tr class="cursor-pointer hover">
                        <td class="font-bold">{{ $stk->drug_concat }}</td>
                        <td class="text-end">
                            {{ number_format($stk->stock_bal, 0) }}
                        </td>
                        <td class="text-end">
                            {{ $stk->max_level && $stk->max_level > 0 ? number_format($stk->max_level, 2) : '' }}</td>
                        <td class="text-end">
                            {{ $stk->critical && $stk->critical > 0 ? number_format($stk->critical, 2) : '' }}</td>
                        </td>
                        <td class="text-center">
                            {{ $stk->status }}
                        </td>
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
        function bulk_request() {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                html: `
                        <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                        <div class="mt-2 text-slate-500" id="inf">Bulk request items below reorder-level. Continue?</div>
                    `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('bulk_request')
                }
            })
        }

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
                XLSX.writeFile(wb, fn || ('Reorder Level.' + (type || 'xlsx')));
        }

        function update_reorder(dmdcomb, dmdctr, reorder_point) {
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
                    Livewire.emit('update_reorder', dmdcomb, dmdctr, reorder_qty.value);
                }
            });
        }
    </script>
@endpush
