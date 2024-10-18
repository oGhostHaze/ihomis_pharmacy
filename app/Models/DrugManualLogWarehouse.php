<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugManualLogWarehouse extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_manual_log_warehouses';

    protected $fillable = [
        'consumption_id',
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'chrgcode',
        'unit_cost',
        'unit_price',
        'beg_bal',
        'total_purchases',
        'sat_iss',
        'opd_iss',
        'cu_iss',
        'or_iss',
        'nst_iss',
        'others_iss',
        'returns_pullout',
        'generated_status',
    ];
}
