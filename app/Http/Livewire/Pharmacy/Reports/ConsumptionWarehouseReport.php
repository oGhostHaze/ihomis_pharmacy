<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\DrugManualLogItem;
use Illuminate\Support\Facades\DB;
use App\Models\DrugManualLogHeader;
use App\Models\References\ChargeCode;
use App\Models\DrugManualLogWarehouse;
use App\Models\Pharmacy\PharmLocation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;

class ConsumptionWarehouseReport extends Component
{
    use LivewireAlert;

    public $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $report_id, $ended, $generated = false;

    public $date_from, $date_to;
    public $location_id;
    public $processing = false;

    public function updatedReportId()
    {
        $cons = DrugManualLogHeader::find($this->report_id);
        $this->ended = $cons ? $cons->consumption_to : NULL;
        $this->generated = $cons ? $cons->generated_status : NULL;
    }

    public function render()
    {
        $locations = PharmLocation::all();

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $drugs_issued = DB::select("SELECT pdsl.dmdcomb, pdsl.dmdctr, pdsl.loc_code,
                                        unit_cost, unit_price, SUM(beg_bal) beg_bal, SUM(total_purchases) total_purchases,
                                        SUM(sat_iss) sat_iss, SUM(opd_iss) opd_iss, SUM(cu_iss) cu_iss, SUM(or_iss) or_iss, SUM(nst_iss) nst_iss, SUM(others_iss) others_iss,  SUM(returns_pullout) returns_pullout,
                                        pdsl.unit_price as dmselprice, drug.drug_concat
                                    FROM [pharm_drug_manual_log_warehouses] as [pdsl]
                                    INNER JOIN hdmhdr as drug ON pdsl.dmdcomb = drug.dmdcomb AND pdsl.dmdctr = drug.dmdctr
                                    INNER JOIN pharm_locations as loc ON pdsl.loc_code = loc.id
                                    WHERE [chrgcode] = '" . $filter_charge[0] . "' and loc_code = '" . session('pharm_location_id') . "' and consumption_id = '" . $this->report_id . "'
                                    GROUP BY pdsl.dmdcomb, pdsl.dmdctr, pdsl.loc_code, pdsl.unit_price, pdsl.unit_cost, drug.drug_concat
                                    ORDER BY drug.drug_concat ASC");

        $cons = DrugManualLogHeader::where('loc_code', session('pharm_location_id'))->latest()->get();

        return view('livewire.pharmacy.reports.consumption-warehouse-report', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'locations' => $locations,
            'cons' => $cons,
            'drugs_issued' => $drugs_issued,
        ]);
    }

    public function stop_log()
    {
        $active_consumption = ConsumptionLogDetail::find(session('active_consumption'));

        $active_consumption_manual = DrugManualLogHeader::find($this->report_id);
        if (!$active_consumption->consumption_to) {
            $active_consumption_manual->consumption_to = now();
            $active_consumption_manual->status = 'I';
            $active_consumption_manual->closed_by = session('user_id');
            $active_consumption_manual->save();

            $active_consumption->consumption_to = now();
            $active_consumption->status = 'I';
            $active_consumption->closed_by = session('user_id');
            $active_consumption->save();

            $this->alert('success', 'Drug Consumption Logger has been successfully stopped on ' . now());
        } else {
            $this->alert('warning', 'Logger currently inactive');
        }
    }

    public function getBeginningBalance()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;
        $filter_charge = explode(',', $this->filter_charge);
        $charge_code = $filter_charge[0];

