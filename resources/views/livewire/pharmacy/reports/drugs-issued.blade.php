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
                <i class="mr-1 las la-clone la-lg"></i> Drug Issuance
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <style>
        .issuance-filter-bar {
            display: grid;
            grid-template-columns: auto minmax(220px, 260px) minmax(220px, 260px) minmax(220px, 260px) minmax(170px, 220px) minmax(320px, 1fr);
            gap: 0.5rem;
            align-items: end;
        }

        .issuance-filter-actions {
            display: flex;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .issuance-filter-field .label {
            min-height: 0;
            padding: 0 0 0.125rem;
        }

        .issuance-filter-field .label-text {
            font-size: 0.75rem;
            line-height: 1rem;
        }

        @media (max-width: 1400px) {
            .issuance-filter-bar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .issuance-filter-actions,
            .issuance-filter-drug {
                grid-column: 1 / -1;
            }
        }
    </style>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-5 py-5 overflow-auto">
        <div class="my-2 issuance-filter-bar">
            <div class="issuance-filter-actions">
                <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info">
                    <i class="las la-lg la-file-excel"></i> Export
                </button>
                <button onclick="printMe()" class="btn btn-sm btn-primary">
                    <i class="las la-lg la-print"></i> Print
                </button>
            </div>

            <div class="form-control issuance-filter-field">
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

            <div class="form-control issuance-filter-field">
                <label class="py-0 label">
                    <span class="label-text">From</span>
                </label>
                <input type="datetime-local" class="w-full input input-sm input-bordered" max="{{ $date_to }}"
                    wire:model.lazy="date_from" />
            </div>

            <div class="form-control issuance-filter-field">
                <label class="py-0 label">
                    <span class="label-text">To</span>
                </label>
                <input type="datetime-local" class="w-full input input-sm input-bordered" min="{{ $date_from }}"
                    wire:model.lazy="date_to" />
            </div>

            <div class="form-control issuance-filter-field">
                <label class="py-0 label">
                    <span class="label-text">Fund Source</span>
                </label>
                <select class="w-full select select-bordered select-sm" wire:model="filter_charge">
                    <option value="%%,All">All</option>
                    @foreach ($charge_codes as $charge)
                        <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">
                            {{ $charge->chrgdesc }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control issuance-filter-field issuance-filter-drug">
                <label class="py-0 label">
                    <span class="label-text">Drug</span>
                </label>
                <select class="w-full select select-bordered select-sm" wire:model="selected_drug">
                    <option value="">All Drugs</option>
                    @foreach ($issued_drugs as $drug)
                        <option value="{{ $drug->dmdcomb }},{{ $drug->dmdctr }}">
                            {{ implode(',', explode('_,', $drug->drug_concat)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="print" class="w-full">
            <table class="w-full bg-white shadow-md table-sm" id="table">
                <thead class="font-bold bg-gray-200">
                    <tr class="text-center">
                        <td class="text-sm uppercase border">#</td>
                        <td class="text-sm border">Item Description</td>
                        <td class="text-sm border">QTY</td>
                        <td class="text-sm border">Date/Time</td>
                        <td class="text-sm border">Hosp #</td>
                        <td class="text-sm border">CS #</td>
                        <td class="text-sm border">Patient's Name</td>
                        <td class="text-sm border">Location</td>
                        <td class="text-sm border">Issued By</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drugs_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr classs="border border-black">
                            <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                            <td class="text-sm border">
                                <div class="text-xs">{{ $concat }}</div>
                            </td>
                            <td class="text-sm text-right border">{{ number_format($rxi->qty) }}</td>
                            <td class="text-sm border">{{ date('Y-m-d h:i A', strtotime($rxi->issuedte)) }}</td>
                            <td class="text-sm border">{{ $rxi->hpercode }}</td>
                            <td class="text-sm border">
                                <a rel="noopener noreferrer" class="font-semibold text-blue-600"
                                    href="{{ route('dispensing.rxo.chargeslip', $rxi->pcchrgcod) }}"
                                    target="_blank">{{ $rxi->pcchrgcod }}</a>
                            </td>
                            <td class="text-sm border">
                                {{ $rxi->patlast . ', ' . $rxi->patfirst . ' ' . $rxi->patmiddle }}</td>
                            <td class="text-sm border">
                                <div>{{ $rxi->wardname }} ({{ $rxi->rmname }})</div>
                            </td>
                            <td class="text-xs border">
                                @if ($rxi->lastname and $rxi->firstname)
                                    {{ $rxi->lastname . ', ' . $rxi->firstname . ' ' . $rxi->middlename }}
                                @else
                                    {{ $rxi->name }}
                                @endif
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
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
                XLSX.writeFile(wb, fn || ('Ward Consumption Report.' + (type || 'xlsx')));
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
