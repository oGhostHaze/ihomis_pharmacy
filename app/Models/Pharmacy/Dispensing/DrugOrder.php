<?php

namespace App\Models\Pharmacy\Dispensing;

use App\Models\Hospital\Employee;
use App\Models\Pharmacy\Dispensing\DrugOrderReturn;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Record\Patients\Patient;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Record\Prescriptions\PrescriptionData;
use App\Models\References\ChargeCode;
use App\Models\User;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugOrder extends Model
{
    use Compoships;
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hrxo', $primaryKey = 'docointkey', $keyType = 'string';
    public $timestamps = false, $incrementing = false;

    protected $fillable = [
        'docointkey', 'enccode', 'hpercode', 'rxooccid', 'rxoref', 'dmdcomb', 'repdayno1', 'rxostatus', 'rxolock', 'rxoupsw', 'rxoconfd', 'dmdctr',
        'pcchrgcod', 'pchrgqty', 'pchrgup', 'pcchrgamt', 'estatus', 'entryby', 'dodate', 'dotime', 'ordcon', 'orderupd',
        'dodtepost', 'dotmepost', 'qtyissued', 'qtybal', 'dmdprdte', 'locacode', 'orderfrom', 'issuetype',
        'exp_date', //added
        'loc_code', //added
        'item_id', //added
        'has_tag', //added
        'tx_type', //added
        'order_by',
        'ris',
        'prescription_data_id',
        'prescribed_by',
        'remarks',
    ];

    public function enctr()
    {
        return $this->belongsTo(EncounterLog::class, 'enccode', 'enccode');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hpercode', 'hpercode');
    }

    public function dm()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'enccode', 'enccode');
    }

    public function prescription_data()
    {
        return $this->belongsTo(PrescriptionData::class, 'prescription_data_id', 'id');
    }

    public function prescription_header()
    {
        return $this->hasMany(Prescription::class, 'enccode', 'enccode')->with('employee');
    }

    public function item()
    {
        return $this->belongsTo(DrugStock::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])->whereNotNull('dmdprdte')->where('stock_bal', '>', '0')->orderBy('exp_date', 'ASC');
    }

    public function items()
    {
        return $this->hasMany(DrugStock::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])->whereNotNull('dmdprdte')->where('stock_bal', '>', '0')->orderBy('exp_date', 'ASC');
    }

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'orderfrom', 'chrgcode');
    }

    public function status()
    {
        if ($this->estatus == 'U') {
            $badge = '<span class="badge badge-sm badge-warning">Pending</span>';
        } elseif ($this->estatus == 'P') {
            $badge = '<span class="badge badge-sm badge-primary">Charged</span>';
        } elseif ($this->estatus == 'S') {
            $badge = '<span class="badge badge-sm badge-success">Issued</span>';
        }

        return $badge;
    }

    public function returns()
    {
        return $this->hasMany(DrugOrderReturn::class, 'docointkey', 'docointkey');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'entryby', 'employeeid');
    }

    public function order_by()
    {
        return $this->belongsTo(Employee::class, 'order_by', 'employeeid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'entryby', 'employeeid');
    }
}
