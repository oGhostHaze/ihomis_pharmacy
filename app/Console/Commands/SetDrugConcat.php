<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\Drug;
use Illuminate\Console\Command;

class SetDrugConcat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:drugconcat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Drug Names';

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
        $drugs = Drug::whereNull('drug_concat')->get();
        foreach($drugs as $drug){
            $drug->drug_concat = $drug->drug_name();
            $drug->save();
        }
        return 0;
    }
}
