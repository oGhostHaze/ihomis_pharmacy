<?php

namespace App\Models\References;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hprov';
    public $timestamps = false;
    protected $primaryKey = 'provcode';
    protected $keyType   = 'string';
}
