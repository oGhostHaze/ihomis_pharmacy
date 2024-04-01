<?php

namespace App\Record\Patients\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAddress extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.haddr', $primaryKey = 'hpercode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'hpercode',
        'patstr',
        'brg',
        'ctycode',
        'provcode',
        'patzip',
        'cntrycode', //PHIL
        'addstat', //A
        'addlock', //N
        'datemod', //now()
        'updsw', //N
        'confdl', //N
        'haddrdte', //now()
        'entryby', //user()->id
        'distzip' //null
    ];
}
