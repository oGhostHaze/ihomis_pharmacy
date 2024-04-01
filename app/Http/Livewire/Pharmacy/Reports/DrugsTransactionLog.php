<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStockLog;

class DrugsTransactionLog extends Component
{
    public $month, $filter_charge = 'DRUMB,*Drugs and Meds (Revolving) Satellite';
    public $date_from, $date_to;
    public $location_id;

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $locations = PharmLocation::all();

        // $logs = DrugStockLog::where('loc_code', $this->location_id)
        //                     ->where('chrgcode', $filter_charge[0])
        //                     ->whereBetween('time_logged', [$this->date_from, $this->date_to])
        //                     ->with('charge')->with('drug')
        //                     ->paginate(15);

        $logs = DrugStockLog::from('pharm_drug_stock_logs as pdsl')
            ->selectRaw("chrgcode, pdsl.dmdcomb, pdsl.dmdctr, pdsl.dmdprdte,
                                        SUM(pdsl.purchased) as purchased,
                                        SUM(pdsl.beg_bal) as beg_bal,
                                        SUM(pdsl.sc_pwd) as sc_pwd,
                                        SUM(pdsl.ems) as ems,
                                        SUM(pdsl.maip) as maip,
                                        SUM(pdsl.wholesale) as wholesale,
                                        SUM(pdsl.pay) as pay,
                                        SUM(pdsl.medicare) as medicare,
                                        SUM(pdsl.service) as service,
                                        SUM(pdsl.govt_emp) as govt_emp,
                                        SUM(pdsl.caf) as caf,
                                        SUM(pdsl.issue_qty) as issue_qty,
                                        SUM(pdsl.return_qty) as return_qty
                                        ")
            ->where('chrgcode', $filter_charge[0])
            ->whereBetween('time_logged', [$date_from, $date_to])
            ->with('charge')->with('drug')
            ->groupBy('pdsl.dmdcomb', 'pdsl.dmdctr', 'pdsl.chrgcode')
            ->groupBy('pdsl.dmdprdte')
            ->get();

        return view('livewire.pharmacy.reports.drugs-transaction-log', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'locations' => $locations,
            'logs' => $logs,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
    }
}
