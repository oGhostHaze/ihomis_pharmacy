<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\DrugManualLogHeader;
use App\Models\DrugManualLogItem;
use App\Models\DrugManualLogWarehouse;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsumptionWarehouseReport extends Component
{
    use LivewireAlert;

    public $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $report_id, $ended, $generated = false;

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

        $cons = ConsumptionLogDetail::where('loc_code', session('pharm_location_id'))->latest()->get();

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

    public function generate_ending_balance()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $sat = DB::select("SELECT loc.ward_name, trans.dmdcomb, trans.dmdctr, SUM(trans.issued_qty) issued_qty, SUM(trans.return_qty) return_qty, trans.chrgcode, pri.dmduprice, pri.dmselprice FROM pharm_ward_ris_requests trans
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
                    $log->opd_iss += $row->issued_qty;
                    break;
                case 'Operating Room':
                    $log->opd_iss += $row->issued_qty;
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
}
