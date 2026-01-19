<?php

namespace App\Http\Livewire\Pharmacy\Deliveries;

use Carbon\Carbon;
use Livewire\Component;
use App\Jobs\LogDrugDelivery;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\DeliveryItems;
use App\Models\Pharmacy\DeliveryDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DeliveryView extends Component
{

    use LivewireAlert;

    protected $listeners = ['add_item', 'refresh' => '$refresh', 'edit_item', 'delete_item', 'save_lock'];
    public $delivery_id, $details, $search, $dmdcomb, $expiry_date, $qty, $unit_price = 0, $lot_no;
    public $has_compounding = false, $compounding_fee = 0;

    public function render()
    {
        $drugs = Drug::where('dmdstat', 'A')
            // ->whereHas('sub', function ($query) {
            //     // return $query->whereIn('dmhdrsub', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE'));
            //     return $query->where('dmhdrsub', 'LIKE', '%DRUM%');
            // })
            ->whereNotNull('drug_concat')
            ->has('generic')
            ->orderBy('drug_concat', 'ASC')
            ->get();

        return view('livewire.pharmacy.deliveries.delivery-view', [
            'drugs' => $drugs,
        ]);
    }

    public function mount($delivery_id)
    {
        $this->delivery_id = $delivery_id;
        $this->details = DeliveryDetail::where('id', $delivery_id)
            ->with('items')->with('supplier')
            ->with('charge')->first();
    }

    public function add_item()
    {
        $this->validate([
            'dmdcomb' => 'required',
            'unit_price' => ['required', 'numeric', 'min:0'],
            'qty' => 'required',
            'expiry_date' => 'required'
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
        } else {
            $retail_price = 0;
            $markup_price = 0;
        }

        if ($this->has_compounding) {

            $this->validate([
                'compounding_fee' => ['required', 'numeric', 'min:0'],
            ]);

            $retail_price = $retail_price + $this->compounding_fee;
        }

        $total_amount = $unit_cost * $this->qty;
        $dm = explode(',', $this->dmdcomb);

        $new_item = new DeliveryItems;
        $new_item->delivery_id = $this->details->id;
        $new_item->dmdcomb = $dm[0];
        $new_item->dmdctr = $dm[1];
        $new_item->qty = $this->qty;
        $new_item->unit_price = $unit_cost;
        $new_item->total_amount = $total_amount;
        $new_item->retail_price = $retail_price;
        $new_item->lot_no = $this->lot_no;
        $new_item->expiry_date = $this->expiry_date;
        $new_item->pharm_location_id = $this->details->pharm_location_id;
        $new_item->charge_code = $this->details->charge_code;
        $new_item->save();

        // Prepare the attributes to check (everything except dmdprdte)
        $attributes = [
            'dmdcomb' => $new_item->dmdcomb,
            'dmdctr' => $new_item->dmdctr,
            'dmhdrsub' => $this->details->charge_code,
            'dmduprice' => $unit_cost,
            'dmselprice' => $new_item->retail_price,
            'expdate' => $new_item->exp_date,
            'stock_id' => $new_item->id,
            'mark_up' => $markup_price,
            'acquisition_cost' => $unit_cost,
            'has_compounding' => $this->has_compounding,
            'retail_price' => $retail_price
        ];

        // Add compounding_fee to attributes if applicable
        if ($this->has_compounding) {
            $attributes['compounding_fee'] = $this->compounding_fee;
        }

        // The only field not to check but to set when creating
        $values = [
            'dmdprdte' => now()
        ];

        // This will only create a new record if no matching record exists
        $new_price = DrugPrice::firstOrCreate($attributes, $values);

        $dmdprdte = $new_price->dmdprdte;

        $new_item->dmdprdte = $dmdprdte;
        $new_item->save();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('success', 'Item added in delivery!');
    }

    public function edit_item($item_id)
    {
        $this->validate([
            'lot_no' => 'required|string',
            'expiry_date' => 'required|date',
            'compounding_fee' => ['required_if:has_compounding,true', 'numeric', 'min:0'],
        ]);

        $update_item = DeliveryItems::find($item_id);

        // Use the existing values from the database
        $unit_cost = $update_item->unit_price;
        $qty = $update_item->qty;

        // Recalculate the base retail price (without compounding)
        $excess = 0;
        if ($unit_cost >= 10000.01) {
            $excess = $unit_cost - 10000;
            $markup_price = 1115 + ($excess * 0.05);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 1000.01 && $unit_cost <= 10000.00) {
            $excess = $unit_cost - 1000;
            $markup_price = 215 + ($excess * 0.10);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 100.01 && $unit_cost <= 1000.00) {
            $excess = $unit_cost - 100;
            $markup_price = 35 + ($excess * 0.20);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 50.01 && $unit_cost <= 100.00) {
            $excess = $unit_cost - 50;
            $markup_price = 20 + ($excess * 0.30);
            $retail_price = $unit_cost + $markup_price;
        } elseif ($unit_cost >= 0.01 && $unit_cost <= 50.00) {
            $markup_price = $unit_cost * 0.40;
            $retail_price = $unit_cost + $markup_price;
        } else {
            $markup_price = 0;
            $retail_price = 0;
        }

        // Add compounding fee if applicable
        if ($this->has_compounding) {

            if ($this->details->charge_code == 'DRUMAN' or $this->details->charge_code == 'DRUMAA') {
                $retail_price = 0;
            } else {
                $retail_price = $retail_price + $this->compounding_fee;
            }
        }

        // Update the lot number, expiry date, and retail price
        $update_item->lot_no = $this->lot_no;
        $update_item->expiry_date = $this->expiry_date;
        $update_item->retail_price = $retail_price;
        $update_item->save();

        // Update drug price record
        $attributes = [
            'dmdcomb' => $update_item->dmdcomb,
            'dmdctr' => $update_item->dmdctr,
            'dmhdrsub' => $this->details->charge_code,
            'dmduprice' => $unit_cost,
            'dmselprice' => $update_item->retail_price,
            'expdate' => $update_item->expiry_date,
            'stock_id' => $update_item->id,
            'mark_up' => $markup_price,
            'acquisition_cost' => $unit_cost,
            'has_compounding' => $this->has_compounding,
            'retail_price' => $retail_price
        ];

        // Add compounding_fee to attributes if applicable
        if ($this->has_compounding) {
            $attributes['compounding_fee'] = $this->compounding_fee;
        }

        // Find existing price record for this stock_id
        $price_record = DrugPrice::where('stock_id', $update_item->id)->first();

        if ($price_record) {
            // Update existing record
            $price_record->fill($attributes);
            $price_record->save();
            $dmdprdte = $price_record->dmdprdte;
        } else {
            // Create new if not exists
            $values = [
                'dmdprdte' => now()
            ];
            $new_price = DrugPrice::create(array_merge($attributes, $values));
            $dmdprdte = $new_price->dmdprdte;
        }

        $update_item->dmdprdte = $dmdprdte;
        $update_item->save();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('success', 'Item updated successfully!');
    }

    public function delete_item($item_id)
    {
        $delete_item = DeliveryItems::find($item_id);
        $delete_item->delete();

        $this->emit('refresh');
        $this->resetExcept('details', 'delivery_id', 'search');
        $this->alert('info', 'Item deleted!');
    }

    public function save_lock()
    {
        $updated = false;

        foreach ($this->details->items->all() as $item) {
            $add_to = DrugStock::firstOrCreate([
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'loc_code' => $item->pharm_location_id,
                'chrgcode' => $item->charge_code,
                'exp_date' => $item->expiry_date,
                'retail_price' => $item->retail_price,
                'drug_concat' => $item->drug->drug_name(),
                'dmdnost' => $item->drug->dmdnost,
                'strecode' => $item->drug->strecode,
                'formcode' => $item->drug->formcode,
                'rtecode' => $item->drug->rtecode,
                'brandname' => $item->drug->brandname,
                'dmdrem' => $item->drug->dmdrem,
                'dmdrxot' => $item->drug->dmdrxot,
                'gencode' => $item->drug->generic->gencode,
                'lot_no' => $item->lot_no,
            ]);
            $add_to->stock_bal = $add_to->stock_bal + $item->qty;
            $add_to->beg_bal = $add_to->beg_bal + $item->qty;

            $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');

            $log = DrugStockLog::firstOrNew([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $add_to->dmdcomb,
                'dmdctr' => $add_to->dmdctr,
                'chrgcode' => $add_to->chrgcode,
                'unit_cost' => $item->unit_price,
                'unit_price' => $item->retail_price,
                'consumption_id' => session('active_consumption'),
            ]);
            $log->purchased += $item->qty;
            $add_to->dmdprdte = $item->dmdprdte;

            $log->save();
            $add_to->save();
            $this->handleLog($item->pharm_location_id, $add_to->dmdcomb, $add_to->dmdctr, $add_to->exp_date, $add_to->chrgcode, $item->qty, $item->drug->drug_concat(), now(), $add_to->dmdprdte);
            $updated = true;

            $item->status = 'delivered';
            $item->save();
        }
        if ($updated) {
            $this->details->status = 'locked';
            $this->details->save();
            $this->emit('refresh');
            $this->alert('success', 'Successfully updated stocks inventory!');
        } else {
            return $this->alert('error', 'Error! There are no drug or medicine that can be added to stock inventory.');
        }
    }

    public function handleLog($pharm_location_id, $dmdcomb, $dmdctr, $exp_date, $chrgcode, $qty, $drug_concat, $date, $dmdprdte)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $card = DrugStockCard::firstOrNew([
            'io_trans_ref_no' => $this->details->si_no,
            'chrgcode' => $chrgcode,
            'loc_code' => $pharm_location_id,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'exp_date' => $exp_date,
            'stock_date' => $date,
            'drug_concat' => $drug_concat,
            'dmdprdte' => $dmdprdte,
        ]);

        $card->rec += $qty;
        $card->bal += $qty;

        $card->save();

        return;
    }
}
