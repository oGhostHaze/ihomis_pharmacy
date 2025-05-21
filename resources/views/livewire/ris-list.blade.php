<div>
    <div class="overflow-hidden bg-white rounded-lg shadow-lg sm:px-6 lg:px-8">
        <div class="flex items-center justify-between p-4 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700"></h2>
            <div class="flex space-x-2">
                <div class="w-full max-w-sm mb-4 form-control">
                    <select wire:model="statusFilter" class="w-full max-w-sm select-sm select select-bordered">
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="issued">Issued</option>
                        <option value="transferred">Transferred to Delivery</option>
                        <option value="not-transferred">Not Transferred</option>
                    </select>
                </div>
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
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>
                            <a wire:click.prevent="sortBy('tbl_ris.risno')" href="#">
                                RIS No.
                                @if ($sortField === 'tbl_ris.risno')
                                    @if ($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </a>
                        </th>
                        <th>
                            <a wire:click.prevent="sortBy('tbl_ris.risdate')" href="#">
                                RIS Date
                                @if ($sortField === 'tbl_ris.risdate')
                                    @if ($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </a>
                        </th>
                        <th>Purpose</th>
                        <th>Items / Amount</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Delivery Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($risItems as $item)
                        <tr>
                            <td>{{ $item->risno }}</td>
                            <td>{{ $item->formatted_risdate }}</td>
                            <td>{{ $item->purpose }}</td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $item->item_count ?? 0 }} Item(s)</span>
                                    <span class="text-sm text-gray-600">
                                        ₱{{ number_format($item->total_amount ?? 0, 2) }}
                                    </span>
                                </div>
                            </td>
                            <td>{{ $item->requested_by }}</td>
                            <td>
                                @if ($item->apprvstat === 'A' && $item->issuedstat === 'I')
                                    <span class="badge badge-success">Issued</span>
                                @elseif($item->apprvstat === 'A')
                                    <span class="badge badge-info">Approved</span>
                                @elseif($item->apprvstat === 'P')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-ghost">Draft</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $this->getDeliveryStatusClass($item) }}">
                                    {{ $this->getDeliveryStatus($item) }}
                                </span>
                                @if ($item->transferred_to_pdims)
                                    <div class="mt-1 text-xs text-gray-500">
                                        Transferred:
                                        {{ \Carbon\Carbon::parse($item->transferred_at)->format('M d, Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('ris.show', $item->risid) }}" class="btn btn-sm btn-primary">
                                    View
                                </a>
                                @if ($item->transferred_to_pdims)
                                    <a href="{{ route('delivery.view', $item->transferred_to_pdims) }}"
                                        class="btn btn-sm btn-secondary" target="_blank">
                                        View Delivery
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No RIS records found</td>
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
