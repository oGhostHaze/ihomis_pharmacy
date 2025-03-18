<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="text-sm breadcrumbs">
            <ul>
                <li class="font-bold">
                    <i class="las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
                </li>
                <li>
                    <i class="las la-tachometer-alt la-lg"></i> Dashboard
                </li>
            </ul>
        </div>
    </div>
</x-slot>
<div>
    <div class="py-6">
        <div class="flex justify-end mx-auto mb-3 space-x-2 sm:px-6 lg:px-8">
            <div class="form-control">
                <select class="select select-sm select-bordered" wire:model="date_range">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="last_week">Last Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            @if ($date_range === 'custom')
                <div class="flex items-center space-x-2">
                    <input type="date" class="input input-sm input-bordered" wire:model="custom_date_from">
                    <span>to</span>
                    <input type="date" class="input input-sm input-bordered" wire:model="custom_date_to">
                </div>
            @endif

            <button class="btn btn-sm btn-ghost" wire:click="$refresh" title="Refresh Dashboard">
                <i class="las la-sync"></i>
            </button>
        </div>
        <div class="px-4 mx-auto sm:px-6 lg:px-8">
            <div class="flex items-start space-x-4">
                <div class="card">
                    <table class="table table-compact">
                        <thead>
                            <th>#</th>
                            <th>Active Area</th>
                        </thead>
                        <tbody>
                            @foreach ($locations as $location)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $location->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col w-full space-y-4">
                    <!-- Stats Cards Section -->
                    <div class="grid self-start flex-1 w-full grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

                        <!-- Near Expiry -->
                        <a href="{{ route('reports.near.exp') }}"
                            class="transition-colors shadow-xl card bg-base-100 hover:bg-base-200">
                            <div class="p-4 card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold">Items Near Expiry</h2>
                                        <p class="mt-2 text-2xl font-bold text-warning">
                                            {{ $stats['near_expiry'] ?? 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 text-white rounded-full bg-warning bg-opacity-20">
                                        <i class="text-2xl las la-hourglass-half"></i>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Expired -->
                        <a href="{{ route('reports.exp') }}"
                            class="transition-colors shadow-xl card bg-base-100 hover:bg-base-200">
                            <div class="p-4 card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold">Expired Items</h2>
                                        <p class="mt-2 text-2xl font-bold text-error">
                                            {{ $stats['expired'] ?? 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 text-white rounded-full bg-error bg-opacity-20">
                                        <i class="text-2xl las la-calendar-times"></i>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Near Reorder Level -->
                        <a href="{{ route('dmd.stk.reorder') }}"
                            class="transition-colors shadow-xl card bg-base-100 hover:bg-base-200">
                            <div class="p-4 card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold">Near Reorder Level</h2>
                                        <p class="mt-2 text-2xl font-bold text-warning">
                                            {{ $stats['near_reorder'] ?? 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 text-white rounded-full bg-warning bg-opacity-20">
                                        <i class="text-2xl las la-history"></i>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Critical Stock -->
                        <a href="{{ route('dmd.stk.reorder') }}"
                            class="transition-colors shadow-xl card bg-base-100 hover:bg-base-200">
                            <div class="p-4 card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold">Critical Stock</h2>
                                        <p class="mt-2 text-2xl font-bold text-error">
                                            {{ $stats['critical'] ?? 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 text-white rounded-full bg-error bg-opacity-20">
                                        <i class="text-2xl las la-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full">
                        @livewire('components.consolidated-drug-summary')
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
