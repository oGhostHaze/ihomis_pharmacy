<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Livewire\Component;
use App\Models\Hospital\Room;
use App\Models\Hospital\Ward;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Pharmacy\Dispensing\DrugOrder;

class UdddsChargeSlipBatch extends Component
{
    public $codes = [];

    public function mount()
    {
        $this->codes = array_values(array_filter(explode(',', (string) request('codes', ''))));
    }

    public function render()
    {
        $slips = [];

        foreach ($this->codes as $pcchrgcod) {
            $rxo = DrugOrder::where('pcchrgcod', $pcchrgcod)
                ->with('dm', 'patient', 'prescriptions', 'returns', 'prescription_data')
                ->latest('dodate')
                ->get();

            if ($rxo->isEmpty()) {
                continue;
            }

            $rxo_header = $rxo->first();
            $displayEnccode = $rxo_header->original_enccode ?: $rxo_header->enccode;
            $displayEncounter = EncounterLog::select('enccode', 'toecode')->where('enccode', $displayEnccode)->first();
            $prescription = $rxo_header->prescriptions->first();
            $patient_room = PatientRoom::where('enccode', $displayEnccode)->latest('hprdate')->first();

            $slips[] = [
                'pcchrgcod' => $pcchrgcod,
                'rxo' => $rxo,
                'rxo_header' => $rxo_header,
                'prescription' => $prescription,
                'toecode' => optional($displayEncounter)->toecode ?: optional($rxo_header->enctr)->toecode,
                'encounter_suffix' => $rxo_header->original_enccode ? 'MGH' : null,
                'wardname' => $patient_room ? Ward::select('wardname')->where('wardcode', $patient_room->wardcode)->first() : null,
                'room_name' => $patient_room ? Room::select('rmname')->where('rmintkey', $patient_room->rmintkey)->first() : null,
            ];
        }

        return view('livewire.pharmacy.dispensing.uddds-charge-slip-batch', [
            'slips' => $slips,
        ])->layout('layouts.print');
    }
}