        $beginnings = DB::select("
            SELECT
                first_day_reference as beg_bal,
                dmdcomb,
                dmdctr,
                dmdprdte,
                chrgcode,
                drug_concat,
                acquisition_cost,
                dmselprice
            FROM (
                SELECT
                    dsc.reference as first_day_reference,
                    dsc.dmdcomb,
                    dsc.dmdctr,
                    dsc.dmdprdte,
                    dsc.chrgcode,
                    drug.drug_concat,
                    price.acquisition_cost,
                    price.dmselprice,
                    ROW_NUMBER() OVER (
                        PARTITION BY dsc.dmdcomb, dsc.dmdctr, dsc.chrgcode, dsc.dmdprdte,
                                     price.acquisition_cost, price.dmselprice
                        ORDER BY dsc.stock_date ASC
                    ) as rn
                FROM pharm_drug_stock_cards dsc
                    JOIN hdmhdr drug ON dsc.dmdcomb = drug.dmdcomb AND dsc.dmdctr = drug.dmdctr
                    JOIN hdmhdrprice price ON dsc.dmdprdte = price.dmdprdte
                WHERE dsc.stock_date BETWEEN ? AND ?
                    AND dsc.loc_code = ?
                    AND dsc.chrgcode = ?
                    AND dsc.dmdprdte IS NOT NULL
            ) ranked
            WHERE rn = 1
        ", [$from_date, Carbon::parse($to_date)->endOfDay(), $location_id, $charge_code]);


        foreach ($beginnings as $row) {
            $log = DrugManualLogWarehouse::firstOrCreate([
                'consumption_id' => $active_consumption->id,
                'loc_code' => $location_id,
                'dmdcomb' => $row->dmdcomb,
                'dmdctr' => $row->dmdctr,
                'chrgcode' => $row->chrgcode,
                'unit_cost' => $row->acquisition_cost,
                'unit_price' => $row->dmselprice,
            ]);
            $log->beg_bal += $row->beg_bal;
            $log->save();
        }

        return;
    }

