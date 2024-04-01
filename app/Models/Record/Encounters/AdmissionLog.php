<?php

namespace App\Models\Record\Encounters;

use Carbon\Carbon;
use App\Models\Record\Patients\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Prescriptions\Prescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdmissionLog extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hadmlog', $primaryKey = 'enccode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false ;

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hpercode', 'hpercode');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'hpercode', 'hpercode');
    }

    public function patient_room()
    {
        return $this->belongsTo(PatientRoom::class, 'enccode', 'enccode');
    }

    public function disdate_format1()
    {
        return Carbon::parse($this->disdate)->format('Y/m/d');
    }

    public function distime_format1()
    {
        return Carbon::parse($this->distime)->format('g:i A');
    }
}
