<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\PharmLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsumptionSummary extends Component
{

    use LivewireAlert;

    public $location_id, $date_from;

    public function render()
    {
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_from)->endOfDay();
        $locations = PharmLocation::all();

        $transactions = DB::select("
            SELECT loc.description location, dept.deptname prescribing_department, rxo.tx_type transaction_type, rxo.hpercode, pat.patlast, pat.patfirst, COUNT(rxo.docointkey) line_item, SUM(rxo.pchrgup * rxo.pchrgqty) amount
            FROM hrxo rxo
                RIGHT JOIN hperson pat ON rxo.hpercode = pat.hpercode
                RIGHT JOIN hpersonal emp ON rxo.prescribed_by = emp.employeeid
                RIGHT JOIN hdept dept ON emp.deptcode = dept.deptcode
                RIGHT JOIN pharm_locations loc ON rxo.loc_code = loc.id
            WHERE rxo.prescribed_by IS NOT NULL
                AND rxo.loc_code = '" . $this->location_id . "'
                AND rxo.estatus = 'S'
                AND rxo.dodtepost BETWEEN '" . $from . "' AND '" . $to . "'
            GROUP BY rxo.hpercode, pat.patlast, pat.patfirst, dept.deptname, rxo.tx_type, loc.description
            ORDER BY pat.patlast ASC, pat.patfirst ASC
        ");

        return view('livewire.pharmacy.reports.consumption-summary', compact(
            'transactions',
            'locations',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = date('Y-m-d');
    }
}
