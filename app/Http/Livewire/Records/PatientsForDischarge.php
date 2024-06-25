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
            SELECT enctr.enccode, adm.admdate, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, room.rmname, ward.wardname, mss.mssikey, serv.tsdesc, adm.condcode
            FROM hospital.dbo.henctr enctr
                LEFT JOIN hospital.dbo.hadmlog adm ON enctr.enccode = adm.enccode
                RIGHT JOIN hospital.dbo.hpatroom pat_room ON enctr.enccode = pat_room.enccode
                RIGHT JOIN hospital.dbo.hroom room ON pat_room.rmintkey = room.rmintkey
                RIGHT JOIN hospital.dbo.hward ward ON pat_room.wardcode = ward.wardcode
                RIGHT JOIN hospital.dbo.hperson pt ON enctr.hpercode = pt.hpercode
                LEFT JOIN hospital.dbo.hpatmss mss ON enctr.enccode = mss.enccode
                RIGHT JOIN hospital.dbo.hdocord ord ON enctr.enccode = ord.enccode
                RIGHT JOIN hospital.dbo.htypser serv ON adm.tscode = serv.tscode
            WHERE (toecode = 'ADM' OR toecode = 'OPDAD' OR toecode = 'ERADM')
                AND pat_room.patrmstat = 'A'
                AND ord.orcode = 'DISCH'
                AND adm.disdate IS NULL
            ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC
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
