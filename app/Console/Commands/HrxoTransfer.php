<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\HrxoSecondary;
use Illuminate\Console\Command;

class HrxoTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrxo:trans';

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
        // $docointkey = '000004090052302/08/202401:59:06DRUMB0000000000871';
        // $second = HrxoSecondary::whereBetween('id', ['1', '99'])->latest()->get();
        $second = HrxoSecondary::get();
        foreach ($second as $hrxo2) {
            $hrxo = DrugOrder::where('docointkey', $hrxo2->docointkey)->first();
            dd($hrxo2->docointkey);
            $hrxo->pchrgup = $hrxo2->pchrgup;
            $hrxo->save();
            // $created = DrugOrder::create([
            //     'docointkey' => $hrxo2->docointkey,
            //     'enccode' => $hrxo2->enccode,
            //     'hpercode' => $hrxo2->hpercode,
            //     'rxooccid' => '1',
            //     'rxoref' => '1',
            //     'dmdcomb' => $hrxo2->dmdcomb,
            //     'repdayno1' => '1',
            //     'rxostatus' => 'A',
            //     'rxolock' => 'N',
            //     'rxoupsw' => 'N',
            //     'rxoconfd' => 'N',
            //     'dmdctr' => $hrxo2->dmdctr,
            //     'estatus' => $hrxo2->estatus,
            //     'entryby' => $hrxo2->entryby,
            //     'ordcon' => 'NEWOR',
            //     'orderupd' => 'ACTIV',
            //     'locacode' => 'PHARM',
            //     'orderfrom' => $hrxo2->chrgcode,
            //     'issuetype' => 'c',
            //     'has_tag' => $hrxo2->has_tag,
            //     'tx_type' => $hrxo2->tx_type,
            //     'ris' => $hrxo2->ris,
            //     'pchrgqty' => $hrxo2->pchrgqty,
            //     'pchrgup' => $hrxo2->pchrgup,
            //     'pcchrgamt' => $hrxo2->pcchrgamt,
            //     'dodate' => $hrxo2->dodate,
            //     'dotime' => $hrxo2->dotime,
            //     'dodtepost' => $hrxo2->dodtepost,
            //     'dotmepost' => $hrxo2->dotmepost,
            //     'dmdprdte' => $hrxo2->dmdprdte,
            //     'exp_date' => $hrxo2->exp_date, //added
            //     'loc_code' => $hrxo2->loc_code, //added
            //     'item_id' => $hrxo2->id, //added
            //     'remarks' => $hrxo2->remarks, //added
            //     'prescription_data_id' => $hrxo2->prescription_data_id,
            //     'prescribed_by' => $hrxo2->prescribed_by,
            //     'hidden' => '1',
            // ]);
            $hrxo2->transferred = '5';
            $hrxo2->save();
        }
        return 0;
    }
}
