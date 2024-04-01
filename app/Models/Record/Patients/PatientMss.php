<?php

namespace App\Models\Record\Patients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientMss extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hpatmss', $primaryKey = 'enccode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function mss_class()
    {
        $class = "";
        switch ($this->mssikey) {
            case 'MSSA11111999':
            case 'MSSB11111999':
                $class = "Pay";
                break;

            case 'MSSC111111999':
                $class = "PP1";
                break;

            case 'MSSC211111999':
                $class = "PP2";
                break;

            case 'MSSC311111999':
                $class = "PP3";
                break;

            case 'MSSD11111999':
                $class = "Indigent";
                break;

            default:
                $class = "---";
        }

        return $class;
    }
}