<div wire:init="loadStocks">
    <div class="flex flex-col px-5 py-5 mx-auto max-w-screen">
        <div class="flex justify-end">
            <div class="flex">
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
        <div class="flex flex-col justify-center w-full mt-2 overflow-hidden h-[500px]">
            <div class="overflow-auto">
                <table class="table w-full table-compact" id="table">
                    <thead>
                        <tr>
                            <th>Source of Fund</th>
                            <th>Generic</th>
                            <th class="text-end">Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stk)
                            <tr class="cursor-pointer hover">
                                <th>{{ $stk->chrgdesc }}</th>
                                <td class="font-bold">{{ $stk->drug_concat }}</td>
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
    </div>
</div>
