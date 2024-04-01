<?php

namespace App\Http\Livewire\Records;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Prescriptions\Prescription;

class PrescriptionOpd extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $filter_date;

    public function render()
    {
        $from = Carbon::parse($this->filter_date)->startOfDay();
        $to = Carbon::parse($this->filter_date)->endOfDay();

        // $prescriptions = Prescription::has('active_opd')->has('data_active')
        //     ->with('active_opd')->with('data_active')
        //     ->with('active_g24')->with('active_or')->with('active_basic')
        //     ->where('stat', 'A')
        //     ->whereBetween('created_at', [$from, $to]);
        $prescriptions = DB::select("SELECT enctr.enccode, opd.opddate, opd.opdtime, enctr.hpercode, pt.patfirst, pt.patmiddle, pt.patlast, pt.patsuffix, mss.mssikey, ser.tsdesc,
                                    (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND (data.order_type = '' OR data.order_type IS NULL)) basic,
                                    (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'G24') g24,
                                    (SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'OR') 'or'
                                FROM hospital.dbo.henctr enctr RIGHT JOIN webapp.dbo.prescription rx ON enctr.enccode = rx.enccode
                                    LEFT JOIN hospital.dbo.hopdlog opd ON enctr.enccode = opd.enccode
                                    RIGHT JOIN hospital.dbo.hperson pt ON enctr.hpercode = pt.hpercode
                                    LEFT JOIN hospital.dbo.hpatmss mss ON enctr.enccode = mss.enccode
                                    LEFT JOIN hospital.dbo.htypser ser ON opd.tscode = ser.tscode
                                WHERE opdtime BETWEEN ? AND ?
                                AND toecode = 'OPD'
                                AND rx.stat = 'A'
                                ORDER BY pt.patlast ASC, pt.patfirst ASC, pt.patmiddle ASC, rx.created_at DESC
                                ", [$from, $to]);

        // $prescriptions = DB::table(DB::raw('hospital.dbo.henctr enctr'))
        //     ->rightJoin(DB::raw('webapp.dbo.prescription rx'), 'enctr.enccode', 'rx.enccode')
        //     ->rightJoin(DB::raw('hospital.dbo.hopdlog opd'), 'enctr.enccode', 'opd.enccode')
        //     ->rightJoin(DB::raw('hospital.dbo.hperson pt'), 'opd.hpercode', 'pt.hpercode')
        //     ->rightJoin(DB::raw('hospital.dbo.htypser ser'), 'opd.tscode', 'ser.tscode')
        //     ->leftJoin(DB::raw('hospital.dbo.hprovider prov'), 'opd.licno', 'prov.licno')
        //     ->leftJoin(DB::raw('hospital.dbo.hpersonal emp'), 'prov.employeeid', 'emp.employeeid')
        //     ->select(
        //         'opd.enccode',
        //         'opd.enccode',
        //         'opd.hpercode',
        //         'opd.opddate',
        //         'opd.opdtime',
        //         'pt.patfirst',
        //         'pt.patmiddle',
        //         'pt.patlast',
        //         'pt.patsuffix',
        //         'prov.licno',
        //         'emp.firstname',
        //         'emp.middlename',
        //         'emp.lastname',
        //         'emp.empprefix',
        //         'ser.tsdesc',
        //         DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND (data.order_type = '' OR data.order_type IS NULL)) basic"),
        //         DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'G24') g24"),
        //         DB::raw("(SELECT COUNT(qty) FROM webapp.dbo.prescription_data data WHERE rx.id = data.presc_id AND data.stat = 'A' AND data.order_type = 'OR') 'or'")
        //     )
        //     ->where('pt.patstat', 'A')
        //     ->where('rx.stat', 'A')
        //     ->where('enctr.encstat', 'A')
        //     ->whereBetween('created_at', [$from, $to])
        //     ->orderBy('pt.patlast', 'ASC')
        //     ->orderBy('pt.patfirst', 'ASC')
        //     ->orderBy('pt.patmiddle', 'ASC')
        //     ->orderByDesc('rx.created_at');

        return view('livewire.records.prescription-opd', [
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
