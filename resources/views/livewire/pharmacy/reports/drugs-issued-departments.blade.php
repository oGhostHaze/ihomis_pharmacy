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
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5 overflow-auto">
        <div class="flex justify-between my-2">
            <div class="flex justify-between">
            </div>
            <div class="flex justify-end">
                <div class="ml-2">
                    <button onclick="ExportToExcel('xlsx')" class="btn btn-sm btn-info"><i
                            class="las la-lg la-file-excel"></i> Export</button>
                </div>
                <div class="ml-2">
                    <button onclick="printMe()" class="btn btn-sm btn-primary"><i class="las la-lg la-print"></i>
                        Print</button>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Department</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="deptcode">
                                <option value="">All</option>
                                @foreach ($depts as $dept)
                                    <option value="{{ $dept->deptcode }}">{{ $dept->deptname }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>From</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="date" class="w-full input input-sm input-bordered"
                                wire:model.lazy="date_to" />
                        </label>
                    </div>
                </div>
                {{-- <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered"
                                min="{{ $date_from }}" wire:model.lazy="date_to" />
                        </label>
                    </div>
                </div> --}}
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Fund Source</span>
                            <select class="select select-bordered select-sm" wire:model="filter_charge">
                                <option></option>
                                @foreach ($charge_codes as $charge)
                                    <option value="{{ $charge->chrgcode }}">
                                        {{ $charge->chrgdesc }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div id="print" class="w-full">
            <table class="table w-full bg-white shadow-md table-sm" id="table">
                <thead class="font-bold bg-gray-200">
                    <tr>
                        <td class="text-sm text-center uppercase border">#</td>
                        <td class="text-sm border">Department</td>
                        <td class="text-sm border">Item Description</td>
                        <td class="text-sm text-right border">TOTAL</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drugs_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr class="border border-black">
                            <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                            <td class="text-sm border">{{ $rxi->deptname }}</td>
                            <td class="text-sm border">
                                <div class="flex flex-col">
                                    <div class="text-xs text-slate-500">
                                        {{ $rxi->chrgdesc }}
                                    </div>
                                    <div class="text-sm font-bold">{{ $concat }}</div>
                                </div>
                            </td>
                            <td class="text-sm text-right border">{{ number_format($rxi->qty) }}</td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Put this part before </body> tag -->
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
