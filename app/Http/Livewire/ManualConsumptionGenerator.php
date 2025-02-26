<?php

namespace App\Http\Livewire;

use App\Models\DrugManualLogHeader;
use App\Models\DrugManualLogItem;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use App\Models\User;
use App\Models\UserSession;
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
    public $ended = NULL;
    public $active_report;

    public function updatedReportId()
    {
        $cons = DrugManualLogHeader::find($this->report_id);
        $this->ended = $cons ? $cons->consumption_to : NULL;
    }

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
        $select_consumption = DrugManualLogHeader::where('loc_code', auth()->user()->pharm_location_id)->latest()->first();
        if ($select_consumption) {
            $this->report_id = $select_consumption->id;
            $this->active_report = $select_consumption->consumption_to ? $select_consumption : NULL;
            $this->ended = $select_consumption->consumption_to ? true : NULL;
        } else {
        }
    }

    public function get_begbal()
    {
        $pharm_location_id = session('pharm_location_id');
        $logs = ConsumptionLogDetail::where('loc_code', $pharm_location_id)->where('id', '<', $this->report_id)->latest()->first();
        $log_items = DB::select("SELECT pdsl.dmdcomb, pdsl.dmdctr,
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
                                    SUM(pdsl.pullout_qty) as pullout_qty,
                                    SUM(pdsl.issue_qty) as issue_qty,
                                    SUM(pdsl.return_qty) as return_qty,
                                    MAX(pdsl.unit_cost) as acquisition_cost,
                                    pdsl.unit_price as dmselprice,
                                    pdsl.chrgcode as chrgcode,
                                    drug.drug_concat
                                FROM [pharm_drug_stock_logs] as [pdsl]
                                INNER JOIN hdmhdr as drug ON pdsl.dmdcomb = drug.dmdcomb AND pdsl.dmdctr = drug.dmdctr
                                INNER JOIN pharm_locations as loc ON pdsl.loc_code = loc.id
                                WHERE consumption_id = '" . $logs->id . "'
                                GROUP BY pdsl.dmdcomb, pdsl.dmdctr,
                                pdsl.chrgcode,
                                pdsl.loc_code,
                                pdsl.unit_price,
                                drug.drug_concat
                                ORDER BY drug.drug_concat ASC");

        foreach ($log_items as $log) {
            $beg_bal = $log->beg_bal;
            $purchased = $log->purchased;
            $issued = $log->issue_qty;

            $ending_balance = $beg_bal + $purchased + $log->received_iotrans + $log->return_qty - ($issued + $log->transferred_iotrans + $log->pullout_qty);
            DrugManualLogItem::updateOrCreate([
                'loc_code' => $log->loc_code,
                'dmdcomb' => $log->dmdcomb,
                'dmdctr' => $log->dmdctr,
                'chrgcode' => $log->chrgcode,
                'unit_cost' => $log->acquisition_cost,
                'unit_price' => $log->dmselprice,
                'consumption_id' => $this->report_id,
            ], [
                'beg_bal' => $ending_balance > 1 ? $ending_balance : 0
            ]);
        }

        $this->alert('success', 'Drug Consumption Logger has been initialized successfully on ' . now());
    }

    public function stop_log()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        if (!$active_consumption->consumption_to) {
            $active_consumption->consumption_to = now();
            $active_consumption->status = 'I';
            $active_consumption->closed_by = session('user_id');
            $active_consumption->save();

            $active_consumption = ConsumptionLogDetail::find(session('active_consumption'));
            $active_consumption->consumption_to = now();
            $active_consumption->status = 'I';
            $active_consumption->closed_by = session('user_id');
            $active_consumption->save();

            session(['active_consumption' => null]);
            $pharm_location_id = session('pharm_location_id');
            //
            $users = User::where('pharm_location_id', $pharm_location_id)->get();
            foreach ($users as $user) {
                $sessions = UserSession::where('user_id', '<>', '1')->where('user_id', $user->id)->get();
                foreach ($sessions as $session) {
                    $session->delete();
                }
            }

            $this->alert('success', 'Drug Consumption Logger has been successfully stopped on ' . now());
        } else {
            $this->alert('warning', 'Logger currently inactive');
        }
    }

    public function generate_ending_balance()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
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
            SELECT i.pullout_qty, d.pharm_location_id, s.dmdcomb, s.dmdctr, s.chrgcode, p.aquisition_cost unit_cost, s.retail_price
            FROM pharm_pull_out_items i
            JOIN pharm_pull_outs p ON i.detail_id = p.id
            JOIN pharm_drug_stocks s ON i.stock_id = s.id
            JOIN hdhdrprice p ON s.dmdprdte = p.dmdprdte
            WHERE i.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND d.pharm_location_id = '" . $location_id . "'
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
