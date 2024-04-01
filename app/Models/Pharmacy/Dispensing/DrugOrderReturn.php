<?php

namespace App\Models\Pharmacy\Dispensing;

use App\Models\Pharmacy\Drug;
use App\Models\Hospital\Employee;
use Awobaz\Compoships\Compoships;
use App\Models\References\ChargeCode;
use App\Models\Record\Patients\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Admission\PatientRoom;
use App\Models\Record\Encounters\AdmissionLog;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugOrderReturn extends Model
{
    use Compoships;
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hrxoreturn', $primaryKey = 'docointkey', $keyType = 'string';
    public $timestamps = false, $incrementing = false;

    protected $fillable = [
        'docointkey',
        'enccode',
        'hpercode',
        'dmdcomb',
        'returndate',
        'returntime',
        'qty',
        'uomcode',
        'returnby',
        'status',
        'rxolock',
        'datemod',
        'updsw',
        'confdl',
        'entryby',
        'locacode',
        'dmdctr',
        'dmdprdte',
        'remarks',
        'returnfrom',
        'chrgcode',
        'pcchrgcod',
        'rcode',
        'retslipfrom',
        'unitprice',
        'pchrgup',
    ];

    public function dm()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])
            ->with('generic')
            ->with('strength')
            ->with('form');
    }

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'returnfrom', 'chrgcode');
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'returnby', 'employeeid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'returnby', 'employeeid');
    }

    public function return_date()
    {
        return date('m/d/Y H:i A', strtotime($this->returndate));
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hpercode', 'hpercode');
    }

    public function encounter()
    {
        return $this->belongsTo(EncounterLog::class, 'enccode', 'enccode');
    }

    public function adm_pat_room()
    {
        return $this->hasOneThrough(PatientRoom::class, AdmissionLog::class, 'enccode', 'enccode', 'enccode')
            ->with('ward')
            ->with('room');
    }

    public function main_order()
    {
        return $this->belongsTo(DrugOrder::class, 'docointkey', 'docointkey');
    }
}
