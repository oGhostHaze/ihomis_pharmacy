<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Dispensing\DrugOrderReturn;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DrugsReturnedSummary extends Component
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

        $drugs_returned = DB::select('SELECT SUM(ret.qty) as qty, drug_concat
                                        FROM hrxoreturn as ret
                                        INNER JOIN hrxo ON ret.docointkey = hrxo.docointkey
                                        INNER JOIN hdmhdr as stock ON ret.dmdcomb = stock.dmdcomb AND ret.dmdctr = stock.dmdctr
                                        WHERE ret.returnfrom = ? AND ret.returndate BETWEEN ? AND ? AND hrxo.loc_code = ?
                                        GROUP BY ret.dmdcomb, ret.dmdctr, stock.drug_concat, hrxo.loc_code
                                        ORDER BY stock.drug_concat
        ', [$filter_charge[0], $date_from, $date_to, session('pharm_location_id')]);

        $locations = PharmLocation::all();
        return view('livewire.pharmacy.reports.drugs-returned-summary', [
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
