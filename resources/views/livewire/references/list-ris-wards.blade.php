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
                <i class="mr-1 las la-map-marker la-lg"></i> RIS Ward Location
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div>
            <button class="btn btn-sm btn-primary" onclick="location_modal()">Add New</button>
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
    <div class="flex flex-col justify-center w-full mt-2 overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ward Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr class="cursor-pointer"
                        onclick="location_modal('Update', '{{ $location->id }}', '{{ $location->ward_name }}')">
                        <th>{{ $location->id }}</th>
                        <td>{{ $location->ward_name }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="3">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $locations->links() }}
    </div>
</div>

@push('scripts')
    <script>
        function location_modal(type = 'Create New', loc_id = null, loc_desc = null) {
            Swal.fire({
                html: `
                    <span class="text-xl font-bold"> ` + type + ` Location </span>
                    <div class="w-full form-control">
                        <label class="label" for="ward_name">
                            <span class="label-text">Ward Name</span>
                        </label>
                        <input id="ward_name" type="text" class="w-full input input-bordered" />
                    </div>`,
                showCancelButton: true,
                confirmButtonText: `Save`,
                didOpen: () => {
                    const ward_name = Swal.getHtmlContainer().querySelector('#ward_name');
                    ward_name.value = loc_desc;

                }
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    @this.set('ward_name', ward_name.value);
                    Livewire.emit('save', loc_id)
                }
            });
        }
    </script>
@endpush
