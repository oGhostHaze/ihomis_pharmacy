<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Livewire\Component;
use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Record\Admission\PatientRoom;
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

        $rxo = DrugOrder::where('pcchrgcod', $pcchrgcod)
            ->with('dm')->with('patient')
            ->with('prescriptions')
            ->latest('dodate');

        if ($this->view_returns) {
            // $rxo->with('returns');
            $this->returned_qty = DrugOrderReturn::where('pcchrgcod', $pcchrgcod)->count();
        }

        // if ($this->view_returns and $rxo->sum('qtyissued') > 0) {
        //     $rxo = $rxo->where('qtyissued', '>', '0');
        // }
        $rxo = $rxo->get();

        $rxo_header = $rxo[0];
        $prescription = $rxo_header->prescriptions->first();

        $this->toecode = $rxo[0]->enctr->toecode;

        $patient_room = PatientRoom::where('enccode', $rxo[0]->enccode)->latest('hprdate')->first();
        // $patient_room = PatientRoom::where('enccode', $rxo[0]->enccode)->where('patrmstat', 'A')->first();
        if ($patient_room) {
            $this->wardname = Ward::select('wardname')->where('wardcode', $patient_room->wardcode)->first();
            $this->room_name = Room::select('rmname')->where('rmintkey', $patient_room->rmintkey)->first();
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
