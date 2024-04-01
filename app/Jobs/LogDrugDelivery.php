<?php

namespace App\Jobs;

use App\Models\Pharmacy\Drugs\DrugStockCard;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class LogDrugDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $pharm_location_id, $dmdcomb, $dmdctr, $exp_date, $chrgcode, $qty, $drug_concat, $date;

    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pharm_location_id, $dmdcomb, $dmdctr, $exp_date, $chrgcode, $qty, $drug_concat, $date)
    {
        $this->onQueue('rx_delivery');
        $this->pharm_location_id = $pharm_location_id;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->exp_date = $exp_date;
        $this->chrgcode = $chrgcode;
        $this->qty = $qty;
        $this->drug_concat = $drug_concat;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = Carbon::parse($this->date)->format('Y-m-d');

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $this->chrgcode,
            'loc_code' => $this->pharm_location_id,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
            'exp_date' => $this->exp_date,
            'stock_date' => $date,
            'drug_concat' => $this->drug_concat,
        ]);

        $card->rec += $this->qty;
        $card->bal += $this->qty;

        $card->save();
    }
}
