<?php

namespace App\Http\Livewire\Components;

use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConsolidatedDrugSummary extends Component
{
    public $readyToLoad = false;
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

    public function loadStocks()
    {
        $this->readyToLoad = true;
    }

    public function render()
    {
        if ($this->readyToLoad) {
            if ($this->selected_fund and $this->selected_fund != 'all') {
                $stocks = DB::select("SELECT hcharge.chrgdesc, pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                                    pds.dmdcomb, pds.dmdctr, pds.chrgcode
                                FROM pharm_drug_stocks as pds
                                JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                                WHERE pds.stock_bal > 0 AND pds.chrgcode LIKE '%" . $this->chrgcode . "'
                                    AND pds.loc_code LIKE '%" . $this->location_id . "%'
                                    AND pds.drug_concat LIKE '%" . $this->search . "%'
                                GROUP BY pds.drug_concat, hcharge.chrgdesc, pds.dmdcomb, pds.dmdctr, pds.chrgcode
                        ");
            } else {
                $stocks = DB::select("SELECT 'ALL' as chrgdesc, pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                        pds.dmdcomb, pds.dmdctr
                    FROM pharm_drug_stocks as pds
                    JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                    WHERE pds.stock_bal > 0 AND pds.loc_code LIKE '%" . $this->location_id . "%'
                        AND pds.drug_concat LIKE '%" . $this->search . "%'
                    GROUP BY pds.drug_concat, pds.dmdcomb, pds.dmdctr
                ");
            }
        } else {
            $stocks = [];
        }

        $locations = PharmLocation::where('non_pharma', false)
            ->orderBy('description')
            ->get();

        return view('livewire.components.consolidated-drug-summary', [
            'stocks' => $stocks,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->charges = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();
    }
}