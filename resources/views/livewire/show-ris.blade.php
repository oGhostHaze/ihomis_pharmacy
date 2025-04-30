<div>
    <!-- Header with actions -->
    <div class="p-4 bg-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">RIS # {{ $ris->risno }}</h1>
                <p class="text-gray-600">{{ $ris->formatted_risdate }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('ris.index') }}" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Back to List
                </a>
                <a href="{{ route('ris.print', $ris->risid) }}" target="_blank" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </a>
            </div>
        </div>
    </div>

    <!-- RIS Form styled like the sample image -->
    <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md sm:p-6 lg:p-8">
        <table class="w-full border border-gray-300">
            <tr class="border-t border-gray-300">
                <td colspan="4" class="p-2 pl-4">
                    <table class="w-full">
                        <tr>
                            <td class="w-20 font-bold">Division:</td>
                            <td class="border-b border-gray-400">Medical Service</td>
                        </tr>
                    </table>
                </td>
                <td colspan="3" class="p-2 pr-4">
                    <table class="w-full">
                        <tr>
                            <td class="font-bold whitespace-nowrap">Responsibility Center Code:</td>
                            <td class="pl-2 border-b border-gray-400">{{ $ris->rcc }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4" class="p-2 pl-4">
                    <table class="w-full">
                        <tr>
                            <td class="w-20 font-bold">Office:</td>
                            <td class="border-b border-gray-400">{{ $ris->officeName }}</td>
                        </tr>
                    </table>
                </td>
                <td colspan="3" class="p-2 pr-4">
                    <table class="w-full">
                        <tr>
                            <td class="w-20 font-bold">RIS No:</td>
                            <td class="border-b border-gray-400">{{ $ris->risno }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4" class="p-2 pl-4">
                    &nbsp;
                </td>
                <td colspan="3" class="p-2 pr-4">
                    <table class="w-full">
                        <tr>
                            <td class="w-20 font-bold">Date:</td>
                            <td class="border-b border-gray-400">{{ $ris->formatted_risdate }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="border-t border-gray-300">
                <td colspan="4" class="p-2 font-bold text-center bg-gray-100 border-b border-r border-gray-300">
                    Requisition</td>
                <td colspan="3" class="p-2 font-bold text-center bg-gray-100 border-b border-gray-300">Issuance</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="w-24 p-2 font-bold text-center border-r border-gray-300">Stock No.</td>
                <td class="w-16 p-2 font-bold text-center border-r border-gray-300">Unit</td>
                <td class="p-2 font-bold text-center border-r border-gray-300">Description</td>
                <td class="w-20 p-2 font-bold text-center border-r border-gray-300">Quantity</td>
                <td class="w-20 p-2 font-bold text-center border-r border-gray-300">Unit Value</td>
                <td class="p-2 font-bold text-center">Remarks</td>
            </tr>

            @forelse($risDetails as $detail)
                <tr class="border-b border-gray-300">
                    <td class="p-2 text-center border-r border-gray-300">{{ $detail->stockno }}</td>
                    <td class="p-2 text-center border-r border-gray-300">{{ $detail->unit }}</td>
                    <td class="p-2 border-r border-gray-300">{{ $detail->description }}</td>
                    <td class="p-2 text-center border-r border-gray-300">{{ number_format($detail->itmqty, 2) }}</td>
                    <td class="p-2 text-right border-r border-gray-300">
                        @if (count($detail->fundSources) > 0)
                            @foreach ($detail->fundSources as $fund)
                                {{ number_format($fund->unitprice, 2) }}
                            @endforeach
                        @endif
                    </td>
                    <td class="p-2">
                        @if (count($detail->fundSources) > 0)
                            @foreach ($detail->fundSources as $fund)
                                CENDU Trust Fund
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="border-b border-gray-300">
                    <td colspan="7" class="p-2 text-center">No items found</td>
                </tr>
            @endforelse

            <!-- Purpose row -->
            <tr class="border-b border-gray-300">
                <td class="p-2 font-bold border-r border-gray-300">Purpose:</td>
                <td colspan="6" class="p-2">{{ strtoupper($ris->purpose) }}</td>
            </tr>
        </table>
    </div>

    @if ($relatedIar)
        <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b bg-blue-50">
                <h2 class="text-xl font-semibold text-blue-700">Related IAR Information</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">IAR Number</p>
                        <p class="font-semibold">{{ $relatedIar->iarNo }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">IAR Date</p>
                        <p class="font-semibold">{{ $relatedIar->formatted_iardate }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Supplier</p>
                        <p class="font-semibold">{{ $relatedIar->supplier }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
