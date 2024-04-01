<?php

namespace App\Jobs;

use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogDrugStockIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $stock_id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $exp_date, $trans_qty, $unit_price, $pcchrgamt, $user_id, $hpercode, $enccode, $toecode, $pcchrgcod, $tag, $ris, $dmdprdte, $retail_price, $concat, $stock_date, $date, $active_consumption = null, $unit_cost;


    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($stock_id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $exp_date, $trans_qty, $unit_price, $pcchrgamt, $user_id, $hpercode, $enccode, $toecode, $pcchrgcod, $tag, $ris, $dmdprdte, $retail_price, $concat, $stock_date, $date, $active_consumption = null, $unit_cost)
    {
        $this->onQueue('rx_issue_logger');
        $this->stock_id = $stock_id;
        $this->docointkey = $docointkey;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->loc_code = $loc_code;
        $this->chrgcode = $chrgcode;
        $this->exp_date = $exp_date;
        $this->trans_qty = $trans_qty;
        $this->unit_price = $unit_price;
        $this->pcchrgamt = $pcchrgamt;
        $this->user_id = $user_id;
        $this->hpercode = $hpercode;
        $this->enccode = $enccode;
        $this->toecode = $toecode;
        $this->pcchrgcod = $pcchrgcod;
        $this->tag = $tag;
        $this->ris = $ris;
        $this->dmdprdte = $dmdprdte;
        $this->retail_price = $retail_price;
        $this->concat = $concat;
        $this->stock_date = $stock_date;
        $this->date = $date;
        $this->active_consumption = $active_consumption;
        $this->unit_cost = $unit_cost;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $issued_drug = DrugStockIssue::create([
            'stock_id' => $this->stock_id,
            'docointkey' => $this->docointkey,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'loc_code' => $this->loc_code,
            'chrgcode' => $this->chrgcode,
            'exp_date' => $this->exp_date,
            'qty' =>  $this->trans_qty,
            'pchrgup' =>  $this->unit_price,
            'pcchrgamt' =>  $this->pcchrgamt,
            'status' => 'Issued',
            'user_id' => $this->user_id,
            'hpercode' => $this->hpercode,
            'enccode' => $this->enccode,
            'toecode' => $this->toecode,
            'pcchrgcod' => $this->pcchrgcod,

            'ems' => $this->tag == 'ems' ? $this->trans_qty : false,
            'maip' => $this->tag == 'maip' ? $this->trans_qty : false,
            'wholesale' => $this->tag == 'wholesale' ? $this->trans_qty : false,
            'pay' => $this->tag == 'pay' ? $this->trans_qty : false,
            'opdpay' => $this->tag == 'opdpay' ? $this->trans_qty : false,
            'service' => $this->tag == 'service' ? $this->trans_qty : false,
            'caf' => $this->tag == 'caf' ? $this->trans_qty : false,
            'ris' =>  $this->ris ? true : false,

            'konsulta' => $this->tag == 'konsulta' ? $this->trans_qty : false,
            'pcso' => $this->tag == 'pcso' ? $this->trans_qty : false,
            'phic' => $this->tag == 'phic' ? $this->trans_qty : false,

            'dmdprdte' => $this->dmdprdte,
        ]);

        $date = Carbon::parse($this->date)->startOfMonth()->format('Y-m-d');

        $log = DrugStockLog::firstOrNew([
            'loc_code' => $this->loc_code,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $date,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->retail_price,
            'consumption_id' => $this->active_consumption,
        ]);
        $log->time_logged = $this->date;
        $log->issue_qty += $this->trans_qty;

        $log->wholesale += $issued_drug->wholesale;
        $log->ems += $issued_drug->ems;
        $log->maip += $issued_drug->maip;
        $log->caf += $issued_drug->caf;
        $log->ris += $issued_drug->ris ? 1 : 0;

        $log->pay += $issued_drug->pay;
        $log->service += $issued_drug->service;

        // added columns
        $log->konsulta += $issued_drug->konsulta;
        $log->pcso += $issued_drug->pcso;
        $log->phic += $issued_drug->phic;

        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->loc_code,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $this->stock_date,
            'drug_concat' => $this->concat,
        ]);
        $card->iss += $this->trans_qty;
        $card->bal -= $this->trans_qty;

        $card->save();
        Log::info($card);
    }
}