<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li>
                <i class="mr-1 las la-user-circle la-lg"></i> Patient Registration Form
            </li>
        </ul>
    </div>
</x-slot>

<div class="min-h-screen px-6 pt-6 space-y-2 max-w-screen xl:pr-2">
    <div class="flex justify-between px-6">
        <div class="inline-block align-middle">
            <span class="font-bold uppercase">
                Patient Registration Form
            </span>
        </div>
        <button class="btn btn-sm btn-primary" wire:click="submit_request"><i class="las la-lg la-save"></i>
            Save</button>
    </div>
    <div class="shadow-xl card bg-base-100">
        <div class="card-body">
            <div class="flex flex-col gap-2">
                <fieldset>
                    <div class="card-title">
                        <legend><i class="las la-lg la-address-card"></i> Patient Profile</legend>
                    </div>
                    <div class="grid grid-cols-12 space-x-0 space-y-2 lg:space-x-8 lg:space-y-0">
                        <div class="col-span-12 lg:col-span-4">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="hpercode">Health Record #</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('hpercode') is-invalid @enderror"
                                        placeholder="Health Record #" aria-label="Health Record #"
                                        aria-describedby="hpercode" wire:model.defer="hpercode">
                                </div>
                                @error('hpercode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patfirst">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patfirst') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name" aria-describedby="patfirst"
                                        wire:model.defer="patfirst">
                                </div>
                                @error('patfirst')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patmiddle">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patmiddle') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name" aria-describedby="patmiddle"
                                        wire:model.defer="patmiddle">
                                </div>
                                @error('patmiddle')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patlast">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patlast') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name" aria-describedby="patlast"
                                        wire:model.defer="patlast">
                                </div>
                                @error('patlast')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patsuffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial flex-initial @error('patsuffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix" aria-describedby="patsuffix"
                                        wire:model.defer="patsuffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('patsuffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="hspocode">Contact #</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('hspocode') is-invalid @enderror"
                                        placeholder="Contact #" aria-label="Contact #" aria-describedby="hspocode"
                                        wire:model.defer="hspocode">
                                </div>
                                @error('hspocode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patsex">Gender</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('patsex') is-invalid @enderror"
                                        aria-describedby="patsex" wire:model.defer="patsex">
                                        <option value="F">Female</option>
                                        <option value="M">Male</option>
                                    </select>
                                </div>
                                @error('patsex')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patbdate">Birth date</span>
                                    <input type="date"
                                        class="input input-sm input-bordered w-full @error('patbdate') is-invalid @enderror"
                                        placeholder="Birth date" aria-label="Birth date" aria-describedby="patbdate"
                                        wire:model="patbdate">
                                </div>
                                @error('patbdate')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patage">Age</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patage') is-invalid @enderror"
                                        aria-label="Age" aria-describedby="patage" wire:model="patage" readonly>
                                </div>
                                @error('patage')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patbplace">Birth Place</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patbplace') is-invalid @enderror"
                                        placeholder="Birth Place" aria-label="Birth Place"
                                        aria-describedby="patbplace" wire:model.defer="patbplace">
                                </div>
                                @error('patbplace')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patcstat">Civil Status</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('patcstat') is-invalid @enderror"
                                        aria-describedby="patcstat" wire:model.defer="patcstat">
                                        <option value="S">Single</option>
                                        <option value="M">Married</option>
                                        <option value="D">Divorced</option>
                                        <option value="X">Separated</option>
                                        <option value="W">Widow/Widower</option>
                                        <option value="N">Neonate</option>
                                    </select>
                                </div>
                                @error('patcstat')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patempstat">Employment Status</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('patempstat') is-invalid @enderror"
                                        aria-describedby="patempstat" wire:model.defer="patempstat">
                                        <option value="UNEMP">Unemployed</option>
                                        <option value="EMPLO">Employed</option>
                                        <option value="SELFE">Self-Employed</option>
                                    </select>
                                </div>
                                @error('patempstat')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="natcode">Nationality</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('natcode') is-invalid @enderror"
                                        aria-describedby="natcode" wire:model.defer="natcode">
                                        <option value="FILIP">Filipino</option>
                                        <option value="AMERI">American</option>
                                        <option value="SPANI">Spanish</option>
                                        <option value="CHINE">Chinese</option>
                                        <option value="JAPAN">Japanese</option>
                                        <option value="UNKNO">Unknown</option>
                                        <option value="GERMN">German</option>
                                        <option value="BANGD">Bangladesh</option>
                                        <option value="BRITS">British</option>
                                        <option value="ENGLS">English</option>
                                        <option value="FRNCH">French</option>
                                        <option value="CANAD">Canadian</option>
                                    </select>
                                </div>
                                @error('natcode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="relcode">Religion</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('relcode') is-invalid @enderror"
                                        aria-describedby="relcode" wire:model.defer="relcode">
                                        <option value="">N/A</option>
                                        @foreach ($religions as $rel)
                                            <option value="{{ $rel->relcode }}">{{ $rel->reldesc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('relcode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <button class="mt-3 btn btn-sm btn-secondary" wire:click='check_record'>Check
                                    Record</button>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-3">
                </fieldset>
                <fieldset>
                    <div class="card-title">
                        <legend><i class="las la-lg la-map"></i> Patient Address</legend>
                    </div>
                    <div class="grid grid-cols-12 space-x-8">
                        <div class="col-span-12 lg:col-span-3">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="patstr">No and Street</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('patstr') is-invalid @enderror"
                                        placeholder="No and Street" aria-label="No and Street"
                                        aria-describedby="patstr" wire:model.defer="patstr">
                                </div>
                                @error('patstr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="provcode">Province</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('provcode') is-invalid @enderror"
                                        aria-describedby="provcode" wire:model="provcode">
                                        @foreach ($provinces as $prov)
                                            <option value="{{ $prov->provcode }}">{{ $prov->provname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('provcode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="ctycode">City/Mun.</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('ctycode') is-invalid @enderror"
                                        aria-describedby="ctycode" wire:model="ctycode">
                                        <option value=""></option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->ctycode }}">{{ $city->ctyzipcode }} -
                                                {{ $city->ctyname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('ctycode')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="brg">Barangay</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('brg') is-invalid @enderror"
                                        aria-describedby="brg" wire:model="brg">
                                        <option value=""></option>
                                        @foreach ($barangays as $brgy)
                                            <option value="{{ $brgy->bgycode }}">{{ $brgy->bgyname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('brg')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <hr class="mt-3">
                </fieldset>
                <div class="grid grid-cols-12 space-x-0 space-y-2 lg:space-x-10 lg:space-y-0">
                    <div class="flex flex-col col-span-12 gap-3 lg:col-span-6">
                        <fieldset>
                            <div class="card-title">
                                <legend>Maiden Name</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="maiden_firstname">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('maiden_firstname') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name"
                                        aria-describedby="maiden_firstname" wire:model.defer="maiden_firstname">
                                </div>
                                @error('maiden_firstname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="maiden_midname">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('maiden_midname') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name"
                                        aria-describedby="maiden_midname" wire:model.defer="maiden_midname">
                                </div>
                                @error('maiden_midname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="maiden_lastname">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('maiden_lastname') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name"
                                        aria-describedby="maiden_lastname" wire:model.defer="maiden_lastname">
                                </div>
                                @error('maiden_lastname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="maiden_suffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial @error('maiden_suffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix" aria-describedby="maiden_suffix"
                                        wire:model.defer="maiden_suffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('maiden_suffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="card-title">
                                <legend>Mother's Maiden Name</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="moth_maiden_firstname">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('moth_maiden_firstname') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name"
                                        aria-describedby="moth_maiden_firstname"
                                        wire:model.defer="moth_maiden_firstname">
                                </div>
                                @error('moth_maiden_firstname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="moth_maiden_midname">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('moth_maiden_midname') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name"
                                        aria-describedby="moth_maiden_midname" wire:model.defer="moth_maiden_midname">
                                </div>
                                @error('moth_maiden_midname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="moth_maiden_lastname">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('moth_maiden_lastname') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name"
                                        aria-describedby="moth_maiden_lastname"
                                        wire:model.defer="moth_maiden_lastname">
                                </div>
                                @error('moth_maiden_lastname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="moth_maiden_suffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial @error('moth_maiden_suffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix"
                                        aria-describedby="moth_maiden_suffix" wire:model.defer="moth_maiden_suffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('moth_maiden_suffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="card-title">
                                <legend>Mother's Information</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motfirst">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motfirst') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name" aria-describedby="motfirst"
                                        wire:model.defer="motfirst">
                                </div>
                                @error('motfirst')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motmid">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motmid') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name" aria-describedby="motmid"
                                        wire:model.defer="motmid">
                                </div>
                                @error('motmid')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motlast">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motlast') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name" aria-describedby="motlast"
                                        wire:model.defer="motlast">
                                </div>
                                @error('motlast')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motsuffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial @error('motsuffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix" aria-describedby="motsuffix"
                                        wire:model.defer="motsuffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('motsuffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motaddr">Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motaddr') is-invalid @enderror"
                                        placeholder="Address" aria-label="Address" aria-describedby="motaddr"
                                        wire:model.defer="motaddr">
                                </div>
                                @error('motaddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="mottel">Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('mottel') is-invalid @enderror"
                                        placeholder="Telephone No" aria-label="Telephone No"
                                        aria-describedby="mottel" wire:model.defer="mottel">
                                </div>
                                @error('mottel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motempname">Employer Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motempname') is-invalid @enderror"
                                        placeholder="Employer Name" aria-label="Employer Name"
                                        aria-describedby="motempname" wire:model.defer="motempname">
                                </div>
                                @error('motempname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motempaddr">Employer Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motempaddr') is-invalid @enderror"
                                        placeholder="Employer Address" aria-label="Employer Address"
                                        aria-describedby="motempaddr" wire:model.defer="motempaddr">
                                </div>
                                @error('motempaddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motempeml">Employer Email Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motempeml') is-invalid @enderror"
                                        placeholder="Employer Email Address" aria-label="Employer Email Address"
                                        aria-describedby="motempeml" wire:model.defer="motempeml">
                                </div>
                                @error('motempeml')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="motemptel">Employer Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('motemptel') is-invalid @enderror"
                                        placeholder="Employer Telephone No" aria-label="Employer Telephone No"
                                        aria-describedby="motemptel" wire:model.defer="motemptel">
                                </div>
                                @error('motemptel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                    </div>
                    <div class="flex flex-col col-span-12 gap-3 lg:col-span-6">
                        <fieldset>
                            <div class="card-title">
                                <legend>Spouse's Information</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spfirst">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spfirst') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name" aria-describedby="spfirst"
                                        wire:model.defer="spfirst">
                                </div>
                                @error('spfirst')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spmid">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spmid') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name" aria-describedby="spmid"
                                        wire:model.defer="spmid">
                                </div>
                                @error('spmid')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="splast">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('splast') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name" aria-describedby="splast"
                                        wire:model.defer="splast">
                                </div>
                                @error('splast')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spsuffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial @error('spsuffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix" aria-describedby="spsuffix"
                                        wire:model.defer="spsuffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('spsuffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spaddr">Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spaddr') is-invalid @enderror"
                                        placeholder="Address" aria-label="Address" aria-describedby="spaddr"
                                        wire:model.defer="spaddr">
                                </div>
                                @error('spaddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="sptel">Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('sptel') is-invalid @enderror"
                                        placeholder="Telephone No" aria-label="Telephone No" aria-describedby="sptel"
                                        wire:model.defer="sptel">
                                </div>
                                @error('sptel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spempname">Employer Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spempname') is-invalid @enderror"
                                        placeholder="Employer Name" aria-label="Employer Name"
                                        aria-describedby="spempname" wire:model.defer="spempname">
                                </div>
                                @error('spempname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spempaddr">Employer Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spempaddr') is-invalid @enderror"
                                        placeholder="Employer Address" aria-label="Employer Address"
                                        aria-describedby="spempaddr" wire:model.defer="spempaddr">
                                </div>
                                @error('spempaddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spempeml">Employer Email Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spempeml') is-invalid @enderror"
                                        placeholder="Employer Email Address" aria-label="Employer Email Address"
                                        aria-describedby="spempeml" wire:model.defer="spempeml">
                                </div>
                                @error('spempeml')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="spemptel">Employer Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('spemptel') is-invalid @enderror"
                                        placeholder="Employer Telephone No" aria-label="Employer Telephone No"
                                        aria-describedby="spemptel" wire:model.defer="spemptel">
                                </div>
                                @error('spemptel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="card-title">
                                <legend>Father's Information</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatfirst">First Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatfirst') is-invalid @enderror"
                                        placeholder="First Name" aria-label="First Name" aria-describedby="fatfirst"
                                        wire:model.defer="fatfirst">
                                </div>
                                @error('fatfirst')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatmid">Middle Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatmid') is-invalid @enderror"
                                        placeholder="Middle Name" aria-label="Middle Name" aria-describedby="fatmid"
                                        wire:model.defer="fatmid">
                                </div>
                                @error('fatmid')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatlast">Last Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatlast') is-invalid @enderror"
                                        placeholder="Last Name" aria-label="Last Name" aria-describedby="fatlast"
                                        wire:model.defer="fatlast">
                                </div>
                                @error('fatlast')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatsuffix">Suffix</span>
                                    <select type="text"
                                        class="select select-sm select-bordered w-full flex-initial @error('fatsuffix') is-invalid @enderror"
                                        placeholder="Suffix" aria-label="Suffix" aria-describedby="fatsuffix"
                                        wire:model.defer="fatsuffix">
                                        <option value="">N/A</option>
                                        <option value="Jr">Jr.</option>
                                        <option value="Sr">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                        <option value="VI">VI</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                        <option value="X">X</option>
                                    </select>
                                </div>
                                @error('fatsuffix')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fataddr">Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fataddr') is-invalid @enderror"
                                        placeholder="Address" aria-label="Address" aria-describedby="fataddr"
                                        wire:model.defer="fataddr">
                                </div>
                                @error('fataddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fattel">Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fattel') is-invalid @enderror"
                                        placeholder="Telephone No" aria-label="Telephone No"
                                        aria-describedby="fattel" wire:model.defer="fattel">
                                </div>
                                @error('fattel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatempname">Employer Name</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatempname') is-invalid @enderror"
                                        placeholder="Employer Name" aria-label="Employer Name"
                                        aria-describedby="fatempname" wire:model.defer="fatempname">
                                </div>
                                @error('fatempname')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatempaddr">Employer Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatempaddr') is-invalid @enderror"
                                        placeholder="Employer Address" aria-label="Employer Address"
                                        aria-describedby="fatempaddr" wire:model.defer="fatempaddr">
                                </div>
                                @error('fatempaddr')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatempeml">Employer Email Address</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatempeml') is-invalid @enderror"
                                        placeholder="Employer Email Address" aria-label="Employer Email Address"
                                        aria-describedby="fatempeml" wire:model.defer="fatempeml">
                                </div>
                                @error('fatempeml')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fatemptel">Employer Telephone No</span>
                                    <input type="text"
                                        class="input input-sm input-bordered w-full @error('fatemptel') is-invalid @enderror"
                                        placeholder="Employer Telephone No" aria-label="Employer Telephone No"
                                        aria-describedby="fatemptel" wire:model.defer="fatemptel">
                                </div>
                                @error('fatemptel')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="card-title">
                                <legend>Other Information</legend>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="s_dec">Spouse Deceased</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('s_dec') is-invalid @enderror"
                                        aria-describedby="s_dec" wire:model.defer="s_dec">
                                        <option value="N">N</option>
                                        <option value="Y">Y</option>
                                    </select>
                                </div>
                                @error('s_dec')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="f_dec">Father Deceased</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('f_dec') is-invalid @enderror"
                                        aria-describedby="f_dec" wire:model.defer="f_dec">
                                        <option value="N">N</option>
                                        <option value="Y">Y</option>
                                    </select>
                                </div>
                                @error('f_dec')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror

                                <div class="w-full input-group">
                                    <span class="whitespace-nowrap" id="fmdec">Mother Deceased</span>
                                    <select
                                        class="select select-sm select-bordered w-full flex-initial @error('fmdec') is-invalid @enderror"
                                        aria-describedby="fmdec" wire:model.defer="fmdec">
                                        <option value="N">N</option>
                                        <option value="Y">Y</option>
                                    </select>
                                </div>
                                @error('fmdec')
                                    <small class="text-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
