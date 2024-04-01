<?php

namespace App\Models\Record\Encounters;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Record\Encounters\ErLog;
use App\Models\Record\Patients\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Encounters\OpdLog;
use App\Models\Record\Encounters\Diagnosis;
use App\Models\Record\Encounters\AdmissionLog;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Record\Prescriptions\PrescriptionData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EncounterLog extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.henctr', $primaryKey = 'enccode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'enccode',
        'fhud',
        'hpercode',
        'encdate',
        'enctime',
        'toecode',
        'sopcode1',
        'encstat',
        'confdl',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hpercode', 'hpercode');
    }

    public function opd()
    {
        return $this->belongsTo(OpdLog::class, 'enccode', 'enccode')->has('patient')->has('provider');
    }

    public function er()
    {
        return $this->belongsTo(ErLog::class, 'enccode', 'enccode')->has('patient')->has('provider');
    }

    public function adm()
    {
        return $this->belongsTo(AdmissionLog::class, 'enccode', 'enccode')->has('patient')->has('patient_room');
    }

    public function enctr_type()
    {
        if ($this->toecode == 'OPD') {
            return 'OPD';
        } elseif ($this->toecode == 'ER') {
            return 'ER';
        } elseif ($this->toecode == 'ADM' or $this->toecode == 'ERADM' or $this->toecode == 'OPDAD') {
            return 'ADMITTED';
        }
    }

    public function active_encounter()
    {
        if ($this->toecode == 'OPD') {
            $enctr = $this->opd()->whereRelation('opd', 'opdstat', 'A');
        } elseif ($this->toecode == 'ER') {
            $enctr = $this->opd()->whereRelation('er', 'erstat', 'A');
        } elseif ($this->toecode == 'ADM') {
            $enctr = $this->opd()->whereRelation('adm', 'admstat', 'A');
        }

        return $enctr->latest('encdate');
    }

    public function diag()
    {
        return $this->belongsTo(Diagnosis::class, 'enccode', 'enccode');
    }

    public function rxo()
    {
        return $this->hasMany(DrugOrder::class, 'enccode', 'enccode')->with('charge')->with('dm');
    }

    public function active_prescription()
    {
        return $this->hasMany(Prescription::class, 'enccode', 'enccode')->with('employee')->with('data_active')->has('data_active');
    }

    public function active_prescribed_meds()
    {
        return $this->hasManyThrough(PrescriptionData::class, Prescription::class, 'enccode', 'id', 'presc_id', 'enccode');
    }
}