    public function generate_ending_balance()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $sat = DB::select("SELECT trans.dmdcomb, trans.dmdctr, SUM(trans.qty) qty, trans.charge_code, pri.dmduprice, pri.dmselprice
                            FROM pharm_delivery_items trans
                            JOIN hdmhdrprice pri
                                ON trans.dmdprdte = pri.dmdprdte
                            WHERE
                                trans.updated_at BETWEEN ? AND ?
                                AND trans.status IN('delivered')
                            GROUP BY trans.dmdcomb, trans.dmdctr, trans.charge_code, pri.dmduprice, pri.dmselprice", [
            $from_date,
            $to_date
        ]);

        foreach ($sat as $row) {
            $log = DrugManualLogWarehouse::firstOrCreate([
                'consumption_id' => $active_consumption->id,
                'loc_code' => $location_id,
                'dmdcomb' => $row->dmdcomb,
                'dmdctr' => $row->dmdctr,
                'chrgcode' => $row->charge_code,
                'unit_cost' => $row->dmduprice,
                'unit_price' => $row->dmselprice,
            ]);
            $log->total_purchases += $row->qty;
            $log->save();
        }

        $sat = DB::select("SELECT loc.ward_name, trans.dmdcomb, trans.dmdctr, SUM(trans.issued_qty) issued_qty, SUM(trans.return_qty) return_qty, trans.chrgcode, pri.dmduprice, pri.dmselprice
                            FROM pharm_ward_ris_requests trans
                            JOIN pharm_ris_wards loc
                                ON loc.id = trans.loc_code
                            JOIN hdmhdrprice pri
                                ON trans.dmdprdte = pri.dmdprdte
                            WHERE
                                trans.updated_at BETWEEN ? AND ?
                                AND trans.trans_stat IN('Received', 'Issued')
                            GROUP BY loc.ward_name, trans.dmdcomb, trans.dmdctr, trans.chrgcode, pri.dmduprice, pri.dmselprice", [
            $from_date,
            $to_date
        ]);

        foreach ($sat as $row) {
            $log = DrugManualLogWarehouse::firstOrCreate([
                'consumption_id' => $active_consumption->id,
                'loc_code' => $location_id,
                'dmdcomb' => $row->dmdcomb,
                'dmdctr' => $row->dmdctr,
                'chrgcode' => $row->chrgcode,
                'unit_cost' => $row->dmduprice,
                'unit_price' => $row->dmselprice,
            ]);
            if ($row->ward_name == 'Nutrition and Dietetics Section') {
                $log->nst_iss += $row->issued_qty;
            } else {
                $log->others_iss += $row->issued_qty;
            }
            $log->returns_pullout += $row->return_qty;
            $log->save();
        }

        $sat = DB::select("SELECT loc.description, trans.dmdcomb, trans.dmdctr, SUM(trans.issued_qty) issued_qty, items.chrgcode, pri.dmduprice, pri.dmselprice FROM pharm_io_trans trans
                            JOIN pharm_locations loc
                                ON loc.id = trans.loc_code
                            JOIN pharm_io_trans_items items
                                ON items.iotrans_id = trans.id
                            JOIN hdmhdrprice pri
                                ON items.dmdprdte = pri.dmdprdte
                            WHERE
                                trans.updated_at BETWEEN ? AND ?
                                AND trans.trans_stat IN('Received', 'Issued')
                            GROUP BY loc.description, trans.dmdcomb, trans.dmdctr, items.chrgcode, pri.dmduprice, pri.dmselprice", [
            $from_date,
            $to_date
        ]);

        foreach ($sat as $row) {
            $log = DrugManualLogWarehouse::firstOrCreate([
                'consumption_id' => $active_consumption->id,
                'loc_code' => $location_id,
                'dmdcomb' => $row->dmdcomb,
                'dmdctr' => $row->dmdctr,
                'chrgcode' => $row->chrgcode,
                'unit_cost' => $row->dmduprice,
                'unit_price' => $row->dmselprice,
            ]);
            switch ($row->description) {
                case 'Satellite Pharmacy':
                    $log->sat_iss += $row->issued_qty;
                    break;
                case 'OPD Pharmacy':
                    $log->opd_iss += $row->issued_qty;
                    break;
                case 'Cancer Unit':
                    $log->cu_iss += $row->issued_qty;
                    break;
                case 'Operating Room':
                    $log->or_iss += $row->issued_qty;
                    break;
                case 'E-cart':
                    $log->others_iss += $row->issued_qty;
                    break;
            }
            $log->save();
        }
        $active_consumption->generated_status = true;
        $active_consumption->save();

        $this->alert('success', 'Issuances recorded successfully ' . now());
    }

    public function createNewReport()
    {
        $this->processing = true;
        try {
            if (!$this->date_from || !$this->date_to) {
                $this->alert('error', 'Please select date range');
                $this->processing = false;
                return;
            }

            if (Carbon::parse($this->date_from)->gt(Carbon::parse($this->date_to))) {
                $this->alert('error', 'From date cannot be later than To date');
                $this->processing = false;
                return;
            }

            $active_consumption = DrugManualLogHeader::create([
                'consumption_from' => Carbon::parse($this->date_from)->startOfDay(),
                'consumption_to' => Carbon::parse($this->date_to)->endOfDay(),
                'status' => 'I',
                'loc_code' => auth()->user()->pharm_location_id ?? session('pharm_location_id'),
                'entry_by' => session('user_id'),
                'generated_status' => false,
                'is_custom' => true,
            ]);

            $this->report_id = $active_consumption->id;
            $this->getBeginningBalance();
            $this->alert('success', 'New consumption report created. You can now generate the data.');
        } catch (\Exception $e) {
            $this->alert('error', 'Error creating report: ' . $e->getMessage());
        }
        $this->processing = false;
    }

    public function cleanse()
    {
        $this->processing = true;
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        if ($active_consumption) {
            DrugManualLogWarehouse::where('consumption_id', $active_consumption->id)->delete();
            $active_consumption->step = 0;
            $active_consumption->save();
            $this->alert('success', 'Consumption report has been successfully deleted.');
        } else {
            $this->alert('warning', 'No active consumption report to delete.');
        }
        $this->processing = false;
    }
}
