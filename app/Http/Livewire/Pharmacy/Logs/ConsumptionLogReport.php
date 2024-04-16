<?php

namespace App\Http\Livewire\Pharmacy\Logs;

use App\Http\Livewire\Pharmacy\Deliveries\DeliveryList;
use App\Models\DrugManualLogHeader;
use Livewire\Component;
use App\Models\DrugManualLogItem;
use App\Models\Pharmacy\Drugs\DrugStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConsumptionLogReport extends Component
{
    public $month, $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to, $end_date;
    public $location_id;

    public function render()
    {
        $this->end_log();
        return view('livewire.pharmacy.logs.consumption-log-report');
    }

    public function mount()
    {
        // $this->location_id = session('pharm_location_id');
        $this->location_id = 1;
        $this->end_date = Carbon::parse(now())->format('Y-m-d H:i');
    }

    public function start_log()
    {
    }

    public function end_log()
    {
        $active_detail = DrugManualLogHeader::where('status', 'A')
            ->where('loc_code', $this->location_id)
            ->first();

        // $start_date = $active_detail ? $active_detail->consumption_from : '2024-04-01';
        $start_date = '2024-03-01';

        $deliveries = DB::select("SELECT COUNT(id) total_deliveries, SUM(qty) qty, dmdcomb, dmdctr, unit_price, retail_price, charge_code, pharm_location_id
                                    FROM pharm_delivery_items
                                    WHERE pharm_location_id = '" . $this->location_id . "'
                                        AND updated_at BETWEEN '" . $start_date . "' AND '" . $this->end_date . "'
                                    GROUP BY
                                        dmdcomb, dmdctr, unit_price, retail_price, charge_code, pharm_location_id");

        foreach ($deliveries as $delivery) {
            DrugManualLogItem::updateOrCreate([
                'detail_id' => $active_detail->id,
                'loc_code' => $delivery->pharm_location_id,
                'chrgcode' => $delivery->chrgcode,
                'dmdcomb' => $delivery->dmdcomb,
                'dmdctr' => $delivery->dmdctr,
            ], [
                'delivered' => $delivery->qty,
            ]);

            DB::update("UPDATE pharm_drug_manual_log_items SET
                            delivered = '" + $delivery->qty + "'
                        WHERE
                            detail_id = '" + $active_detail->id + "' AND
                            chrgcode = '" + $delivery->chrgcode + "' AND
                            dmdcomb = '" + $delivery->dmdcomb + "' AND
                            dmdctr = '" + $delivery->dmdctr + "' AND
                            loc_code = '" + $delivery->pharm_location_id + "'
                        ");
        }
    }
}
