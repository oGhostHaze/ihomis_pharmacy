<?php

namespace App\Http\Livewire\Records;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use Carbon\Carbon;
use Livewire\Component;
use App\Models\References\City;
use Illuminate\Support\Facades\DB;
use App\Models\References\Barangay;
use App\Models\References\Province;
use App\Models\References\Religion;
use Illuminate\Support\Facades\Auth;
use App\Models\Record\Patients\Patient;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Record\Patients\Models\PatientAddress;

class PatientRegister extends Component
{
    use LivewireAlert;

    public $hpercode, $patfirst, $patmiddle, $patlast, $patsuffix, $hspocode, $patsex = 'F', $patbplace, $patcstat = 'S', $patempstat = 'UNEMP', $natcode = 'FILIP', $relcode, $patstr, $brg, $patmaidnm, $patmmdn,
        $fatlast, $fatmid, $fatfirst, $fatsuffix, $fataddr, $fattel, $fatempname, $fatempaddr, $fatempeml, $fatemptel, $motlast, $motmid, $motfirst,
        $motsuffix, $motaddr, $mottel, $motempname, $motempaddr, $motempeml, $motemptel, $splast, $spmid, $spfirst, $spsuffix, $spaddr, $sptel, $spempname,
        $spempaddr, $spempeml, $spemptel, $requesting_user, $patage, $patbdate, $ctycode, $provcode = '0128', $s_dec, $f_dec, $fmdec;

    public $moth_maiden_firstname, $moth_maiden_midname, $moth_maiden_lastname, $moth_maiden_suffix;
    public $maiden_firstname, $maiden_midname, $maiden_lastname, $maiden_suffix;

    public function render()
    {
        $religions = Religion::orderby('reldesc')->get();
        $provinces = Province::orderby('provname')->get();
        $cities = City::where('ctyprovcod', $this->provcode)->orderby('ctyname')->get();
        $barangays = Barangay::where('bgymuncod', $this->ctycode)->orderby('bgyname')->get();

        return view('livewire.records.patient-register', compact('cities', 'barangays', 'provinces', 'religions'));
    }

    // public function mount(){

    //     $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code')->where('stock_bal', '>', 0)->get();

    //     foreach ($stocks as $stock) {
    //         $card = DrugStockCard::
    //         where('reference', '<', 0)
    //         ->where('rec', 0)
    //         ->where('chrgcode', $stock->chrgcode)
    //         ->where('loc_code', $stock->loc_code)
    //         ->where('dmdcomb', $stock->dmdcomb)
    //         ->where('dmdctr', $stock->dmdctr)
    //         ->where('exp_date', $stock->exp_date)
    //         ->first();

    //         if($card){
    //             $card->reference = $stock->stock_bal;
    //             $card->rec = null;
    //             $card->save();
    //         }
    //     }
    // }

    public function updatedPatbdate()
    {
        $this->patage = Carbon::parse($this->patbdate)->age;
    }

