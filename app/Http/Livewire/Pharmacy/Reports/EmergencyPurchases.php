<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\DrugEmergencyPurchase;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class EmergencyPurchases extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['new_ep', 'refresh' => 'reset_page', 'push', 'cancel_purchase'];

    public $search;
    public $purchase_date, $or_no, $pharmacy_name, $charge_code = 'DRUMC', $dmdcomb, $expiry_date,
        $qty, $unit_price, $lot_no, $has_compounding = false, $compounding_fee = 0, $remarks;

    public function render()
    {
        $drugs = Drug::where('dmdstat', 'A')
            // ->whereHas('sub', function ($query) {
            //     // return $query->whereIn('dmhdrsub', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE'));
            //     return $query->where('dmhdrsub', 'LIKE', '%DRUM%');
            // })
            ->whereNotNull('drug_concat')
            ->has('generic')
            ->orderBy('drug_concat', 'ASC');

        $charges = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMC'))
            ->get();

        $purchases = DrugEmergencyPurchase::with('drug')
            ->with('current_price')
            ->with('charge')
            ->where('pharm_location_id', session('pharm_location_id'))
            ->paginate(15);

        return view('livewire.pharmacy.reports.emergency-purchases', [
            'drugs' => $drugs->get(),
            'charges' => $charges,
            'purchases' => $purchases,
        ]);
    }

    public function new_ep()
    {
        $this->validate([
            'purchase_date' => ['required', 'date', 'before_or_equal:' . now()],
            'or_no' => ['required', 'string', 'max:10', 'min:1'],
            'pharmacy_name' => ['required', 'string', 'max:100'],
            'charge_code' => ['required', 'string', 'max:6'],
            'dmdcomb' => ['required'],
            'expiry_date' => ['required', 'date', 'after:' . now()],
            'qty' => ['required', 'numeric', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:1'],
            'lot_no' => ['nullable', 'string', 'max:10'],
            'has_compounding' => ['nullable'],
        ]);

        $unit_cost = $this->unit_price;
        $excess = 0;

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
        $total_amount = $unit_cost * $this->qty;

        $new_ep = DrugEmergencyPurchase::create([
            'or_no' => $this->or_no,
            'pharmacy_name' => $this->pharmacy_name,
            'user_id' => session('user_id'),
            'purchase_date' => $this->purchase_date,
            'dmdcomb' => $dm[0],
            'dmdctr' => $dm[1],
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'total_amount' => $total_amount,
            'markup_price' => $markup_price,
            'retail_price' => $retail_price,
            'lot_no' => $this->lot_no,
            'expiry_date' => $this->expiry_date,
            'charge_code' => $this->charge_code,
            'pharm_location_id' => session('pharm_location_id'),
            'remarks' => $this->remarks,
        ]);

        $new_price = new DrugPrice;
        $new_price->dmdcomb = $new_ep->dmdcomb;
        $new_price->dmdctr = $new_ep->dmdctr;
        $new_price->dmhdrsub = $new_ep->charge_code;
        $new_price->dmduprice = $unit_cost;
        $new_price->dmselprice = $new_ep->retail_price;
        $new_price->dmdprdte = now();
        $new_price->expdate = $new_ep->exp_date;
        $new_price->stock_id = $new_ep->id;
        $new_price->mark_up = $markup_price;
        $new_price->acquisition_cost = $unit_cost;
        $new_price->has_compounding = $this->has_compounding;
        if ($this->has_compounding) {
            $new_price->compounding_fee = $this->compounding_fee;
        }
        $new_price->retail_price = $retail_price;
        $new_price->save();

        $dmdprdte = $new_price->dmdprdte;

        $new_ep->dmdprdte = $dmdprdte;
        $new_ep->save();

        $this->emit('refresh');
        $this->resetExcept('search');
        $this->alert('success', 'Emergency purchase saved!');
    }

    public function push(DrugEmergencyPurchase $purchase)
    {
        $add_to = DrugStock::firstOrCreate([
            'dmdcomb' => $purchase->dmdcomb,
            'dmdctr' => $purchase->dmdctr,
            'loc_code' => $purchase->pharm_location_id,
            'chrgcode' => $purchase->charge_code,
            'exp_date' => $purchase->expiry_date,
            'retail_price' => $purchase->retail_price,
            'drug_concat' => $purchase->drug->drug_name(),
        ]);

        $add_to->stock_bal = $add_to->stock_bal + $purchase->qty;
        $add_to->beg_bal = $add_to->beg_bal + $purchase->qty;

        $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
        $log = DrugStockLog::firstOrNew([
            'loc_code' => $purchase->pharm_location_id,
            'dmdcomb' => $add_to->dmdcomb,
            'dmdctr' => $add_to->dmdctr,
            'chrgcode' => $add_to->chrgcode,
            'date_logged' => $date,
            'unit_cost' => $purchase->unit_price,
            'unit_price' => $purchase->retail_price,
            'consumption_id' => session('active_consumption'),
        ]);
        $log->time_logged = now();
        $log->purchased += $purchase->qty;
        $add_to->dmdprdte = $purchase->dmdprdte;

        $log->save();
        $add_to->save();

        $purchase->status = 'pushed';
        $purchase->save();

        $this->emit('refresh');
        $this->alert('success', 'Successfully updated stocks inventory!');
    }

    public function cancel_purchase(DrugEmergencyPurchase $purchase)
    {
        $purchase->status = 'cancelled';
        $purchase->save();

        $this->alert('success', 'Emergency purchase cancelled');
    }

    public function reset_page()
    {
        $this->resetErrorBag();
    }
}
