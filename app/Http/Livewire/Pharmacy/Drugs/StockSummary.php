<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\DrugStockReorderLevel;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class StockSummary extends Component
{
    use LivewireAlert;

    protected $listeners = ['update_reorder'];
    public $search, $location_id, $selected_fund, $charges, $chrgcode = '', $chrgdesc;

    public function updatedSelectedFund()
    {
        if ($this->selected_fund) {
            $fund = $this->selected_fund;
            $selected_fund = explode(',', $fund);
            $this->chrgcode = $selected_fund[0];
            $this->chrgdesc = $selected_fund[1];
        }
    }

    public function render()
    {
        $locationFilter = filled($this->location_id)
            ? " AND pds.loc_code = ? "
            : "";

        if ($this->selected_fund && $this->selected_fund != 'all') {

            $sql = "
                SELECT
                    hcharge.chrgdesc,
                    pds.drug_concat,
                    pds.lot_no,
                    pds.exp_date,
                    SUM(pds.stock_bal) AS stock_bal,
                    pds.dmdcomb,
                    pds.dmdctr,
                    pds.chrgcode
                FROM pharm_drug_stocks AS pds
                JOIN hcharge
                    ON pds.chrgcode = hcharge.chrgcode
                WHERE pds.stock_bal > 0
                    AND pds.chrgcode LIKE ?
                    {$locationFilter}
                    AND pds.drug_concat LIKE ?
                GROUP BY
                    pds.drug_concat,
                    hcharge.chrgdesc,
                    pds.dmdcomb,
                    pds.dmdctr,
                    pds.chrgcode,
                    pds.lot_no,
                    pds.exp_date
            ";

            $bindings = [
                '%' . $this->chrgcode,
            ];

            if (filled($this->location_id)) {
                $bindings[] = $this->location_id;
            }

            $bindings[] = '%' . $this->search . '%';

            $stocks = DB::select($sql, $bindings);
        } else {

            $sql = "
                SELECT
                    'ALL' AS chrgdesc,
                    pds.drug_concat,
                    SUM(pds.stock_bal) AS stock_bal,
                    pds.dmdcomb,
                    pds.dmdctr,
                    pds.lot_no,
                    pds.exp_date
                FROM pharm_drug_stocks AS pds
                JOIN hcharge
                    ON pds.chrgcode = hcharge.chrgcode
                WHERE pds.stock_bal > 0
                    {$locationFilter}
                    AND pds.drug_concat LIKE ?
                GROUP BY
                    pds.drug_concat,
                    pds.dmdcomb,
                    pds.dmdctr,
                    pds.lot_no,
                    pds.exp_date
            ";
        }

        $bindings = [];

        if (filled($this->location_id)) {
            $bindings[] = $this->location_id;
        }

        $bindings[] = '%' . $this->search . '%';

        $stocks = DB::select($sql, $bindings);

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.drugs.stock-summary', [
            'stocks' => $stocks,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->location_id = Auth::user()->pharm_location_id;

        $this->charges = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();
    }

    public function update_reorder($dmdcomb, $dmdctr, $chrgcode, $reorder_point)
    {
        DrugStockReorderLevel::updateOrCreate([
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'chrgcode' => $chrgcode,
        ], [
            'reorder_point' => $reorder_point,
            'user_id' => session('user_id'),
        ]);

        $this->alert('success', 'Reorder level updated');
    }
}
