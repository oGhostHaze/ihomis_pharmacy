<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\PharmLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PrescriptionIssuance extends Component
{
    public $date_from;
    public $date_to;
    public $location_id;

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d\TH:i');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d\TH:i');
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $prescribingDoctor = "COALESCE(
            NULLIF(rxi.prescribed_by, ''),
            NULLIF(rxo.prescribed_by, ''),
            NULLIF(pd.entry_by, '')
        )";

        $issued_prescriptions = DB::table('hrxoissue as rxi')
            ->join('hrxo as rxo', 'rxi.docointkey', '=', 'rxo.docointkey')
            ->join('hperson as pat', 'rxi.hpercode', '=', 'pat.hpercode')
            ->leftJoin('webapp.dbo.prescription_data as pd', function ($join) {
                $join->on('pd.id', '=', DB::raw("COALESCE(rxi.prescription_data_id, rxo.prescription_data_id)"));
            })
            ->leftJoin('hpersonal as doc', 'doc.employeeid', '=', DB::raw($prescribingDoctor))
            ->when($this->location_id, function ($query) {
                $query->where('rxo.loc_code', $this->location_id);
            })
            ->whereBetween(DB::raw('CONVERT(varchar(19), rxi.issuedte, 120)'), [$date_from, $date_to])
            ->selectRaw("
                rxi.issuedte,
                rxi.qty,
                pat.patlast,
                pat.patfirst,
                pat.patmiddle,
                pat.patsuffix,
                doc.lastname as doctor_lastname,
                doc.firstname as doctor_firstname,
                doc.middlename as doctor_middlename
            ")
            ->orderByRaw('CONVERT(varchar(19), rxi.issuedte, 120) DESC')
            ->get();

        return view('livewire.pharmacy.reports.prescription-issuance', [
            'issued_prescriptions' => $issued_prescriptions,
            'locations' => PharmLocation::all(),
        ]);
    }
}
