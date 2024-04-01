<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Http\Request;

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
}
