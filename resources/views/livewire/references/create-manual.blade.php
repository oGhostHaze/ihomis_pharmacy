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
                <i class="mr-1 las la-book la-lg"></i> New Manual
            </li>
        </ul>
    </div>
</x-slot>

@push('head')
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.0/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.0/dist/trix.umd.min.js"></script>
@endpush

<div class="flex flex-col h-screen py-5 mx-auto space-y-3 max-w-screen-2xl">
    <div class="w-full h-full p-5 rounded shadow-md bg-neutral">
        <div class="flex flex-col w-full h-full p-3 space-y-3 bg-base-100 rounded-xl">
            <h2 class="font-bold">Add new item</h2>
            <div class="w-full form-control">
                <label class="label">
                    <span class="label-text">Title</span>
                </label>
                <input type="text" class="w-full input input-bordered input-sm" wire:model.defer="title">
                @error('title')
                    <label class="label">
                        <span class="text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>
            <div class="w-full form-control">
                <label class="label">
                    <span class="label-text">Description</span>
                </label>
                <input id="x" class="hidden" name="content">
                <trix-editor input="x" wire:ignore></trix-editor>
            </div>
            <div class="w-full form-control">
                <label class="label">
                    <span class="label-text">Photo/s</span>
                </label>
                <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                    x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <!-- File Input -->
                    <input type="file" class="w-full file-input file-input-bordered file-input-sm"
                        wire:model.defer="photos" multiple />

                    <!-- Progress Bar -->
                    <div x-show="isUploading">
                        <progress class="w-full progress progress-success" max="100"
                            x-bind:value="progress"></progress>
                    </div>
                </div>
                @error('photos.*')
                    <label class="label">
                        <span class="text-error">{{ $message }}</span>
                    </label>
                @enderror
                @error('photos')
                    <label class="label">
                        <span class="text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>
            <div class="flex justify-end">
                <button class="btn btn-primary" onclick="save()">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function save() {
            @this.description = $('#x').val();
            Livewire.emit('save');
        }
    </script>
@endpush
