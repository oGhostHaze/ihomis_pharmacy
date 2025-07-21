<?php

namespace App\Http\Livewire\Records;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Hospital\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Prescriptions\Prescription;

class PrescriptionWard extends Component
{

    use WithPagination;
    use LivewireAlert;

    public $wardcode, $wards;
    public $is_basic = true, $is_g24, $is_or;

    public function updatingWardcode()
    {
        $this->resetPage();
    }

    public function toggle_basic()
    {
        $this->is_basic = !$this->is_basic;
        $this->resetPage();
    }

    public function toggle_g24()
    {
        $this->is_g24 = !$this->is_g24;
        $this->resetPage();
    }

    public function toggle_or()
    {
        $this->is_or = !$this->is_or;
        $this->resetPage();
    }

    public function render()
    {
        $prescriptions = DB::select("SELECT enctr.enccode, adm.admdate, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, room.rmname, ward.wardname, mss.mssikey,
                                (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND (data.order_type = '' OR data.order_type IS NULL)) basic,
                                (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'G24') g24,
                                (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'OR') 'or'
                            FROM hospital.dbo.henctr enctr RIGHT JOIN webapp.dbo.prescription rx ON enctr.enccode = rx.enccode
                                LEFT JOIN hospital.dbo.hadmlog adm ON enctr.enccode = adm.enccode
                                RIGHT JOIN hospital.dbo.hpatroom pat_room ON rx.enccode = pat_room.enccode
                                RIGHT JOIN hospital.dbo.hroom room ON pat_room.rmintkey = room.rmintkey
                                RIGHT JOIN hospital.dbo.hward ward ON pat_room.wardcode = ward.wardcode
                                RIGHT JOIN hospital.dbo.hperson pt ON enctr.hpercode = pt.hpercode
                                LEFT JOIN hospital.dbo.hpatmss mss ON enctr.enccode = mss.enccode
                            WHERE (toecode = 'ADM' OR toecode = 'OPDAD' OR toecode = 'ERADM')
                            AND pat_room.patrmstat = 'A'
                            AND rx.stat = 'A'
                            ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC, rx.created_at DESC
                            ");

        return view('livewire.records.prescription-ward', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function mount()
    {
        $this->wards = Ward::where('wardstat', 'A')
            ->get();
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
