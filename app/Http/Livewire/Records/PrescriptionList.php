<?php

namespace App\Http\Livewire\Records;

use App\Models\Hospital\Ward;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Prescriptions\Prescription;

class PrescriptionList extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $wardcode, $wards;
    public $department = 'opd';

    public function updatingDepartment()
    {
        $this->resetPage();
    }
    public function updatingWardcode()
    {
        $this->resetPage();
    }

    public function render()
    {

        switch ($this->department) {
            case 'ward':
                $prescriptions = Prescription::has('adm')->has('data');
                break;

            case 'opd':
                $prescriptions = Prescription::has('opd')->has('data');
                break;

            default: //er
                $prescriptions = Prescription::has('er')->has('data');
        }

        $prescriptions = $prescriptions->where('stat', 'A')->where('updated_at', '>', '2020-01-01');

        return view('livewire.records.prescription-list', [
            'prescriptions' => $prescriptions->paginate(10),
        ]);
    }

    public function mount()
    {
        $this->wards = Ward::where('wardcode', '<>', $this->wardcode)
            ->where('wardstat', 'A')
            ->get();
    }
}
