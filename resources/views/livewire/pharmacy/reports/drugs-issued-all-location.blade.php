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
                <i class="mr-1 las la-clone la-lg"></i> Consolidated Drug Issuances
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
@endpush

<div class="max-w-screen">
    <div class="flex flex-col px-5 py-5 overflow-auto">
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
                <form action="{{ route('reports.issuance.consol.loc') }}" method="GET" class="flex">
                    <div class="ml-2">
                        <div class="form-control">
                            <label class="input-group">
                                <span>From</span>
                                <input type="datetime-local" class="w-full input input-sm input-bordered"
                                    max="{{ $date_to }}" wire:model.defer="date_from" name="from" />
                            </label>
                        </div>
                    </div>
                    <div class="ml-2">
                        <div class="form-control">
                            <label class="input-group">
                                <span>To</span>
                                <input type="datetime-local" class="w-full input input-sm input-bordered"
                                    min="{{ $date_from }}" wire:model.defer="date_to" name="to" />
                            </label>
                        </div>
                    </div>
                    <div class="ml-2">
                        <button class="btn btn-sm btn-info" type="submit"><i class="las la-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div id="print" class="w-full bg-white">
            <table id="table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>QTY</th>
                        <th>Encounter Type</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @foreach ($adm_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr>
                            <td class="text-xs">{{ $concat }}</td>
                            <td>{{ number_format($rxi->total_issue) }}</td>
                            <td>{{ $rxi->encounter }}</td>
                            <td> {{ $rxi->tsdesc }} </td>
                        </tr>
                    @endforeach
                    @foreach ($opd_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr>
                            <td class="text-xs">{{ $concat }}</td>
                            <td>{{ number_format($rxi->total_issue) }}</td>
                            <td>{{ $rxi->encounter }}</td>
                            <td> {{ $rxi->tsdesc }} </td>
                        </tr>
                    @endforeach
                    @foreach ($er_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr>
                            <td class="text-xs">{{ $concat }}</td>
                            <td>{{ number_format($rxi->total_issue) }}</td>
                            <td>{{ $rxi->encounter }}</td>
                            <td> {{ $rxi->tsdesc }} </td>
                        </tr>
                    @endforeach
                    @foreach ($walkn_issued as $rxi)
                        @php
                            $concat = implode(',', explode('_,', $rxi->drug_concat));
                        @endphp
                        <tr>
                            <td class="text-xs">{{ $concat }}</td>
                            <td>{{ number_format($rxi->total_issue) }}</td>
                            <td>{{ $rxi->encounter }}</td>
                            <td> {{ $rxi->tsdesc ?? 'N/A' }} </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Item Description</th>
                        <th></th>
                        <th>Encounter Type</th>
                        <th>Department</th>
                    </tr>
                </tfoot>
            </table>
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

        new DataTable('#table', {
            initComplete: function() {
                this.api()
                    .columns()
                    .every(function() {
                        let column = this;
                        if (column[0] != 1) {
                            // Create select element
                            let select = document.createElement('select');
                            select.className = "select select-bordered select-sm"
                            select.add(new Option('All', ''));
                            column.footer().replaceChildren(select);

                            // Apply listener for user change in value
                            select.addEventListener('change', function() {
                                column
                                    .search(select.value, {
                                        exact: true
                                    })
                                    .draw();
                            });

                            // Add list of options
                            column
                                .data()
                                .unique()
                                .sort()
                                .each(function(d, j) {
                                    select.add(new Option(d));
                                });
                        }
                    });
            },
            paging: false,
            scrollY: 600,
            dom: 'lrtip',
        });

        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
