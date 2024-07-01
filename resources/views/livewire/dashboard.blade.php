<x-slot name="header">
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
</x-slot>

<div class="py-12">
    <div class="h-screen mx-auto uppercase max-w-screen-2xl sm:px-6 lg:px-8">
        <div class="h-screen">
            <div class="grid grid-flow-row grid-cols-1 gap-4 mt-4 sm:grid-cols-2 lg:grid-cols-4">
                @can('manage-logger')
                    <div class="w-full h-40 shadow-xl card bg-base-100">
                        <div class="card-body">
                            <h2 class="text-lg text-center">Drug Consumption Logger</h2>
                            <p class="text-xl text-center">{!! session('active_consumption')
                                ? '<span class="p-3 text-xl font-bold uppercase badge badge-success">Active</span>'
                                : '<span class="p-3 text-xl font-bold uppercase badge badge-error">Inactive</span>' !!}</p>
                            <div class="justify-end card-actions">
                                @if (session('active_consumption'))
                                    <button class="btn btn-xs btn-error" onclick="stop_log()"
                                        wire:loading.attr="disabled">Stop</button>
                                @else
                                    <button class="btn btn-xs btn-primary" onclick="start_log()"
                                        wire:loading.attr="disabled">Start</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endcan
                <a class="w-full h-40 shadow-xl cursor-pointer card bg-base-100"
                    href="{{ route('dispensing.rxo.pending') }}" target="_blank">
                    <div class="card-body">
                        <h2 class="text-lg text-center">Pending/Charged Orders</h2>
                        <p class="text-xl text-center">
                            <span class="text-6xl font-bold uppercase text-info whitespace-nowrap">
                                <i class="las la-stream"></i>
                                {{ $pending_order }}
                            </span>
                        </p>
                    </div>
                </a>
            </div>
            <div class="grid grid-flow-row grid-cols-1 gap-4 mt-4 sm:grid-cols-2 lg:grid-cols-4">
                <a class="w-full h-40 shadow-xl card bg-base-100 hover:bg-slate-600"
                    href="{{ route('reports.near.exp') }}" target="_blank">
                    <div class="card-body">
                        <h2 class="text-lg text-center">Items Near Expiry</h2>
                        <p class="text-xl text-center">
                            <span class="text-6xl font-bold uppercase text-warning whitespace-nowrap">
                                <i class="las la-hourglass-half"></i>
                                {{ $near_expiry }}
                            </span>
                        </p>
                    </div>
                </a>
                <a class="w-full h-40 shadow-xl card bg-base-100" href="{{ route('reports.exp') }}" target="_blank">
                    <div class="card-body">
                        <h2 class="text-lg text-center">Expired Items</h2>
                        <p class="text-xl text-center">
                            <span class="text-6xl font-bold uppercase text-error whitespace-nowrap">
                                <i class="las la-calendar-times"></i>
                                {{ $expired }}
                            </span>
                        </p>
                    </div>
                </a>
                <a class="w-full h-40 shadow-xl card bg-base-100" href="{{ route('dmd.stk.reorder') }}" target="_blank">
                    <div class="card-body">
                        <h2 class="text-lg text-center">Near Reorder Level</h2>
                        <p class="text-xl text-center whitespace-nowrap text-warning">
                            <span class="text-6xl font-bold uppercase">
                                <i class="las la-history"></i>
                                {{ $near_reorder }}
                            </span>
                        </p>
                    </div>
                </a>
                <a class="w-full h-40 shadow-xl card bg-base-100" href="{{ route('dmd.stk.reorder') }}" target="_blank">
                    <div class="card-body">
                        <h2 class="text-lg text-center">Critical Stock</h2>
                        <p class="text-xl text-center text-error whitespace-nowrap">
                            <span class="text-6xl font-bold uppercase">
                                <i class="las la-exclamation-triangle"></i>
                                {{ $critical }}
                            </span>
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function start_log() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Enter your password to continue. <br><small>(this serves as your signature)</small> </span>
                    <div class="w-full form-control">
                        <label class="label" for="password">
                            <span class="label-text">Password</span>
                        </label>
                        <input id="password" type="password" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Start`,
                didOpen: () => {
                    const password = Swal.getHtmlContainer().querySelector('#password');
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('password', password.value);

                    Livewire.emit('start_log');
                }
            });
        }

        function stop_log() {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Enter your password to continue. <br><small>(this serves as your signature)</small> </span>
                    <div class="w-full form-control">
                        <label class="label" for="password">
                            <span class="label-text">Password</span>
                        </label>
                        <input id="password" type="password" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Stop`,
                didOpen: () => {
                    const password = Swal.getHtmlContainer().querySelector('#password');
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('password', password.value);

                    Livewire.emit('stop_log');
                }
            });
        }
    </script>
@endpush
