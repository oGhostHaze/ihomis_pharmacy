<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyStockCard extends Component
{
    public $date_from, $date_to, $location_id, $drugs, $dmdcomb, $dmdctr, $fund_sources, $selected_drug, $selected_fund, $chrgcode, $chrgdesc;


    public function updatedSelectedDrug()
    {
        $drug = $this->selected_drug;
        $selected_drug = explode(',', $drug);
        $this->dmdcomb = $selected_drug[0];
        $this->dmdctr = $selected_drug[1];
    }

    public function updatedSelectedFund()
    {
        $fund = $this->selected_fund;
        $selected_fund = explode(',', $fund);
        $this->chrgcode = $selected_fund[0];
        $this->chrgdesc = $selected_fund[1];
    }

    public function render()
    {
        $locations = PharmLocation::all();
        $cards = DrugStockCard::select(DB::raw('reference, SUM(rec) as rec, SUM(iss) as iss, SUM(bal) as bal'), 'drug_concat', 'exp_date', 'stock_date', 'reference', 'chrgcode')
            ->where('dmdcomb', $this->dmdcomb)
            ->where('dmdctr', $this->dmdctr)
            ->whereBetween('stock_date', [$this->date_from, $this->date_to])
            ->where('loc_code', $this->location_id);

        if ($this->selected_fund) {
            $cards = $cards->where('chrgcode', $this->chrgcode);
        }

        $cards = $cards->groupBy('dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'stock_date')
            ->orderBy('stock_date', 'ASC')
            ->orderBy('drug_concat', 'ASC')
            ->orderBy('exp_date', 'ASC')
            ->with('charge')
            ->get();

        return view('livewire.pharmacy.reports.daily-stock-card', compact(
            'locations',
            'cards',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->subDays(2)->format('Y-m-d');
        $this->date_to = Carbon::parse(now())->format('Y-m-d');

        $this->drugs = Drug::where('dmdstat', 'A')
            // ->whereHas('sub', function ($query) {
            //     return $query->where('dmhdrsub', 'LIKE', '%DRUM%');
            // })
            ->whereNotNull('drug_concat')
            ->has('generic')
            ->orderBy('drug_concat', 'ASC')->get();

        $this->fund_sources = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();
    }
}
