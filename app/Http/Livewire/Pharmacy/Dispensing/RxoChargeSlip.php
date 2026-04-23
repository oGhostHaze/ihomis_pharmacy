<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Livewire\Component;
use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;

class RxoChargeSlip extends Component
{
    public $pcchrgcod, $view_returns = false, $returned_qty = 0;
    public $wardname, $room_name, $toecode;

    public function updatedViewReturns()
    {
        $this->reset('returned_qty');
    }

    public function render()
    {
        $pcchrgcod = $this->pcchrgcod;
        $header = [];
        $rxo = DrugOrder::where('pcchrgcod', $pcchrgcod)
            ->with('dm')->with('patient')->with('prescriptions')->with('returns');

        if ($this->view_returns) {
            $this->returned_qty = (float) DrugOrderReturn::where('pcchrgcod', $pcchrgcod)->sum('qty');
        } else {
            $header = DrugOrder::where('pcchrgcod', $pcchrgcod)
                ->first();
        }

        $rxo = $rxo->latest('dodate')->get();

        $rxo_header = $header ?: $rxo[0];
        $displayEnccode = $rxo_header->original_enccode ?: $rxo_header->enccode;
        $displayEncounter = EncounterLog::select('enccode', 'toecode')
            ->where('enccode', $displayEnccode)
            ->first();
        $prescription = $rxo_header->prescriptions->first()
            ?: optional(DrugOrder::where('enccode', $displayEnccode)->with('prescriptions')->first())->prescriptions->first();

        $this->toecode = optional($displayEncounter)->toecode ?: optional($rxo_header->enctr)->toecode;

        $patient_room = PatientRoom::where('enccode', $displayEnccode)->latest('hprdate')->first();
        // $patient_room = PatientRoom::where('enccode', $rxo[0]->enccode)->where('patrmstat', 'A')->first();
        if ($patient_room) {
            $this->wardname = Ward::select('wardname')->where('wardcode', $patient_room->wardcode)->first();
            $this->room_name = Room::select('rmname')->where('rmintkey', $patient_room->rmintkey)->first();
        } else {
            $this->wardname = null;
            $this->room_name = null;
        }

        return view('livewire.pharmacy.dispensing.rxo-charge-slip', [
            'rxo_header' => $rxo_header,
            'rxo' => $rxo,
            'prescription' => $prescription,
        ])->layout('layouts.print');
    }

    public function mount($pcchrgcod)
    {
        $this->pcchrgcod = $pcchrgcod;
    }
}

