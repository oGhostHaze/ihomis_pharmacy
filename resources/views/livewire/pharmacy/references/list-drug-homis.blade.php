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
                    <th>Combination</th>
                    <th>Counter</th>
                    <th>Generic</th>
                    <th>Strength</th>
                    <th>Form</th>
                    <th>Route</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drugs as $drug)
                    <tr>
                        <th>{{ $drug->dmdcomb }}</th>
                        <th>{{ $drug->dmdctr }}</th>
                        <td>{{ $drug->generic->gendesc }}</td>
                        <td>{{ $drug->dmdnost . ' ' . $drug->strecode }}</td>
                        <td>{{ $drug->formcode }}</td>
                        <td>{{ $drug->rtecode }}</td>
                        <td>{{ $drug->dmdrem }}</td>
                    </tr>
                @empty
                    <tr>
                        <th class="text-center" colspan="3">No record found!</th>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $drugs->links() }}
    </div>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="new_generic" class="modal-toggle" />
    <div class="modal">
        <div class="relative modal-box">
            <label for="new_generic" class="absolute btn btn-sm btn-circle right-2 top-2">✕</label>
            <h3 class="text-lg font-bold">Create new Generic</h3>
            <p class="flex flex-col space-y-2">
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Generic Code</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="gencode" />
                    @error('gencode')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Generic Description</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="gendesc" />
                    @error('gendesc')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Rationale</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="rationale" />
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Recommendations</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="monitor" />
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Interactions</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="interactions" />
                </label>

                <button class="mt-3 ml-auto btn btn-sm btn-primary" wire:click="new_generic">Save</button>
            </p>
        </div>
    </div>


    <input type="checkbox" id="new_drug" class="modal-toggle" />
    <div class="modal">
        <div class="relative modal-box">
            <label for="new_drug" class="absolute btn btn-sm btn-circle right-2 top-2">✕</label>
            <h3 class="text-lg font-bold">Create new Drug/Medicine</h3>
            <p class="flex flex-col space-y-2">
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Drug Classification</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="dmdrxot">
                        <option value="OTC" selected>Over The Counter</option>
                        <option value="RXX">With Prescription</option>
                    </select>
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Generic</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="grpcode">
                        <option disabled selected></option>
                        @foreach ($generics as $gen)
                            <option value="{{ $gen->gencode }}">{{ $gen->gendesc }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Brand Name</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="brandname" />
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Strength</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="dmdnost" />
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Strength Description</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="strecode">
                        <option disabled selected></option>
                        @foreach ($strengths as $stre)
                            <option value="{{ $stre->strecode }}">{{ $stre->stredesc }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Form</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="formcode">
                        <option disabled selected></option>
                        @foreach ($forms as $form)
                            <option value="{{ $form->formcode }}">{{ $form->formdesc }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Route</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="rtecode">
                        <option disabled selected></option>
                        @foreach ($routes as $rte)
                            <option value="{{ $rte->rtecode }}">{{ $rte->rtedesc }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">Remarks</span>
                    </div>
                    <input type="text" class="w-full input input-bordered" wire:model.defer="dmdrem" />
                </label>
                <label class="w-full form-control">
                    <div class="label">
                        <span class="label-text">PNDF</span>
                    </div>
                    <select class="w-full select select-bordered" wire:model.defer="dmdpndf">
                        <option value="Y">YES</option>
                        <option value="N">NO</option>
                    </select>
                </label>

                <button class="mt-3 ml-auto btn btn-sm btn-primary" wire:click="new_generic">Save</button>
            </p>
        </div>
    </div>
</div>
