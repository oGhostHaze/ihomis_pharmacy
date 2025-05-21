<div>
    <!-- Loading indicator -->
    @if ($loading)
        <div class="flex items-center justify-center p-12">
            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="mt-4 text-gray-700">Loading RIS data...</span>
            </div>
        </div>
    @else
        <!-- Error Message -->
        @if (session()->has('error'))
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header with actions -->
        <div class="p-4 bg-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">RIS # {{ $ris->risno ?? ($risNo ?? 'N/A') }}</h1>
                    <p class="text-gray-600">{{ $ris->formatted_risdate ?? ($risDate ?? 'N/A') }}</p>
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
                    @if ($ris || $dataLoaded)
                        <a href="{{ route('ris.print', $risId) }}" target="_blank" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </a>
                    @endif
                </div>
            </div>

            <!-- Add this after the Drug Association Status section -->
            @if (isset($associationStatus) && $associationStatus['allAssociated'])
                @if (isset($ris->transferred_to_pdims) && $ris->transferred_to_pdims)
                    <div class="p-3 mt-4 border border-green-200 rounded-lg bg-green-50">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-green-500"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="font-medium text-green-800">
                                This RIS was transferred to Pharmacy Delivery System
                            </span>
                            <a href="{{ route('delivery.view', $ris->transferred_to_pdims) }}"
                                class="ml-3 text-green-700 underline hover:text-green-900">
                                View Delivery
                            </a>
                        </div>
                        <p class="mt-1 text-xs text-green-600">
                            Transferred on:
                            {{ $ris->transferred_at ? date('M d, Y h:i A', strtotime($ris->transferred_at)) : 'N/A' }}
                        </p>
                    </div>
                @else
                    <div class="flex justify-end mt-4">
                        <button wire:click="openTransferModal" class="flex items-center btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m-4 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Transfer to Pharmacy Delivery
                        </button>
                    </div>
                @endif
            @endif
        </div>

        <!-- Flash Message -->
        @if (session()->has('message'))
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg">
                {{ session('message') }}
            </div>
        @endif

        @if ($ris || $dataLoaded)
            <!-- RIS Form styled like the sample image -->
            <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md sm:p-6 lg:p-8" wire:key="ris-form-data-table"
                id="ris-form-data-table">
                <table class="w-full border border-gray-300">
                    <tr class="border-t border-gray-300">
                        <td colspan="5" class="p-2 pl-4">
                            <table class="w-full">
                                <tr>
                                    <td class="w-20 font-bold">Division:</td>
                                    <td class="border-b border-gray-400">Medical Service</td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="5" class="p-2 pr-4">
                            <table class="w-full">
                                <tr>
                                    <td class="font-bold whitespace-nowrap">Responsibility Center Code:</td>
                                    <td class="pl-2 border-b border-gray-400">{{ $ris->rcc ?? ($rcc ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="p-2 pl-4">
                            <table class="w-full">
                                <tr>
                                    <td class="w-20 font-bold">Office:</td>
                                    <td class="border-b border-gray-400">
                                        {{ $ris->officeName ?? ($officeName ?? 'N/A') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="6" class="p-2 pr-4">
                            <table class="w-full">
                                <tr>
                                    <td class="w-20 font-bold">RIS No:</td>
                                    <td class="border-b border-gray-400">{{ $ris->risno ?? ($risNo ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="p-2 pl-4">
                            &nbsp;
                        </td>
                        <td colspan="5" class="p-2 pr-4">
                            <table class="w-full">
                                <tr>
                                    <td class="w-20 font-bold">Date:</td>
                                    <td class="border-b border-gray-400">
                                        {{ $ris->formatted_risdate ?? ($risDate ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="border-t border-gray-300">
                        <td colspan="4"
                            class="p-2 font-bold text-center bg-gray-100 border-b border-r border-gray-300">
                            Requisition</td>
                        <td colspan="6" class="p-2 font-bold text-center bg-gray-100 border-b border-gray-300">
                            Issuance</td>
                    </tr>
                    <tr class="border-b border-gray-300">
                        <td class="w-24 p-2 font-bold text-center border-r border-gray-300">Stock No.</td>
                        <td class="w-16 p-2 font-bold text-center border-r border-gray-300">Unit</td>
                        <td class="p-2 font-bold text-center border-r border-gray-300">Description</td>
                        <td class="w-20 p-2 font-bold text-center border-r border-gray-300">Quantity</td>
                        <td class="w-24 p-2 font-bold text-center border-r border-gray-300">Batch No.</td>
                        <td class="w-24 p-2 font-bold text-center border-r border-gray-300">Expiry Date</td>
                        <td class="w-20 p-2 font-bold text-center border-r border-gray-300">Unit Price</td>
                        <td class="w-24 p-2 font-bold text-center border-r border-gray-300">Total Amount</td>
                        <td class="p-2 font-bold text-center border-r border-gray-300">Fund Source</td>
                        <td class="w-40 p-2 font-bold text-center">Drug Association</td>
                    </tr>

                    @forelse($risDetails ?? [] as $detail)
                        <tr class="border-b border-gray-300">
                            <td class="p-2 text-center border-r border-gray-300">
                                {{ number_format($detail->stockno) ?? 'N/A' }}</td>
                            <td class="p-2 text-center border-r border-gray-300">{{ $detail->unit ?? 'N/A' }}</td>
                            <td class="p-2 border-r border-gray-300">{{ $detail->description ?? 'N/A' }}</td>
                            <td class="p-2 text-center border-r border-gray-300">
                                {{ isset($detail->itmqty) ? number_format($detail->itmqty, 2) : 'N/A' }}</td>

                            <!-- New columns for batch number and expiry date -->
                            <td class="p-2 text-center border-r border-gray-300">{{ $detail->batch_no ?? 'N/A' }}</td>
                            <td class="p-2 text-center border-r border-gray-300">
                                <span
                                    class="whitespace-nowrap">{{ $detail->sql_formatted_expire_date ?? 'N/A' }}</span>
                            </td>

                            <td class="p-2 text-right border-r border-gray-300">
                                @if (isset($detail->fundSources) && count($detail->fundSources) > 0)
                                    @foreach ($detail->fundSources as $fund)
                                        <div class="text-right">
                                            <span class="font-medium">₱{{ number_format($fund->unitprice, 2) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-2 text-right border-r border-gray-300">
                                @if (isset($detail->fundSources) && count($detail->fundSources) > 0)
                                    @foreach ($detail->fundSources as $fund)
                                        <div class="text-right">
                                            @php
                                                $totalAmount = $detail->itmqty * $fund->unitprice;
                                            @endphp
                                            <span class="font-medium">₱{{ number_format($totalAmount, 2) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-2 border-r border-gray-300">
                                @if (isset($detail->fundSources) && count($detail->fundSources) > 0)
                                    @foreach ($detail->fundSources as $fund)
                                        <div>
                                            <span class="font-medium">{{ $fund->fsname ?? 'Unknown Fund' }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-2 text-center">
                                @if (isset($detail->pdims_itemcode) && $detail->pdims_itemcode)
                                    <div class="flex flex-col items-center">
                                        <div class="mb-1 text-sm">
                                            <span class="font-semibold">Code:</span> {{ $detail->pdims_itemcode }}
                                        </div>
                                        <div class="mb-2 text-sm">
                                            <span class="font-semibold">Drug:</span> {{ $detail->pdims_drugdesc }}
                                        </div>
                                        <button wire:click="removeDrugAssociation({{ $detail->itemID }})"
                                            class="px-2 py-1 text-xs text-red-600 border border-red-300 rounded hover:bg-red-50">
                                            Remove Association
                                        </button>
                                    </div>
                                @else
                                    <button wire:click="openDrugModal({{ $detail->itemID }})"
                                        class="px-3 py-1 text-sm text-blue-600 border border-blue-300 rounded hover:bg-blue-50">
                                        Link to Drug
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-b border-gray-300">
                            <td colspan="10" class="p-2 text-center">No items found</td>
                        </tr>
                    @endforelse

                    <!-- Purpose row -->
                    <tr class="border-b border-gray-300">
                        <td class="p-2 font-bold border-r border-gray-300">Purpose:</td>
                        <td colspan="9" class="p-2">{{ $ris->purpose ?? ($purpose ?? 'N/A') }}</td>
                    </tr>

                    <!-- Grand Total row -->
                    <tr class="border-b border-gray-300 bg-gray-50">
                        <td colspan="7" class="p-2 font-bold text-right border-r border-gray-300">Grand Total:</td>
                        <td class="p-2 font-bold text-right border-r border-gray-300">
                            @php
                                $grandTotal = 0;
                                if (isset($risDetails) && count($risDetails) > 0) {
                                    foreach ($risDetails as $detail) {
                                        if (isset($detail->fundSources) && count($detail->fundSources) > 0) {
                                            foreach ($detail->fundSources as $fund) {
                                                $grandTotal += $detail->itmqty * $fund->unitprice;
                                            }
                                        }
                                    }
                                }
                            @endphp
                            ₱{{ number_format($grandTotal, 2) }}
                        </td>
                        <td colspan="2" class="p-2"></td>
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
                                <p class="font-semibold">{{ $relatedIar->iarNo ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">IAR Date</p>
                                <p class="font-semibold">{{ $relatedIar->formatted_iardate ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Supplier</p>
                                <p class="font-semibold">{{ $relatedIar->supplier ?? 'N/A' }}</p>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('iar.show', $relatedIar->iarID) }}" target="_blank"
                                    class="btn btn-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View IAR Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- No RIS Data Found -->
            <div class="p-8 text-center bg-white rounded-lg shadow">
                <div class="mb-4 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mb-1 text-xl font-semibold text-gray-700">No RIS Data Found</h3>
                <p class="text-gray-500">The requested RIS information could not be retrieved.</p>
                <div class="mt-6">
                    <a href="{{ route('ris.index') }}"
                        class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">
                        Return to RIS List
                    </a>
                </div>
            </div>
        @endif
    @endif

    <!-- Drug Search Modal -->
    <div x-data="{ show: @entangle('isModalOpen') }" x-show="show" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Associate Drug with Item
                        </h3>

                        @if ($selectedItemId)
                            @php
                                $selectedItem = collect($risDetails ?? [])->firstWhere('itemID', $selectedItemId);
                            @endphp
                            @if ($selectedItem)
                                <div class="p-3 mt-2 text-sm rounded-md bg-blue-50">
                                    <p class="font-medium text-blue-800">Selected Item:</p>
                                    <p class="mt-1">{{ $selectedItem->description }}</p>

                                    @if ($selectedItem->pdims_itemcode)
                                        <div class="pt-2 mt-2 border-t border-blue-200">
                                            <p class="font-medium text-blue-800">Current Association:</p>
                                            <p class="mt-1">{{ $selectedItem->pdims_drugdesc }}</p>
                                            <p class="mt-1 text-xs text-blue-600">
                                                ({{ $selectedItem->pdims_itemcode }})</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        <div class="mt-4">
                            <div class="mb-4">
                                <label for="search" class="block text-sm font-medium text-gray-700">Search for a
                                    drug</label>
                                <div class="relative mt-1">
                                    <input type="text" wire:model.debounce.300ms="drugSearchTerm"
                                        wire:keyup="searchDrugs"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="Type at least 2 characters...">
                                </div>
                            </div>

                            @if (count($searchResults) > 0)
                                <div class="mt-2 overflow-y-auto max-h-60 drug-search-results">
                                    <ul class="divide-y divide-gray-200">
                                        @foreach ($searchResults as $drug)
                                            <li class="py-2 cursor-pointer hover:bg-gray-50"
                                                wire:click="associateDrug('{{ $drug['id'] }}')">
                                                <div class="flex flex-col">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {!! isset($drug['highlighted_name']) ? $drug['highlighted_name'] : $drug['name'] !!}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ $drug['id'] }}</div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="show = false"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer to Delivery Modal -->
    <div x-data="{ show: @entangle('isTransferModalOpen').defer }" x-show="show" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                <div class="mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        Transfer RIS to Pharmacy Delivery
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Please provide delivery information to complete the transfer.
                    </p>
                </div>

                <div class="p-3 mb-4 rounded-md bg-blue-50">
                    <p class="text-sm text-blue-800">
                        <span class="font-medium">RIS #:</span> {{ $ris->risno ?? ($risNo ?? 'N/A') }}
                    </p>
                    <p class="mt-1 text-sm text-blue-800">
                        <span class="font-medium">Date:</span> {{ $ris->formatted_risdate ?? ($risDate ?? 'N/A') }}
                    </p>
                    <p class="mt-1 text-sm text-blue-800">
                        <span class="font-medium">Items to transfer:</span> {{ $associationStatus['total'] ?? 0 }}
                    </p>
                    <p class="mt-1 text-sm text-blue-800">
                        <span class="font-medium">Transfer as PO #:</span> {{ $ris->risno ?? ($risNo ?? 'N/A') }}
                    </p>
                </div>

                <form wire:submit.prevent="transferToDelivery">
                    <div class="space-y-4">
                        <!-- Supplier Selection -->
                        <div>
                            <label for="supplier" class="block text-sm font-medium text-gray-700">Supplier</label>
                            <div class="mt-1">
                                <select wire:model="deliveryData.suppcode" id="supplier" required
                                    class="w-full select select-bordered">
                                    <option value="">Select a supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->suppcode }}">{{ $supplier->suppname }}</option>
                                    @endforeach
                                </select>
                                @error('deliveryData.suppcode')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Delivery Type -->
                        <div>
                            <label for="delivery_type" class="block text-sm font-medium text-gray-700">Delivery
                                Type</label>
                            <div class="mt-1">
                                <select wire:model="deliveryData.delivery_type" id="delivery_type" required
                                    class="w-full select select-bordered">
                                    <option value="">Select delivery type</option>
                                    <option value="RIS">RIS Transfer</option>
                                    <option value="REGULAR">Regular Delivery</option>
                                    <option value="EMERGENCY">Emergency</option>
                                </select>
                                @error('deliveryData.delivery_type')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Charge Code -->
                        <div>
                            <label for="charge_code" class="block text-sm font-medium text-gray-700">Fund
                                Source</label>
                            <div class="mt-1">
                                <select wire:model="deliveryData.charge_code" id="charge_code" required
                                    class="w-full select select-bordered">
                                    <option value="">Select fund source</option>
                                    @foreach ($chargeCodes as $chargeCode)
                                        <option value="{{ $chargeCode->chrgcode }}">{{ $chargeCode->chrgdesc }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('deliveryData.charge_code')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Pharmacy Location -->
                        <div>
                            <label for="pharm_location_id" class="block text-sm font-medium text-gray-700">Pharmacy
                                Location</label>
                            <div class="mt-1">
                                <select wire:model="deliveryData.pharm_location_id" id="pharm_location_id" required
                                    class="w-full select select-bordered">
                                    <option value="">Select location</option>
                                    @foreach ($pharmacyLocations as $location)
                                        <option value="{{ $location->id }}">{{ $location->description }}</option>
                                    @endforeach
                                </select>
                                @error('deliveryData.pharm_location_id')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Delivery Date -->
                        <div>
                            <label for="delivery_date" class="block text-sm font-medium text-gray-700">Delivery
                                Date</label>
                            <div class="mt-1">
                                <input type="date" wire:model="deliveryData.delivery_date" id="delivery_date"
                                    required class="w-full input input-bordered">
                                @error('deliveryData.delivery_date')
                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- SI Number -->

                        <div>
                            <label for="si_no" class="block text-sm font-medium text-gray-700">SI Number</label>
                            <div class="mt-1">
                                @if ($relatedIar && $relatedIar->invoiceNo)
                                    <input type="text" value="{{ $relatedIar->invoiceNo }}"
                                        class="w-full input input-bordered" readonly disabled>
                                    <p class="mt-1 text-xs text-blue-600">
                                        Using invoice number from related IAR
                                    </p>
                                @else
                                    <input type="text" wire:model="deliveryData.si_no" id="si_no"
                                        class="w-full input input-bordered"
                                        placeholder="Enter SI number if available">
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit"
                            class="inline-flex justify-center w-full btn btn-primary sm:col-start-2">
                            Transfer to Delivery
                        </button>
                        <button type="button" @click="show = false"
                            class="inline-flex justify-center w-full mt-3 btn btn-outline sm:mt-0 sm:col-start-1">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard navigation to the drug search results
            Livewire.on('drugsSearched', () => {
                const resultsList = document.querySelector('.drug-search-results');
                if (!resultsList) return;

                const resultItems = resultsList.querySelectorAll('li');
                let selectedIndex = -1;

                // Reset selected index when new search results come in
                selectedIndex = -1;

                // Remove previous event listeners
                document.removeEventListener('keydown', handleKeyDown);

                // Add keydown event listener
                document.addEventListener('keydown', handleKeyDown);

                function handleKeyDown(e) {
                    // Only handle keys if the modal is open
                    const modal = document.querySelector('[x-data="{ show: true }"]');
                    if (!modal || getComputedStyle(modal).display === 'none') return;

                    switch (e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            if (selectedIndex < resultItems.length - 1) {
                                selectedIndex++;
                                highlightItem();
                            }
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            if (selectedIndex > 0) {
                                selectedIndex--;
                                highlightItem();
                            }
                            break;
                        case 'Enter':
                            e.preventDefault();
                            if (selectedIndex >= 0 && selectedIndex < resultItems.length) {
                                resultItems[selectedIndex].click();
                            }
                            break;
                        case 'Escape':
                            e.preventDefault();
                            // Close the modal (this will trigger Alpine.js to close it)
                            const closeButton = document.querySelector('[x-data="{ show: true }"] button');
                            if (closeButton) closeButton.click();
                            break;
                    }
                }

                function highlightItem() {
                    // Remove highlight from all items
                    resultItems.forEach(item => {
                        item.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
                    });

                    // Add highlight to selected item
                    if (selectedIndex >= 0) {
                        resultItems[selectedIndex].classList.add('bg-blue-50', 'border-l-4',
                            'border-blue-500');
                        // Ensure the selected item is visible in the scroll view
                        resultItems[selectedIndex].scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                }
            });

            // Handle data reloading when state is lost
            window.addEventListener('livewire:load', function() {
                // Listen for Livewire component missing data
                Livewire.hook('message.processed', (message, component) => {
                    // Check if we need to force a refresh
                    if (component.fingerprint.name === 'show-ris' &&
                        component.serverMemo.data.dataLoaded === true &&
                        !component.serverMemo.data.loading) {

                        // If we detect state issues, trigger hydration
                        if (!document.querySelector('.drug-search-results')) {
                            // State seems to be corrupted, refresh the component
                            setTimeout(() => {
                                Livewire.find(component.id).call('loadRis');
                            }, 100);
                        }
                    }
                });
            });

            // Handle any errors that might occur during Livewire operations
            Livewire.on('error', message => {
                console.error('Livewire Error:', message);

                // You could show a toast notification here
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error: ' + message);
                }
            });
        });
    </script>
@endpush
