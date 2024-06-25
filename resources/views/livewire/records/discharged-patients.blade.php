<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-user-alt la-lg"></i> Discharged Patients
            </li>
        </ul>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-3">
    <form action="" method="GET" class="flex">
        <div class="ml-2">
            <div class="form-control">
                <label class="input-group">
                    <span>From</span>
                    <input type="datetime-local" class="w-full input input-sm input-bordered"
                        wire:model.defer="date_from" name="from" />
                </label>
            </div>
        </div>
        <div class="ml-2">
            <div class="form-control">
                <label class="input-group">
                    <span>To</span>
                    <input type="datetime-local" class="w-full input input-sm input-bordered" wire:model.defer="date_to"
                        name="to" />
                </label>
            </div>
        </div>
        <div class="ml-2">
            <button class="btn btn-sm btn-info" type="submit"><i class="las la-search"></i></button>
        </div>
    </form>
    <table class="table w-full mb-3 table-compact" id="table">
        <thead>
            <tr>
                <th class="w-2/12">Date of Admission</th>
                <th class="w-1/12">Hospital #</th>
                <th class="w-4/12">Patient Name</th>
                <th class="w-3/12">Ward/Room</th>
                <th class="w-3/12">Department</th>
                <th class="w-2/12">Date Discharged</th>
                <th class="w-3/12">Condition/Status</th>
                <th class="w-2/12">MSS Classification</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                <tr wire:key="select-patient-{{ $patient->hpercode }}-{{ $loop->iteration }}"
                    wire:click="view_enctr('{{ $patient->enccode }}')" style="cursor: pointer" class="hover">
                    <td>{{ date('m/d/Y h:i A', strtotime($patient->admdate)) }}</td>
                    <td>{{ $patient->hpercode }}</td>
                    <td><span class="font-semibold">{{ $patient->patlast }}, {{ $patient->patfirst }}
                            {{ $patient->patsuffix }}
                            {{ $patient->patmiddle }}</span></td>
                    <td> <span class="font-semibold">{{ $patient->wardname }} </span> ({{ $patient->rmname }})
                    <td> <span class="font-semibold">{{ $patient->tsdesc }} </span></td>
                    <td>{{ date('m/d/Y h:i A', strtotime($patient->disdate)) }}</td>
                    <td>
                        <span class="font-semibold">
                            @php
                                switch ($patient->condcode) {
                                    case 'RECOV':
                                        echo 'Recovered';
                                        break;

                                    case 'DIEMI':
                                        echo '< 48 hours Autopsied';
                                        break;

                                    case 'DIENA':
                                        echo 'Died < 48 hours Not Autopsied';
                                        break;

                                    case 'DIEPO':
                                        echo 'Died>48 hours Autopsied';
                                        break;

                                    case 'DPONA':
                                        echo 'Died >48 hours Not Autopsied';
                                        break;

                                    case 'IMPRO':
                                        echo 'Improved';
                                        break;

                                    case 'UNIMP':
                                        echo 'Unimproved';
                                        break;
                                }
                            @endphp
                        </span>
                    </td>
                    <td>
                        @php
                            $class = '---';
                            switch ($patient->mssikey) {
                                case 'MSSA11111999':
                                case 'MSSB11111999':
                                    $class = 'Pay';
                                    break;

                                case 'MSSC111111999':
                                    $class = 'PP1';
                                    break;

                                case 'MSSC211111999':
                                    $class = 'PP2';
                                    break;

                                case 'MSSC311111999':
                                    $class = 'PP3';
                                    break;

                                case 'MSSD11111999':
                                    $class = 'Indigent';
                                    break;

                                default:
                                    $class = '---';
                            }
                            echo $class;
                        @endphp
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No record found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
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

    @push('scripts')
        <script>
            new DataTable('#table', {
                initComplete: function() {
                    this.api()
                        .columns()
                },
                paginate: false,
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#refreshBtn').click();
                }
            });

            document.addEventListener('keydown', e => {
                if (e.ctrlKey && e.key == 'c') {
                    console.log('wow')
                    e.preventDefault();
                    $('#newPatBtn').click();
                }
            });
        </script>
    @endpush
