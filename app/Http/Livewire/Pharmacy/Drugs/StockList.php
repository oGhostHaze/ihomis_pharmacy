<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Jobs\LogDrugTransaction;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class StockList extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['add_item', 'refresh' => '$refresh', 'add_item_new', 'update_item_new', 'adjust_qty'];

    public $search;
    public $location_id;
    public $dmdcomb, $chrgcode, $expiry_date, $qty, $unit_cost, $lot_no;
    public $has_compounding = false, $compounding_fee = 0;

    public $item_id;

    public $drugs, $locations, $charge_codes;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {

        // $stocks = DrugStock::with('charge')->with('location')->with('current_price')->has('current_price')
        //     ->where('loc_code', $this->location_id)
        //     ->where('drug_concat', 'LIKE', '%' . $this->search . '%')
        //     ->paginate(20);

        $stocks = DrugStock::join('hcharge', 'hcharge.chrgcode', 'pharm_drug_stocks.chrgcode')
            ->join('hdmhdrprice', 'hdmhdrprice.dmdprdte', 'pharm_drug_stocks.dmdprdte')
            ->join('pharm_locations', 'pharm_locations.id', 'pharm_drug_stocks.loc_code')
            ->where('drug_concat', 'LIKE', '%' . $this->search . '%')
            ->where('loc_code', $this->location_id)
            ->where('stock_bal', '>', 0)
            ->select(
                'pharm_drug_stocks.dmdcomb',
                'pharm_drug_stocks.dmdctr',
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
            ->orderBy('drug_concat', 'ASC')
            ->get();

        return view('livewire.pharmacy.drugs.stock-list', [
            'stocks' => $stocks,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');

        $this->locations = PharmLocation::all();

        $this->charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $this->drugs = Drug::where('dmdstat', 'A')
            // ->whereHas('sub', function ($query) {
            //     // return $query->whereIn('dmhdrsub', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE'));
            //     return $query->where('dmhdrsub', 'LIKE', '%DRUM%');
            // })
            ->whereNotNull('drug_concat')
            ->has('generic')->orderBy('drug_concat', 'ASC')
            ->get();
    }

    public function add_item_new()
    {
        $this->validate([
            'dmdcomb' => 'required',
            'unit_cost' => 'required',
            'qty' => ['required', 'numeric', 'min:0'],
            'expiry_date' => 'required',
            'chrgcode' => 'required',
        ]);

        $unit_cost = $this->unit_cost;
        $excess = 0;
        $markup_price = 0;
        $retail_price = 0;

        if ($unit_cost >= 10000.01) {
            $excess = $unit_cost - 10000;
            $markup_price = 1115 + ($excess * 0.05);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 1000.01 and $unit_cost <= 10000.00) {
            $excess = $unit_cost - 1000;
            $markup_price = 215 + ($excess * 0.10);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 100.01 and $unit_cost <= 1000.00) {
            $excess = $unit_cost - 100;
            $markup_price = 35 + ($excess * 0.20);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 50.01 and $unit_cost <= 100.00) {
            $excess = $unit_cost - 50;
            $markup_price = 20 + ($excess * 0.30);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 0.01 and $unit_cost <= 50.00) {
            $markup_price = $unit_cost * 0.40;
            $retail_price = $unit_cost + $markup_price;
        }

        if ($this->has_compounding) {
            $this->validate([
                'compounding_fee' => ['required', 'numeric', 'min:0'],
            ]);

            $retail_price = $retail_price + $this->compounding_fee;
        }

        $dm = explode(',', $this->dmdcomb);

        $drug = Drug::where('dmdcomb', $dm[0])->where('dmdctr', $dm[1])->first();

        $stock = DrugStock::firstOrCreate([
            'dmdcomb' => $dm[0],
            'dmdctr' => $dm[1],
            'loc_code' =>  session('pharm_location_id'),
            'chrgcode' => $this->chrgcode,
            'exp_date' => $this->expiry_date,
            'retail_price' => $retail_price,
            'drug_concat' => $drug->drug_name(),
            'dmdnost' => $drug->dmdnost,
            'strecode' => $drug->strecode,
            'formcode' => $drug->formcode,
            'rtecode' => $drug->rtecode,
            'brandname' => $drug->brandname,
            'dmdrem' => $drug->dmdrem,
            'dmdrxot' => $drug->dmdrxot,
            'gencode' => $drug->generic->gzencode,
        ]);
        $stock->stock_bal = $stock->stock_bal + $this->qty;
        $stock->beg_bal = $stock->beg_bal + $this->qty;

        $new_price = new DrugPrice;
        $new_price->dmdcomb = $stock->dmdcomb;
        $new_price->dmdctr = $stock->dmdctr;
        $new_price->dmhdrsub = $stock->chrgcode;
        $new_price->dmduprice = $unit_cost;
        $new_price->dmselprice = $stock->retail_price;
        $new_price->dmdprdte = now();
        $new_price->expdate = $stock->exp_date;
        $new_price->stock_id = $stock->id;
        $new_price->mark_up = $markup_price;
        $new_price->acquisition_cost = $unit_cost;
        $new_price->has_compounding = $this->has_compounding ? true : false;
        if ($this->has_compounding) {
            $new_price->compounding_fee = $this->compounding_fee;
        }
        $new_price->retail_price = $retail_price;
        $new_price->save();

        $dmdprdte = $new_price->dmdprdte;

        $stock->dmdprdte = $dmdprdte;

        $stock->save();
        LogDrugTransaction::dispatch(session('pharm_location_id'), $stock->dmdcomb, $stock->dmdctr, $stock->chrgcode, date('Y-m-d'), $dmdprdte, $unit_cost, $retail_price, $this->qty, $stock->id, $stock->exp_date, $drug->drug_concat(), date('Y-m-d'), session('active_consumption'));

        $this->resetExcept('location_id', 'drugs', 'locations', 'charge_codes');
        $this->alert('success', 'Item beginning balance has been saved!');
    }

    public function update_item_new(DrugStock $stock)
    {
        $this->validate([
            'unit_cost' => 'required',
            'qty' => ['required', 'numeric', 'min:0'],
            'expiry_date' => 'required',
            'chrgcode' => 'required',
        ]);
        $old_chrgcode = $stock->chrgcode;
        $old_stock_bal = $stock->stock_bal;

        $unit_cost = $this->unit_cost;
        $excess = 0;
        $markup_price = 0;
        $retail_price = 0;

        if ($unit_cost >= 10000.01) {
            $excess = $unit_cost - 10000;
            $markup_price = 1115 + ($excess * 0.05);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 1000.01 and $unit_cost <= 10000.00) {
            $excess = $unit_cost - 1000;
            $markup_price = 215 + ($excess * 0.10);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 100.01 and $unit_cost <= 1000.00) {
            $excess = $unit_cost - 100;
            $markup_price = 35 + ($excess * 0.20);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 50.01 and $unit_cost <= 100.00) {
            $excess = $unit_cost - 50;
            $markup_price = 20 + ($excess * 0.30);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 0.01 and $unit_cost <= 50.00) {
            $markup_price = $unit_cost * 0.40;
            $retail_price = $unit_cost + $markup_price;
        }

        if ($this->has_compounding) {

            $this->validate([
                'compounding_fee' => ['required', 'numeric', 'min:0'],
            ]);

            $retail_price = $retail_price + $this->compounding_fee;
        }
        $stock->beg_bal = 0;
        $stock->stock_bal = 0;

        $stock->chrgcode = $this->chrgcode;
        $stock->exp_date = $this->expiry_date;
        $stock->retail_price = $retail_price;
        $stock->stock_bal = $stock->stock_bal + $this->qty;
        $stock->beg_bal = $stock->beg_bal + $this->qty;

        $new_price = new DrugPrice;
        $new_price->dmdcomb = $stock->dmdcomb;
        $new_price->dmdctr = $stock->dmdctr;
        $new_price->dmhdrsub = $stock->chrgcode;
        $new_price->dmduprice = $unit_cost;
        $new_price->dmselprice = $stock->retail_price;
        $new_price->dmdprdte = now();
        $new_price->expdate = $stock->exp_date;
        $new_price->stock_id = $stock->id;
        $new_price->mark_up = $markup_price;
        $new_price->acquisition_cost = $unit_cost;
        $new_price->has_compounding = $this->has_compounding ? true : false;
        if ($this->has_compounding) {
            $new_price->compounding_fee = $this->compounding_fee;
        }
        $new_price->retail_price = $retail_price;

        $old_log = DrugStockLog::where('loc_code', session('pharm_location_id'))
            ->where('dmdcomb', $stock->dmdcomb)
            ->where('dmdctr', $stock->dmdctr)
            ->where('chrgcode', $old_chrgcode)
            ->where('date_logged', date('Y-m-d', strtotime($stock->created_at)))
            ->where('dmdprdte', $stock->dmdprdte)
            ->first();
        if ($old_log) {
            $old_log->time_logged = date('Y-m-d H:i:s');
            $old_log->beg_bal -= $old_stock_bal;
            $old_log->save();
        }

        $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  session('pharm_location_id'),
            'dmdcomb' => $stock->dmdcomb,
            'dmdctr' => $stock->dmdctr,
            'chrgcode' => $stock->chrgcode,
            'date_logged' => $date,
            'unit_cost' => $unit_cost,
            'unit_price' => $retail_price,
            'consumption_id' => session('active_consumption'),
        ]);
        $log->time_logged = now();
        $log->beg_bal += $this->qty;
        $stock->dmdprdte =  $new_price->dmdprdte;
        $new_price->save();
        $log->save();
        $stock->save();

        $this->resetExcept('location_id', 'drugs', 'locations', 'charge_codes');
        $this->alert('success', 'Item beginning balance has been saved!');
    }

    public function add_item()
    {
        $this->validate([
            'dmdcomb' => 'required',
            'unit_cost' => 'required',
            'qty' => 'required',
            'expiry_date' => 'required',
            'chrgcode' => 'required',
        ]);

        $retail_price = $this->unit_cost + ((float)$this->unit_cost * 0.30);
        $total_amount = $this->unit_cost * $this->qty;
        $dm = explode(',', $this->dmdcomb);

        $stock = DrugStock::firstOrCreate([
            'dmdcomb' => $dm[0],
            'dmdctr' => $dm[1],
            'loc_code' =>  session('pharm_location_id'),
            'chrgcode' => $this->chrgcode,
            'exp_date' => $this->expiry_date,
            'retail_price' => $retail_price,
        ]);
        $stock->stock_bal = $stock->stock_bal + $this->qty;
        $stock->beg_bal = $stock->beg_bal + $this->qty;

        $current_price = DrugPrice::where('dmdcomb', $dm[0])
            ->where('dmdctr', $dm[1])
            ->where('dmhdrsub', $this->chrgcode)
            ->latest('dmdprdte')
            ->first();

        if ($current_price and $current_price->dmduprice == $this->unit_cost and $current_price->dmselprice) {
            $dmdprdte = $current_price->dmdprdte;
            $dmduprice = $current_price->dmduprice;
            $dmselprice = $current_price->dmselprice;
        } else {
            $new_price = new DrugPrice;
            $new_price->dmdcomb = $stock->dmdcomb;
            $new_price->dmdctr = $stock->dmdctr;
            $new_price->dmhdrsub = $stock->chrgcode;
            $new_price->dmduprice = (100 / 130) * $stock->retail_price;
            $new_price->dmselprice = $stock->retail_price;
            $new_price->dmdprdte = now();
            $new_price->expdate = $stock->exp_date;
            $new_price->stock_id = $stock->id;
            $new_price->save();

            $dmdprdte = $new_price->dmdprdte;
            $dmduprice = $new_price->dmduprice;
            $dmselprice = $new_price->dmselprice;
        }
        $stock->dmdprdte = $dmdprdte;

        $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
        $log = DrugStockLog::firstOrNew([
            'loc_code' =>  session('pharm_location_id'),
            'dmdcomb' => $stock->dmdcomb,
            'dmdctr' => $stock->dmdctr,
            'chrgcode' => $stock->chrgcode,
            'date_logged' => $date,
            'unit_cost' => $dmduprice,
            'unit_price' => $dmselprice,
            'consumption_id' => session('active_consumption'),
        ]);
        $log->time_logged = now();
        $log->beg_bal += $this->qty;

        $log->save();
        $stock->save();

        $this->resetExcept('location_id', 'drugs', 'locations', 'charge_codes');
        $this->alert('success', 'Item beginning balance has been saved!');
    }

    public function sync_items()
    {
        Artisan::call('init:drugconcat');
        $this->alert('success', 'Items in sync');
    }

    public function adjust_qty($stock_id, $qty)
    {
        $stock = DrugStock::find($stock_id);

        $current_bal = $stock->stock_bal;

        $stock->stock_bal = $qty;
        $stock->save();

        StockAdjustment::create([
            'stock_id' => $stock_id,
            'user_id' => session('user_id'),
            'from_qty' => $current_bal,
            'to_qty' => $qty,
        ]);

        return $this->alert('success', 'Success!');
    }
}
