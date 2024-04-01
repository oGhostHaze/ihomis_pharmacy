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
                <i class="mr-1 las la-users-cog la-lg"></i> Manage Users
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div>
            {{-- <button class="btn btn-sm btn-primary" onclick="location_modal()">Create User</button> --}}
        </div>
        <div>
            <div class="form-control">
                <label class="input-group input-group-sm">
                    <span><i class="las la-search"></i></span>
                    <input type="text" placeholder="Search" class="input input-bordered input-sm"
                        wire:model.lazy="search" />
                </label>
            </div>
        </div>
    </div>
    <div class="flex flex-col justify-center w-full mt-2 space-y-3 overflow-x-auto">
        <table class="table w-full table-compact">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Role</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="hover">
                        <th>{{ $user->id }}</th>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->location->description }}</td>
                        <td>{{ $user->getRoleNames()->first() }}</td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <div><button class="btn btn-sm btn-info"
                                        onclick="update_role('{{ $user->id }}', '{{ $user->getRoleNames()->first() }}')"><i
                                            class="las la-user-shield la-lg"></i></button></div>
                                <div><button class="btn btn-sm btn-warning"><i class="las la-undo la-lg"></i></button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="6">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $users->links() }}
    </div>
</div>

@push('scripts')
    <script>
        function update_role(user_id, current_role) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> Update User Role </span>
                    <div class="w-full form-control">
                        <label class="label">
                            <span class="label-text">Role</span>
                        </label>
                        <select class="select select-bordered" id="role_name">
                            <option></option>
                            @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const role_name = Swal.getHtmlContainer().querySelector('#role_name');
                    role_name.value = current_role;
                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('role_name', role_name.value);
                    Livewire.emit('update_role', user_id)
                }
            });
        }
    </script>
@endpush
