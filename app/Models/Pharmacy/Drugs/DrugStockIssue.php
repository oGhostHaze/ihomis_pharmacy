<?php

namespace App\Models\Pharmacy\Drugs;

use App\Models\Pharmacy\Drug;
use Awobaz\Compoships\Compoships;
use App\Models\Pharmacy\DrugPrice;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugStockIssue extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_stock_issues';

    protected $fillable = [
        'stock_id',
        'docointkey',
        'dmdcomb',
        'dmdctr',
        'loc_code',
        'chrgcode',
        'exp_date',
        'qty',
        'returned_qty',
        'status',
        'user_id',
        'hpercode',
        'enccode',
        'toecode',
        'pcchrgcod',
        'pchrgup',
        'pcchrgamt',
        'sc_pwd',
        'ems',
        'maip',
        'wholesale',
        'pay',
        'medicare',
        'service',
        'govt_emp',
        'caf',
        'ris',
        'dmdprdte',
    ];

    public function stock()
    {
        return $this->belongsTo(DrugStock::class, 'stock_id');
    }

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'chrgcode', 'chrgcode');
    }

    public function location()
    {
        return $this->belongsTo(PharmLocation::class, 'loc_code', 'id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])
            ->with('strength')->with('form')->with('route')->with('generic');
    }

    public function current_price()
    {
        return $this->belongsTo(DrugPrice::class, 'dmdprdte', 'dmdprdte');
    }
}
