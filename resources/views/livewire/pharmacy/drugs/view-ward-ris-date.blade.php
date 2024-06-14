<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-exchange la-lg"></i> Ward RIS
            </li>
            <li>
                {{ $date }}
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col p-5 mx-auto mt-5">
    <div class="p-4 mb-3 bg-white rounded-lg">
        <div class="flex justify-end space-x-3">
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
                    <div class="w-36">Date:</div>
                    <div class="font-bold uppercase w-96">{{ $date }}</div>
                </div>
            </div>
        </div>
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th class="w-1/12">Reference</th>
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
                        <th class="text-xs cursor-pointer" wire:click="view_trans('{{ $tran->trans_no }}')">
                            <span class="text-blue-500"><i class="las la-lg la-eye"></i> {{ $tran->trans_no }}</span>
                        </th>
                        <td class="text-xs">{{ $tran->created_at }}</td>
                        <td class="text-xs">{{ $tran->ward->ward_name }}</td>
                        <td class="text-xs cursor-pointer"
                            @if ($tran->issued_qty > 0) onclick="cancel_issue({{ $tran->id }})" @endif>
                            <span class="text-blue-500">
                                <i class="las la-lg la-hand-pointer"></i>
                                {{ $tran->drug->drug_concat() }}
                            </span>
                        </td>
                        <td class="text-xs">
                            @if ($tran->return_qty > 0)
                                <span class="text-error">{{ number_format($tran->return_qty) }} (returned)</span>
                            @else
                                {{ number_format($tran->issued_qty < 1 ? '0' : $tran->issued_qty) }}
                            @endif
                        </td>
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
        function cancel_issue(trans_id) {
            Swal.fire({
                title: 'Are you sure you want to cancel this transaction?',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                confirmButtonColor: 'red',
                html: `
                    <i data-feather="x-circle" class="w-16 h-16 mx-auto mt-3 text-danger"></i>
                    <div class="mt-2 text-slate-500" id="inf">All items issued that have not been received will return to warehouse. <br>This process cannot be undone. Continue?</div>
                `,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('cancel_issue', trans_id)
                }
            })
        }

        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
