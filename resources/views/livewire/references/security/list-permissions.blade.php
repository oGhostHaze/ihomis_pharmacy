<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold">
                <i class="mr-1 las la-cog la-lg"></i>Settings
            </li>
            <li>
                <i class="mr-1 las la-shield-alt la-lg"></i> Roles and Permission
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div>
        </div>
        <div>
            {{-- <div class="form-control">
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm" wire:model.lazy="search" />
                </label>
            </div> --}}
        </div>
    </div>
    <div class="grid grid-cols-12 gap-4 mt-3">
        <div class="col-span-3 overflow-x-auto">
            <table class="table w-full table-compact table-bordered">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Guard Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr class="cursor-pointer hover @if ($selected_role && $role->id == $selected_role->id) active @endif"
                            wire:key="select-role-{{ $role->id }}" wire:click="select_role({{ $role->id }})">
                            <th>{{ $role->name }}</th>
                            <th>{{ $role->guard_name }}</th>
                        </tr>
                    @empty
                        <tr>
                            <th class="text-center" colspan="2">No record found!</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="col-span-3 overflow-x-auto">
            <table class="table w-full table-compact table-bordered">
                <thead>
                    <tr>
                        <th>Role has Permission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($role_permissions as $rp)
                        <tr class="cursor-pointer hover" onclick="remove_permission('{{ $rp->name }}')">
                            <th>{{ $rp->name }}</th>
                        </tr>
                    @empty
                        <tr>
                            <th class="text-center">No record found!</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="col-span-3 overflow-x-auto">
            <table class="table w-full table-compact table-bordered">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Guard Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr class="cursor-pointer hover" onclick="add_permission('{{ $permission->name }}')">
                            <th>{{ $permission->name }}</th>
                            <th>{{ $permission->guard_name }}</th>
                        </tr>
                    @empty
                        <tr>
                            <th class="text-center" colspan="2">No record found!</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        function add_permission(permission) {
            Swal.fire({
                html: `
                    <span class="font-bold"> Confirm add permission to role? </span>`,
                showCancelButton: true,
                confirmButtonText: `Confirm`,
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('add_permission', permission)
                }
            });
        }

        function remove_permission(permission) {
            Swal.fire({
                html: `
                    <span class="font-bold"> Confirm revocation of permission? </span>`,
                showCancelButton: true,
                confirmButtonText: `Confirm`,
                confirmButtonColor: 'red',
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    Livewire.emit('remove_permission', permission)
                }
            });
        }
    </script>
@endpush
