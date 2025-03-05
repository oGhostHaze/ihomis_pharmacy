<div>
    <form wire:submit.prevent="save" class="p-6 bg-white rounded-lg shadow-md">
        <div class="mb-6">
            <label for="title" class="block mb-1 text-sm font-medium text-gray-700">Title</label>
            <input type="text" id="title" wire:model="title" class="w-full input input-bordered"
                placeholder="Enter ticket title">
            @error('title')
                <span class="text-sm text-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
            <div>
                <label for="priority" class="block mb-1 text-sm font-medium text-gray-700">Priority</label>
                <select id="priority" wire:model="priority" class="w-full select select-bordered">
                    @foreach ($priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')
                    <span class="text-sm text-error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="type" class="block mb-1 text-sm font-medium text-gray-700">Type</label>
                <select id="type" wire:model="type" class="w-full select select-bordered">
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <span class="text-sm text-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="description" class="block mb-1 text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" wire:model="description" rows="6" class="w-full textarea textarea-bordered"
                placeholder="Detailed description of the issue or feature request"></textarea>
            @error('description')
                <span class="text-sm text-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-6">
            <label for="attachments" class="block mb-1 text-sm font-medium text-gray-700">Attachments</label>
            <input type="file" wire:model="attachments" multiple class="w-full file-input file-input-bordered" />
            <div wire:loading wire:target="attachments" class="mt-2 text-sm text-gray-500">
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
                            <li>{{ $attachment->getClientOriginalName() }} ({{ round($attachment->getSize() / 1024) }}
                                KB)</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="flex justify-between">
            <a href="{{ route('tickets.index') }}" class="btn btn-ghost">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                {{ $ticketId ? 'Update Ticket' : 'Create Ticket' }}
            </button>
        </div>
    </form>
</div>
