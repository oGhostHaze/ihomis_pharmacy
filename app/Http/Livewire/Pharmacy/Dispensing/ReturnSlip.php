<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnSlip extends Component
{
    public $patient;
    public $hpercode;
    public $wardname;
    public $room_name;
    public $toecode;

    public function render()
    {
        $items = DB::select("
            SELECT rxo.pcchrgcod, rxi.issuedte, drug.drug_concat, rxi.qty total_issued, rxr.qty as total_returns, rxo.pchrgup, (rxo.pchrgup * rxi.qty) pchrgamt
            FROM hrxo rxo
            LEFT JOIN hrxoissue rxi ON rxo.docointkey = rxi.docointkey
            INNER JOIN hrxoreturn rxr ON rxo.docointkey = rxr.docointkey
            JOIN hdmhdr drug ON rxo.dmdcomb = drug.dmdcomb AND rxo.dmdctr = drug.dmdctr
            WHERE rxo.hpercode = '" . $this->hpercode . "'
            ORDER BY rxo.dodate DESC
        ");

        $latestReturnedOrder = DrugOrder::where('hpercode', $this->hpercode)
            ->whereHas('returns')
            ->latest('dodate')
            ->first();

        $displayEnccode = $latestReturnedOrder?->original_enccode ?: $latestReturnedOrder?->enccode;
        $displayEncounter = $displayEnccode
            ? EncounterLog::select('enccode', 'toecode')->where('enccode', $displayEnccode)->first()
            : null;

        $this->toecode = $displayEncounter?->toecode;

        $patientRoom = $displayEnccode
            ? PatientRoom::where('enccode', $displayEnccode)->latest('hprdate')->first()
            : null;

        if ($patientRoom) {
            $this->wardname = Ward::select('wardname')->where('wardcode', $patientRoom->wardcode)->first();
            $this->room_name = Room::select('rmname')->where('rmintkey', $patientRoom->rmintkey)->first();
        } else {
            $this->wardname = null;
            $this->room_name = null;
        }

        return view('livewire.pharmacy.dispensing.return-slip', compact(
            'items'
        ))->layout('layouts.print');
    }

    public function mount($hpercode)
    {
        $this->patient = DB::select("SELECT * FROM hperson WHERE hpercode = '" . $hpercode . "'");
    }
}
