<?php

namespace App\Http\Livewire\Records;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PatientsForDischarge extends Component
{
    public function render()
    {
        $patients = DB::select("
            SELECT enctr.enccode, adm.admdate, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, room.rmname, ward.wardname, mss.mssikey
            FROM hospital.dbo.henctr enctr RIGHT JOIN webapp.dbo.prescription rx ON enctr.enccode = rx.enccode
                LEFT JOIN hospital.dbo.hadmlog adm ON enctr.enccode = adm.enccode
                RIGHT JOIN hospital.dbo.hpatroom pat_room ON rx.enccode = pat_room.enccode
                RIGHT JOIN hospital.dbo.hroom room ON pat_room.rmintkey = room.rmintkey
                RIGHT JOIN hospital.dbo.hward ward ON pat_room.wardcode = ward.wardcode
                RIGHT JOIN hospital.dbo.hperson pt ON enctr.hpercode = pt.hpercode
                LEFT JOIN hospital.dbo.hpatmss mss ON enctr.enccode = mss.enccode
                RIGHT JOIN hospital.dbo.hdocord ord ON enctr.enccode = ord.enccode
            WHERE (toecode = 'ADM' OR toecode = 'OPDAD' OR toecode = 'ERADM')
                AND pat_room.patrmstat = 'A'
                AND rx.stat = 'A'
                AND ord.orcode = 'DISCH'
            ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC, rx.created_at DESC
        ");

        return view('livewire.records.patients-for-discharge', [
            'patients' => $patients,
        ]);
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
