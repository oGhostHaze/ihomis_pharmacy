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
                    <select class="w-full text-sm select select-bordered select-sm" wire:model.defer="location_id">
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
                    <input type="text" class="w-full text-sm input input-bordered input-sm" id="selected_drug_search"
                        list="issued_drug_options" placeholder="Search drug" autocomplete="off"
                        value="{{ $selected_drug_label }}">
                    <input type="hidden" id="selected_drug" wire:model.defer="selected_drug">
                    <datalist id="issued_drug_options">
                        @foreach ($issued_drugs as $drug)
                            <option data-value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}"
                                value="{{ implode(',', explode('_,', $drug->drug_concat)) }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">Type of Encounter</span>
                    </label>
                    <select class="w-full text-sm select select-bordered select-sm" wire:model.defer="toecode">
                        <option value="">All</option>
                        @foreach ($toecode_options as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">From</span>
                    </label>
                    <input type="datetime-local" class="w-full input input-sm input-bordered" max="{{ $date_to }}"
                        wire:model.defer="date_from" />
                </div>

                <div class="form-control">
                    <label class="py-0 label">
                        <span class="label-text">To</span>
                    </label>
                    <input type="datetime-local" class="w-full input input-sm input-bordered" min="{{ $date_from }}"
                        wire:model.defer="date_to" />
                </div>

                <div class="form-control">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="applyFilters">
                        <i class="las la-lg la-filter"></i> Filter
                    </button>
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
                        <td class="text-sm text-center border">Type of Encounter</td>
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
                            <td class="text-sm text-center border">{{ $issued->encounter_type }}</td>
                            <td class="text-sm border">{{ $patientName }}</td>
                            <td class="text-sm border">{{ $doctorName }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-sm text-center border">
                                {{ $has_applied_drug_filter ? 'No issued prescriptions found.' : 'Select a drug and click Filter to generate the report.' }}
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
        function initializePrescriptionIssuanceDrugSearch() {
            const search = document.getElementById('selected_drug_search');
            const selectedDrug = document.getElementById('selected_drug');
            const options = document.getElementById('issued_drug_options');

            if (!search || !selectedDrug || !options) {
                return;
            }

            if (search.prescriptionIssuanceSyncSelectedDrug) {
                search.removeEventListener('input', search.prescriptionIssuanceSyncSelectedDrug);
                search.removeEventListener('change', search.prescriptionIssuanceSyncSelectedDrug);
            }

            search.prescriptionIssuanceSyncSelectedDrug = function() {
                const option = Array.from(options.options).find(function(item) {
                    return item.value === search.value;
                });

                selectedDrug.value = option ? option.dataset.value : '';
                selectedDrug.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            };

            search.addEventListener('input', search.prescriptionIssuanceSyncSelectedDrug);
            search.addEventListener('change', search.prescriptionIssuanceSyncSelectedDrug);
        }

        document.addEventListener('livewire:load', function() {
            initializePrescriptionIssuanceDrugSearch();

            Livewire.hook('message.processed', function() {
                initializePrescriptionIssuanceDrugSearch();
            });
        });

        document.addEventListener('DOMContentLoaded', initializePrescriptionIssuanceDrugSearch);
        document.addEventListener('turbolinks:load', initializePrescriptionIssuanceDrugSearch);

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
