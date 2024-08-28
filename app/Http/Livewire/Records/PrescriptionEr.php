<?php

namespace App\Http\Livewire\Records;

use App\Models\Record\Prescriptions\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class PrescriptionEr extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $filter_date;

    public function render()
    {
        $from = Carbon::parse($this->filter_date)->subDay()->startOfDay();
        $to = Carbon::parse($this->filter_date)->endOfDay();

        $prescriptions = DB::select("SELECT enctr.enccode, er.erdate, er.ertime, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, ser.tsdesc,
                                        emp.lastname, emp.firstname, emp.empprefix, emp.middlename,
                                        (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND (data.order_type = '' OR data.order_type IS NULL)) basic,
                                        (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'G24') g24,
                                        (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'OR') 'or'
                                    FROM hospital.dbo.henctr enctr
                                        LEFT JOIN webapp.dbo.prescription rx ON enctr.enccode = rx.enccode
                                        LEFT JOIN hospital.dbo.herlog er ON enctr.enccode = er.enccode
                                        LEFT JOIN hospital.dbo.hperson pt ON enctr.hpercode = pt.hpercode
                                        LEFT JOIN hospital.dbo.htypser ser ON er.tscode = ser.tscode
                                        LEFT JOIN hospital.dbo.hprovider prov ON er.licno = prov.licno
                                        LEFT JOIN hospital.dbo.hpersonal emp ON prov.employeeid = emp.employeeid
                                    WHERE erdate BETWEEN ? AND ?
                                        AND toecode = 'ER'
                                        AND erstat = 'A'
                                    ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC
                                    ", [$from, $to]);

        return view('livewire.records.prescription-er', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function mount()
    {
        $this->filter_date = date('Y-m-d');
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
