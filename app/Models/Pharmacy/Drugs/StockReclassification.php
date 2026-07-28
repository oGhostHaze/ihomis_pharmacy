<?php

namespace App\Models\Pharmacy\Drugs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReclassification extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_stock_reclassifications';

    protected $fillable = [
        'reference_no',
        'source_stock_id',
        'destination_stock_id',
        'user_id',
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'source_chrgcode',
        'destination_chrgcode',
        'quantity',
        'unit_cost',
        'source_before',
        'source_after',
        'destination_before',
        'destination_after',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];
}
