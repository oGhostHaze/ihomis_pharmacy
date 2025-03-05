<div>
    <div class="flex flex-wrap items-center justify-between mb-4">
        <div class="flex flex-wrap items-center space-x-2">
            <div class="form-control">
                <div class="input-group">
                    <input type="text" placeholder="Search tickets..." wire:model.debounce.300ms="search"
                        class="input input-bordered" />
                    <button class="btn btn-square">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-control">
                <select wire:model="priority" class="select select-bordered">
                    <option value="">All Priorities</option>
                    @foreach ($priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <select wire:model="type" class="select select-bordered">
                    <option value="">All Types</option>
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <select wire:model="assignee" class="select select-bordered">
                    <option value="">All Assignees</option>
                    <option value="unassigned">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <button wire:click="resetFilters" class="btn btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="mt-2 md:mt-0">
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                New Ticket
            </a>
            <a href="{{ route('tickets.index') }}" class="btn btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd" />
                </svg>
                List View
            </a>
        </div>
    </div>

    <!-- Vertical Kanban Board -->
    <div class="grid grid-cols-4 gap-6">
        <!-- Pending Column -->
        <div class="overflow-hidden bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 font-bold bg-warning text-warning-content">
                <span>Pending ({{ count($pendingTickets) }})</span>
                <span class="badge badge-neutral">Awaiting Review</span>
            </div>
            <div class="p-3 max-h-[50vh] overflow-y-auto" id="pending-column" x-data x-on:dragover.prevent
                x-on:drop="
                    $event.preventDefault();
                    const ticketId = $event.dataTransfer.getData('ticketId');
                    Livewire.emit('ticketMoved', ticketId, 'pending');
                 ">
                <div class="grid grid-cols-1 gap-3 ">
                    @forelse ($pendingTickets as $ticket)
                        <div class="transition-shadow duration-200 shadow-md cursor-move card bg-base-100 hover:shadow-lg"
                            draggable="true"
                            x-on:dragstart="event.dataTransfer.setData('ticketId', {{ $ticket->id }})">
                            <div class="p-4 card-body">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <span class="text-xs font-medium opacity-70">#{{ $ticket->id }}</span>
                                        {!! $ticket->priority_badge !!}
                                        {!! $ticket->type_badge !!}
                                    </div>
                                    <div class="text-xs opacity-70">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <h3 class="mb-2 text-sm card-title">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-primary">
                                        {{ Str::limit($ticket->title, 50) }}
                                    </a>
                                </h3>

                                <p class="mb-2 text-xs text-gray-600 line-clamp-2">
                                    {{ Str::limit($ticket->description, 100) }}
                                </p>

                                <div class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100">
                                    <div class="flex items-center">
                                        <div class="avatar">
                                            <div class="w-6 rounded-full">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->reporter->name) }}&background=random"
                                                    alt="{{ $ticket->reporter->name }}" />
                                            </div>
                                        </div>
                                        <span class="ml-1 text-xs truncate">{{ $ticket->reporter->name }}</span>
                                    </div>

                                    @if ($ticket->assignee)
                                        <div class="flex items-center">
                                            <div class="avatar">
                                                <div class="w-6 rounded-full">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->assignee->name) }}&background=random"
                                                        alt="{{ $ticket->assignee->name }}" />
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge badge-sm badge-ghost">Unassigned</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 col-span-full">
                            No pending tickets
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Approved Column -->
        <div class="overflow-hidden bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 font-bold bg-info text-info-content">
                <span>Approved ({{ count($approvedTickets) }})</span>
                <span class="badge badge-neutral">Ready to Start</span>
            </div>
            <div class="p-3 max-h-[50vh] overflow-y-auto" id="approved-column" x-data x-on:dragover.prevent
                x-on:drop="
                    $event.preventDefault();
                    const ticketId = $event.dataTransfer.getData('ticketId');
                    Livewire.emit('ticketMoved', ticketId, 'approved');
                 ">
                <div class="grid grid-cols-1 gap-3 ">
                    @forelse ($approvedTickets as $ticket)
                        <div class="transition-shadow duration-200 shadow-md cursor-move card bg-base-100 hover:shadow-lg"
                            draggable="true"
                            x-on:dragstart="event.dataTransfer.setData('ticketId', {{ $ticket->id }})">
                            <div class="p-4 card-body">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <span class="text-xs font-medium opacity-70">#{{ $ticket->id }}</span>
                                        {!! $ticket->priority_badge !!}
                                        {!! $ticket->type_badge !!}
                                    </div>
                                    <div class="text-xs opacity-70">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <h3 class="mb-2 text-sm card-title">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-primary">
                                        {{ Str::limit($ticket->title, 50) }}
                                    </a>
                                </h3>

                                <p class="mb-2 text-xs text-gray-600 line-clamp-2">
                                    {{ Str::limit($ticket->description, 100) }}
                                </p>

                                <div class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100">
                                    <div class="flex items-center">
                                        <div class="avatar">
                                            <div class="w-6 rounded-full">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->reporter->name) }}&background=random"
                                                    alt="{{ $ticket->reporter->name }}" />
                                            </div>
                                        </div>
                                        <span class="ml-1 text-xs truncate">{{ $ticket->reporter->name }}</span>
                                    </div>

                                    @if ($ticket->assignee)
                                        <div class="flex items-center">
                                            <div class="avatar">
                                                <div class="w-6 rounded-full">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->assignee->name) }}&background=random"
                                                        alt="{{ $ticket->assignee->name }}" />
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge badge-sm badge-ghost">Unassigned</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 col-span-full">
                            No approved tickets
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Ongoing Column -->
        <div class="overflow-hidden bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 font-bold bg-primary text-primary-content">
                <span>Ongoing ({{ count($ongoingTickets) }})</span>
                <span class="badge badge-neutral">In Progress</span>
            </div>
            <div class="p-3 max-h-[50vh] overflow-y-auto" id="ongoing-column" x-data x-on:dragover.prevent
                x-on:drop="
                    $event.preventDefault();
                    const ticketId = $event.dataTransfer.getData('ticketId');
                    Livewire.emit('ticketMoved', ticketId, 'ongoing');
                 ">
                <div class="grid grid-cols-1 gap-3 ">
                    @forelse ($ongoingTickets as $ticket)
                        <div class="transition-shadow duration-200 shadow-md cursor-move card bg-base-100 hover:shadow-lg"
                            draggable="true"
                            x-on:dragstart="event.dataTransfer.setData('ticketId', {{ $ticket->id }})">
                            <div class="p-4 card-body">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <span class="text-xs font-medium opacity-70">#{{ $ticket->id }}</span>
                                        {!! $ticket->priority_badge !!}
                                        {!! $ticket->type_badge !!}
                                    </div>
                                    <div class="text-xs opacity-70">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <h3 class="mb-2 text-sm card-title">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-primary">
                                        {{ Str::limit($ticket->title, 50) }}
                                    </a>
                                </h3>

                                <p class="mb-2 text-xs text-gray-600 line-clamp-2">
                                    {{ Str::limit($ticket->description, 100) }}
                                </p>

                                <div class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100">
                                    <div class="flex items-center">
                                        <div class="avatar">
                                            <div class="w-6 rounded-full">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->reporter->name) }}&background=random"
                                                    alt="{{ $ticket->reporter->name }}" />
                                            </div>
                                        </div>
                                        <span class="ml-1 text-xs truncate">{{ $ticket->reporter->name }}</span>
                                    </div>

                                    @if ($ticket->assignee)
                                        <div class="flex items-center">
                                            <div class="avatar">
                                                <div class="w-6 rounded-full">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->assignee->name) }}&background=random"
                                                        alt="{{ $ticket->assignee->name }}" />
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge badge-sm badge-ghost">Unassigned</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 col-span-full">
                            No ongoing tickets
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Finished Column -->
        <div class="overflow-hidden bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 font-bold bg-success text-success-content">
                <span>Finished ({{ count($finishedTickets) }})</span>
                <span class="badge badge-neutral">Completed</span>
            </div>
            <div class="p-3 max-h-[50vh] overflow-y-auto" id="finished-column" x-data x-on:dragover.prevent
                x-on:drop="
                    $event.preventDefault();
                    const ticketId = $event.dataTransfer.getData('ticketId');
                    Livewire.emit('ticketMoved', ticketId, 'finished');
                 ">
                <div class="grid grid-cols-1 gap-3 ">
                    @forelse ($finishedTickets as $ticket)
                        <div class="transition-shadow duration-200 shadow-md cursor-move card bg-base-100 hover:shadow-lg"
                            draggable="true"
                            x-on:dragstart="event.dataTransfer.setData('ticketId', {{ $ticket->id }})">
                            <div class="p-4 card-body">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <span class="text-xs font-medium opacity-70">#{{ $ticket->id }}</span>
                                        {!! $ticket->priority_badge !!}
                                        {!! $ticket->type_badge !!}
                                    </div>
                                    <div class="text-xs opacity-70">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <h3 class="mb-2 text-sm card-title">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-primary">
                                        {{ Str::limit($ticket->title, 50) }}
                                    </a>
                                </h3>

                                <p class="mb-2 text-xs text-gray-600 line-clamp-2">
                                    {{ Str::limit($ticket->description, 100) }}
                                </p>

                                <div class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100">
                                    <div class="flex items-center">
                                        <div class="avatar">
                                            <div class="w-6 rounded-full">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->reporter->name) }}&background=random"
                                                    alt="{{ $ticket->reporter->name }}" />
                                            </div>
                                        </div>
                                        <span class="ml-1 text-xs truncate">{{ $ticket->reporter->name }}</span>
                                    </div>

                                    @if ($ticket->assignee)
                                        <div class="flex items-center">
                                            <div class="avatar">
                                                <div class="w-6 rounded-full">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->assignee->name) }}&background=random"
                                                        alt="{{ $ticket->assignee->name }}" />
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge badge-sm badge-ghost">Unassigned</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 col-span-full">
                            No finished tickets
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:load', function() {
            Livewire.on('alertSuccess', message => {
                // If you have a toast notification system
                if (typeof Toast !== 'undefined') {
                    Toast.fire({
                        icon: 'success',
                        title: message
                    });
                } else {
                    alert(message);
                }
            });
        });
    </script>
</div>
