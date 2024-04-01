<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_sessions';
    protected $keyType = 'string';
    public $timestamps = false, $incrementing = false;
}
