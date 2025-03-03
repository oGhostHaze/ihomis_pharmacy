<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;

class SharedController extends Controller
{
    public static function available_stock($dmdcomb, $dmdctr, $chrgcode, $loc_code)
    {
        $stock = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $chrgcode)
            ->where('loc_code', $loc_code)
            ->where('stock_bal', '>', '0')
            ->where('exp_date', '>', now())
            ->sum('stock_bal');

        return $stock;
    }

    public static function record_hrxoissue($docointkey, $qty)
    {
        $order = DrugOrder::find($docointkey);

        $issued = DrugOrderIssue::updateOrCreate([
            'docointkey' => $docointkey,
            'enccode' => $order->enccode,
            'hpercode' => $order->hpercode,
            'dmdcomb' => $order->dmdcomb,
            'dmdctr' => $order->dmdctr,
        ], [
            'issuedte' => now(),
            'issuetme' => now(),
            'qty' => $qty,
            'issuedby' => session('employeeid'),
            'status' => 'A', //A
            'rxolock' => 'N', //N
            'updsw' => 'N', //N
            'confdl' => 'N', //N
            'entryby' => session('employeeid'),
            'locacode' => 'PHARM', //PHARM
            'dmdprdte' => now(),
            'issuedfrom' => $order->orderfrom,
            'pcchrgcod' => $order->pcchrgcod,
            'chrgcode' => $order->orderfrom,
            'pchrgup' => $order->pchrgup,
            'issuetype' => 'c', //c
        ]);

        return $issued;
    }

    public function stocklogger($pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id, $exp_date, $drug_concat, $date, $active_consumption = null)
    {
        $date = Carbon::parse($trans_date)->startOfMonth()->format('Y-m-d');

        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  $pharm_location_id,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'chrgcode' => $chrgcode,
            'unit_cost' => $unit_cost,
            'unit_price' => $retail_price,
            'consumption_id' => $active_consumption,
        ]);
        $log->beg_bal += $qty;

        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $chrgcode,
            'loc_code' => $pharm_location_id,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'exp_date' => $exp_date,
            'stock_date' => $date,
            'drug_concat' => $drug_concat,
            'dmdprdte' => $dmdprdte,
        ]);
        $card->reference += $qty;
        $card->bal += $qty;

        $card->save();
    }
}
