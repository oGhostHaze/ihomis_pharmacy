<?php

namespace App\Http\Livewire\Records;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Hospital\Ward;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\Pharmacy\UdddsService;

class UdddsWard extends Component
{
    use LivewireAlert;

    public $wardcode;
    public $selected_date;
    public $wards = [];
    public $selected_items = [];

    public function mount()
    {
        $this->selected_date = now('Asia/Manila')->toDateString();
        $this->wards = Ward::where('wardstat', 'A')->orderBy('wardname')->get();
    }

    public function updatingWardcode()
    {
        $this->reset('selected_items');
    }

    public function updatingSelectedDate()
    {
        $this->reset('selected_items');
    }

    public function showToday()
    {
        $this->selected_date = now('Asia/Manila')->toDateString();
        $this->reset('selected_items');
    }

    public function render()
    {
        $items = $this->filteredItems();
        $patients = $this->groupPatients($items);
        $actionableKeys = collect($items)
            ->filter(fn ($item) => (bool) $item->is_actionable)
            ->pluck('docointkey')
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
        $udddsReady = UdddsService::hasHrxoColumns();

        return view('livewire.records.uddds-ward', [
            'items' => $items,
            'patients' => $patients,
            'hasActionableItems' => !empty($actionableKeys),
            'actionableKeys' => $actionableKeys,
            'displayDate' => Carbon::parse($this->selected_date)->format('F j, Y'),
            'isToday' => $this->selected_date === now('Asia/Manila')->toDateString(),
            'eligibleCount' => collect($items)->where('is_billable', 0)->count(),
            'billableCount' => collect($items)->where('is_billable', 1)->count(),
            'issuedCount' => collect($items)->filter(fn ($item) => !empty($item->is_source_issued_for_date)
                || ($item->estatus === 'S' && !empty($item->uddds_source_docointkey)))->count(),
            'udddsReady' => $udddsReady,
            'udddsMessage' => $udddsReady ? null : app(UdddsService::class)->schemaMissingMessage(),
        ]);
    }

    public function readyToBill($enccode)
    {
        $items = $this->filteredItems();
        $keys = [];
        foreach ($items as $item) {
            if ($item->enccode === $enccode && $item->is_actionable) {
                $keys[] = $item->docointkey;
            }
        }

        $this->processKeys($keys);
    }

    public function processSelected(array $keys = [])
    {
        $this->processKeys($keys);
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));

        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }

    protected function processKeys(array $keys)
    {
        $udddsService = app(UdddsService::class);
        $keys = $udddsService->materializeDailyItems($keys, $this->selected_date);
        $result = $udddsService->chargeAndIssue($keys, session('pharm_location_id'), [
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
        $this->dispatchBrowserEvent('uddds-selection-cleared');
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
            if ($item->is_actionable) {
                $patients[$item->enccode]['keys'][] = $item->docointkey;
            }
        }

        return $patients;
    }

    protected function filteredItems(): array
    {
        return app(UdddsService::class)->wardItemsForDate(
            $this->wardcode,
            session('pharm_location_id'),
            $this->selected_date
        );
    }
}
