<?php

namespace App\Jobs;

use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class LogDrugTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id, $exp_date, $drug_concat, $date, $active_consumption = null;


    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pharm_location_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $dmdprdte, $unit_cost, $retail_price, $qty, $stock_id, $exp_date, $drug_concat, $date, $active_consumption = null)
    {
        $this->onQueue('stocklogger');
        $this->pharm_location_id = $pharm_location_id;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->chrgcode = $chrgcode;
        $this->trans_date = $trans_date;
        $this->dmdprdte = $dmdprdte;
        $this->unit_cost = $unit_cost;
        $this->retail_price = $retail_price;
        $this->qty = $qty;
        $this->stock_id = $stock_id;
        $this->exp_date = $exp_date;
        $this->drug_concat = $drug_concat;
        $this->date = $date;
        $this->active_consumption = $active_consumption;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = Carbon::parse($this->trans_date)->startOfMonth()->format('Y-m-d');

        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'chrgcode' => $this->chrgcode,
            'date_logged' => $date,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->retail_price,
            'consumption_id' => $this->active_consumption,
        ]);
        $log->time_logged = $this->date;
        $log->beg_bal += $this->qty;

        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $this->date,
            'drug_concat' => $this->drug_concat,
        ]);
        $card->reference += $this->qty;
        $card->bal += $this->qty;

        $card->save();
    }
}