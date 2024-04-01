<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConssumptionReport extends Component
{
    public $month, $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to;
    public $location_id;
    public $report_id;

    public function render()
    {
        $date_from = Carbon::parse($this->date_from . '-01')->startOfMonth()->format('Y-m-d');
        $date_to = Carbon::parse($this->date_from . '-01')->endOfMonth()->format('Y-m-d');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $cons = ConsumptionLogDetail::where('loc_code', session('pharm_location_id'))->latest()->get();

        $drugs_issued = DB::select("SELECT pdsl.dmdcomb, pdsl.dmdctr,
                                        pdsl.loc_code,
                                        SUM(pdsl.purchased) as purchased,
                                        SUM(pdsl.received) as received_iotrans,
                                        SUM(pdsl.transferred) as transferred_iotrans,
                                        SUM(pdsl.beg_bal) as beg_bal,
                                        SUM(pdsl.ems) as ems,
                                        SUM(pdsl.maip) as maip,
                                        SUM(pdsl.wholesale) as wholesale,
                                        SUM(pdsl.opdpay) as opdpay,
                                        SUM(pdsl.pay) as pay,
                                        SUM(pdsl.service) as service,
                                        SUM(pdsl.konsulta) as konsulta,
                                        SUM(pdsl.pcso) as pcso,
                                        SUM(pdsl.phic) as phic,
                                        SUM(pdsl.caf) as caf,
                                        SUM(pdsl.issue_qty) as issue_qty,
                                        SUM(pdsl.return_qty) as return_qty,
                                        MAX(pdsl.unit_cost) as acquisition_cost,
                                        pdsl.unit_price as dmselprice,
                                        loc.description as location,
                                        drug.drug_concat
                                    FROM [pharm_drug_stock_logs] as [pdsl]
                                    INNER JOIN hdmhdr as drug ON pdsl.dmdcomb = drug.dmdcomb AND pdsl.dmdctr = drug.dmdctr
                                    INNER JOIN hdmhdrprice as price ON pdsl.dmdprdte = price.dmdprdte
                                    INNER JOIN pharm_locations as loc ON pdsl.loc_code = loc.id
                                    WHERE [chrgcode] = '" . $filter_charge[0] . "' and loc_code = '" . session('pharm_location_id') . "' and consumption_id = '" . $this->report_id . "'
                                    GROUP BY pdsl.dmdcomb, pdsl.dmdctr,
                                    pdsl.loc_code,
                                    pdsl.unit_price,
                                    loc.description,
                                    drug.drug_concat
                                    ORDER BY drug.drug_concat ASC");

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.conssumption-report', [
            'charge_codes' => $charge_codes,
            // 'charges' => $charges,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
            'locations' => $locations,
            'cons' => $cons,
        ]);
    }

    public function mount()
    {
        $this->date_from = date('Y-m', strtotime(now()));
        $this->location_id = session('pharm_location_id');
    }
}
