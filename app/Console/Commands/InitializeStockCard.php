<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\UserSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;

class InitializeStockCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:stock-card';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize daily stock card';

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
        $locations = PharmLocation::all();
        foreach($locations as $location){
            $location->under_maintenance = true;
            $location->save();
        }


        $sessions = UserSession::where('user_id', '<>', '1')->get();
        foreach ($sessions as $session) {
            $session->delete();
        }

        $date_before = Carbon::parse(now())->subDay()->format('Y-m-d');
        $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code')
                ->where('stock_bal', '>', 0)
                ->orWhere(function($query) use ($date_before){
                    $query->where('stock_bal', '>', 0)
                    ->where('updated_at', '>', $date_before);
                })->get();

        foreach ($stocks as $stock) {
            if($stock->stock_bal > 0){
                DrugStockCard::create([
                    'chrgcode' => $stock->chrgcode,
                    'loc_code' => $stock->loc_code,
                    'dmdcomb' => $stock->dmdcomb,
                    'dmdctr' => $stock->dmdctr,
                    'drug_concat' => $stock->drug_concat(),
                    'exp_date' => $stock->exp_date,
                    'stock_date' => date('Y-m-d'),
                    'reference' => $stock->stock_bal,
                    'bal' => $stock->stock_bal,
                ]);
            }


            $card = DrugStockCard::whereNull('reference')
                ->whereNull('rec')
                ->where('chrgcode', $stock->chrgcode)
                ->where('loc_code', $stock->loc_code)
                ->where('dmdcomb', $stock->dmdcomb)
                ->where('dmdctr', $stock->dmdctr)
                ->where('drug_concat', $stock->drug_concat())
                ->where('exp_date', $stock->exp_date)
                ->first();

            if ($card) {
                $card->reference = $stock->stock_bal + $card->iss + $card->rec;
                $card->bal = $stock->stock_bal;
                $card->save();
            }
        }

        foreach($locations as $location){
            $location->under_maintenance = false;
            $location->save();
        }

        return 'Stock card reference value captured';
    }
}
