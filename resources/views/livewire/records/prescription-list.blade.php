<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li>
                <i class="mr-1 las la-file-prescription la-lg"></i> Rx/Orders
            </li>
        </ul>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex space-x-8 justify-normal">
        <div class="form-control">
            <label class="input-group input-group-sm">
                <span class="text-sm">Department</span>
                <select class="w-32 p-0 pl-2 text-sm select select-bordered select-sm" wire:model="department">
                    <option value="opd">OPD</option>
                    <option value="er">ER</option>
                    <option value="ward">WARD</option>
                </select>
            </label>
        </div>
        <div class="ml-3 form-control">
            @if ($department == 'ward')
                <label class="input-group input-group-sm">
                    <span class="text-sm">Ward</span>
                    <select class="p-0 pl-2 text-sm w-80 select select-bordered select-sm" wire:model="wardcode">
                        @foreach ($wards as $ward)
                            <option value="{{ $ward->wardcode }}">{{ $ward->wardname }} ({{ $ward->wclcode }})</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
    </div>
    <div>
        <span wire:loading>
            <i class="las la-spinner la-lg animate-spin"></i>
            Processing...
        </span>
    </div>
    <div class="flex flex-col justify-center w-full mt-3 overflow-x-auto">
        <table class="table w-full mb-3 table-compact">
            <thead>
                <tr>
                    <th>Date Admitted</th>
                    <th>Patient Name</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prescriptions as $rx)
                    @php
                        if ($department == 'ward') {
                            $log = $rx->adm;
                            $room = $log->room;
                        } elseif ($department == 'opd') {
                            $log = $rx->opd;
                        } else {
                            $log = $rx->er;
                        }
                    @endphp
                    <tr>
                        <td>{{ $rx->enccode }}</td>
                        <td>
                            {{ $log->patient->fullname() }}
                        </td>
                        <td>
                            @if ($department == 'ward')
                                <div class="flex-col">
                                    <div>
                                        {{ $room->ward->wardname }}
                                    </div>
                                    <div>
                                        {{ $room->rmname }}
                                    </div>
                                </div>
                            @elseif($department == 'opd')
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No record found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">
            {{ $prescriptions->links() }}
        </div>
    </div>
</div>
