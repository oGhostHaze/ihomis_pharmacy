<div>
    <div class="overflow-hidden bg-white rounded-lg shadow-lg sm:px-6 lg:px-8">
        <div class="flex items-center justify-between p-4 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700"></h2>
            <div class="flex space-x-2">
                <div class="form-control">
                    <div class="input-group">
                        <input type="text" placeholder="Search..." wire:model.debounce.300ms="search"
                            class="input input-bordered input-sm" />
                        <button class="btn btn-square btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <select wire:model="perPage" class="select select-bordered select-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full table-zebra">
                <thead>
                    <tr>
                        <th wire:click="sortBy('tbl_ris.risno')" class="cursor-pointer">
                            <div class="flex items-center">
                                RIS No.
                                @if ($sortField === 'tbl_ris.risno')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('tbl_ris.risdate')" class="cursor-pointer">
                            <div class="flex items-center">
                                Date
                                @if ($sortField === 'tbl_ris.risdate')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Purpose</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($risItems as $item)
                        <tr>
                            <td>{{ $item->risno }}</td>
                            <td>{{ $item->formatted_risdate }}</td>
                            <td>
                                @if (strlen($item->purpose) > 50)
                                    {{ substr($item->purpose, 0, 50) }}...
                                @else
                                    {{ $item->purpose }}
                                @endif
                            </td>
                            <td>{{ $item->requested_by }}</td>
                            <td>
                                <div class="flex space-x-1">
                                    <a href="{{ route('ris.show', $item->risid) }}" class="btn btn-sm btn-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="hidden ml-1 sm:inline">View</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center">No RIS records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $risItems->links() }}
        </div>
    </div>
</div>
