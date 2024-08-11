<?php

namespace App\Http\Livewire;

use App\Models\DrugManualLogHeader;
use App\Models\DrugManualLogItem;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ManualConsumptionGenerator extends Component
{

    use LivewireAlert;
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
            ->whereIn('chrgcode', app('chargetable'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $cons = DrugManualLogHeader::where('loc_code', session('pharm_location_id'))->latest()->get();

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
                                        drug.drug_concat
                                    FROM [pharm_drug_stock_logs_copy] as [pdsl]
                                    INNER JOIN hdmhdr as drug ON pdsl.dmdcomb = drug.dmdcomb AND pdsl.dmdctr = drug.dmdctr
                                    INNER JOIN pharm_locations as loc ON pdsl.loc_code = loc.id
                                    WHERE [chrgcode] = '" . $filter_charge[0] . "' and loc_code = '" . session('pharm_location_id') . "' and consumption_id = '" . $this->report_id . "'
                                    GROUP BY pdsl.dmdcomb, pdsl.dmdctr,
                                    pdsl.loc_code,
                                    pdsl.unit_price,
                                    drug.drug_concat
                                    ORDER BY drug.drug_concat ASC");

        $locations = PharmLocation::all();

        return view('livewire.manual-consumption-generator', [
            'charge_codes' => $charge_codes,
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

    public function get_begbal()
    {
        $pharm_location_id = session('pharm_location_id');

        $active_consumption = DrugManualLogHeader::create([
            'consumption_from' => now(),
            'status' => 'A',
            'entry_by' => session('user_id'),
            'loc_code' => $pharm_location_id,
        ]);

        $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code', 'dmdprdte', 'retail_price')
        ->with('current_price')
        ->where('loc_code', auth()->user()->pharm_location_id)
        ->where('stock_bal', '>', 0)
        ->get();

        foreach ($stocks as $stock) {
            DrugManualLogItem::create([
                'loc_code' => $stock->loc_code,
                'dmdcomb' => $stock->dmdcomb,
                'dmdctr' => $stock->dmdctr,
                'chrgcode' => $stock->chrgcode,
                'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                'unit_price' => $stock->retail_price,
                'beg_bal' => $stock->stock_bal,
                'consumption_id' => $active_consumption->id,
            ]);
        }

        $this->report_id = $active_consumption->id;

        $this->alert('success', 'Drug Consumption Logger has been initialized successfully on ' . now());
    }

    public function stop_log()
    {
        $active_consumption = DrugManualLogHeader::where('loc_code', auth()->user()->pharm_location_id)->latest()->first();
        if (!$active_consumption->consumption_to) {
                $active_consumption->consumption_to = now();
                $active_consumption->status = 'I';
                $active_consumption->closed_by = session('user_id');
                $active_consumption->save();

                $this->alert('success', 'Drug Consumption Logger has been successfully stopped on ' . now());
        } else {
            $this->alert('warning', 'Logger currently inactive');
        }
    }

    public function generate_ending_balance()
    {
        $active_consumption = DrugManualLogHeader::where('loc_code', auth()->user()->pharm_location_id)->latest()->first();
        $from_date = $active_consumption->consumption_from;
        $to_date = now();
        $location_id = auth()->user()->pharm_location_id;

        $issueances = DB::select("
            SELECT hrxo.loc_code, hrxo.dmdcomb, hrxo.dmdctr, hrxo.orderfrom chrgcode, drug_concat, COUNT(*) LineItem, SUM(pchrgqty) qty_issued, pri.acquisition_cost unit_code, pri.dmselprice retail_price, tx_type
            FROM hrxo
                JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                JOIN hdmhdrprice pri ON hrxo.dmdprdte = pri.dmdprdte
            WHERE dodtepost BETWEEN '".$from_date."' AND '".$to_date."'
                AND loc_code = '".$location_id."'
                AND hrxo.estatus = 'S'
            GROUP BY drug_concat, pri.acquisition_cost, pri.dmselprice, tx_type, hrxo.dmdcomb, hrxo.dmdctr, hrxo.orderfrom, hrxo.loc_code
        ");

        foreach($issueances as $item){
            $log = DrugStockLog::create([
                'loc_code' => $item->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption,

                'issue_qty' => $item->qty_issued,
                'wholesale' => $item->tx_type == 'wholesale' ? $item->qty_issued : 0,
                'ems' => $item->tx_type == 'ems' ? $item->qty_issued : 0,
                'maip' => $item->tx_type == 'maip' ? $item->qty_issued : 0,
                'caf' => $item->tx_type == 'caf' ? $item->qty_issued : 0,
                'ris' => $item->tx_type == 'ris' ? $item->qty_issued : 0,
                'pay' => $item->tx_type == 'pay' ? $item->qty_issued : 0,
                'service' => $item->tx_type == 'service' ? $item->qty_issued : 0,
                'konsulta' => $item->tx_type == 'konsulta' ? $item->qty_issued : 0,
                'pcso' => $item->tx_type == 'pcso' ? $item->qty_issued : 0,
                'phic' => $item->tx_type == 'phic' ? $item->qty_issued : 0,
                'opdpay' => $item->tx_type == 'opdpay' ? $item->qty_issued : 0,
            ]);
        }
    }
}
