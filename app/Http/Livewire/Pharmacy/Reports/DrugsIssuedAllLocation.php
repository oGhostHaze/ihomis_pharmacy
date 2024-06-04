<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DrugsIssuedAllLocation extends Component
{
    use WithPagination;

    public $filter_charge = 'DRUMB,*Drugs and Meds (Revolving) Satellite';
    public $date_from, $date_to, $dmdcomb, $dmdctr;

    public function updatedSelectedDrug()
    {
        $drug = $this->selected_drug;
        $selected_drug = explode(',', $drug);
        $this->dmdcomb = $selected_drug[0];
        $this->dmdctr = $selected_drug[1];
    }

    public function updatingFilterCharge()
    {
        $this->resetPage();
    }
    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH', 'DRUMAI', 'DRUMAJ'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $drugs_issued = DB::select("SELECT rxi.enccode, rxi.qty, rxi.hpercode, rxi.pcchrgcod, rxi.issuedte, hdr.drug_concat, ward.wardname, room.rmname, pat.patlast, pat.patfirst, pat.patmiddle, emp2.name, emp.firstname, emp.lastname, emp.middlename
        FROM hrxoissue rxi
        INNER JOIN hrxo rxo ON rxi.docointkey = rxo.docointkey
        INNER JOIN hperson as pat ON rxi.hpercode = pat.hpercode
        LEFT JOIN hpersonal as emp ON rxi.issuedby = emp.employeeid
        LEFT JOIN pharm_users as emp2 ON rxi.issuedby = emp2.employeeid
        INNER JOIN hospital.dbo.hdmhdr hdr ON rxo.dmdcomb = hdr.dmdcomb AND rxo.dmdctr = hdr.dmdctr
        LEFT JOIN hward ward ON (SELECT TOP(1) wardcode FROM hpatroom WHERE enccode = rxi.enccode ORDER BY hprtime DESC) = ward.wardcode
        LEFT JOIN hroom room ON (SELECT TOP(1) rmintkey FROM hpatroom WHERE enccode = rxi.enccode ORDER BY hprtime DESC) = room.rmintkey
        WHERE issuedfrom = '" . $filter_charge[0] . "'
        AND issuedte BETWEEN '" . $date_from . "' AND '" . $date_to . "'
        ORDER BY hdr.drug_concat ASC, rxi.issuedte DESC");

        return view('livewire.pharmacy.reports.drugs-issued-all-location', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
        ]);
    }

    public function mount()
    {
        $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
    }
}
