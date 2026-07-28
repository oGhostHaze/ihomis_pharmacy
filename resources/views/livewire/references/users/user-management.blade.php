<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{ session('pharm_location_name') }}
            </li>
            <li class="font-bold"><i class="mr-1 las la-cog la-lg"></i> Settings</li>
            <li><i class="mr-1 las la-users-cog la-lg"></i> User Management</li>
        </ul>
    </div>
</x-slot>

<div class="min-h-screen px-4 py-6 bg-base-200 sm:px-6 lg:px-8">
    <div class="mx-auto space-y-6 max-w-7xl">
        <section class="overflow-hidden shadow-lg rounded-xl bg-base-100"
            x-data="{ open: {{ $search || $role_filter || $location_filter || $status_filter ? 'true' : 'false' }} }">
            <button type="button"
                class="flex items-center justify-between w-full px-5 py-4 text-left hover:bg-base-200"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()">
                <span class="inline-flex items-center gap-2 text-lg font-semibold">
                    <i class="las la-filter text-primary"></i>
                    Filters
                    @if ($search || $role_filter || $location_filter || $status_filter)
                        <span class="badge badge-primary badge-sm">Active</span>
                    @endif
                </span>
                <i class="las la-chevron-down transition-transform duration-200"
                    x-bind:class="{ 'transform rotate-180': open }"></i>
            </button>

            <div class="px-5 pb-5 border-t border-base-300" x-show="open" x-transition>
                <div class="flex justify-end pt-3 mb-2">
                    <button type="button" class="gap-2 btn btn-sm btn-ghost" wire:click="clearFilters"
                        wire:loading.attr="disabled">
                        <i class="las la-undo"></i> Reset filters
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="form-control sm:col-span-2">
                        <label class="label" for="user_search">
                            <span class="font-semibold label-text">Search</span>
                        </label>
                        <label class="input-group">
                            <span><i class="las la-search"></i></span>
                            <input id="user_search" type="search"
                                placeholder="Name, email, or employee ID"
                                class="w-full input input-bordered"
                                wire:model.debounce.400ms="search" />
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label" for="role_filter">
                            <span class="font-semibold label-text">Role</span>
                        </label>
                        <select id="role_filter" class="select select-bordered" wire:model="role_filter">
                            <option value="">All roles</option>
                            <option value="unassigned">No assigned role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label" for="location_filter">
                            <span class="font-semibold label-text">Location</span>
                        </label>
                        <select id="location_filter" class="select select-bordered" wire:model="location_filter">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->description }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label" for="status_filter">
                            <span class="font-semibold label-text">Status</span>
                        </label>
                        <select id="status_filter" class="select select-bordered" wire:model="status_filter">
                            <option value="">All statuses</option>
                            <option value="active">Active only</option>
                            <option value="inactive">Inactive only</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden shadow-lg card bg-base-100">
            <div class="flex flex-col gap-3 px-5 py-4 border-b sm:flex-row sm:items-center sm:justify-between border-base-300">
                <div>
                    <h2 class="text-xl font-bold">User accounts</h2>
                    <p class="mt-1 text-sm text-base-content/60">
                        Showing {{ $users->firstItem() ?: 0 }}–{{ $users->lastItem() ?: 0 }} of
                        {{ number_format($users->total()) }} matching users
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm whitespace-nowrap" for="per_page">Rows per page</label>
                    <select id="per_page" class="select select-bordered select-sm" wire:model="per_page">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead class="bg-base-200">
                        <tr>
                            <th>
                                <button type="button" class="flex items-center gap-1 font-bold"
                                    wire:click="sortBy('id')">
                                    ID
                                    @if ($sort_field === 'id')
                                        <i class="las {{ $sort_direction === 'asc' ? 'la-sort-up' : 'la-sort-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" class="flex items-center gap-1 font-bold"
                                    wire:click="sortBy('name')">
                                    User
                                    @if ($sort_field === 'name')
                                        <i class="las {{ $sort_direction === 'asc' ? 'la-sort-up' : 'la-sort-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" class="flex items-center gap-1 font-bold"
                                    wire:click="sortBy('employeeid')">
                                    Employee ID
                                    @if ($sort_field === 'employeeid')
                                        <i class="las {{ $sort_direction === 'asc' ? 'la-sort-up' : 'la-sort-down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>Location</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $role = $user->roles->first();
                                $isProtected = $role && $role->name === 'Super Admin';
                            @endphp
                            <tr class="{{ $user->trashed() ? 'opacity-60' : '' }}"
                                wire:key="user-row-{{ $user->id }}">
                                <td class="font-mono text-xs">#{{ $user->id }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="w-10 rounded-full bg-primary/10 text-primary">
                                                <span class="font-bold uppercase">{{ substr(trim($user->name), 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-semibold uppercase">{{ $user->name }}</div>
                                            <div class="text-xs text-base-content/60">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->employeeid ?: '—' }}</td>
                                <td>
                                    @if ($user->location)
                                        <span class="gap-1 badge badge-ghost">
                                            <i class="las la-map-marker-alt"></i>
                                            {{ $user->location->description }}
                                        </span>
                                    @else
                                        <span class="badge badge-warning badge-outline">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($role)
                                        <span class="badge badge-info badge-outline">{{ $role->name }}</span>
                                    @else
                                        <span class="badge badge-warning badge-outline">No role</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->trashed())
                                        <span class="gap-1 badge badge-error badge-outline">
                                            <i class="las la-times-circle"></i> Inactive
                                        </span>
                                    @else
                                        <span class="gap-1 badge badge-success badge-outline">
                                            <i class="las la-check-circle"></i> Active
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="gap-1 btn btn-sm btn-info btn-outline"
                                            wire:click="editUser({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            @if ($isProtected) disabled @endif>
                                            <i class="las la-user-edit"></i> Edit access
                                        </button>

                                        @if (!$isProtected && (int) $user->id !== (int) auth()->id())
                                            <button type="button"
                                                class="gap-1 btn btn-sm {{ $user->trashed() ? 'btn-success' : 'btn-error' }} btn-outline"
                                                wire:click="toggleActive({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                onclick="if (!confirm('{{ $user->trashed() ? 'Reactivate' : 'Deactivate' }} this user account?')) { event.stopImmediatePropagation(); }">
                                                <i class="las {{ $user->trashed() ? 'la-user-check' : 'la-user-slash' }}"></i>
                                                {{ $user->trashed() ? 'Reactivate' : 'Deactivate' }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="py-12 text-center">
                                        <i class="text-5xl las la-user-times text-base-content/20"></i>
                                        <p class="mt-2 font-semibold">No matching users</p>
                                        <p class="mt-1 text-sm text-base-content/60">
                                            Try changing or resetting the current filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-5 py-4 border-t border-base-300">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>

    <input type="checkbox" class="modal-toggle" wire:model="show_edit_modal" />
    <div class="modal {{ $show_edit_modal ? 'modal-open' : '' }}">
        <div class="max-w-lg modal-box">
            <button type="button" class="absolute btn btn-sm btn-circle btn-ghost right-2 top-2"
                wire:click="$set('show_edit_modal', false)">✕</button>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-info/10 text-info">
                    <i class="las la-user-shield la-2x"></i>
                </span>
                <div>
                    <h3 class="text-xl font-bold">Edit user access</h3>
                    <p class="text-sm text-base-content/60">{{ $selected_user_name }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div class="form-control">
                    <label class="label" for="selected_role">
                        <span class="font-semibold label-text">Assigned role</span>
                    </label>
                    <select id="selected_role" class="select select-bordered" wire:model.defer="selected_role">
                        <option value="">No assigned role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('selected_role')
                        <span class="mt-1 text-sm text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-control">
                    <label class="label" for="selected_location">
                        <span class="font-semibold label-text">Pharmacy location</span>
                    </label>
                    <select id="selected_location" class="select select-bordered"
                        wire:model.defer="selected_location">
                        <option value="">Choose a location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->description }}</option>
                        @endforeach
                    </select>
                    @error('selected_location')
                        <span class="mt-1 text-sm text-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mt-6 modal-action">
                <button type="button" class="btn btn-ghost"
                    wire:click="$set('show_edit_modal', false)">Cancel</button>
                <button type="button" class="gap-2 btn btn-primary"
                    wire:click="updateUser" wire:loading.attr="disabled" wire:target="updateUser">
                    <span wire:loading.remove wire:target="updateUser">
                        <i class="las la-save"></i> Save changes
                    </span>
                    <span wire:loading wire:target="updateUser">Saving…</span>
                </button>
            </div>
        </div>
    </div>
</div>
