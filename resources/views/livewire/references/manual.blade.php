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
                <i class="mr-1 las la-book la-lg"></i> Manual
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col h-full min-h-screen py-5 mx-auto space-y-3 max-w-screen-2xl">
    <div class="flex space-x-2">
        <a  rel="noopener noreferrer" href="{{ route('ref.manual.add') }}" class="btn btn-sm btn-primary"><i class="mr-2 las la-lg la-plus"></i>
            Add</a>
    </div>
    <div class="flex flex-col w-full min-h-full p-5 space-y-5 bg-neutral">
        @forelse ($manuals as $manual)
            @php
                $photos = explode(',', $manual->photos);
                $cover = $photos[0];

            @endphp
            <div class="shadow-xl min-h-60 card card-side bg-base-100">
                <figure class="cursor-pointer h-60" wire:click="$set('view_img', '{{ $cover }}')"
                    onclick="$('#my-modal-4').click()"><img src="{{ asset('storage/' . $cover) }}" class="w-96"
                        alt="Movie" /></figure>
                <div class="card-body">
                    <h2 class="card-title">{{ $manual->title }}</h2>
                    <p>{!! $manual->description !!}</p>
                    <div class="justify-end card-actions">
                        <button class="btn btn-primary">View</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="shadow-xl card card-side bg-base-100">
                <div class="card-body">
                    <h2 class="card-title">No record found</h2>
                    <a  rel="noopener noreferrer" href="{{ route('ref.manual.add') }}" class="btn btn-sm btn-primary"><i
                            class="mr-2 las la-lg la-plus"></i>
                        Add</a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="my-modal-4" class="modal-toggle" />
    <label for="my-modal-4" class="cursor-pointer modal">
        <label class="relative w-11/12 max-w-5xl modal-box" for="">
            <img class="mx-auto" src="{{ asset('storage/' . $view_img) }}" />
        </label>
    </label>
</div>
