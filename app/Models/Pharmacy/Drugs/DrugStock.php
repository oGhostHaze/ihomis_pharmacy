<?php

namespace App\Models\Pharmacy\Drugs;

use Carbon\Carbon;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugForm;
use Awobaz\Compoships\Compoships;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\DrugRoute;
use App\Models\Pharmacy\DrugStrength;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugStock extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_stocks';

    protected $fillable = [
        'dmdcomb',
        'dmdctr',
        'loc_code',
        'chrgcode',
        'exp_date',
        'stock_bal',
        'beg_bal',
        'retail_price',
        'dmdprdte',
        'drug_concat',
        'dmdnost',
        'strecode',
        'formcode',
        'rtecode',
        'brandname',
        'dmdrem',
        'dmdrxot',
        'gencode',
    ];

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

    public function balance()
    {
        return number_format($this->stock_bal ?? 0, 0);
    }

    public function expiry()
    {
        if (Carbon::parse($this->exp_date)->diffInDays(now(), false) >= 1 && $this->stock_bal > 0) {
            $badge = '<span class="badge badge-xs text-nowrap badge-error">' . Carbon::create($this->exp_date)->format('Y-m-d') . '</span>';
        } elseif (Carbon::parse($this->exp_date)->diffInDays(now(), false) > -182.5 && $this->stock_bal > 0) {
            $badge = '<span class="badge badge-xs text-nowrap badge-warning">' . Carbon::create($this->exp_date)->format('Y-m-d') . '</span>';
        } elseif ($this->stock_bal < 1) {
            $badge = '<span class="badge badge-xs text-nowrap badge-ghost">' . Carbon::create($this->exp_date)->format('Y-m-d') . '</span>';
        } elseif (Carbon::parse($this->exp_date)->diffInDays(now(), false) <= -182.5) {
            $badge = '<span class="badge badge-xs text-nowrap badge-success">' . Carbon::create($this->exp_date)->format('Y-m-d') . '</span>';
        }

        return $badge;
    }

    public function prices()
    {
        // return $this->hasMany(DrugPrice::class, ['dmdcomb', 'dmdctr', 'dmhdrsub', 'expdate'], ['dmdcomb', 'dmdctr', 'chrgcode', 'exp_date']);
        return $this->hasMany(DrugPrice::class, ['dmdcomb', 'dmdctr', 'dmhdrsub'], ['dmdcomb', 'dmdctr', 'chrgcode'])
            ->where('expdate', 'LIKE', '%' . $this->exp_date)
            ->latest('dmdprdte');
    }

    public function stock_prices()
    {
        return $this->hasMany(DrugPrice::class, 'stock_id', 'id')->latest('dmdprdte');
    }

    public function issued_drugs()
    {
        return $this->hasMany(DrugStockIssue::class, 'stock_id');
    }

    public function current_price()
    {
        return $this->belongsTo(DrugPrice::class, 'dmdprdte', 'dmdprdte');
    }

    public function drug_concat()
    {
        $concat = explode('_', $this->drug_concat);

        return implode("", $concat);
    }
}
