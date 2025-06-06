<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use Carbon\Carbon;
use Livewire\Component;
use App\Jobs\LogIoTransIssue;
use App\Models\Pharmacy\Drug;
use App\Jobs\LogIoTransReceive;
use App\Events\IoTransNewRequest;
use Illuminate\Support\Facades\DB;
use App\Events\IoTransRequestUpdated;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Notifications\IoTranNotification;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\Drugs\InOutTransactionItem;

class ViewIotrans extends Component
{
    use LivewireAlert;

    protected $listeners = ['add_request', 'cancel_tx', 'receive_issued', 'issue_request'];
    public $reference_no, $from, $to, $requested_qty, $remarks, $stock_id;
    public $selected_request, $chrgcode, $issue_qty = 0;
    public $available_drugs;

    public function render()
    {
        $trans = InOutTransaction::where('trans_no', $this->reference_no)->with('drug')
            ->with('charge')
            ->get();

        $drugs = Drug::where('dmdstat', 'A')
            ->whereNotNull('drug_concat')
            ->has('stock')
            ->has('generic')->orderBy('drug_concat', 'ASC')
            ->get();

        if (!$this->from && !$this->to) {
            $this->from = $trans[0]->loc_code;
            $this->to = $trans[0]->request_from;
        }

        return view('livewire.pharmacy.drugs.view-iotrans', [
            'trans' => $trans,
            'drugs' => $drugs,
        ]);
    }

    public function mount($reference_no)
    {
        $this->reference_no = $reference_no;
    }

