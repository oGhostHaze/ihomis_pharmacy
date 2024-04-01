<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmManual extends Model
{
    use HasFactory;
    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_manuals';

    protected $fillable = [
        'title',
        'description',
        'photos',
    ];
}
