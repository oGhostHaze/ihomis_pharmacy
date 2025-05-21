<div>
    <!-- Header with actions -->
    <div class="p-4 mb-6 bg-white rounded-lg shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">IAR # {{ $iar->iarNo }}</h1>
                <p class="text-gray-600">{{ $iar->formatted_iardate }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('iar.index') }}" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- IAR Information -->
    <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700">IAR Information</h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <h3 class="font-medium text-gray-500">IAR Details</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="text-gray-500">IAR No:</span>
                            <span class="ml-1 font-medium">{{ $iar->iarNo }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Date:</span>
                            <span class="ml-1 font-medium">{{ $iar->formatted_iardate }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Fund Cluster:</span>
                            <span class="ml-1 font-medium">{{ $iar->rcc }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Office:</span>
                            <span class="ml-1 font-medium">{{ $iar->officeName }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-medium text-gray-500">Supplier Information</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="text-gray-500">Supplier:</span>
                            <span class="ml-1 font-medium">{{ $iar->supplier }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">PO No:</span>
                            <span class="ml-1 font-medium">{{ $iar->pono ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">PR No:</span>
                            <span class="ml-1 font-medium">{{ $iar->prno ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-medium text-gray-500">Invoice Information</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="text-gray-500">Invoice No:</span>
                            <span class="ml-1 font-medium">{{ $iar->invoiceNo ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Invoice Date:</span>
                            <span class="ml-1 font-medium">{{ $iar->formatted_invoicedate ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total Amount:</span>
                            <span class="ml-1 font-medium">₱ {{ number_format($iar->iartotalprice, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2">
                <div>
                    <h3 class="font-medium text-gray-500">Inspection Information</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="text-gray-500">Date Inspected:</span>
                            <span class="ml-1 font-medium">{{ $iar->formatted_inspecteddate ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Inspection Status:</span>
                            <span class="ml-1">
                                @if ($iar->inspectStatus == 'A')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($iar->inspectStatus == 'P')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge">{{ $iar->inspectStatus }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-medium text-gray-500">Acceptance Information</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="text-gray-500">Accepted By:</span>
                            <span class="ml-1 font-medium">{{ $iar->accptname ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Designation:</span>
                            <span class="ml-1 font-medium">{{ $iar->accptdesig ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Date Received:</span>
                            <span class="ml-1 font-medium">{{ $iar->formatted_receiveddate ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Acceptance Status:</span>
                            <span class="ml-1">
                                @if ($iar->acceptStatus == 'A')
                                    <span class="badge badge-success">Accepted</span>
                                @elseif($iar->acceptStatus == 'P')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge">{{ $iar->acceptStatus }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($iar->remarks)
                <div class="mt-6">
                    <h3 class="font-medium text-gray-500">Remarks</h3>
                    <div class="p-4 mt-2 rounded-md bg-gray-50">
                        {{ $iar->remarks }}
                    </div>
                </div>
            @endif

            @if ($relatedRis)
                <div class="mt-6">
                    <h3 class="font-medium text-gray-500">Related RIS</h3>
                    <div class="p-4 mt-2 rounded-md bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p><span class="text-gray-500">RIS No:</span> <span
                                        class="font-medium">{{ $relatedRis->risno }}</span></p>
                                <p><span class="text-gray-500">Date:</span> <span
                                        class="font-medium">{{ $relatedRis->formatted_risdate }}</span></p>
                                <p><span class="text-gray-500">Purpose:</span> <span
                                        class="font-medium">{{ $relatedRis->purpose }}</span></p>
                                <p><span class="text-gray-500">Requested By:</span> <span
                                        class="font-medium">{{ $relatedRis->requested_by_name }}</span></p>
                            </div>
                            <a href="{{ route('ris.show', $relatedRis->risid) }}" class="btn btn-sm btn-info">
                                View RIS
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- IAR Items -->
    <div class="overflow-hidden bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700">IAR Items</h2>
        </div>

        <div class="p-6 overflow-x-auto">
            <table class="table w-full table-zebra">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th>Unit</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Amount</th>
                        <th>Batch No.</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @forelse($iarDetails as $detail)
                        @php $totalAmount += $detail->totalprice; @endphp
                        <tr>
                            <td>{{ $detail->itemcode }}</td>
                            <td>{{ $detail->description }}</td>
                            <td>{{ $detail->unit }}</td>
                            <td class="text-right">{{ number_format($detail->quantity, 2) }}</td>
                            <td class="text-right">₱ {{ number_format($detail->unitprice, 2) }}</td>
                            <td class="text-right">₱ {{ number_format($detail->totalprice, 2) }}</td>
                            <td>{{ $detail->batch_no ?? 'N/A' }}</td>
                            <td>{{ $detail->expire_date ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 text-center">No items found.</td>
                        </tr>
                    @endforelse
                    <tr class="font-bold bg-gray-100">
                        <td colspan="5" class="text-right">Total Amount:</td>
                        <td class="text-right">₱ {{ number_format($totalAmount, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
