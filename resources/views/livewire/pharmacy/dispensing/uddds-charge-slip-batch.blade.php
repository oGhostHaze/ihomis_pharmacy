<div class="container max-w-xl mx-auto mt-5">
    <div class="flex justify-end mb-3 no-print">
        <button class="btn btn-sm btn-primary" onclick="printMe()">Print all</button>
    </div>
    <div id="print" class="bg-white">
        @forelse ($slips as $slip)
            @php
                $total_issued = 0;
                $total_amt = 0;
                $pcchrgcod = $slip['pcchrgcod'];
                $rxo = $slip['rxo'];
                $rxo_header = $slip['rxo_header'];
                $prescription = $slip['prescription'];
                $toecode = $slip['toecode'];
                $encounter_suffix = $slip['encounter_suffix'];
                $wardname = $slip['wardname'];
                $room_name = $slip['room_name'];
            @endphp
            <div class="p-2 uddds-slip" style="page-break-after: always;">
                <div class="flex flex-col text-xs/4">
                    <h5 class="mb-0 text-2xl text-left"><strong class="uppercase">*{{ $pcchrgcod }}*</strong></h5>
                    <div class="flex flex-col text-center whitespace-nowrap">
                        <div>MMMHMC-A-PHB-QP-005 Form 1 Rev 0 Charge Slip</div>
                        <div>MARIANO MARCOS MEM HOSP. MED CTR</div>
                        <div>CHARGE SLIP / TRANSACTION SLIP</div>
                        <div class="font-bold">{{ $pcchrgcod }}</div>
                    </div>
                    <div class="flex flex-col text-left whitespace-nowrap">
                        <div>Dep't./Section: <span class="font-semibold">Pharmacy</span></div>
                        <div>Date/Time: <span
                                class="font-semibold">{{ date('F j, Y h:i A', strtotime($rxo_header->dodate)) }}</span>
                        </div>
                        <div>Patient's Name: <span class="font-semibold">{{ $rxo_header->patient ? $rxo_header->patient->fullname() : '' }}</span></div>
                        <div>Hosp Number: <span class="font-semibold">{{ $rxo_header->patient ? $rxo_header->patient->hpercode : '' }}</span></div>
                        <div>Ward:
                            <span class="font-semibold">{{ $wardname ? $wardname->wardname : '' }}</span>
                            <span class="font-semibold">{{ $room_name ? $room_name->rmname : '' }}
                                / {{ $toecode }}{{ $encounter_suffix ? ' / ' . $encounter_suffix : '' }}</span>
                        </div>
                        <div>Ordering Physician: <span
                                class="font-semibold">{{ $rxo_header->prescription_data && $rxo_header->prescription_data->employee ? 'Dr. ' . $rxo_header->prescription_data->employee->fullname() : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <table class="w-full text-xs/4">
                    <thead class="border border-black">
                        <tr class="border-b-2 border-b-black">
                            <th class="text-left">ITEM</th>
                            <th class="w-20 text-right">QTY</th>
                            <th class="w-20 text-right">UNIT COST</th>
                            <th class="w-20 text-right">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rxo as $item)
                            @php
                                $amount = $item->pcchrgamt;
                                $total_amt += $amount;
                                $concat = $item->dm ? implode(',', explode('_,', $item->dm->drug_concat)) : '';
                            @endphp
                            <tr class="border-t border-black border-x">
                                <td class="!text-2xs font-semibold text-wrap" colspan="4">{{ $concat }}</td>
                            </tr>
                            <tr class="border-b border-black border-x">
                                <td class="text-right" colspan="2">
                                    {{ number_format($item->qtyissued ?? $item->pchrgqty, 0) }}</td>
                                <td class="text-right">{{ number_format($item->pchrgup, 2) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                            @php $total_issued++; @endphp
                        @empty
                            <tr class="border-b border-black border-x">
                                <td colspan="4" class="text-center">No issued items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr align="right" class="font-bold border border-t-2 border-black">
                            <td colspan="2">{{ number_format($total_issued) }} ITEMS</td>
                            <td colspan="2">TOTAL {{ number_format($total_amt, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @empty
            <div class="p-8 text-center">No charge slips to print.</div>
        @endforelse
    </div>
</div>

@push('scripts')
    <script>
        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            document.body.innerHTML = printContents;
            window.print();
        }
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
@endpush
