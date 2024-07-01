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
                    <tr class="cursor-pointer hover" id="tr{{ '-' . $stk->id }}">
                        <td class="w-10 text-center">{{ $loop->iteration }}</td>
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
    <script></script>
@endpush
