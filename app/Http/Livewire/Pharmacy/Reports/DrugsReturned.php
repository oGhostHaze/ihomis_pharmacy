<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;

class DrugsReturned extends Component
{
    use WithPagination;

    public $filter_charge = 'DRUMB,*Drugs and Meds (Revolving) Satellite';
    public $date_from, $date_to, $location_id;

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

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $drugs_returned = DrugOrderReturn::with('dm')->with('patient')->with('receiver')->with('adm_pat_room')->with('encounter')
            ->where('returnfrom', $filter_charge[0])
            ->whereRelation('main_order', 'loc_code', $this->location_id)
            ->whereBetween('returndate', [$date_from, $date_to])
            ->latest('returndate')
            ->paginate(15);

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.drugs-returned', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'drugs_returned' => $drugs_returned,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
    }
}
