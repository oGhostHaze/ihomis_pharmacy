<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\WardRisRequest;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ViewWardRisRef extends Component
{
    use LivewireAlert;

    protected $listeners = ['cancel_issue'];
    public $reference_no;

    public function render()
    {
        $trans = WardRisRequest::with('charge')
            ->where('loc_code', session('pharm_location_id'))
            ->where('trans_no', $this->reference_no)
            ->latest()
            ->get();

        return view('livewire.pharmacy.drugs.view-ward-ris-ref', compact(
            'trans',
        ));
    }

    public function mount($reference_no)
    {
        $this->reference_no = $reference_no;
    }

    public function view_trans_date($date)
    {
        return $this->redirect(route('dmd.view.ris.date', ['date' => $date]));
    }

    public function cancel_issue($row_id)
    {
        $item = WardRisRequest::find($row_id);
        $date = Carbon::parse(date('Y-m-d'))->startOfMonth()->format('Y-m-d');
        $drug = DrugStock::find($item->stock_id);
        if ($drug) {
            $drug->stock_bal += $item->issued_qty;
            $drug->save();

            $log = DrugStockLog::firstOrNew([
                'loc_code' => $item->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $drug->current_price ? $drug->current_price->acquisition_cost : 0,
                'unit_price' => $drug->retail_price,
                'consumption_id' => session('active_consumption'),
            ]);
            $log->return_qty += $item->issued_qty;
            $log->save();

            $card = DrugStockCard::firstOrNew([
                'chrgcode' => $item->chrgcode,
                'loc_code' => $item->loc_code,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'exp_date' => $drug->exp_date,
                'stock_date' => date('Y-m-d'),
                'drug_concat' => $drug->drug_concat(),
                'dmdprdte' => $drug->dmdprdte,
            ]);
            $card->rec += $item->issued_qty;
            $card->save();

            $item->return_qty = $item->issued_qty;
            $item->issued_qty = 0;
            $item->save();

            $this->alert('success', 'Items returned successfully!');
        } else {
            $this->alert('error', 'Item not found!');
        }
    }
}
