<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugManualLogHeader extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_manual_log_headers';

    protected $fillable = [
        'consumption_from',
        'consumption_to',
        'status',
        'entry_by',
        'closed_by',
        'loc_code',
    ];
}
