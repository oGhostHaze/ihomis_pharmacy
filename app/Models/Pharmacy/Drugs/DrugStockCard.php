<?php

namespace App\Models\Pharmacy\Drugs;

use App\Models\Pharmacy\DrugPrice;
use App\Models\References\ChargeCode;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugStockCard extends Model
{
    use HasFactory;

    protected $table = 'pharm_drug_stock_cards';

    protected $fillable = [
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'chrgcode',
        'drug_concat',
        'exp_date',
        'stock_date',
        'reference',
        'rec',
        'iss',
        'bal',
        'pullout_qty',
        'dmdprdte',
    ];


    public function cur_price()
    {
        return $this->belongsTo(DrugPrice::class, 'dmdprdte', 'dmdprdte');
    }

    public function stock()
    {
        return $this->belongsTo(DrugStock::class, 'stock_id', 'id');
    }

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'chrgcode', 'chrgcode');
    }
}
