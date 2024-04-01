<?php

namespace App\Models\Record\Encounters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hencdiag', $primaryKey = 'enccode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false ;
}
