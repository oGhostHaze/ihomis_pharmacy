<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugManualLogItem extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_manual_log_items';

    protected $fillable = [
        'detail_id',
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'chrgcode',

        'unit_cost',
        'unit_price',

        'beg_bal',

        'delivered',

        'trans_in',
        'trans_out',

        'adjustments',

        'issue_qty',
        'return_qty',

        'ems',
        'maip',
        'wholesale',
        'pay',
        'service',
        'caf',
        'ris',
        'pcso',
        'phic',
        'konsulta',
        'opdpay',
    ];
}
