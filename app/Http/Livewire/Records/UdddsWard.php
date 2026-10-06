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
    public $queueLoaded = false;
    public $queue_view = 'active';
    public $status_filter = 'all';
    public $lastBatchPrintUrl;
    public $processingProblem;

    public function mount()
    {
        $this->selected_date = now('Asia/Manila')->toDateString();
        $this->wards = Ward::where('wardstat', 'A')->orderBy('wardname')->get();
    }

    public function loadQueue()
    {
        $this->queueLoaded = true;
    }

    public function updatingStatusFilter()
    {
        $this->reset('selected_items', 'processingProblem');
        $this->dispatchBrowserEvent('uddds-selection-cleared');
    }

    public function updatingQueueView()
    {
        $this->reset('selected_items', 'processingProblem');
    }

    public function updatingWardcode()
    {
        $this->reset('selected_items', 'processingProblem');
    }

    public function updatingSelectedDate()
    {
        $this->reset('selected_items', 'processingProblem');
    }

    public function showToday()
    {
        $this->selected_date = now('Asia/Manila')->toDateString();
        $this->reset('selected_items', 'processingProblem');
    }

    public function render()
    {
        $items = $this->filteredItems();
        if ($this->queue_view === 'active') {
            $items = app(UdddsService::class)->annotatePendingStock($items, session('pharm_location_id'));
        }
        $patients = $this->groupPatients($items);
        $actionableKeys = collect($items)
            ->filter(fn ($item) => (bool) $item->is_actionable)
            ->pluck('docointkey')
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
        $udddsReady = !$this->queueLoaded || UdddsService::hasHrxoColumns();

        return view('livewire.records.uddds-ward', [
            'items' => $items,
            'patients' => $patients,
            'batchReprintUrl' => $this->reprintUrl($items),
            'hasActionableItems' => !empty($actionableKeys),
            'actionableKeys' => $actionableKeys,
            'displayDate' => Carbon::parse($this->selected_date)->format('F j, Y'),
            'isToday' => $this->selected_date === now('Asia/Manila')->toDateString(),
            'eligibleCount' => collect($items)->where('queue_status', 'eligible')->count(),
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
        if ($this->queue_view === 'processed') {
            return;
        }
        $udddsService = app(UdddsService::class);
        $allowed = collect($this->filteredItems())->filter(fn ($item) => (bool) $item->is_actionable)->pluck('docointkey')->all();
        $keys = array_values(array_intersect(array_unique($keys), $allowed));
        $this->processingProblem = null;
        $keys = $udddsService->materializeDailyItems($keys, $this->selected_date);
        $result = $udddsService->chargeAndIssue($keys, session('pharm_location_id'), [
            'employeeid' => session('employeeid'),
            'user_id' => session('user_id'),
            'consumption_id' => session('active_consumption'),
            'toecode' => 'ADM',
        ]);

        if (!empty($result['pcchrgcods'])) {
            $this->lastBatchPrintUrl = route('dispensing.uddds.chargeslips', ['codes' => implode(',', $result['pcchrgcods'])]);
            $this->dispatchBrowserEvent('uddds-print', ['url' => $this->lastBatchPrintUrl]);
        }

        if (!$result['ok']) {
            $this->processingProblem = $result['message'];
            $this->alert('error', $result['message']);
            return;
        }

        $this->reset('selected_items', 'processingProblem');
        $this->dispatchBrowserEvent('uddds-selection-cleared');
        $this->alert('success', $result['message']);

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

        foreach ($patients as &$patient) {
            $patient['reprint_url'] = $this->reprintUrl($patient['items']);
        }
        unset($patient);

        return $patients;
    }

    protected function reprintUrl(array $items): ?string
    {
        $codes = app(UdddsService::class)->reprintChargeCodes($items, $this->selected_date);

        return $codes ? route('dispensing.uddds.chargeslips', ['codes' => implode(',', $codes)]) : null;
    }

    protected function filteredItems(): array
    {
        if (!$this->queueLoaded) {
            return [];
        }

        $service = app(UdddsService::class);
        $processed = $this->queue_view === 'processed';
        $items = $processed
            ? $service->processedWardItemsForDate($this->wardcode, session('pharm_location_id'), $this->selected_date)
            : $service->wardItemsForDate($this->wardcode, session('pharm_location_id'), $this->selected_date);

        return $service->filterWardItemsByStatus($items, $this->status_filter, $processed);
    }
}
