<?php

namespace App\Http\Livewire\Records;

use App\Models\Pharmacy\TemporaryPatient;
use App\Models\Record\Encounters\EncounterLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Models\Record\Patients\Patient;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PatientsList extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['walk_in', 'view_enctr'];
    public $enccode;
    public $search = '', $searchpatfirst = '', $searchpatmiddle = '', $searchpatlast = '', $searchhpercode = '', $searchpatdob = '';
    public $hpercode;
    public $enc_list = [];

    public function searchBtn()
    {
        $this->reset('enc_list');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchpatfirst()
    {
        $this->resetPage();
    }

    public function updatingSearchpatmiddle()
    {
        $this->resetPage();
    }

    public function updatingSearchpatlast()
    {
        $this->resetPage();
    }

    public function updatingSearchhpercode()
    {
        $this->resetPage();
    }

    public function updatingSearchpatdob()
    {
        $this->resetPage();
    }

    public function render()
    {
        $patients = Patient::selectRaw('hpercode, patlast, patfirst, patmiddle, patsex, patbdate, patbplace, patcstat');

        if ($this->searchpatlast || $this->searchpatlast != '')
            $patients = $patients->where('patlast', 'LIKE', $this->searchpatlast);
        if ($this->searchpatmiddle || $this->searchpatmiddle != '')
            $patients = $patients->where('patmiddle', 'LIKE', $this->searchpatmiddle);
        if ($this->searchpatfirst || $this->searchpatfirst != '')
            $patients = $patients->where('patfirst', 'LIKE', $this->searchpatfirst . '%');
        if ($this->searchhpercode || $this->searchhpercode != '')
            $patients = $patients->where('hpercode', $this->searchhpercode);
        if ($this->searchpatdob || $this->searchpatdob != '')
            $patients = $patients->where(DB::raw('CONVERT(date, patbdate)'), '=', $this->searchpatdob);

        $patients = $patients->orderBy('patlast');

        return view('livewire.records.patients-list', [
            'patients' => $patients->take(10)->get(),
        ]);
    }

    public function select_patient($hpercode)
    {
        $this->enc_list = DB::select("SELECT enctr.enccode, enctr.encstat, enctr.toecode, diag.diagtext, enctr.encdate
                                    FROM henctr enctr
                                    LEFT JOIN hencdiag diag ON enctr.enccode = diag.enccode
                                    WHERE enctr.toecode <> 'WALKN' AND enctr.toecode <> '32' AND enctr.hpercode = ?
                                    ORDER BY enctr.encdate DESC
                                ", [$hpercode]);

        $this->hpercode = $hpercode;
    }

    public function view_enctr($code = null)
    {
        if ($code) {
            $this->enccode = $code;
        }
        $enccode = Crypt::encrypt(str_replace(' ', '--', $this->enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }

    public function walk_in()
    {
        $check_walkn = EncounterLog::where('encstat', 'W')
            ->where('toecode', 'WALKN')
            ->where('hpercode', $this->hpercode)
            ->latest('encdate')
            ->first();

        if ($check_walkn) {
            $enccode = Crypt::encrypt(str_replace(' ', '--', $check_walkn->enccode));
        } else {
            $new_enccode = '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now()));
            $new_encounter = EncounterLog::create([
                'enccode' => $new_enccode,
                'fhud' => '0000040',
                'hpercode' => $this->hpercode,
                'encdate' => now(),
                'enctime' => now(),
                'toecode' => 'WALKN',
                'sopcode1' => 'SELPA',
                'encstat' => 'W',
                'confdl' => 'N',
            ]);

            $enccode = Crypt::encrypt(str_replace(' ', '--', $new_encounter->enccode));
        }

        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }

    public function new_pat()
    {
        $this->validate([
            'searchpatfirst' => ['required', 'string', 'max:255'],
            'searchpatmiddle' => ['nullable', 'string', 'max:255'],
            'searchpatlast' => ['required', 'string', 'max:255'],
        ]);
        $prefix = 'W' . date('Y');
        $count = Patient::where('hpercode', 'LIKE', $prefix . '%')->count();
        $hpercode = $prefix . sprintf('%07d', $count + 1);

        $patient = Patient::firstOrCreate([
            'hpercode' => $hpercode,
            'hpatkey' => $hpercode,
            'hpatcode' => $hpercode,
            'patsex' => 'M',
            'hfhudcode' => '0000040',
            'patstat' => 'A',
            'patlock' => 'N',
            'confdl' => 'N',
            'updsw' => 'U',
            'datemod' => now(),
            'patfirst' => $this->searchpatfirst,
            'patmiddle' => $this->searchpatmiddle,
            'patlast' => $this->searchpatlast,
        ]);

        $this->hpercode = $hpercode;
        $this->walk_in();
    }
}
