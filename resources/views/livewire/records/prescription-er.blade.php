<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-file-prescription la-lg"></i> Rx/Orders
            </li>
            <li>
                <i class="mr-1 las la-prescription-alt la-lg"></i> Emergency Room
            </li>
        </ul>
    </div>
    <div class="flex justify-center">
        <x-jet-nav-link class="ml-2" href="{{ route('rx.ward') }}" :active="request()->routeIs('rx.ward')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Wards') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.opd') }}" :active="request()->routeIs('rx.opd')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Out Patient Department') }}
        </x-jet-nav-link>
        <x-jet-nav-link class="ml-2" href="{{ route('rx.er') }}" :active="request()->routeIs('rx.er')">
            <i class="mr-1 las la-lg la-file-prescription"></i> {{ __('Emergency Room') }}
        </x-jet-nav-link>
    </div>
</x-slot>


<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    {{-- <div class="flex space-x-8 justify-normal">
        <div class="ml-3 form-control">
            <label class="input-group input-group-sm">
                <span class="text-sm">Ward</span>
                <select class="p-0 pl-2 text-sm w-80 select select-bordered select-sm" wire:model="wardcode">
                    @foreach ($wards as $ward)
                        <option value="{{$ward->wardcode}}">{{$ward->wardname}} ({{$ward->wclcode}})</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div> --}}
    <div class="flex justify-end">
        <div class="ml-2">
            <div class="form-control">
                <label class="input-group">
                    <span class="whitespace-nowrap">Filter Date</span>
                    <input type="date" class="w-full input input-sm input-bordered" max="{{ date('Y-m-d') }}"
                        wire:model.lazy="filter_date" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-3 overflow-x-auto">
        <div>
            <span wire:loading>
                <i class="las la-spinner la-lg animate-spin"></i>
                Processing...
            </span>
        </div>
        <div wire:loading.class="hidden">
            <table class="table w-full mb-3 table-compact table-zebra">
                <thead>
                    <tr>
                        <th class="cursor-pointer" onclick="sortTable(0)">Date <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th class="cursor-pointer" onclick="sortTable(1)">Patient Name <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th class="cursor-pointer" onclick="sortTable(2)">Department <span class="ml-1"><i
                                    class="las la-sort"></i></span></th>
                        <th>Order Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prescriptions as $rx)
                        <tr wire:key="view-enctr-{{ $rx->enccode }}-{{ $loop->iteration }}"
                            wire:click="view_enctr('{{ $rx->enccode }}')" class="cursor-pointer hover">
                            <td>
                                <div class="flex-col">
                                    <div>{{ $rx->active_er->erdate_format1() }}</div>
                                    <div>{{ $rx->active_er->ertime_format1() }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ $rx->active_er->patient->fullname() }}</div>
                                    <div class="text-sm"><span
                                            class="badge badge-ghost badge-sm">{{ $rx->active_er->hpercode }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex-col">
                                    <div>{{ $rx->active_er->provider->emp->fullname() }}</div>
                                    <div>{{ $rx->active_er->service_type->tsdesc }}</div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $basic = $rx->active_basic->count();
                                    $g24 = $rx->active_g24->count();
                                    $or = $rx->active_or->count();
                                @endphp
                                <ul class="text-sm rounded-md menu menu-horizontal bg-base-200">
                                    @if ($basic)
                                        <li>
                                            <div class="tooltip" data-tip="BASIC"><i
                                                    class="las la-2g la-prescription"></i>
                                                <div class="badge badge-accent badge-xs">{{ $basic }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($g24)
                                        <li>
                                            <div class="tooltip" data-tip="Good For 24 Hrs"><i
                                                    class="las la-2g la-hourglass-start"></i>
                                                <div class="badge badge-error badge-xs">{{ $g24 }}</div>
                                            </div>
                                        </li>
                                    @endif
                                    @if ($or)
                                        <li>
                                            <div class="tooltip" data-tip="For Operating Use"><i
                                                    class="las la-2g la-syringe"></i>
                                                <div class="badge badge-secondary badge-xs">{{ $or }}</div>
                                            </div>
                                        </li>
                                    @endif
                                </ul>
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
                {{-- {{ $prescriptions->links() }} --}}
            </div>
        </div>
    </div>
</div>
