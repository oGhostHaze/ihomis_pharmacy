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
    <div class="h-screen mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
        <div class="h-screen overflow-hidden">
            <div class="flex space-x-3">
                @can('manage-logger')
                    <div class="shadow-xl card w-96 bg-base-100">
                        <div class="card-body">
                            <h2 class="text-center card-title">Drug Consumption Logger</h2>
                            <p class="text-xl text-center">{!! session('active_consumption')
                                ? '<span class="p-3 text-xl font-bold uppercase badge badge-success">Active</span>'
                                : '<span class="p-3 text-xl font-bold uppercase badge badge-error">Inactive</span>' !!}</p>
                            <div class="justify-end card-actions">
                                @if (session('active_consumption'))
                                    <button class="btn btn-sm btn-error" onclick="stop_log()">Stop</button>
                                @else
                                    <button class="btn btn-sm btn-primary" onclick="start_log()">Start</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endcan
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
