<?php

namespace App\Models\References;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hreligion';
    public $timestamps = false;
    protected $primaryKey = 'relcode';
    protected $keyType   = 'string';
}
