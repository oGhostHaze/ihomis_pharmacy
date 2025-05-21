<div>
    <div class="mx-auto overflow-hidden bg-white rounded-lg shadow-lg sm:px-6 lg:px-8">
        <div class="flex items-center justify-between p-4 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700">Inspection and Acceptance Reports - Pharmacy</h2>
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
                        <th wire:click="sortBy('tbl_iar.iarNo')" class="cursor-pointer">
                            <div class="flex items-center">
                                IAR No.
                                @if ($sortField === 'tbl_iar.iarNo')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('tbl_iar.iardate')" class="cursor-pointer">
                            <div class="flex items-center">
                                Date
                                @if ($sortField === 'tbl_iar.iardate')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('tbl_iar.supplier')" class="cursor-pointer">
                            <div class="flex items-center">
                                Supplier
                                @if ($sortField === 'tbl_iar.supplier')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>PO No.</th>
                        <th>Invoice No.</th>
                        <th>RIS No.</th>
                        <th wire:click="sortBy('tbl_iar.iartotalprice')" class="cursor-pointer">
                            <div class="flex items-center">
                                Total Price
                                @if ($sortField === 'tbl_iar.iartotalprice')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Status</th>
                        <th>Actions</th>
                <tbody>
                    @forelse ($iars as $iar)
                        <tr>
                            <td>{{ $iar->iarNo }}</td>
                            <td>{{ $iar->formatted_iardate }}</td>
                            <td>{{ $iar->supplier }}</td>
                            <td>{{ $iar->pono }}</td>
                            <td>{{ $iar->invoiceNo }}</td>
                            <td>
                                @if ($iar->risno)
                                    <span class="badge badge-info">{{ $iar->risno }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($iar->iartotalprice, 2) }}</td>
                            <td>
                                @if ($iar->acceptStatus == 'A' && $iar->inspectStatus == 'A')
                                    <span class="badge badge-success">Complete</span>
                                @elseif ($iar->acceptStatus == 'P' || $iar->inspectStatus == 'P')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge">Unknown</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex space-x-1">
                                    <a href="{{ route('iar.show', $iar->iarID) }}" class="btn btn-sm btn-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="ml-1">View</span>
                                    </a>
                                    <button wire:click="viewIar({{ $iar->iarID }})" class="btn btn-sm btn-ghost">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                                        </svg>
                                        <span class="ml-1">Quick View</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-center">No IAR records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $iars->links() }}
        </div>
    </div>

    <!-- IAR View Modal -->
    @if ($showViewModal && $selectedIar)
        <div class="modal modal-open">
            <div class="max-w-5xl modal-box">
                <h3 class="text-lg font-bold">IAR Details - {{ $selectedIar['iar']->iarNo }}</h3>

                <div class="p-4 mt-4 bg-gray-100 rounded-md">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-500">IAR Number</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->iarNo }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">IAR Date</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->formatted_iardate }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Office</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->officeName }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Supplier</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->supplier }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">PO Number</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->pono ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">PR Number</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->prno ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Invoice Number</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->invoiceNo ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Invoice Date</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->formatted_invoicedate ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fund Cluster</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->rcc ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm text-gray-500">Date Inspected</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->formatted_inspecteddate ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date Received</p>
                            <p class="font-semibold">{{ $selectedIar['iar']->formatted_receiveddate ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Acceptance Information</p>
                        <p class="font-semibold">{{ $selectedIar['iar']->accptname ?? 'N/A' }}</p>
                        <p class="text-sm">{{ $selectedIar['iar']->accptdesig ?? 'N/A' }}</p>
                    </div>

                    @if ($selectedIar['iar']->remarks)
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">Remarks</p>
                            <p>{{ $selectedIar['iar']->remarks }}</p>
                        </div>
                    @endif

                    @if ($selectedIar['iar']->ris_in_iar && $selectedIar['iar']->risid)
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">Related RIS</p>
                            <div class="flex items-center justify-between">
                                <p class="font-semibold">RIS No:
                                    {{ DB::connection('pims')->table('tbl_ris')->where('risid', $selectedIar['iar']->risid)->value('risno') ?? 'N/A' }}
                                </p>
                                <a href="{{ route('ris.show', $selectedIar['iar']->risid) }}"
                                    class="btn btn-xs btn-info">View RIS</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="divider">Items</div>

                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                                <th>Batch No.</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAmount = 0; @endphp
                            @forelse ($selectedIar['details'] as $detail)
                                @php $totalAmount += $detail->totalprice; @endphp
                                <tr>
                                    <td>{{ $detail->itemcode }}</td>
                                    <td>{{ $detail->description }}</td>
                                    <td>{{ $detail->unit }}</td>
                                    <td class="text-right">{{ number_format($detail->quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format($detail->unitprice, 2) }}</td>
                                    <td class="text-right">{{ number_format($detail->totalprice, 2) }}</td>
                                    <td>{{ $detail->batch_no ?? 'N/A' }}</td>
                                    <td>{{ $detail->expire_date ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No items found</td>
                                </tr>
                            @endforelse
                            <tr class="font-bold">
                                <td colspan="5" class="text-right">Total Amount:</td>
                                <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-action">
                    <button wire:click="$set('showViewModal', false)" class="btn">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
