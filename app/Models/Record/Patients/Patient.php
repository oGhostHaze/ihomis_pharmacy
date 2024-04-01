<?php

namespace App\Models\Record\Patients;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Record\Patients\PatientMss;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\References\Religion;
use App\Record\Patients\Models\PatientAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hperson', $primaryKey = 'hpercode', $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'hpatkey', 'hfhudcode', 'hpercode', 'hpatcode', 'hspocode', //patient contact number
        'patlast', 'patfirst', 'patmiddle', 'patsuffix', 'patprefix',
        'patmaidnm', 'patbdate', 'patbplace', 'patsex', 'patcstat', 'patempstat',
        'citcode', 'natcode', 'relcode', 'patmmdn', 'phicnum', 'patmedno',
        'patemernme', 'patemaddr', 'pattelno', 'relemacode', 'f_dec', 'patstat', //A
        'patlock', //N
        'datemod', 'updsw', //U
        'confdl', //N
        'fm_dec', 'bldcode', 'entryby', 'fatlast', 'fatfirst', 'fatmid', 'motlast',
        'motfirst', 'motmid', 'splast', 'spfirst', 'spmid', 'fataddr', 'motaddr', 'spaddr',
        'fatsuffix', 'motsuffix', 'spsuffix', 'fatempname', 'fatempaddr', 'fatempeml', 'fatemptel',
        'motempname', 'motempaddr', 'motempeml', 'motemptel', 'spempname', 'spempaddr', 'spempeml',
        'spemptel', 'fattel', 'mottel', 'mssno', 'srcitizen', 'picname', 's_dec', 'hsmokingcs', 'hospperson',
        'radcasenum', 'kmc', 'kmc_stat', 'renal', 'renal_stat', 'ems', 'ems_type', 'kmc_id', 'kmc_rem', 'kmc_date',
        'hperson_verified',
    ];

    public function fullname()
    {
        return $this->patlast . ', ' . $this->patsuffix . ' ' . $this->patfirst . ' ' . mb_substr($this->patmiddle, 0, 1) . '.';
    }

    public function age()
    {
        return Carbon::parse($this->patbdate)->diff(\Carbon\Carbon::now())->format('%yY, %mM and %dD');
    }

    public function addresses()
    {
        return $this->hasMany(PatientAddress::class, 'pid', 'pid');
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class, 'relcode', 'relcode')->select('reldesc');
    }

    public function bdate_format1()
    {
        return Carbon::parse($this->patbdate)->format('Y/m/d');
    }

    public function csstat()
    {
        $stat = $this->patcstat;
        switch ($stat) {
            case 'S':
                $stat = 'Single';
                break;
            case 'M':
                $stat = 'Married';
                break;
            case 'D':
                $stat = 'Divorced';
                break;
            case 'X':
                $stat = 'Separated';
                break;
            case 'W':
                $stat = 'Widow/Widower';
                break;
            case 'N':
                $stat = 'Not Applicable';
                break;
            default:
                $stat = '...';
        }

        return $stat;
    }

    public function empstat()
    {
        $stat = $this->patempstat;
        switch ($stat) {
            case 'EMPLO':
                $stat = 'Employed';
                break;
            case 'SELFE':
                $stat = 'Self-employed';
                break;
            case 'UNEMP':
                $stat = 'Unemployed';
                break;
            default:
                $stat = 'N/A';
        }

        return $stat;
    }

    public function gender()
    {
        return $this->patsex == 'M' ? 'Male' : 'Female';
    }

    //CDOE

    public function encounters()
    {
        return $this->hasMany(EncounterLog::class, 'hpercode', 'hpercode');
    }

    public function active_encounter()
    {
        $enctr = $this->hasMany(EncounterLog::class, 'hpercode', 'hpercode')
            ->where('encstat', 'A')
            ->where('toecode', '<>', 'WALKN')
            ->where('toecode', '<>', '32')
            ->where('enclock', 'N');

        $this_enctr = $enctr->latest('encdate')->take(1)->first();
        if ($this_enctr) {
            $toecode = $this_enctr->toecode;
            if ($toecode == 'OPD') {
                $enctr = $enctr->with('opd')->whereRelation('opd', 'opdstat', 'A');
            } elseif ($toecode == 'ER' or $toecode == 'ERADM') {
                $enctr = $enctr->with('er')->whereRelation('er', 'erstat', 'A');
            } elseif ($toecode == 'ADM' or $toecode == 'OPDAD') {
                $enctr = $enctr->with('adm')->whereRelation('adm', 'admstat', 'A');
            }
        }

        return $enctr;
    }

    public function latest_encounter()
    {
        return $this->hasMany(EncounterLog::class, 'hpercode', 'hpercode')
            ->where('encstat', 'A')
            ->where('toecode', '<>', 'WALKN')
            ->where('toecode', '<>', '32')
            ->where('enclock', 'N')
            ->latest('encdate');
    }

    public function admission()
    {
        return $this->hasOne('App\Hospital\emr\hadmlog', 'hpercode', 'hpercode');
    }

    public function opd()
    {
        return $this->hasOne('App\Hospital\emr\hopdlog', 'hpercode', 'hpercode');
    }

    public static function address($hpercode)
    {
        $address = DB::SELECT("SELECT hbrgy.bgyname, hcity.ctyname, hprov.provname
		FROM haddr
		INNER JOIN hbrgy ON hbrgy.bgycode = haddr.brg
		INNER JOIN hcity ON hcity.ctycode = haddr.ctycode
		INNER JOIN hprov ON hprov.provcode = haddr.provcode
		WHERE haddr.hpercode='$hpercode'");

        if ($address)
            return $address[0]->bgyname . ' ' . $address[0]->ctyname . ', ' . $address[0]->provname;

        else
            return ' ';
    }

    public function mss()
    {
        return $this->belongsTo(PatientMss::class, 'enccode', 'enccode');
    }
}
