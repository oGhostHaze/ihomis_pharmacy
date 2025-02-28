<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\UserSession;
use App\Models\DrugManualLogItem;
use Illuminate\Support\Facades\DB;
use App\Models\DrugManualLogHeader;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;

class ConsumptionReportRange extends Component
{

    use LivewireAlert;
    public $month, $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to;
    public $location_id;
    public $report_id;
    public $ended = NULL;
    public $active_report;
    public $active_consumption = [];

    public function updatedReportId()
    {
        $cons = DrugManualLogHeader::find($this->report_id);
        $this->ended = $cons ? $cons->consumption_to : NULL;
    }

    public function render()
    {
        // $date_from = Carbon::parse($this->date_from . '-01')->startOfMonth()->format('Y-m-d');
        // $date_to = Carbon::parse($this->date_from . '-01')->endOfMonth()->format('Y-m-d');

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
                                        SUM(pdsl.pullout_qty) as pullout_qty,
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

        return view('livewire.pharmacy.reports.consumption-report-range', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
            'locations' => $locations,
            'cons' => $cons,
        ]);
    }

    public function mount()
    {
        $this->date_from = date('Y-m-d', strtotime(now()));
        $this->date_to = date('Y-m-d', strtotime(now()));
        $this->location_id = session('pharm_location_id');
    }

    public function get_begbal()
    {
        $pharm_location_id = session('pharm_location_id');
        $active_manual_consumption = $this->active_consumption;

        $card = DrugStockCard::select(DB::raw('SUM(reference) as begbal, dmdcomb, dmdctr, dmdprdte, chrgcode'))
            ->whereBetween('stock_date', [$this->date_from, Carbon::parse($this->date_from)->endOfDay()])
            ->where('loc_code', $this->location_id)
            ->groupBy('dmdcomb', 'dmdctr', 'chrgcode', 'stock_date', 'dmdprdte')
            ->get();

        foreach ($card as $log) {
            $beg_bal = $log->beg_bal;

            DrugManualLogItem::updateOrCreate([
                'loc_code' => $this->location_id,
                'dmdcomb' => $log->dmdcomb,
                'dmdctr' => $log->dmdctr,
                'chrgcode' => $log->chrgcode,
                'unit_cost' => $log->cur_price->acquisition_cost,
                'unit_price' => $log->cur_price->dmselprice,
                'consumption_id' => $active_manual_consumption->id,
            ], [
                'beg_bal' => $beg_bal > 1 ? $beg_bal : 0
            ]);
        }

        $this->alert('success', 'Drug Consumption Logger has been initialized successfully on ' . now());
    }

    public function generate_ending_balance()
    {
        $this->active_consumption = DrugManualLogHeader::create([
            'consumption_from' => $this->date_from,
            'consumption_to' => $this->date_to,
            'status' => 'I',
            'entry_by' => session('user_id'),
            'loc_code' => auth()->user()->pharm_location_id,
        ]);
        $active_consumption = $this->active_consumption;
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        DrugManualLogItem::where('consumption_id', $active_consumption->id)->delete();

        $this->get_begbal();

        $issueances = DB::select("
            SELECT hrxo.loc_code, hrxo.dmdcomb, hrxo.dmdctr, hrxo.orderfrom chrgcode, drug_concat, COUNT(*) LineItem, SUM(pchrgqty) qty_issued, pri.acquisition_cost unit_cost, pri.dmselprice retail_price, tx_type
            FROM hrxo
                JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                JOIN hdmhdrprice pri ON hrxo.dmdprdte = pri.dmdprdte
            WHERE dodtepost BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND loc_code = '" . $location_id . "'
                AND hrxo.estatus = 'S'
            GROUP BY drug_concat, pri.acquisition_cost, pri.dmselprice, tx_type, hrxo.dmdcomb, hrxo.dmdctr, hrxo.orderfrom, hrxo.loc_code
        ");

        foreach ($issueances as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

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

        $this->alert('success', 'Issuances recorded successfully ' . now());
        $this->generate_returns();
    }

    public function generate_returns()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT hrxo.loc_code, hrxo.dmdcomb, hrxo.dmdctr, hrxo.chrgcode chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM hrxoreturn hrxo
                JOIN hdmhdrprice pri ON hrxo.dmdprdte = pri.dmdprdte
            WHERE returndate BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND loc_code = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, hrxo.dmdcomb, hrxo.dmdctr, hrxo.chrgcode, hrxo.loc_code
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'return_qty' => $item->qty_returned,
            ]);
        }

        $this->alert('success', 'Returns recorded successfully ' . now());
        $this->generate_iotrans();
    }

    public function generate_iotrans()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $incoming = DB::select("
            SELECT pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, pri.acquisition_cost, pri.dmselprice, SUM(qty) qty, [to]
            FROM pharm_io_trans_items pit
                JOIN hdmhdr as drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
            WHERE [to] = '" . $location_id . "'
                AND status = 'Received'
                AND pit.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, [to]
        ");

        foreach ($incoming as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->to,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->acquisition_cost,
                'unit_price' => $item->dmselprice,
                'consumption_id' => $active_consumption->id,
                'received' => $item->qty,
            ]);
        }

        $outgoing = DB::select("
            SELECT pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, pri.acquisition_cost, pri.dmselprice, SUM(qty) qty, [to]
            FROM pharm_io_trans_items pit
                JOIN hdmhdr as drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
            WHERE [from] = '" . $location_id . "'
                AND status = 'Received'
                AND pit.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, [to]
        ");


        foreach ($outgoing as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->to,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->acquisition_cost,
                'unit_price' => $item->dmselprice,
                'consumption_id' => $active_consumption->id,
                'transferred' => $item->qty,
            ]);
        }

        $this->alert('success', 'IO Trans recorded successfully ' . now());
        $this->generate_deliveries();
    }

    public function generate_deliveries()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT di.pharm_location_id, di.dmdcomb, di.dmdctr, di.charge_code chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND di.pharm_location_id = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, di.dmdcomb, di.dmdctr, di.charge_code, di.pharm_location_id
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'purchased' => $item->qty_returned,
            ]);
        }

        $this->alert('success', 'Deliveries recorded successfully ' . now());
        $this->generate_pullout();
    }

    public function generate_ep()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT di.pharm_location_id, di.dmdcomb, di.dmdctr, di.charge_code chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND di.pharm_location_id = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, di.dmdcomb, di.dmdctr, di.charge_code, di.pharm_location_id
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'purchased' => $item->qty_returned,
            ]);
        }

        $this->alert('success', 'Deliveries recorded successfully ' . now());
    }

    public function generate_pullout()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT i.pullout_qty, p.pharm_location_id, s.dmdcomb, s.dmdctr, s.chrgcode, price.acquisition_cost unit_cost, s.retail_price
            FROM pharm_pull_out_items i
            JOIN pharm_pull_outs p ON i.detail_id = p.id
            JOIN pharm_drug_stocks s ON i.stock_id = s.id
            JOIN hdmhdrprice price ON s.dmdprdte = price.dmdprdte
            WHERE i.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND p.pharm_location_id = '" . $location_id . "'
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'pullout_qty' => $item->pullout_qty,
            ]);
        }

        $this->alert('success', 'Pullouts recorded successfully ' . now());
    }
}
