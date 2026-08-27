<?php

namespace App\Models\Record\Prescriptions;

use App\Models\Hospital\Employee;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionData extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'webapp';
    protected $table = 'webapp.dbo.prescription_data';

    public function issued()
    {
        return $this->hasMany(PrescriptionDataIssued::class, 'presc_data_id');
    }

    public function dm()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }

    public function dmd()
    {
        return $this->join('hospital.dbo.hdmhdr as hdm', 'hdm.dmdcomb', 'LIKE', 'webapp.dbo.prescription_data.dmdcomb');
    }

    public function item()
    {
        return $this->belongsTo(DrugStock::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])->where('stock_bal', '>', '0')->orderBy('exp_date', 'ASC');
    }

    public function rx()
    {
        return $this->belongsTo(Prescription::class, 'presc_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'entry_by', 'employeeid')->with('dept')->with('provider');
    }

    public function issueRemarksText()
    {
        return self::formatIssueRemarks($this->qty, $this->remark, $this->frequency, $this->addtl_remarks);
    }

    public static function formatDrugDescription($drugConcat)
    {
        $raw = trim((string) $drugConcat);
        if ($raw === '') {
            return '';
        }

        $parts = preg_split('/_,/', $raw);
        $parts = array_map(function ($part) {
            return trim(str_replace('_', ' ', $part));
        }, $parts);
        $parts = array_filter($parts, function ($part) {
            return $part !== '';
        });

        return trim(implode(' ', $parts));
    }

    public static function formatIssueRemarks($qty, $frequencyText, $days, $addtlRemarks = null)
    {
        $parts = [];

        if ($qty !== null && $qty !== '') {
            if (is_numeric($qty)) {
                $qty = 0 + $qty;
            }
            $parts[] = (string) $qty;
        }

        $freq = trim((string) $frequencyText);
        if ($freq !== '') {
            $parts[] = $freq;
        }

        if ($days !== null && $days !== '' && is_numeric($days) && (int) $days > 0) {
            $n = (int) $days;
            $parts[] = $n === 1 ? 'for 1 day' : 'for ' . $n . ' days';
        }

        $text = implode(' ', $parts);
        $addtl = trim((string) $addtlRemarks);
        if ($addtl !== '') {
            $text = $text === '' ? $addtl : $text . ' - ' . $addtl;
        }

        if (function_exists('mb_strlen') && mb_strlen($text) > 255) {
            return mb_substr($text, 0, 255);
        }

        if (strlen($text) > 255) {
            return substr($text, 0, 255);
        }

        return $text;
    }
}
