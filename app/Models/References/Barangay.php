<?php

namespace App\Models\References;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;


    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hbrgy';
    public $timestamps = false;
    protected $primaryKey = 'bgycode';
    protected $keyType   = 'string';
}
