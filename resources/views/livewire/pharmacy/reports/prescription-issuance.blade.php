<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-file-excel la-lg"></i> Report
            </li>
            <li>
                <i class="mr-1 las la-file-prescription la-lg"></i> Prescription Issuance
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-5 py-5 overflow-auto">
        <div class="flex flex-wrap items-end justify-between gap-2 my-2">
            <div class="flex gap-2">
                <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info">
                    <i class="las la-lg la-file-excel"></i> Export
                </button>
                <button onclick="printMe()" class="btn btn-sm btn-primary">
                    <i class="las la-lg la-print"></i> Print
                </button>
            </div>

            <div class="flex flex-wrap items-end justify-end gap-2">
                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">Location</span>
                    </label>
                    <select class="w-full text-sm select select-bordered select-sm" wire:model="location_id">
                        <option value="">All</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">Drug</span>
                    </label>
                    <select class="w-full text-sm select select-bordered select-sm" wire:model="selected_drug">
                        <option value="">Select Drug</option>
                        @foreach ($issued_drugs as $drug)
                            <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">
                                {{ implode(',', explode('_,', $drug->drug_concat)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">From</span>
                    </label>
                    <input type="datetime-local" class="w-full input input-sm input-bordered" max="{{ $date_to }}"
                        wire:model.lazy="date_from" />
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">To</span>
                    </label>
                    <input type="datetime-local" class="w-full input input-sm input-bordered" min="{{ $date_from }}"
                        wire:model.lazy="date_to" />
                </div>
            </div>
        </div>

        <div id="print" class="w-full">
            <table class="w-full bg-white shadow-md table-sm" id="table">
                <thead class="font-bold bg-gray-200">
                    <tr class="text-center">
                        <td class="text-sm uppercase border">#</td>
                        <td class="text-sm border">Date Issued</td>
                        <td class="text-sm text-right border">Qty Issued</td>
                        <td class="text-sm border">Toecode</td>
                        <td class="text-sm border">Patient Name</td>
                        <td class="text-sm border">Prescribing Doctor</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issued_prescriptions as $issued)
                        @php
                            $patientName = trim(
                                $issued->patlast .
                                    ', ' .
                                    trim($issued->patsuffix . ' ' . $issued->patfirst . ' ' . $issued->patmiddle),
                            );

                            $doctorName = trim(
                                $issued->doctor_lastname .
                                    ', ' .
                                    trim($issued->doctor_firstname . ' ' . $issued->doctor_middlename),
                            );
                            $doctorName = $issued->doctor_lastname ? $doctorName : '';
                        @endphp
                        <tr class="border border-black">
                            <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                            <td class="text-sm border">{{ date('Y-m-d h:i A', strtotime($issued->issuedte)) }}</td>
                            <td class="text-sm text-right border">{{ number_format($issued->qty) }}</td>
                            <td class="text-sm border">{{ $issued->toecode }}</td>
                            <td class="text-sm border">{{ $patientName }}</td>
                            <td class="text-sm border">{{ $doctorName }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-sm text-center border">
                                {{ $selected_drug ? 'No issued prescriptions found.' : 'Select a drug to generate the report.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <input type="checkbox" id="my-modal" class="modal-toggle" wire:loading.attr="checked" />
    <div class="modal">
        <div class="modal-box">
            <div>
                <span>
                    <i class="las la-spinner la-lg animate-spin"></i>
                    Processing...
                </span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('table');
            var wb = XLSX.utils.table_to_book(elt, {
                sheet: "sheet1"
            });
            return dl ?
                XLSX.write(wb, {
                    bookType: type,
                    bookSST: true,
                    type: 'base64'
                }) :
                XLSX.writeFile(wb, fn || ('Prescription Issuance Report.' + (type || 'xlsx')));
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
