<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> IO Transactions
            </li>
            <li>
                {{ $reference_no }}
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto mt-5">
    <div class="p-4 mb-3 bg-white rounded-lg">
        <div class="flex justify-end space-x-3">
            {{-- @if ($trans[0]->loc_code == session('pharm_location_id'))
                <button class="btn btn-sm btn-primary" onclick="add_request()" wire:loading.attr="disabled">Add
                    Item</button>
            @endif --}}
            <button class="btn btn-sm" onclick="printMe()" wire:loading.attr="disabled">Print</button>
        </div>
    </div>
    @if ($errors->first())
        <div class="mb-3 shadow-lg alert alert-error">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-6 h-6 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif
    <div class="flex flex-col p-5 bg-white rounded-lg" id="print">
        <div class="flex justify-between w-full pb-2 border-b">
            <div class="flex flex-col w-1/2">
                <div class="flex">
                    <div class="w-36">Reference No:</div>
                    <div class="font-bold uppercase w-96">{{ $reference_no }}</div>
                </div>
            </div>
        </div>
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th class="w-1/12">Date Issued</th>
                    <th class="w-1/12">TO</th>
                    <th class="w-6/12">Item</th>
                    <th class="w-1/12">Issued QTY</th>
                    <th class="w-1/12">Fund Source</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trans as $tran)
                    <tr class="hover" wire:key="select-txt-{{ $loop->iteration . $tran->id }}">
                        <td class="text-xs cursor-pointer"
                            wire:click="view_trans_date('{{ date('Y-m-d', strtotime($tran->created_at)) }}')">
                            <span class="text-blue-500"><i class="las la-lg la-eye"></i>
                                {{ $tran->created_at() }}</span>
                        </td>
                        <td class="text-xs">{{ $tran->ward->ward_name }}</td>
                        <td class="text-xs cursor-pointer">
                            <span class="text-blue-500">
                                <i class="las la-lg la-hand-pointer"></i>
                                {{ $tran->drug->drug_concat() }}
                            </span>
                        </td>
                        <td class="text-xs">{{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}</td>
                        <td class="text-xs">
                            {{ $tran->charge->chrgdesc }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
    </div>
</div>

@push('scripts')
    <script>
        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
