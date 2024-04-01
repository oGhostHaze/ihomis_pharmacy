<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Hospital\Department;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;

class DrugsIssuedDepartments extends Component
{
    public $locations, $location_id;
    public $date_from, $date_to;
    public $charge_codes, $filter_charge;
    public $depts, $deptcode;

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i:s');

        if (!$this->deptcode) {
            $this->deptcode = '%%';
        }

        if (!$this->filter_charge) {
            $this->filter_charge = '%%';
        }

        $drugs_issued = DB::select("SELECT dept.deptname, drug.drug_concat, charge.chrgdesc, SUM(rxo.qty) as qty
                                    FROM hospital.dbo.hrxoissue rxo
                                    INNER JOIN hospital.dbo.hrxo ON rxo.docointkey = hrxo.docointkey
                                    INNER JOIN hospital.dbo.hpatroom pat_room ON rxo.enccode = pat_room.enccode
                                    INNER JOIN webapp.dbo.prescription_data_issued rx_i ON rxo.docointkey = rx_i.docointkey
                                    INNER JOIN webapp.dbo.prescription_data rx_d ON rx_i.presc_data_id = rx_d.id
                                    INNER JOIN hospital.dbo.hpersonal dr ON rx_d.entry_by = dr.employeeid
                                    INNER JOIN hospital.dbo.hdept dept ON dr.deptcode = dept.deptcode
                                    INNER JOIN hospital.dbo.hdmhdr drug ON rxo.dmdcomb = drug.dmdcomb AND rxo.dmdctr = drug.dmdctr
                                    INNER JOIN hospital.dbo.hcharge charge ON rxo.chrgcode = charge.chrgcode
                                    WHERE rxo.issuedte BETWEEN '" . $date_from . "' AND '" . $date_to . "'
                                    AND dept.deptcode LIKE '" . $this->deptcode . "'
                                    AND rxo.chrgcode LIKE '" . $this->filter_charge . "'
                                    AND hrxo.loc_code = '" . session('pharm_location_id') . "'
                                    GROUP BY dept.deptname, drug.drug_concat, charge.chrgdesc
                                    ORDER BY dept.deptname ASC, drug.drug_concat ASC
                                    ");

        return view('livewire.pharmacy.reports.drugs-issued-departments', compact(
            'drugs_issued',
        ));
    }

    public function mount()
    {
        $this->locations = PharmLocation::all();
        $this->depts = Department::all();
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfDay()->format('Y-m-d');
        $this->date_to = Carbon::parse(now())->endOfDay()->format('Y-m-d');
        // $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        // $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
        $this->charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();
    }
}