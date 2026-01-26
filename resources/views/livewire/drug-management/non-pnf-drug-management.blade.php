<div class="container px-4 py-6 mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Non-PNF Drug Management</h1>
        <p class="mt-1 text-gray-600">Manage non-Philippine National Formulary drugs and medicines</p>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Filters and Actions --}}
    <div class="p-4 mb-6 bg-white rounded-lg shadow-sm">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div class="flex flex-col flex-1 w-full gap-4 md:flex-row md:w-auto">
                {{-- Search --}}
                <div class="flex-1 min-w-0">
                    <input type="text" wire:model.debounce.300ms="search"
                        placeholder="Search by medicine name, dose, or unit..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Filter by Status --}}
                <select wire:model="filterActive"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>

                {{-- Per Page --}}
                <select wire:model="perPage"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10 per page</option>
                    <option value="15">15 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                <button wire:click="openImportModal"
                    class="px-4 py-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="mr-2 fas fa-file-import"></i>Import Excel
                </button>
                <button wire:click="create"
                    class="px-4 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-plus"></i>Add New Drug
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden bg-white rounded-lg shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Medicine Name</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Dose
                        </th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Unit
                        </th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Status</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Remarks</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($drugs as $drug)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $drug->medicine_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $drug->dose ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $drug->unit ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleStatus({{ $drug->id }})" class="inline-flex items-center">
                                    @if ($drug->is_active)
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">Active</span>
                                    @else
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-red-800 bg-red-100 rounded-full">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs text-sm text-gray-500 truncate">{{ $drug->remarks ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                <button wire:click="edit({{ $drug->id }})"
                                    class="mr-3 text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button wire:click="confirmDelete({{ $drug->id }})"
                                    class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="mb-2 text-4xl fas fa-inbox"></i>
                                <p class="text-lg">No drugs found</p>
                                <p class="text-sm">Try adjusting your search or filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $drugs->links() }}
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="closeModal"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal panel --}}
                <div
                    class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="save">
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">{{ $editMode ? 'Edit' : 'Add New' }}
                                Non-PNF Drug</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Medicine Name <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" wire:model="medicine_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter medicine name">
                                    @error('medicine_name')
                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Dose</label>
                                    <input type="text" wire:model="dose"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., 100mg/4ml">
                                    @error('dose')
                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Unit</label>
                                    <input type="text" wire:model="unit"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., tablet, vial">
                                    @error('unit')
                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="is_active"
                                            class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">Active</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea wire:model="remarks" rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Additional notes"></textarea>
                                    @error('remarks')
                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $editMode ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" wire:click="closeModal"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Import Modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="closeImportModal">
                </div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="import">
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Import Non-PNF Drugs from Excel</h3>
                                <p class="mt-1 text-sm text-gray-500">Upload an Excel file (.xlsx, .xls, or .csv)</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">Select File <span
                                            class="text-red-500">*</span></label>
                                    <label
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="mb-2 text-4xl text-gray-400 fas fa-cloud-upload-alt"></i>
                                            <p class="text-sm text-gray-500"><span class="font-semibold">Click to
                                                    upload</span> or drag and drop</p>
                                            <p class="text-xs text-gray-500">Excel files up to 2MB</p>
                                        </div>
                                        <input type="file" wire:model="importFile" class="hidden"
                                            accept=".xlsx,.xls,.csv">
                                    </label>
                                    @if ($importFile)
                                        <p class="mt-2 text-sm text-green-600"><i class="fas fa-check-circle"></i>
                                            {{ $importFile->getClientOriginalName() }}</p>
                                    @endif
                                    @error('importFile')
                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                                    <h4 class="mb-2 text-sm font-medium text-blue-800"><i
                                            class="fas fa-info-circle"></i> File Format:</h4>
                                    <ul class="space-y-1 text-xs text-blue-700 list-disc list-inside">
                                        <li>Headers: <strong>LIST OF MEDICINES</strong>, <strong>Dose</strong>,
                                            <strong>Unit</strong>
                                        </li>
                                        <li>Medicine name is required</li>
                                        <li>Duplicates will be updated</li>
                                    </ul>
                                </div>

                                @if ($importSummary)
                                    <div class="p-4 border border-green-200 rounded-lg bg-green-50">
                                        <h4 class="mb-2 text-sm font-medium text-green-800"><i
                                                class="fas fa-check-circle"></i> Import Summary:</h4>
                                        <div class="grid grid-cols-2 gap-2 text-xs text-green-700">
                                            <div>New: <strong>{{ $importSummary['imported'] }}</strong></div>
                                            <div>Updated: <strong>{{ $importSummary['updated'] }}</strong></div>
                                            <div>Skipped: <strong>{{ $importSummary['skipped'] }}</strong></div>
                                            <div>Total: <strong>{{ $importSummary['total'] }}</strong></div>
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($importErrors))
                                    <div
                                        class="p-4 overflow-y-auto border border-red-200 rounded-lg bg-red-50 max-h-40">
                                        <h4 class="mb-2 text-sm font-medium text-red-800"><i
                                                class="fas fa-exclamation-triangle"></i> Errors:</h4>
                                        <ul class="space-y-1 text-xs text-red-700">
                                            @foreach ($importErrors as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" wire:loading.attr="disabled" wire:target="importFile,import"
                                class="inline-flex items-center justify-center w-full px-4 py-2 text-base font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="import"><i
                                        class="mr-2 fas fa-upload"></i>Import</span>
                                <span wire:loading wire:target="import"><i
                                        class="mr-2 fas fa-spinner fa-spin"></i>Importing...</span>
                            </button>
                            <button type="button" wire:click="closeImportModal" wire:loading.attr="disabled"
                                wire:target="import"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if ($confirmingDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    wire:click="$set('confirmingDeletion', false)"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <i class="text-red-600 fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Delete Drug</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to delete this drug? This
                                        action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="delete"
                            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                        <button type="button" wire:click="$set('confirmingDeletion', false)"
                            class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
