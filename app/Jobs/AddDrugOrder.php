<?php

namespace App\Jobs;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Livewire\Component;

class AddDrugOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $enccode, $hpercode;
    protected $order_qty, $unit_price;
    protected $dm, $sc, $ems, $maip, $wholesale, $pay, $medicare, $service, $caf, $govt, $type, $employeeid;


    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($enccode, $hpercode, $order_qty, $unit_price, DrugStock $dm, $sc, $ems, $maip, $wholesale, $pay, $medicare, $service, $caf, $govt, $type, $employeeid)
    {
        $this->onQueue('iotx');
        $this->enccode = $enccode;
        $this->hpercode = $hpercode;
        $this->order_qty = $order_qty;
        $this->unit_price = $unit_price;
        $this->dm = $dm;
        $this->sc = $sc;
        $this->ems = $ems;
        $this->maip = $maip;
        $this->wholesale = $wholesale;
        $this->pay = $pay;
        $this->medicare = $medicare;
        $this->service = $service;
        $this->caf = $caf;
        $this->govt = $govt;
        $this->type = $type;
        $this->employeeid = $employeeid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dm = $this->dm;

        $dmdcomb = $dm->dmdcomb;
        $dmdctr = $dm->dmdctr;
        $chrgcode = $dm->chrgcode;
        $loc_code = $dm->loc_code;
        $total_deduct = $this->order_qty;

        if ($this->sc) {
            $this->type = 'sc_pwd';
        } else if ($this->ems) {
            $this->type = 'ems';
        } else if ($this->maip) {
            $this->type = 'maip';
        } else if ($this->wholesale) {
            $this->type = 'wholesale';
        } else if ($this->pay) {
            $this->type = 'pay';
        } else if ($this->medicare) {
            $this->type = 'medicare';
        } else if ($this->service) {
            $this->type = 'service';
        } else if ($this->caf) {
            $this->type = 'caf';
        } else if ($this->govt) {
            $this->type = 'govt';
        }

        $available = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $chrgcode)
            ->where('loc_code', $loc_code)
            ->where('stock_bal', '>', '0')
            ->where('exp_date', '>', now())
            ->sum('stock_bal');

        if ($available >= $total_deduct) {
            DrugOrder::updateOrCreate([
                'docointkey' => '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now())) . $dm->chrgcode . $dm->dmdcomb . $dm->dmdctr,
                'enccode' => $this->enccode,
                'hpercode' => $this->hpercode,
                'rxooccid' => '1',
                'rxoref' => '1',
                'dmdcomb' => $dm->dmdcomb,
                'repdayno1' => '1',
                'rxostatus' => 'A',
                'rxolock' => 'N',
                'rxoupsw' => 'N',
                'rxoconfd' => 'N',
                'dmdctr' => $dm->dmdctr,
                'estatus' => 'U',
                'entryby' => $this->employeeid,
                'ordcon' => 'NEWOR',
                'orderupd' => 'ACTIV',
                'locacode' => 'PHARM',
                'orderfrom' => $dm->chrgcode,
                'issuetype' => 'c',
                'has_tag' => $this->type ? true : false, //added
                'tx_type' => $this->type, //added
            ], [
                'pchrgqty' => $this->order_qty,
                'pchrgup' => $this->unit_price,
                'pcchrgamt' => $this->order_qty * $this->unit_price,
                'dodate' => now(),
                'dotime' => now(),
                'dodtepost' => now(),
                'dotmepost' => now(),
                'dmdprdte' => $dm->dmdprdte,
                'exp_date' => $dm->exp_date, //added
                'loc_code' => $dm->loc_code, //added
                'item_id' => $dm->id, //added
            ]);
        }

        // $this->dispatchBrowserEvent('name-updated');
    }
}