<?php

namespace App\Http\Livewire\DrugManagement;

use App\Models\NonPnfDrug;
use App\Imports\NonPnfDrugImport;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class NonPnfDrugManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 15;
    public $showModal = false;
    public $editMode = false;
    public $confirmingDeletion = false;
    public $showImportModal = false;

    // Form fields
    public $drugId;
    public $medicine_name;
    public $dose;
    public $unit;
    public $is_active = true;
    public $remarks;

    // Import
    public $importFile;
    public $importSummary = null;
    public $importErrors = [];

    // Filter
    public $filterActive = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    protected $rules = [
        'medicine_name' => 'required|string|max:255',
        'dose' => 'nullable|string|max:100',
        'unit' => 'nullable|string|max:100',
        'is_active' => 'boolean',
        'remarks' => 'nullable|string',
    ];

    protected $messages = [
        'medicine_name.required' => 'Medicine name is required.',
        'importFile.required' => 'Please select a file to import.',
        'importFile.mimes' => 'File must be an Excel file (.xlsx, .xls, or .csv).',
        'importFile.max' => 'File size must not exceed 2MB.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterActive()
    {
        $this->resetPage();
    }

    public function render()
    {
        $drugs = NonPnfDrug::query()
            ->when($this->search, function ($query) {
                $query->search($this->search);
            })
            ->when($this->filterActive === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->filterActive === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('medicine_name')
            ->paginate($this->perPage);

        return view('livewire.drug-management.non-pnf-drug-management', [
            'drugs' => $drugs,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $drug = NonPnfDrug::findOrFail($id);

        $this->drugId = $drug->id;
        $this->medicine_name = $drug->medicine_name;
        $this->dose = $drug->dose;
        $this->unit = $drug->unit;
        $this->is_active = $drug->is_active;
        $this->remarks = $drug->remarks;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            $drug = NonPnfDrug::findOrFail($this->drugId);
            $drug->update([
                'medicine_name' => $this->medicine_name,
                'dose' => $this->dose,
                'unit' => $this->unit,
                'is_active' => $this->is_active,
                'remarks' => $this->remarks,
            ]);

            session()->flash('message', 'Non-PNF drug updated successfully.');
        } else {
            NonPnfDrug::create([
                'medicine_name' => $this->medicine_name,
                'dose' => $this->dose,
                'unit' => $this->unit,
                'is_active' => $this->is_active,
                'remarks' => $this->remarks,
            ]);

            session()->flash('message', 'Non-PNF drug created successfully.');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->drugId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        $drug = NonPnfDrug::findOrFail($this->drugId);
        $drug->delete();

        session()->flash('message', 'Non-PNF drug deleted successfully.');

        $this->confirmingDeletion = false;
        $this->drugId = null;
    }

    public function toggleStatus($id)
    {
        $drug = NonPnfDrug::findOrFail($id);
        $drug->update(['is_active' => !$drug->is_active]);

        session()->flash('message', 'Drug status updated successfully.');
    }

    public function openImportModal()
    {
        $this->resetImportForm();
        $this->showImportModal = true;
    }

    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $import = new NonPnfDrugImport();
            Excel::import($import, $this->importFile->getRealPath());

            $this->importSummary = $import->getSummary();
            $this->importErrors = $import->errors;

            if ($this->importSummary['imported'] > 0 || $this->importSummary['updated'] > 0) {
                session()->flash(
                    'message',
                    "Import completed! New: {$this->importSummary['imported']}, " .
                        "Updated: {$this->importSummary['updated']}, " .
                        "Skipped: {$this->importSummary['skipped']}"
                );
            } else {
                session()->flash('error', 'No records were imported. Please check your file format.');
            }

            // Only close modal if there are no errors
            if (empty($this->importErrors) && empty($import->failures)) {
                $this->closeImportModal();
            }
        } catch (\Exception $e) {
            $this->importErrors[] = $e->getMessage();
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return response()->download(
            storage_path('app/templates/non_pnf_drugs_template.xlsx'),
            'non_pnf_drugs_template.xlsx'
        );
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->resetImportForm();
    }

    public function resetForm()
    {
        $this->drugId = null;
        $this->medicine_name = '';
        $this->dose = '';
        $this->unit = '';
        $this->is_active = true;
        $this->remarks = '';
        $this->resetValidation();
    }

    public function resetImportForm()
    {
        $this->importFile = null;
        $this->importSummary = null;
        $this->importErrors = [];
        $this->resetValidation(['importFile']);
    }

    public function export()
    {
        // This can be implemented later with Excel export functionality
        session()->flash('message', 'Export functionality coming soon.');
    }
}
