<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;

class DrugsIssued extends Component
{
    public $filter_charge = '%%,All';
    public $date_from, $date_to, $location_id, $selected_drug, $dmdcomb, $dmdctr;

    public function updatedSelectedDrug()
    {
        if (!$this->selected_drug) {
            $this->dmdcomb = null;
            $this->dmdctr = null;

            return;
        }

        $selected_drug = explode(',', $this->selected_drug);
        $this->dmdcomb = $selected_drug[0] ?? null;
        $this->dmdctr = $selected_drug[1] ?? null;
    }

    public function updatingFilterCharge()
    {
        $this->resetDrugFilter();
    }

    public function updatingLocationId()
    {
        $this->resetDrugFilter();
    }

    public function updatingDateFrom()
    {
        $this->resetDrugFilter();
    }

    public function updatingDateTo()
    {
        $this->resetDrugFilter();
    }

    private function resetDrugFilter()
    {
        $this->selected_drug = null;
        $this->dmdcomb = null;
        $this->dmdctr = null;
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge ?: '%%,All', 2);
        $charge_code = $filter_charge[0] ?: '%%';
        $charge_label = $filter_charge[1] ?? 'All';

        $issued_drugs_query = DB::table('hrxoissue as rxi')
            ->join('hrxo as rxo', 'rxi.docointkey', '=', 'rxo.docointkey')
            ->join('hospital.dbo.hdmhdr as hdr', function ($join) {
                $join->on('rxo.dmdcomb', '=', 'hdr.dmdcomb')
                    ->on('rxo.dmdctr', '=', 'hdr.dmdctr');
            })
            ->where('rxi.issuedfrom', 'LIKE', $charge_code)
            ->when($this->location_id, function ($query) {
                $query->where('rxo.loc_code', $this->location_id);
            })
            ->whereBetween(DB::raw('CONVERT(varchar(19), rxi.issuedte, 120)'), [$date_from, $date_to])
            ->whereNotNull('rxo.pcchrgcod');

        $issued_drugs = (clone $issued_drugs_query)
            ->select('rxo.dmdcomb', 'rxo.dmdctr', 'hdr.drug_concat')
            ->distinct()
            ->orderBy('hdr.drug_concat')
            ->get();

        $drugs_issued = $issued_drugs_query
            ->selectRaw("rxi.enccode, rxi.qty, rxi.hpercode, rxo.pcchrgcod, rxi.issuedte, hdr.drug_concat, ward.wardname, room.rmname, pat.patlast, pat.patfirst, pat.patmiddle, emp2.name, emp.firstname, emp.lastname, emp.middlename")
            ->join('hperson as pat', 'rxi.hpercode', '=', 'pat.hpercode')
            ->leftJoin('hpersonal as emp', 'rxi.issuedby', '=', 'emp.employeeid')
            ->leftJoin('pharm_users as emp2', 'rxi.issuedby', '=', 'emp2.employeeid')
            ->leftJoin('hward as ward', 'ward.wardcode', '=', DB::raw('(SELECT TOP(1) wardcode FROM hpatroom WHERE enccode = rxi.enccode ORDER BY hprtime DESC)'))
            ->leftJoin('hroom as room', 'room.rmintkey', '=', DB::raw('(SELECT TOP(1) rmintkey FROM hpatroom WHERE enccode = rxi.enccode ORDER BY hprtime DESC)'))
            ->when($this->dmdcomb && $this->dmdctr, function ($query) {
                $query->where('rxo.dmdcomb', $this->dmdcomb)
                    ->where('rxo.dmdctr', $this->dmdctr);
            })
            ->orderBy('hdr.drug_concat')
            ->orderByRaw('CONVERT(varchar(19), rxi.issuedte, 120) DESC')
            ->get();

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.drugs-issued', [
            'charge_codes' => $charge_codes,
            'current_charge' => $charge_label,
            'drugs_issued' => $drugs_issued,
            'issued_drugs' => $issued_drugs,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d\TH:i');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d\TH:i');
    }
}
