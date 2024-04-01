<?php

namespace App\Models\Pharmacy;

use App\Models\Pharmacy\Drugs\DrugStock;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Drug extends Model
{
    use Compoships;
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hdmhdr';
    protected $primaryKey = 'dmdcomb';
    protected $keyType = 'string';
    public $timestamps = false, $incrementing = false;

    protected $fillable = [
        'drug_concat',
    ];

    public function generic()
    {
        return $this->hasOneThrough(DrugGeneric::class, DrugGroup::class, 'grpcode', 'gencode', 'grpcode', 'gencode');
    }

    public function strength()
    {
        return $this->belongsTo(DrugStrength::class, 'strecode', 'strecode');
    }

    public function form()
    {
        return $this->belongsTo(DrugForm::class, 'formcode', 'formcode');
    }

    public function route()
    {
        return $this->belongsTo(DrugRoute::class, 'rtecode', 'rtecode');
    }

    public function sub()
    {
        return $this->belongsTo(DrugSub::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }

    public function drug_name()
    {
        $generic = $this->generic ? $this->generic->gendesc : '';
        $dmdnost = $this->dmdnost;
        $brandname = $this->brandname;
        $strength = $this->strength->stredesc ?? $this->strecode;
        $form = $this->form->fomdesc ?? $this->formcode;

        $drug = $generic . "_, " . $this->brandname . " " . $dmdnost . $strength . " " . $form;

        return $drug;
    }

    public function drug_concat()
    {
        $concat = explode('_', $this->drug_concat);

        return implode("", $concat);
    }

    public function stock()
    {
        return $this->hasOne(DrugStock::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }
}
