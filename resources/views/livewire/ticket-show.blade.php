<div>
    <div class="flex flex-wrap items-center justify-between mb-4">
        <div class="flex flex-wrap items-center">
            <a href="{{ route('tickets.index') }}" class="btn btn-ghost btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
                Back to Tickets
            </a>

            <h1 class="ml-4 text-xl font-bold">
                Ticket #{{ $ticket->id }}: {{ $ticket->title }}
            </h1>
        </div>

        <div class="flex mt-2 space-x-2 md:mt-0">
            @if (auth()->user()->can('edit-tickets') || $ticket->reporter_id === auth()->id())
                <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-sm btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="md:col-span-2">
            <!-- Ticket Details -->
            <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="prose max-w-none">
                        {!! nl2br(e($ticket->description)) !!}
                    </div>

                    @if ($ticket->attachments->count() > 0)
                        <div class="pt-4 mt-6 border-t">
                            <h3 class="mb-2 text-lg font-medium">Attachments</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                @foreach ($ticket->attachments as $attachment)
                                    <div class="flex items-center p-3 border rounded-lg">
                                        <div class="flex-shrink-0 mr-3">
                                            @if ($attachment->is_image)
                                                <img src="{{ Storage::url($attachment->path) }}"
                                                    class="object-cover w-10 h-10 rounded"
                                                    alt="{{ $attachment->filename }}">
                                            @else
                                                <div
                                                    class="flex items-center justify-center w-10 h-10 bg-gray-100 rounded">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $attachment->filename }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $attachment->human_size }}
                                            </p>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Submit Comment
                                            </button>
                                        </div>
                                        </form>
                                    </div>
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <!-- Ticket Info Card -->
                            <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
                                <div class="p-4 border-b">
                                    <h3 class="text-lg font-medium">Ticket Information</h3>
                                </div>
                                <div class="p-4">
                                    <dl class="divide-y">
                                        <div class="flex justify-between py-2">
                                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                                            <dd class="text-sm text-right">{!! $ticket->status_badge !!}</dd>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                            <dd class="text-sm text-right">{!! $ticket->priority_badge !!}</dd>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                                            <dd class="text-sm text-right">{!! $ticket->type_badge !!}</dd>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                                            <dd class="text-sm text-right">
                                                {{ $ticket->created_at->format('M d, Y \a\t g:i a') }}</dd>
                                        </div>
                                        @if ($ticket->closed_at)
                                            <div class="flex justify-between py-2">
                                                <dt class="text-sm font-medium text-gray-500">Closed</dt>
                                                <dd class="text-sm text-right">
                                                    {{ $ticket->closed_at->format('M d, Y \a\t g:i a') }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            </div>

                            <!-- Reporter & Assignee Card -->
                            <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
                                <div class="p-4 border-b">
                                    <h3 class="text-lg font-medium">People</h3>
                                </div>
                                <div class="p-4">
                                    <dl class="divide-y">
                                        <div class="flex items-center py-3">
                                            <dt class="w-20 text-sm font-medium text-gray-500">Reporter</dt>
                                            <dd class="flex-1 text-sm">
                                                <div class="flex items-center">
                                                    <div class="mr-2 avatar">
                                                        <div class="w-8 rounded-full">
                                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->reporter->name) }}&background=random"
                                                                alt="{{ $ticket->reporter->name }}" />
                                                        </div>
                                                    </div>
                                                    <span>{{ $ticket->reporter->name }}</span>
                                                </div>
                                            </dd>
                                        </div>
                                        <div class="flex items-center py-3">
                                            <dt class="w-20 text-sm font-medium text-gray-500">Assignee</dt>
                                            <dd class="flex-1 text-sm">
                                                @if ($ticket->assignee)
                                                    <div class="flex items-center">
                                                        <div class="mr-2 avatar">
                                                            <div class="w-8 rounded-full">
                                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->assignee->name) }}&background=random"
                                                                    alt="{{ $ticket->assignee->name }}" />
                                                            </div>
                                                        </div>
                                                        <span>{{ $ticket->assignee->name }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-gray-500">Unassigned</span>
                                                @endif

                                                @if (auth()->user()->can('manage-tickets'))
                                                    <button wire:click="openAssignModal"
                                                        class="mt-1 btn btn-ghost btn-xs">
                                                        {{ $ticket->assignee ? 'Change' : 'Assign' }}
                                                    </button>
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                </div>

                <!-- Assign Modal -->
                @if ($showAssignModal)
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true">
                        <div
                            class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true">
                            </div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                aria-hidden="true">&#8203;</span>

                            <div
                                class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                                Assign Ticket
                                            </h3>
                                            <div class="mt-4">
                                                <label for="assignee"
                                                    class="block mb-1 text-sm font-medium text-gray-700">Assignee</label>
                                                <select id="assignee" wire:model="assigneeId"
                                                    class="w-full select select-bordered">
                                                    <option value="">Unassigned</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="button" wire:click="assignTicket" class="btn btn-primary btn-sm">
                                        Assign
                                    </button>
                                    <button type="button" wire:click="$set('showAssignModal', false)"
                                        class="mr-2 btn btn-ghost btn-sm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

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
            <a href="{{ Storage::url($attachment->path) }}" target="_blank"
                class="text-sm text-primary hover:underline">
                View
            </a>
        </div>
    </div>
    @endforeach
</div>
</div>
@endif
</div>
</div>

<!-- Status Actions -->
@if (auth()->user()->can('manage-tickets'))
    <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
        <div class="p-4">
            <h3 class="mb-3 text-lg font-medium">Actions</h3>
            <div class="flex flex-wrap gap-2">
                <button wire:click="updateStatus('pending')"
                    class="btn btn-sm {{ $ticket->status === 'pending' ? 'btn-warning' : 'btn-outline' }}">
                    Pending
                </button>
                <button wire:click="updateStatus('approved')"
                    class="btn btn-sm {{ $ticket->status === 'approved' ? 'btn-info' : 'btn-outline' }}">
                    Approved
                </button>
                <button wire:click="updateStatus('ongoing')"
                    class="btn btn-sm {{ $ticket->status === 'ongoing' ? 'btn-primary' : 'btn-outline' }}">
                    Ongoing
                </button>
                <button wire:click="updateStatus('finished')"
                    class="btn btn-sm {{ $ticket->status === 'finished' ? 'btn-success' : 'btn-outline' }}">
                    Finished
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Comments -->
<div class="mb-6 overflow-hidden bg-white rounded-lg shadow-md">
    <div class="p-4 border-b">
        <h3 class="text-lg font-medium">Comments</h3>
    </div>
    <div class="divide-y">
        @forelse ($ticket->comments as $comment)
            <div class="p-4 {{ $comment->is_system ? 'bg-gray-50' : '' }}">
                <div class="flex space-x-3">
                    <div class="flex-shrink-0">
                        <div class="avatar">
                            <div class="w-10 rounded-full">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=random"
                                    alt="{{ $comment->user->name }}" />
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $comment->user->name }}
                            @if ($comment->is_system)
                                <span class="ml-2 badge badge-sm">System</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ $comment->created_at->format('M d, Y \a\t g:i a') }}
                        </p>

                        <div class="mt-2 text-sm text-gray-700">
                            {!! nl2br(e($comment->comment)) !!}
                        </div>

                        @if ($comment->attachments->count() > 0)
                            <div class="mt-2">
                                <p class="text-sm font-medium text-gray-700">Attachments:</p>
                                <div class="grid grid-cols-1 gap-2 mt-1 sm:grid-cols-2">
                                    @foreach ($comment->attachments as $attachment)
                                        <div class="flex items-center p-2 text-sm border rounded">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1 text-gray-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <a href="{{ Storage::url($attachment->path) }}" target="_blank"
                                                class="truncate hover:underline">
                                                {{ $attachment->filename }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-4 text-center text-gray-500">
                No comments yet
            </div>
        @endforelse
    </div>

    <!-- Add Comment Form -->
    <div class="p-4 bg-gray-50">
        <form wire:submit.prevent="addComment">
            <div class="mb-3">
                <label for="comment" class="block mb-1 text-sm font-medium text-gray-700">Add Comment</label>
                <textarea id="comment" wire:model="comment" rows="3" class="w-full textarea textarea-bordered"
                    placeholder="Add your comment here..."></textarea>
                @error('comment')
                    <span class="text-sm text-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="comment-attachments" class="block mb-1 text-sm font-medium text-gray-700">Attachments
                    (optional)</label>
                <input type="file" id="comment-attachments" wire:model="attachments" multiple
                    class="w-full file-input file-input-bordered" />
                <div wire:loading wire:target="attachments" class="mt-1 text-sm text-gray-500">
                    Uploading...
                </div>
                @error('attachments.*')
                    <span class="text-sm text-error">{{ $message }}</span>
                @enderror

                @if (count($attachments) > 0)
                    <div class="mt-2">
                        <p class="text-sm font-medium text-gray-700">Selected files:</p>
                        <ul class="mt-1 text-sm text-gray-500">
                            @foreach ($attachments as $attachment)
                                <li>{{ $attachment->getClientOriginalName() }}
                                    ({{ round($attachment->getSize() / 1024) }} KB)</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div>
