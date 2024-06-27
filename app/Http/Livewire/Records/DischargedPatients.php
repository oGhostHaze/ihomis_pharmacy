<?php

namespace App\Http\Livewire\Records;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class DischargedPatients extends Component
{
    public $date_from, $date_to;

    public function render()
    {
        $patients = DB::select("
            SELECT enctr.enccode, adm.admdate, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, room.rmname, ward.wardname,
                    mss.mssikey, serv.tsdesc, adm.condcode, adm.disdate
            FROM henctr enctr
                LEFT JOIN hadmlog adm ON enctr.enccode = adm.enccode
                RIGHT JOIN hpatroom pat_room ON enctr.enccode = pat_room.enccode
                RIGHT JOIN hroom room ON pat_room.rmintkey = room.rmintkey
                RIGHT JOIN hward ward ON pat_room.wardcode = ward.wardcode
                RIGHT JOIN hperson pt ON enctr.hpercode = pt.hpercode
                LEFT JOIN hpatmss mss ON enctr.enccode = mss.enccode
                RIGHT JOIN htypser serv ON adm.tscode = serv.tscode
            WHERE adm.disdate BETWEEN '" . $this->date_from . "' AND '" . $this->date_to . "'
                AND (toecode = 'ADM' OR toecode = 'OPDAD' OR toecode = 'ERADM')
                AND adm.disdate IS NOT NULL
                AND pat_room.updsw = 'Y'
            ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC
        ");

        return view('livewire.records.discharged-patients', [
            'patients' => $patients,
        ]);
    }


    public function mount()
    {
        if (isset($_GET['from']) and isset($_GET['to'])) {
            $this->date_from = Carbon::parse($_GET['from'])->format('Y-m-d H:i');
            $this->date_to = Carbon::parse($_GET['to'])->format('Y-m-d H:i');
        } else {
            $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i');
            $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i');
        }
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
