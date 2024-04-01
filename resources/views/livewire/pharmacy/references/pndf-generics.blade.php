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
                <i class="mr-1 las la-map-marker la-lg"></i> Homis Drugs and Medicine
            </li>
        </ul>
    </div>
</x-slot>

<div class="flex flex-col py-5 mx-auto max-w-screen-2xl">
    <div class="flex justify-between">
        <div class="flex space-x-2">
            <label for="new_generic" class="btn btn-sm btn-primary">New Generic</label>
            <label for="new_drug" class="btn btn-sm btn-secondary">New Drug</label>
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
        <table class="table w-full table-compact table-zebra table-bordered">
            <thead>
                <tr>
                    <th>Major Category</th>
                    <th>SUB 1 Group Description</th>
                    <th>SUB 2 Group Description</th>
                    <th>SUB 3 Group Description</th>
                    <th>SUB 4 Group Description</th>
                    <th>Generic Name</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $group)
                    <tr>
                        <th>{{ $group->submajor->dmdesc }}</th>
                        <th>{{ $group->dms1key ? $group->sub1->dms1desc : ''}}</th>
                        <th>{{ $group->dms2key ? $group->sub2->dms2desc : ''}}</th>
                        <th>{{ $group->dms3key ? $group->sub3->dms3desc : ''}}</th>
                        <th>{{ $group->dms4key ? $group->sub4->dms4desc : ''}}</th>
                        <td>{{ $group->generic ? $group->generic->gendesc : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="3">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $groups->links() }}
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="new_generic" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box relative">
            <label for="new_generic" class="btn btn-sm btn-circle absolute right-2 top-2">✕</label>
            <h3 class="text-lg font-bold">Create new Generic</h3>
            <p class="space-y-2 flex flex-col">
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Generic Code</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="gencode" />
                    @error('gencode')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Generic Description</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="gendesc" />
                    @error('gendesc')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Rationale</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="rationale" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Recommendations</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="monitor" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Interactions</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="interactions" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Major Category</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model="selected_major">
                      <option></option>
                      @foreach ($majors as $major)
                        <option value="{{ $major->dmcode }}">{{ $major->dmdesc }}</option>
                      @endforeach
                    </select>
                    @error('major_category')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Sub 1 Group</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model="selected_sub1">
                      <option></option>
                      @foreach ($sub1 as $s1)
                        <option value="{{ $s1->dms1key }}">{{ $s1->dms1desc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Sub 2 Group</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model="selected_sub2">
                      <option></option>
                      @foreach ($sub2 as $s2)
                        <option value="{{ $s2->dms2key }}">{{ $s2->dms2desc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Sub 3 Group</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model="selected_sub3">
                      <option></option>
                      @foreach ($sub3 as $s3)
                        <option value="{{ $s3->dms3key }}">{{ $s3->dms3desc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Sub 4 Group</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model="selected_sub4">
                      <option></option>
                      @foreach ($sub4 as $s4)
                        <option value="{{ $s4->dms4key }}">{{ $s4->dms4desc }}</option>
                      @endforeach
                    </select>
                </label>

                <button class="btn btn-sm btn-primary ml-auto mt-3" wire:click="new_generic">Save</button>
            </p>
        </div>
    </div>


    {{-- <input type="checkbox" id="new_drug" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box relative">
            <label for="new_drug" class="btn btn-sm btn-circle absolute right-2 top-2">✕</label>
            <h3 class="text-lg font-bold">Create new Drug/Medicine</h3>
            <p class="space-y-2 flex flex-col">
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Drug Classification</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="dmdrxot" >
                      <option value="OTC" selected>Over The Counter</option>
                      <option value="RXX">With Prescription</option>
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Generic</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="grpcode">
                      <option disabled selected></option>
                      @foreach ($generics as $gen)
                        <option value="{{ $gen->gencode }}">{{ $gen->gendesc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Brand Name</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="brandname" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Strength</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="dmdnost" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Strength Description</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="strecode">
                      <option disabled selected></option>
                      @foreach ($strengths as $stre)
                        <option value="{{ $stre->strecode }}">{{ $stre->stredesc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Form</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="formcode">
                      <option disabled selected></option>
                      @foreach ($forms as $form)
                        <option value="{{ $form->formcode }}">{{ $form->formdesc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Route</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="rtecode">
                      <option disabled selected></option>
                      @foreach ($routes as $rte)
                        <option value="{{ $rte->rtecode }}">{{ $rte->rtedesc }}</option>
                      @endforeach
                    </select>
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">Remarks</span>
                    </div>
                    <input type="text" class="input input-bordered w-full" wire:model.defer="dmdrem" />
                </label>
                <label class="form-control w-full">
                    <div class="label">
                    <span class="label-text">PNDF</span>
                    </div>
                    <select class="select select-bordered w-full" wire:model.defer="dmdpndf" >
                      <option value="Y">YES</option>
                      <option value="N">NO</option>
                    </select>
                </label>

                <button class="btn btn-sm btn-primary ml-auto mt-3" wire:click="new_generic">Save</button>
            </p>
        </div>
    </div> --}}
</div>
