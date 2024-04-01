<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\References\Supplier;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\PullOut;
use App\Models\Pharmacy\Drugs\PullOutItem;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class StockPullOutList extends Component
{

    use LivewireAlert;

    protected $listeners = ['pull_out'];

    public $search, $location_id, $below_date;
    public $locations, $charge_codes;
    public $selected_items = [], $pullout_date, $suppcode;

    public function pull_out()
    {
        $this->validate([
            'selected_items' => ['required', 'array'],
            'suppcode' => ['required', 'string'],
            'pullout_date' => ['required', 'date'],
        ]);

        $detail = PullOut::create([
            'pullout_date' => $this->pullout_date,
            'suppcode' => $this->suppcode,
            'pharm_location_id' => $this->location_id,
        ]);

        foreach ($this->selected_items as $item) {
            $stock = DrugStock::find($item);
            PullOutItem::create([
                'detail_id' => $detail->id,
                'stock_id' => $stock->id,
                'pullout_qty' => $stock->stock_bal,
            ]);
            $stock->stock_bal -= $stock->stock_bal;
            $stock->save();
        }

        $this->alert('success', 'Items pulled out successfully');
    }

    public function render()
    {
        $suppliers = Supplier::all();

        $stocks = DrugStock::join('hcharge', 'hcharge.chrgcode', 'pharm_drug_stocks.chrgcode')
            ->join('hdmhdrprice', 'hdmhdrprice.dmdprdte', 'pharm_drug_stocks.dmdprdte')
            ->join('pharm_locations', 'pharm_locations.id', 'pharm_drug_stocks.loc_code')
            ->where('drug_concat', 'LIKE', '%' . $this->search . '%')
            ->where('loc_code', $this->location_id)
            ->where('exp_date', '<', $this->below_date)
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

        return view('livewire.pharmacy.drugs.stock-pull-out-list', compact(
            'stocks',
            'suppliers',
        ));
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->locations = PharmLocation::all();
        $this->below_date = Carbon::parse(now())->addMonths(6)->format('Y-m-d');

        $this->charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();
    }
}