    public function add_request()
    {
        $dm = explode(',', $this->stock_id);
        $dmdcomb = $dm[0];
        $dmdctr = $dm[1];

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1'],
            'remarks' => ['nullable', 'string'],
        ]);

        $io_tx = InOutTransaction::create([
            'trans_no' => $this->reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => session('user_id'),
            'loc_code' => $this->from,
            'request_from' => $this->to,
            'remarks_request' => $this->remarks,
        ]);

        // $location = PharmLocation::find($this->to);
        // IoTransNewRequest::dispatch($location, $io_tx);
        // $location->notify(new IoTranNotification($io_tx, session('user_id')));

        $this->resetExcept('locations', 'to', 'from', 'reference_no');
        $this->alert('success', 'Request added!');
    }

    public function cancel_tx(InOutTransaction $txn)
    {
        // $trans_id = $txn->id;

        // $issued_items = InOutTransactionItem::where('iotrans_id', $trans_id)
        //     ->where('status', 'Pending')
        //     ->latest('exp_date')
        //     ->get();

        // if ($issued_items) {
        //     foreach ($issued_items as $item) {
        //         $from_stock = $item->from_stock;
        //         $from_stock->stock_bal += $item->qty;
        //         $from_stock->save();

        //         $item->status = 'Cancelled';
        //         $item->save();
        //     }
        // }

        // $txn->issued_qty = 0;
        // $txn->trans_stat = 'Cancelled';
        // $txn->save();

        // $this->alert('success', 'Transaction cancelled. All issued items has been returned to the warehouse!');
        $this->alert('info', 'Cannot proceed with your action. Please inform the issuer to cancel from their side.');
        $this->resetExcept('locations', 'to', 'from', 'reference_no');
    }

    public function receive_issued(InOutTransaction $txn)
    {
        $trans_id = $txn->id;

        $issued_items = InOutTransactionItem::where('iotrans_id', $trans_id)
            ->where('status', 'Pending')
            ->latest('exp_date')
            ->get();
        if ($issued_items) {
            foreach ($issued_items as $item) {

                $stock = DrugStock::firstOrCreate([
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $item->to,
                    'chrgcode' => $item->chrgcode,
                    'exp_date' => $item->exp_date,
                    'retail_price' => $item->retail_price,
                    'dmdprdte' => $item->dmdprdte,
                    'drug_concat' => $item->dm->drug_concat,
                ]);
                $stock->stock_bal += $item->qty;
                $stock->beg_bal += $item->qty;
                $txn->received_by += $item->qty;

                $item->status = 'Received';

                $stock->save();
                $item->save();
                $this->handleLog_transReceive($item->to, $item->dmdcomb, $item->dmdctr, $item->chrgcode, date('Y-m-d'), $item->retail_price, now(), $item->qty, $stock->exp_date, $stock->drug_concat(), session('active_consumption'), $stock->current_price ? $stock->current_price->acquisition_cost : 0, $stock->dmdprdte, $txn->trans_no);
            }
        }

        $txn->trans_stat = 'Received';
        $txn->save();

        $this->alert('success', 'Transaction successful. All items received!');
        $this->resetExcept('locations', 'to', 'from', 'reference_no');
    }


    public function handleLog_transReceive($to, $dmdcomb, $dmdctr, $chrgcode, $date_logged, $retail_price, $qty, $exp_date, $drug_concat, $active_consumption = null, $unit_cost, $dmdprdte, $ref_no)
    {
        $log = DrugStockLog::firstOrNew([
            'loc_code' => $to,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'chrgcode' => $chrgcode,
            'unit_cost' => $unit_cost,
            'unit_price' => $retail_price,
            'consumption_id' => $active_consumption,
        ]);
        $log->received += $qty;
        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $chrgcode,
            'loc_code' => $to,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'exp_date' => $exp_date,
            'stock_date' => $date_logged,
            'drug_concat' => $drug_concat,
            'dmdprdte' => $dmdprdte,
            'io_trans_ref_no' => $ref_no,
        ]);
        $card->rec += $qty;
        $card->bal += $qty;

        $card->save();
    }

    public function select_request(InOutTransaction $txn)
    {
        $this->selected_request = $txn;
        $this->issue_qty = $txn->requested_qty;
        $this->available_drugs = DrugStock::with('charge')->with('drug')
            ->select('chrgcode', DB::raw('SUM(stock_bal) as "avail"'))
            ->where('loc_code', $txn->request_from)->where('stock_bal', '>', '0')
            ->where('exp_date', '>', now())
            ->where('dmdcomb', $txn->dmdcomb)
            ->where('dmdctr', $txn->dmdctr)
            ->groupBy('chrgcode')
            ->get();
        $this->dispatchBrowserEvent('toggleIssue');
    }

    public function issue_request()
    {
        $this->validate([
            'issue_qty' => ['required', 'numeric', 'min:1'],
            'chrgcode' => ['required'],
            'selected_request' => ['required'],
            'remarks' => ['nullable', 'string', 'max:255']
        ]);

        $issue_qty = $this->issue_qty;
        $issued_qty = 0;
        $location_id = PharmLocation::find($this->selected_request->request_from)->id;

        $available_qty = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
            ->where('dmdctr', $this->selected_request->dmdctr)
            ->where('chrgcode', $this->chrgcode)
            ->where('exp_date', '>', date('Y-m-d'))
            ->where('loc_code', $location_id)
            ->where('stock_bal', '>', '0')
            ->groupBy('chrgcode')
            ->sum('stock_bal');

        if ($available_qty >= $issue_qty) {

            $stocks = DrugStock::where('dmdcomb', $this->selected_request->dmdcomb)
                ->where('dmdctr', $this->selected_request->dmdctr)
                ->where('chrgcode', $this->chrgcode)
                ->where('exp_date', '>', date('Y-m-d'))
                ->where('loc_code', $location_id)
                ->where('stock_bal', '>', '0')
                ->oldest('exp_date')
                ->get();

            foreach ($stocks as $stock) {
                if ($issue_qty) {
                    if ($issue_qty > $stock->stock_bal) {
                        $trans_qty = $stock->stock_bal;
                        $issue_qty -= $stock->stock_bal;
                        $stock->stock_bal = 0;
                    } else {
                        $trans_qty = $issue_qty;
                        $stock->stock_bal -= $issue_qty;
                        $issue_qty = 0;
                    }

                    $issued_qty += $trans_qty;

                    $trans_item = InOutTransactionItem::create([
                        'stock_id' => $stock->id,
                        'iotrans_id' => $this->selected_request->id,
                        'dmdcomb' => $this->selected_request->dmdcomb,
                        'dmdctr' => $this->selected_request->dmdctr,
                        'from' => $this->selected_request->request_from,
                        'to' => $this->selected_request->loc_code,
                        'chrgcode' => $stock->chrgcode,
                        'exp_date' => $stock->exp_date,
                        'qty' => $trans_qty,
                        'status' => 'Pending',
                        'user_id' => session('user_id'),
                        'retail_price' => $stock->retail_price,
                        'dmdprdte' => $stock->dmdprdte,
                    ]);
                    $stock->save();
                    $this->handleLog_transIssue($location_id, $trans_item->dmdcomb, $trans_item->dmdctr, $trans_item->chrgcode, date('Y-m-d'), $stock->retail_price, $trans_item->qty, $stock->exp_date, $stock->drug_concat(), session('active_consumption'), $stock->current_price ? $stock->current_price->acquisition_cost : 0, $stock->dmdprdte);
                }
            }
            $this->selected_request->issued_qty = $issued_qty;
            $this->selected_request->issued_by = session('user_id');
            $this->selected_request->trans_stat = 'Issued';

            $this->selected_request->save();

            IoTransRequestUpdated::dispatch($this->selected_request, 'A requested drugs/medicine has been issued from the warehouse.');
            $this->dispatchBrowserEvent('toggleIssue');
            $this->alert('success', 'Request issued successfully!');
            $this->resetExcept('locations', 'to', 'from', 'reference_no');
        } else {
            $this->alert('error', 'Failed to issue medicine. Selected fund source insufficient stock!');
        }
    }

    public function handleLog_transIssue($warehouse_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $retail_price, $qty, $exp_date, $drug_concat, $active_consumption = null, $unit_cost, $dmdprdte)
    {
        $log = DrugStockLog::firstOrNew([
            'loc_code' => $warehouse_id,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'chrgcode' => $chrgcode,
            'unit_cost' => $unit_cost,
            'unit_price' => $retail_price,
            'consumption_id' => $active_consumption,
        ]);
        $log->transferred += $qty;
        $log->save();

        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $chrgcode,
            'loc_code' => $warehouse_id,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'exp_date' => $exp_date,
            'stock_date' => $trans_date,
            'drug_concat' => $drug_concat,
            'dmdprdte' => $dmdprdte,
            'io_trans_ref_no' => $this->selected_request->trans_no
        ]);
        $card->iss += $qty;
        $card->bal -= $qty;

        $card->save();
        return;
    }
}
