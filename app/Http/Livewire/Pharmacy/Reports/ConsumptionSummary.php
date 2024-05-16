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

    public $location_id, $date_from, $tagging;

    public function render()
    {
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_from)->endOfDay();
        $locations = PharmLocation::all();

        $transactions = DB::select("
            SELECT loc.description location, MAX(dept.deptname) prescribing_department, rxo.tx_type transaction_type, rxo.hpercode, pat.patlast, pat.patfirst, COUNT(rxo.docointkey) line_item, SUM(rxo.pchrgup * rxo.pchrgqty) amount, enctr.toecode, serv.tsdesc, COUNT(DISTINCT(pcchrgcod)) 'rx'
            FROM hrxo rxo
                RIGHT JOIN hperson pat ON rxo.hpercode = pat.hpercode
                RIGHT JOIN henctr enctr ON rxo.enccode = enctr.enccode
                RIGHT JOIN pharm_locations loc ON rxo.loc_code = loc.id
                LEFT JOIN hpersonal emp ON rxo.prescribed_by = emp.employeeid
                LEFT JOIN hdept dept ON emp.deptcode = dept.deptcode
                LEFT JOIN hopdlog log ON rxo.enccode = log.enccode
                LEFT JOIN htypser serv ON log.tscode = serv.tscode
            WHERE rxo.loc_code = '" . $this->location_id . "'
                AND rxo.tx_type LIKE '%" . $this->tagging . "'
                AND rxo.estatus = 'S'
                AND rxo.dodtepost BETWEEN '" . $from . "' AND '" . $to . "'
            GROUP BY rxo.hpercode, pat.patlast, pat.patfirst, rxo.tx_type, loc.description, enctr.toecode, serv.tsdesc
            ORDER BY pat.patlast ASC, pat.patfirst ASC
        ");

        $tags = DB::select("SELECT DISTINCT(tx_type) FROM hrxo rxo
            WHERE rxo.loc_code = '" . $this->location_id . "'
            AND rxo.estatus = 'S'
            AND rxo.dodtepost BETWEEN '" . $from . "' AND '" . $to . "'");

        return view('livewire.pharmacy.reports.consumption-summary', compact(
            'transactions',
            'locations',
            'tags',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = date('Y-m-d');
    }
}