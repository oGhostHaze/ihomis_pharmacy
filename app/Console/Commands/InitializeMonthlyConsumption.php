<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InitializeMonthlyConsumption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:consumption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Monthly Consumption Report';

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
        $sessions = UserSession::where('user_id', '<>', '1')->get();
        foreach ($sessions as $session) {
            $session->delete();
        }
        $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
        $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code', 'dmdprdte', 'retail_price')->with('current_price')->where('stock_bal', '>', 0)->get();
        foreach ($stocks as $stock) {
            $log = DrugStockLog::firstOrNew([
                'loc_code' => $stock->loc_code,
                'dmdcomb' => $stock->dmdcomb,
                'dmdctr' => $stock->dmdctr,
                'chrgcode' => $stock->chrgcode,
                'date_logged' => $date,
                'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                'unit_price' => $stock->retail_price,
                'beg_bal' => $stock->stock_bal,
            ]);
            $log->time_logged = now();
            $log->save();
        }

        return 'Stock card reference value captured';
    }
}
