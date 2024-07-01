<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ItemsNearExpiryOverview extends Component
{

    use LivewireAlert;
    public $search, $location_id, $below_date;
    public $locations, $charge_codes;

    public function render()
    {
        $stocks = DrugStock::join('hcharge', 'hcharge.chrgcode', 'pharm_drug_stocks.chrgcode')
            ->join('hdmhdrprice', 'hdmhdrprice.dmdprdte', 'pharm_drug_stocks.dmdprdte')
            ->join('pharm_locations', 'pharm_locations.id', 'pharm_drug_stocks.loc_code')
            ->where('drug_concat', 'LIKE', '%' . $this->search . '%')
            ->where('loc_code', $this->location_id)
            ->where('exp_date', '<', $this->below_date)
            ->where('exp_date', '>', date('Y-m-d'))
            ->where('stock_bal', '>', 0)
            ->select(
                'pharm_drug_stocks.dmdcomb',
                'pharm_drug_stocks.dmdctr',
                'pharm_drug_stocks.formcode',
                'drug_concat',
                'hcharge.chrgdesc',
                'pharm_drug_stocks.chrgcode',
                'hdmhdrprice.dmselprice',
                'hdmhdrprice.dmduprice',
                'pharm_drug_stocks.loc_code',
                'pharm_drug_stocks.dmdprdte',
                'pharm_drug_stocks.updated_at',
                'pharm_drug_stocks.exp_date',
                'pharm_drug_stocks.stock_bal',
                'pharm_drug_stocks.id',
                'hdmhdrprice.has_compounding',
                'hdmhdrprice.compounding_fee',
                'pharm_locations.description',
            )
            ->get();

        return view('livewire.pharmacy.reports.items-near-expiry-overview', compact(
            'stocks',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->locations = PharmLocation::all();
        $this->below_date = Carbon::parse(now())->addMonths(6)->format('Y-m-d');

        $this->charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();
    }
}
