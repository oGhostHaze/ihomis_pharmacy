<?php

namespace App\Jobs;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogDrugOrderIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris, $prescription_data_id, $date, $dmdprdte;


    public function middleware(): array
    {
        return [(new WithoutOverlapping())];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris, $prescription_data_id, $date, $dmdprdte)
    {
        $this->onQueue('rxo_issue_logger');
        $this->docointkey = $docointkey;
        $this->enccode = $enccode;
        $this->hpercode = $hpercode;
        $this->dmdcomb = $dmdcomb;
        $this->dmdctr = $dmdctr;
        $this->pchrgqty = $pchrgqty;
        $this->employeeid = $employeeid;
        $this->orderfrom = $orderfrom;
        $this->pcchrgcod = $pcchrgcod;
        $this->pchrgup = $pchrgup;
        $this->ris = $ris;
        $this->prescription_data_id = $prescription_data_id;
        $this->date = $date;
        $this->dmdprdte = $dmdprdte;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if ($this->prescription_data_id) {
            PrescriptionDataIssued::create([
                'presc_data_id' => $this->prescription_data_id,
                'docointkey' => $this->docointkey,
                'qtyissued' => $this->pchrgqty,
            ]);
        } else {
            $rx_header = Prescription::where('enccode', $this->enccode)
                ->with('data_active')
                ->get();
            if ($rx_header) {
                foreach ($rx_header as $rxh) {
                    $rx_data = $rxh->data_active()
                        ->where('dmdcomb', $this->dmdcomb)
                        ->where('dmdctr', $this->dmdctr)
                        ->first();
                    if ($rx_data) {
                        PrescriptionDataIssued::create([
                            'presc_data_id' => $rx_data->id,
                            'docointkey' => $this->docointkey,
                            'qtyissued' => $this->pchrgqty,
                        ]);

                        DB::update(
                            "UPDATE hospital.dbo.hrxo SET prescription_data_id = ?, prescribed_by = ? WHERE docointkey = ?",
                            [$rx_data->id, $rx_data->entry_by, $this->docointkey]
                        );

                        $rx_data->stat = 'I';
                        $rx_data->save();
                    }
                }
            }
        }


        DrugOrderIssue::updateOrCreate([
            'docointkey' => $this->docointkey,
            'enccode' => $this->enccode,
            'hpercode' => $this->hpercode,
            'dmdcomb' => $this->dmdcomb,
            'dmdctr' => $this->dmdctr,
        ], [
            'issuedte' => $this->date,
            'issuetme' => $this->date,
            'qty' => $this->pchrgqty,
            'issuedby' => $this->employeeid,
            'status' => 'A', //A
            'rxolock' => 'N', //N
            'updsw' => 'N', //N
            'confdl' => 'N', //N
            'entryby' => $this->employeeid,
            'locacode' => 'PHARM', //PHARM
            'dmdprdte' => $this->dmdprdte,
            'issuedfrom' => $this->orderfrom,
            'pcchrgcod' => $this->pcchrgcod,
            'chrgcode' => $this->orderfrom,
            'pchrgup' => $this->pchrgup,
            'issuetype' => 'c', //c
            'ris' =>  $this->ris ? true : false,
        ]);
    }
}
