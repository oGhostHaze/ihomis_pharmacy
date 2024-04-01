<?php

namespace App\Models\Record\Encounters;

use App\Models\Hospital\Provider;
use Carbon\Carbon;
use App\Models\Record\Patients\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\References\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpdLog extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hopdlog', $primaryKey = 'enccode', $keyType = 'string';
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

    public function opddate_format1()
    {
        return Carbon::parse($this->opddate)->format('Y/m/d');
    }

    public function opdtime_format1()
    {
        return Carbon::parse($this->opdtime)->format('g:i A');
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
