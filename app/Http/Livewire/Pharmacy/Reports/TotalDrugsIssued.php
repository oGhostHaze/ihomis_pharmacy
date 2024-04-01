<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Dispensing\DrugOrderIssue;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TotalDrugsIssued extends Component
{

    public $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to, $location_id, $drugs, $selected_drug, $dmdcomb, $dmdctr;


    public function updatedSelectedDrug()
    {
        $drug = $this->selected_drug;
        $selected_drug = explode(',', $drug);
        $this->dmdcomb = $selected_drug[0];
        $this->dmdctr = $selected_drug[1];
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $drugs_issued = DB::select("SELECT drug.drug_concat, charge.chrgdesc, SUM(rxo.qty) as qty, rx.exp_date
                                    FROM hospital.dbo.hrxoissue rxo
                                    INNER JOIN hospital.dbo.hdmhdr drug ON rxo.dmdcomb = drug.dmdcomb AND rxo.dmdctr = drug.dmdctr
                                    INNER JOIN hospital.dbo.hcharge charge ON rxo.chrgcode = charge.chrgcode
                                    INNER JOIN hospital.dbo.hrxo rx ON rxo.docointkey = rx.docointkey
                                    WHERE rxo.issuedte BETWEEN ? AND ?
                                    AND rxo.chrgcode LIKE ?
                                    AND rx.loc_code = ?
                                    GROUP BY drug.drug_concat, charge.chrgdesc, rx.exp_date
                                    ORDER BY drug.drug_concat ASC
                                    ", [$date_from, $date_to, $filter_charge[0] ?? '%%', $this->location_id]);

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.total-drugs-issued', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->date_from = Carbon::parse(now())->startOfDay()->format('Y-m-d H:i:s');
        $this->date_to = Carbon::parse(now())->endOfDay()->format('Y-m-d H:i:s');
        $this->location_id = session('pharm_location_id');
        $this->drugs = Drug::where('dmdstat', 'A')
            // ->whereHas('sub', function ($query) {
            //     return $query->where('dmhdrsub', 'LIKE', '%DRUM%');
            // })
            ->whereNotNull('drug_concat')
            ->has('generic')
            ->orderBy('drug_concat', 'ASC')->get();
    }
}
