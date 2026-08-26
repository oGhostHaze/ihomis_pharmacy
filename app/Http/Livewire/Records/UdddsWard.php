<?php

namespace App\Http\Livewire\Records;

use Livewire\Component;
use App\Models\Hospital\Ward;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\Pharmacy\UdddsService;

class UdddsWard extends Component
{
    use LivewireAlert;

    public $wardcode;
    public $wards = [];
    public $selected_items = [];

    public function mount()
    {
        $this->wards = Ward::where('wardstat', 'A')->orderBy('wardname')->get();
    }

    public function updatingWardcode()
    {
        $this->reset('selected_items');
    }

    public function render()
    {
        $items = app(UdddsService::class)->todaysWardItems($this->wardcode, session('pharm_location_id'));
        $patients = $this->groupPatients($items);
        $udddsReady = UdddsService::hasHrxoColumns();

        return view('livewire.records.uddds-ward', [
            'items' => $items,
            'patients' => $patients,
            'udddsReady' => $udddsReady,
            'udddsMessage' => $udddsReady ? null : app(UdddsService::class)->schemaMissingMessage(),
        ]);
    }

    public function readyToBill($enccode)
    {
        $items = app(UdddsService::class)->todaysWardItems($this->wardcode, session('pharm_location_id'));
        $keys = [];
        foreach ($items as $item) {
            if ($item->enccode === $enccode) {
                $keys[] = $item->docointkey;
            }
        }

        $this->processKeys($keys);
    }

    public function processSelected()
    {
        $this->processKeys($this->selected_items);
    }

    public function processWard()
    {
        $items = app(UdddsService::class)->todaysWardItems($this->wardcode, session('pharm_location_id'));
        $keys = [];
        foreach ($items as $item) {
            $keys[] = $item->docointkey;
        }

        $this->processKeys($keys);
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));

        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }

    protected function processKeys(array $keys)
    {
        $result = app(UdddsService::class)->chargeAndIssue($keys, session('pharm_location_id'), [
            'employeeid' => session('employeeid'),
            'user_id' => session('user_id'),
            'consumption_id' => session('active_consumption'),
            'toecode' => 'ADM',
        ]);

        if (!$result['ok']) {
            $this->alert('error', $result['message']);
            return;
        }

        $this->reset('selected_items');
        $this->alert('success', $result['message']);

        if (!empty($result['pcchrgcods'])) {
            $this->dispatchBrowserEvent('uddds-print', [
                'url' => route('dispensing.uddds.chargeslips', ['codes' => implode(',', $result['pcchrgcods'])]),
            ]);
        }
    }

    protected function groupPatients(array $items)
    {
        $patients = [];

        foreach ($items as $item) {
            $name = trim(($item->patlast ?? '') . ', ' . ($item->patfirst ?? '') . ' ' . ($item->patmiddle ?? ''));
            if (!isset($patients[$item->enccode])) {
                $patients[$item->enccode] = [
                    'enccode' => $item->enccode,
                    'hpercode' => $item->hpercode,
                    'name' => $name,
                    'wardname' => $item->wardname,
                    'rmname' => $item->rmname,
                    'items' => [],
                    'keys' => [],
                ];
            }
            $patients[$item->enccode]['items'][] = $item;
            $patients[$item->enccode]['keys'][] = $item->docointkey;
        }

        return $patients;
    }
}
