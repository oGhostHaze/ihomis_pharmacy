<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;

class DrugsChargeSlips extends Component
{
    use WithPagination;

    public $date_from, $date_to, $location_id = "";

    public function updatingFilterCharge()
    {
        $this->resetPage();
    }
    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $hrxo = DrugOrder::from('hrxo as hrxo')
            ->selectRaw("hrxo.pcchrgcod, hrxo.hpercode, SUM(hrxo.pchrgqty) as total_qty, SUM(hrxo.pcchrgamt) as total_amount, MAX(hrxo.dodate) as dodate")
            ->with('patient')
            ->where('hrxo.estatus', '<>', 'U')
            ->where('hrxo.pcchrgcod', '<>', '')
            ->whereBetween('hrxo.dodate', [$date_from, $date_to])
            ->groupBy('hrxo.pcchrgcod', 'hrxo.hpercode');

        $drugs_ordered = $this->location_id ? $hrxo->where('hrxo.loc_code', $this->location_id) : $hrxo;

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.drugs-charge-slips', [
            'drugs_ordered' => $drugs_ordered->paginate(15),
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
    }
}
