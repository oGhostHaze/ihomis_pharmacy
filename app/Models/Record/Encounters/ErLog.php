<?php

namespace App\Models\Record\Encounters;

use Carbon\Carbon;
use App\Models\Hospital\Provider;
use App\Models\References\ServiceType;
use App\Models\Record\Patients\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Prescriptions\Prescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErLog extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.herlog', $primaryKey = 'enccode', $keyType = 'string';
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

    public function erdate_format1()
    {
        return Carbon::parse($this->erdate)->format('Y/m/d');
    }

    public function ertime_format1()
    {
        return Carbon::parse($this->ertime)->format('g:i A');
    }

    public function service_type()
    {
        return $this->belongsTo(ServiceType::class, 'tscode', 'tscode');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'licno', 'licno')->with('emp');
    }
}
