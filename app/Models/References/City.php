<?php

namespace App\Models\References;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hcity';
    public $timestamps = false;
    protected $primaryKey = 'ctycode';
    protected $keyType   = 'string';
}
