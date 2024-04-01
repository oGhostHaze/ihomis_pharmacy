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
                <i class="mr-1 las la-undo la-lg"></i> Returned Log
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
                            <span>Location</span>
                            <select class="text-sm select select-bordered select-sm" wire:model="location_id">
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->description }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>From</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered"
                                max="{{ $date_to }}" wire:model.lazy="date_from" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>To</span>
                            <input type="datetime-local" class="w-full input input-sm input-bordered"
                                min="{{ $date_from }}" wire:model.lazy="date_to" />
                        </label>
                    </div>
                </div>
                <div class="ml-2">
                    <div class="form-control">
                        <label class="input-group">
                            <span>Fund Source</span>
                            <select class="select select-bordered select-sm" wire:model="filter_charge">
                                <option></option>
                                @foreach ($charge_codes as $charge)
                                    <option value="{{ $charge->chrgcode }},{{ $charge->chrgdesc }}">
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
                    <tr class="text-center">
                        <td class="text-sm uppercase border">#</td>
                        <td class="text-sm border">Item Description</td>
                        <td class="text-sm border">QTY</td>
                        <td class="text-sm border">Date/Time</td>
                        <td class="text-sm border">Hosp #</td>
                        <td class="text-sm border">CS #</td>
                        <td class="text-sm border">Patient's Name</td>
                        <td class="text-sm border">Location</td>
                        <td class="text-sm border">Received By</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drugs_returned as $rxr)
                        <tr classs="border border-black">
                            <td class="text-sm text-right border">{{ $loop->iteration }}</td>
                            <td class="text-sm border">
                                <div class="flex flex-col">
                                    <div class="text-sm font-bold">{{ $rxr->dm->generic->gendesc }}</div>
                                    <div class="ml-10 text-xs text-slate-800">
                                        {{ $rxr->dm->dmdnost }}{{ $rxr->dm->strength->stredesc ?? '' }}
                                        {{ $rxr->dm->form->formdesc ?? '' }}</div>
                                </div>
                            </td>
                            <td class="text-sm text-right border">{{ $rxr->qty }}</td>
                            <td class="text-sm border">{{ $rxr->return_date() }}</td>
                            <td class="text-sm border">{{ $rxr->hpercode }}</td>
                            <td class="text-sm border">
                                <a rel="noopener noreferrer" class="font-semibold text-blue-600"
                                    href="{{ route('dispensing.rxo.chargeslip', $rxr->pcchrgcod) }}"
                                    target="_blank">{{ $rxr->pcchrgcod }}</a>
                            </td>
                            <td class="text-sm border">{{ $rxr->patient->fullname() }}</td>
                            <td class="text-sm border">
                                @if ($rxr->adm_pat_room)
                                    <div class="flex-col">
                                        <div>{{ $rxr->adm_pat_room->ward->wardname }}</div>
                                        <div class="text-sm">{{ $rxr->adm_pat_room->room->rmname }}</div>
                                    </div>
                                @else
                                    {{ $rxr->encounter->enctr_type() }}
                                @endif
                            </td>
                            <td class="text-sm border">
                                {{ $rxr->issuer ? $rxr->receiver->fullname() : $rxr->user->name }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="font-bold text-center uppercase bg-red-400 border border-black">No
                                record found!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            {{ $drugs_returned->links() }}
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
