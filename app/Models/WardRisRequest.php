<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\RisWard;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WardRisRequest extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_ward_ris_requests';

    protected $fillable = [
        'trans_no',
        'stock_id',
        'ris_location_id',
        'dmdcomb',
        'dmdctr',
        'loc_code',
        'chrgcode',
        'issued_qty',
        'issued_by',
        'trans_stat',
        'dmdprdte'
    ];

    public function drug()
    {
        return $this->belongsTo(DrugStock::class, 'stock_id', 'id');
    }

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'chrgcode', 'chrgcode');
    }

    public function location()
    {
        return $this->belongsTo(PharmLocation::class, 'loc_code', 'id');
    }

    public function ward()
    {
        return $this->belongsTo(RisWard::class, 'ris_location_id', 'id');
    }

    public function created_at()
    {
        return Carbon::parse($this->created_at)->format('M d, Y G:i A');
    }

    public function updated_at()
    {
        if ($this->trans_stat == 'Requested') {
            $status = '<span class="mr-2 badge bg-slate-500 hover">' . $this->trans_stat . '</span>';
        } elseif ($this->trans_stat == 'Cancelled') {
            $status = '<span class="mr-2 bg-red-500 badge hover">' . $this->trans_stat . '</span>';
        } elseif ($this->trans_stat == 'Issued') {
            $status = '<span class="mr-2 bg-blue-500 badge hover">' . $this->trans_stat . '</span>';
        } elseif ($this->trans_stat == 'Received') {
            $status = '<span class="mr-2 bg-green-500 badge hover">' . $this->trans_stat . '</span>';
        }

        return '<div class="flex justify-between">' . $status . " " . Carbon::parse($this->updated_at)->diffForHumans() . '</div>';
    }
}