    public function submit_request()
    {
        $this->requesting_user = Auth::user()->id;
        $this->patmmdn = $this->moth_maiden_lastname . ', ' . $this->moth_maiden_firstname . ' ' . $this->moth_maiden_suffix . ' ' . $this->moth_maiden_midname;
        $this->patmaidnm = $this->maiden_lastname . ', ' . $this->maiden_firstname . ' ' . $this->maiden_suffix . ' ' . $this->maiden_midname;

        $patient_details = $this->validate([
            'hpercode' => ['required', 'string', 'max:20', 'unique:hospital.dbo.hperson,hpercode'],
            'patlast' => ['required', 'string', 'max:50'],
            'patfirst' => ['required', 'string', 'max:50'],
            'patmiddle' => ['nullable', 'string', 'max:50'],
            'patsuffix' => ['nullable', 'string', 'max:5'],
            'hspocode' => ['nullable', 'string', 'max:20'],
            'patcstat' => ['required', 'string', 'max:1'],
            'patbdate' => ['required', 'date', 'before_or_equal:' . date('Y-m-d')],
            'patbplace' => ['required', 'string', 'max:60'],
            'patsex' => ['required', 'string', 'max:1'],
            'patempstat' => ['required', 'string', 'max:5'],
            'natcode' => ['required', 'string', 'max:5'],
            'relcode' => ['nullable', 'string', 'max:5'],
            'patmaidnm' => ['nullable', 'string', 'max:60'],
            'patmmdn' => ['nullable', 'string', 'max:60'],
            'fatlast' => ['nullable', 'string', 'max:50'],
            'fatmid' => ['nullable', 'string', 'max:50'],
            'fatfirst' => ['nullable', 'string', 'max:50'],
            'fatsuffix' => ['nullable', 'string', 'max:5'],
            'fataddr' => ['nullable', 'string', 'max:255'],
            'fattel' => ['nullable', 'string', 'max:20'],
            'fatempname' => ['nullable', 'string', 'max:50'],
            'fatempaddr' => ['nullable', 'string', 'max:150'],
            'fatempeml' => ['nullable', 'string', 'max:20'],
            'fatemptel' => ['nullable', 'string', 'max:20'],
            'motlast' => ['nullable', 'string', 'max:50'],
            'motmid' => ['nullable', 'string', 'max:50'],
            'motfirst' => ['nullable', 'string', 'max:50'],
            'motsuffix' => ['nullable', 'string', 'max:5'],
            'motaddr' => ['nullable', 'string', 'max:255'],
            'mottel' => ['nullable', 'string', 'max:20'],
            'motempname' => ['nullable', 'string', 'max:50'],
            'motempaddr' => ['nullable', 'string', 'max:150'],
            'motempeml' => ['nullable', 'string', 'max:20'],
            'motemptel' => ['nullable', 'string', 'max:20'],
            'splast' => ['nullable', 'string', 'max:50'],
            'spmid' => ['nullable', 'string', 'max:50'],
            'spfirst' => ['nullable', 'string', 'max:50'],
            'spsuffix' => ['nullable', 'string', 'max:5'],
            'spaddr' => ['nullable', 'string', 'max:255'],
            'spempname' => ['nullable', 'string', 'max:50'],
            'spempaddr' => ['nullable', 'string', 'max:150'],
            'spempeml' => ['nullable', 'string', 'max:20'],
            'spemptel' => ['nullable', 'string', 'max:20'],
        ]);
        $patient_details['hpatkey'] = $patient_details['hpercode'];
        $patient_details['hpatcode'] = $patient_details['hpercode'];
        $patient_details['patstat'] = 'A';
        $patient_details['patlock'] = 'N';
        $patient_details['datemod'] = now();
        $patient_details['confdl'] = 'N';
        $patient_details['updsw'] = 'U';
        $patient_details['hfhudcode'] = '0000040';

        $patient_address = $this->validate([
            'patstr' => ['required', 'string', 'max:100'],
            'provcode' => ['required', 'string', 'max:4'],
            'ctycode' => ['required', 'string', 'max:6'],
            'brg' => ['required', 'string', 'max:9'],
        ]);
        $patient_address['hpercode'] = $patient_details['hpercode'];
        $patient_address['cntrycode'] = 'PHIL';
        $patient_address['addstat'] = 'A';
        $patient_address['addlock'] = 'N';
        $patient_address['datemod'] = now();
        $patient_address['updsw'] = 'N';
        $patient_address['confdl'] = 'N';
        $patient_address['haddrdte'] = now();
        $patient_address['entryby'] = Auth::user()->id;

        Patient::create($patient_details);
        PatientAddress::create($patient_address);

        $this->reset();
        $this->alert('success', 'Patient record successfully saved!');
    }

    public function check_record()
    {
        $validated = $this->validate([
            'patlast' => ['required'],
            'patfirst' => ['required'],
            'patmiddle' => ['nullable'],
            'patsuffix' => ['nullable'],
            'patcstat' => ['nullable'],
            'patbdate' => ['required'],
        ]);

        $existing_record = Patient::where('patlast', $validated['patlast'])
            ->where('patfirst', $validated['patfirst'])
            ->where('patmiddle', $validated['patmiddle'])
            ->where(DB::raw('CAST(patbdate as DATE)'), $validated['patbdate']);

        if ($existing_record->count() == 1) {
            $this->alert('success', 'Record found! Hospital #: ' . $existing_record->first()->hpercode, [
                'showConfirmButton' => true,
                'confirmButtonText' => 'View Patient Record',
                'onConfirmed' => 'logout',
                'timer' => false,
                'toast' => false,
                'showDenyButton' => true,
                'denyButtonText' => 'Close',
                'position' => 'center',
            ]);
        } else {
            $this->alert('error', 'No record found!');
        }
    }
}
