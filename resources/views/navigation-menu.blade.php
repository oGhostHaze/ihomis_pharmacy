<div class="text-sm navbar bg-base-100">
    <div class="navbar-start">
        <div class="dropdown">
            <label tabindex="0" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </label>
            <ul tabindex="0" class="p-2 mt-3 shadow menu menu-compact dropdown-content bg-base-100 rounded-box w-52">
                <li><a><i class="mr-1 las la-lg la-tachometer-alt"></i> Dashboard</a></li>
                <li tabindex="0">
                    <a class="justify-between">
                        Parent
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24">
                            <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z" />
                        </svg>
                    </a>
                    <ul class="p-2">
                        <li><a>Submenu 1</a></li>
                        <li><a>Submenu 2</a></li>
                    </ul>
                </li>
                <li><a>Item 3</a></li>
            </ul>
        </div>
        <div class="hidden lg:flex">
            <a rel="noopener noreferrer" class="text-xl normal-case btn btn-ghost" href="{{ route('dashboard') }}">
                <x-jet-application-mark class="block w-auto h-9" />
            </a>
            <ul class="px-1 mr-auto menu menu-horizontal">
                <li class="mt-2 dropdown">
                    <x-jet-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        <i class="las la-lg la-tachometer-alt"></i> {{ __('Dashboard') }}
                    </x-jet-nav-link>
                </li>
                @can('view-patients')
                    <li tabindex="0" class="mt-2 dropdown">
                        <x-jet-nav-link :active="request()->routeIs('patients.*') && !request()->routeIs('dispensing.rxo.pending')">
                            <i class="las la-lg la-user-alt"></i> {{ __('Patients') }}
                            <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
                                <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                            </svg>
                        </x-jet-nav-link>
                        <ul
                            class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                            <li><a href="{{ route('patients.list') }}">Patient Search</a></li>
                            <li><a href="{{ route('patients.fordisc') }}">For Discharge</a></li>
                            <li><a href="{{ route('patients.discharged') }}">Discharged Patients</a></li>
                        </ul>
                    </li>
                @endcan
                @can('view-prescriptions')
                    <li tabindex="0" class="mt-2 dropdown">
                        <x-jet-nav-link :active="request()->routeIs('rx.*') || request()->routeIs('dispensing.rxo.pending')">
                            <i class="las la-lg la-file-prescription"></i> Rx/Orders
                            <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
                                <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                            </svg>
                        </x-jet-nav-link>
                        <ul
                            class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                            <li><a href="{{ route('rx.opd') }}">OPD Rx</a></li>
                            <li><a href="{{ route('rx.ward') }}">Ward Rx</a></li>
                            <li><a href="{{ route('rx.er') }}">ER Rx</a></li>
                            <li>
                                <a href="{{ route('dispensing.rxo.pending') }}">Pending Orders</a>
                            </li>
                        </ul>
                    </li>
                @endcan
                <li tabindex="0" class="mt-2 dropdown">
                    <x-jet-nav-link :active="request()->routeIs('dmd.stk') || request()->routeIs('iotrans.*')">
                        <i class="las la-lg la-pills"></i> Drugs & Meds
                        <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24">
                            <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                        </svg>
                    </x-jet-nav-link>
                    <ul
                        class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                        @can('inventory-viewer')
                            <li><a href="{{ route('dmd.stk') }}">Stocks</a></li>
                            <li><a href="{{ route('dmd.stk.sum') }}">Stocks Summary</a></li>
                            <li><a href="{{ route('reports.stkcrd') }}">Stock Card</a></li>
                        @endcan
                        @can('manage-reorder-levels')
                            <li><a href="{{ route('dmd.stk.reorder') }}">Reorder Levels</a></li>
                            <li><a href="{{ route('dmd.stk.reorder2') }}">Reorder Levels (Computed)</a></li>
                        @endcan
                        @can('view-iotrans')
                            <li><a href="{{ route('iotrans.list') }}">IO Trans</a></li>
                        @endcan
                        @can('view-iotrans-limited')
                            <li><a href="{{ route('iotrans.requests') }}">IO Trans Requests</a></li>
                        @endcan
                        @can('view-ris')
                            <li><a href="{{ route('dmd.stk.ris') }}">Ward RIS</a></li>
                        @endcan
                    </ul>
                </li>
                @canany('view-deliveries', 'view-eps')
                    <li tabindex="0" class="mt-2 dropdown">
                        <x-jet-nav-link :active="request()->routeIs('delivery.*')">
                            <i class="las la-lg la-truck-loading"></i> Purchases
                            <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
                                <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                            </svg>
                        </x-jet-nav-link>
                        <ul
                            class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                            @can('view-deliveries')
                                <li><a href="{{ route('ris.index') }}">PIMS RIS</a></li>
                                <li><a href="{{ route('delivery.list') }}">Deliveries</a></li>
                                <li><a href="{{ route('delivery.donations') }}">Donations</a></li>
                            @endcan
                            @can('view-eps')
                                <li><a href="{{ route('delivery.ep') }}">Emergency Purchase</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcan
                @can('view-tickets')
                    <li tabindex="0" class="mt-2 dropdown">
                        <x-jet-nav-link :active="request()->routeIs('tickets.*')">
                            <i class="las la-lg la-ticket-alt"></i> {{ __('Tickets') }}
                            <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
                                <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                            </svg>
                        </x-jet-nav-link>
                        <ul
                            class="overflow-y-auto shadow-2xl dropdown-content bg-base-100 text-base-content rounded-t-box rounded-b-box">
                            <li><a href="{{ route('tickets.index') }}">List View</a></li>
                            <li><a href="{{ route('tickets.kanban') }}">Kanban Board</a></li>
                            @can('create-tickets')
                                <li><a href="{{ route('tickets.create') }}">Create New Ticket</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcan

            </ul>
        </div>
    </div>
    <div class="navbar-end">
        @can('view-reports')
            <!-- Reports Dropdown -->
            <div class="relative ml-3">
                <x-jet-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                <i class="las la-lg la-file-excel"></i>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">

                        <x-jet-dropdown-link href="{{ route('reports.stkcrd') }}">
                            {{ __('Stock Card') }}
                        </x-jet-dropdown-link>
                        <x-jet-dropdown-link href="{{ route('reports.consumption.manual-range') }}">
                            {{ __('Consumption Report') }}
                        </x-jet-dropdown-link>
                        <x-jet-dropdown-link href="{{ route('reports.consumption.depts') }}">
                            {{ __('Consumption Department') }}
                        </x-jet-dropdown-link>
                        @if (session('pharm_location_id') == 1)
                            <x-jet-dropdown-link href="{{ route('reports.consumption.warehouse') }}">
                                {{ __('Consumption Warehouse') }}
                            </x-jet-dropdown-link>
                            <div class="border-t border-gray-100"></div>
                            <x-jet-dropdown-link href="{{ route('reports.issuance.consol.loc') }}">
                                {{ __('Consolidated Drug Issuances') }}
                            </x-jet-dropdown-link>
                        @endif
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.all') }}">
                            {{ __('Drug Issuance') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.total') }}">
                            {{ __('Total Drugs Issued') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.returns') }}">
                            {{ __('Returned Log') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.returns.summary') }}">
                            {{ __('Returned Log Summary') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.issuance.charges') }}">
                            {{ __('Summary of Charge Slip') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.consumption.wards') }}">
                            {{ __('Ward Consumption') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.iotrans.issued') }}">
                            {{ __('IO Trans Issued') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.iotrans.received') }}">
                            {{ __('IO Trans Received') }}
                        </x-jet-dropdown-link>
                        <div class="border-t border-gray-100"></div>
                        <x-jet-dropdown-link href="{{ route('reports.delivery.sum') }}">
                            {{ __('Deliveries Summary') }}
                        </x-jet-dropdown-link>
                        <x-jet-dropdown-link href="{{ route('reports.cons.sum') }}">
                            {{ __('Consumption Summary') }}
                        </x-jet-dropdown-link>

                    </x-slot>
                </x-jet-dropdown>
            </div>
        @endcan
        @can('view-settings')
            <!-- Settings Dropdown -->
            <div class="relative ml-3">
                <x-jet-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 border border-transparent rounded-md focus:outline-none">
                                <i class="las la-lg la-cog"></i>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">

                        <x-jet-dropdown-link href="{{ route('ref.location') }}">
                            {{ __('Location') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.dmd') }}">
                            {{ __('Drugs and Meds (Homis)') }}
                        </x-jet-dropdown-link>

                        <x-jet-dropdown-link href="{{ route('ref.pndf') }}">
                            {{ __('PNDF Generics (Homis)') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.permissions') }}">
                            {{ __('Permissions') }}
                        </x-jet-dropdown-link>


                        <x-jet-dropdown-link href="{{ route('ref.users') }}">
                            {{ __('Manage Users') }}
                        </x-jet-dropdown-link>

                        <div class="border-t border-gray-100"></div>

                        <x-jet-dropdown-link href="{{ route('ref.wards') }}">
                            {{ __('Manage RIS Wards') }}
                        </x-jet-dropdown-link>

                    </x-slot>
                </x-jet-dropdown>
            </div>
        @endcan
        <!-- Account Dropdown -->
        <div class="relative ml-3">
            <x-jet-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <span class="inline-flex rounded-md">
                        <button type="button"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 uppercase transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
                            <i class="las la-lg la-user"></i>
                            @php
                                $words = preg_split('/[\s,_-]+/', session('user_name'));
                                $acronym = '';

                                foreach ($words as $w) {
                                    $acronym .= mb_substr($w, 0, 1);
                                }
                                echo $acronym;
                            @endphp

                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </x-slot>

                <x-slot name="content">
                    <!-- Account Management -->
                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Account') }}
                    </div>

                    <x-jet-dropdown-link href="{{ route('profile.show') }}">
                        {{ __('Profile') }}
                    </x-jet-dropdown-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-jet-dropdown-link href="{{ route('api-tokens.index') }}">
                            {{ __('API Tokens') }}
                        </x-jet-dropdown-link>
                    @endif

                    <div class="border-t border-gray-100"></div>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-jet-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-jet-dropdown-link>
                    </form>
                </x-slot>
            </x-jet-dropdown>
        </div>
    </div>
</div>
