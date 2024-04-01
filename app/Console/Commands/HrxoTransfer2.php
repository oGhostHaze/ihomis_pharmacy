<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Dispensing\HrxoSecondary;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Console\Command;

class HrxoTransfer2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrxo:trans2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer from mysql to sql server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $rxos = DrugOrder::where('hpercode', '900523')->get();
        foreach ($rxos as $rxo) {
            $stock = DrugStock::where('id', $rxo->item_id)
                ->first();
            // $stock = DrugStock::where('dmdcomb', $rxo->dmdcomb)
            //     ->where('dmdctr', $rxo->dmdctr)
            //     ->where('retail_price', $rxo->pchrgup)
            //     ->where('chrgcode', $rxo->orderfrom)
            //     ->where('loc_code', $rxo->loc_code)
            //     ->first();

            if ($stock) {
                $rxo->dmdprdte = $stock->dmdprdte;
                $rxo->save();

                $issue = DrugOrderIssue::where('docointkey', $rxo->docointkey)->first();
                if ($issue) {
                    $issue->dmdprdte = $rxo->dmdprdte;
                    $issue->save();
                }
            }
        }
        return 0;
    }
}
