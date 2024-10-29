<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Events\IoTransRequestIssued;
use App\Events\IoTransRequestUpdated;
use App\Jobs\LogIoTransIssue;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\Drugs\InOutTransactionItem;
use App\Models\Pharmacy\PharmLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransList extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['add_request', 'cancel_issued', 'refreshComponent' => '$refresh', 'issue_request', 'deny_request', 'bypass_add_request'];
    // protected $listeners = ['add_request', 'cancel_issued', 'echo:io-trans,new-request' => 'notifyRequest'];
    // protected $listeners = ['add_request', 'cancel_issued', 'echo:private-io-trans,new-request' => 'notifyRequest', 'echo:io-trans,new-request' => 'notifyRequest'];

    public $stock_id, $requested_qty, $remarks;
    public $location_id;
    public $selected_request, $chrgcode, $issue_qty = 0;
    public $issued_qty = 0;
    public $received_qty = 0;
    public $search;
    public $available_drugs, $issueModal = false;


    public function render()
    {
        $trans = InOutTransaction::with('drug')->with('location')
            ->with('charge')
            ->where(function ($query) {
                $query->where('loc_code', session('pharm_location_id'))
                    ->orWhere('request_from', session('pharm_location_id'));
            })
            ->whereHas('drug', function ($query) {
                $query->where('drug_concat', 'LIKE', '%' . $this->search . '%');
            });

        $locations = PharmLocation::where('id', '<>', session('pharm_location_id'))->get();

        $drugs = DB::select("SELECT pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, pharm_drug_stocks.loc_code, pharm_drug_stocks.dmdprdte as dmdprdte, SUM(stock_bal) as stock_bal, MAX(id) as id, MIN(exp_date) as exp_date
                                FROM hospital.dbo.pharm_drug_stocks
                                INNER JOIN hcharge on hcharge.chrgcode = pharm_drug_stocks.chrgcode
                                INNER JOIN hdmhdrprice on hdmhdrprice.dmdprdte = pharm_drug_stocks.dmdprdte
                                WHERE loc_code = '" . session('pharm_location_id') . "'
                                AND stock_bal > 0
                                GROUP BY pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.loc_code, pharm_drug_stocks.dmdprdte
                                ORDER BY drug_concat");

        return view('livewire.pharmacy.drugs.io-trans-list', [
            'trans' => $trans->latest()->paginate(20),
            'locations' => $locations,
            'drugs' => $drugs,
        ]);
    }

    public function add_request()
    {
        $this->validate(['stock_id' => ['required', 'numeric']]);
        $stock = DrugStock::find($this->stock_id);
        $dmdcomb = $stock->dmdcomb;
        $dmdctr = $stock->dmdctr;

        $current_qty = DrugStock::whereRelation('location', 'description', 'LIKE', '%Warehouse%')
            ->where('dmdcomb', $dmdcomb)->where('dmdctr', $dmdctr)
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr')->sum('stock_bal');

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1', 'max:' . $current_qty],
            'remarks' => ['nullable', 'string'],
        ]);

        $reference_no = Carbon::now()->format('y-m-') . (sprintf("%04d", InOutTransaction::count() + 1));

        InOutTransaction::create([
            'trans_no' => $reference_no,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'requested_qty' => $this->requested_qty,
            'requested_by' => session('user_id'),
            'loc_code' => session('pharm_location_id'),
        ]);

        $this->alert('success', 'Request added!');
    }

    public function bypass_add_request($last_request = false)
    {
        $dm = explode(',', $this->stock_id);
        $dmdcomb = $dm[0];
        $dmdctr = $dm[1];

        $this->validate([
            'requested_qty' => ['required', 'numeric', 'min:1'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($last_request) {
            $past = InOutTransaction::where('loc_code', $this->location_id)->latest()->first();
            if ($past) {
                $reference_no = $past->trans_no;
            } else {
                $reference_no = null;
            }
        } else {
            $reference_no = Carbon::now()->format('y-m-') . (sprintf("%04d", count(InOutTransaction::select(DB::raw('COUNT(trans_no)'))->groupBy('trans_no')->get()) + 1));
        }

        if ($reference_no) {
            $txn = InOutTransaction::create([
                'trans_no' => $reference_no,
                'dmdcomb' => $dmdcomb,
                'dmdctr' => $dmdctr,
                'requested_qty' => $this->requested_qty,
                'requested_by' => session('user_id'),
                'loc_code' => $this->location_id,
                'request_from' => session('pharm_location_id'),
                'remarks_request' => $this->remarks,
            ]);

            $this->select_request($txn);
        } else {
            return $this->alert('No previous request from selected location!');
        }
    }

    public function select_request(InOutTransaction $txn)
    {
        $this->selected_request = $txn;
        $this->issue_qty = $txn->requested_qty;
        $this->available_drugs = DB::select("
                SELECT charge.chrgcode, charge.chrgdesc, SUM(pdsl.stock_bal) avail FROM pharm_drug_stocks pdsl
                INNER JOIN hcharge charge ON pdsl.chrgcode = charge.chrgcode
                WHERE pdsl.dmdcomb = '" . $txn->dmdcomb . "'
                    AND pdsl.dmdctr = '" . $txn->dmdctr . "'
                    AND pdsl.loc_code = '" . $txn->request_from . "'
                    AND pdsl.stock_bal > 0
                    AND pdsl.exp_date > '" . now() . "'
                GROUP BY charge.chrgcode, charge.chrgdesc
            ");
        $this->issueModal = true;
        // $this->dispatchBrowserEvent('toggleIssue');
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
                    $this->handleLog($location_id, $trans_item->dmdcomb, $trans_item->dmdctr, $trans_item->chrgcode, date('Y-m-d'), $stock->retail_price, $stock->dmdprdte, now(), $trans_item->qty, $stock->exp_date, $stock->drug_concat(), session('active_consumption'), $stock->current_price ? $stock->current_price->acquisition_cost : 0);
                }
            }
            $this->selected_request->issued_qty = $issued_qty;
            $this->selected_request->issued_by = session('user_id');
            $this->selected_request->trans_stat = 'Issued';

            $this->selected_request->save();

            IoTransRequestUpdated::dispatch($this->selected_request, 'A requested drugs/medicine has been issued from the warehouse.');
            $this->issueModal = false;
            $this->alert('success', 'Request issued successfully!');
            return redirect(request()->header('Referer'));
        } else {
            $this->alert('error', 'Failed to issue medicine. Selected fund source insufficient stock!');
        }
    }

    public function handleLog($warehouse_id, $dmdcomb, $dmdctr, $chrgcode, $trans_date, $retail_price, $dmdprdte, $trans_time, $qty, $exp_date, $drug_concat, $active_consumption = null, $unit_cost)
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
        ]);
        $card->iss += $qty;
        $card->bal -= $qty;

        $card->save();

        return;
    }

    public function view_trans($trans_no)
    {
        return $this->redirect(route('iotrans.view', ['reference_no' => $trans_no]));
    }

    public function view_trans_date($date)
    {
        return $this->redirect(route('iotrans.view_date', ['date' => $date]));
    }

    public function cancel_issued($iotrans_id)
    {
        $items = InOutTransactionItem::where('iotrans_id', $iotrans_id)->where('status', 'Pending')->get();
        foreach ($items as $item) {
            $stock = DrugStock::find($item->stock_id);
            $stock->stock_bal += $item->qty;
            $stock->save();

            $item->status = 'Returned';
            $item->save();

            $date = Carbon::parse(now())->startOfMonth()->format('Y-m-d');
            $log = DrugStockLog::firstOrNew([
                'loc_code' => session('pharm_location_id'),
                'dmdcomb' => $stock->dmdcomb,
                'dmdctr' => $stock->dmdctr,
                'chrgcode' => $stock->chrgcode,
                'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                'unit_price' => $stock->retail_price,
                'consumption_id' => session('active_consumption'),
            ]);
            $log->transferred -= $item->qty;
            $log->save();

            $card = DrugStockCard::firstOrNew([
                'chrgcode' => $item->chrgcode,
                'loc_code' => $item->to,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'exp_date' => $item->exp_date,
                'stock_date' => $date,
                'drug_concat' => $stock->drug_concat(),
            ]);
            $card->rec += $item->qty;
            $card->bal += $item->qty;

            $card->save();
        }
        $trans = InOutTransaction::find($iotrans_id);
        $trans->trans_stat = 'Cancelled';
        $trans->save();

        $this->alert('success', 'Issued items successfully recalled!');
    }

    public function deny_request($remarks)
    {
        if ($this->selected_request->trans_stat == 'Requested') {
            $this->selected_request->remarks_cancel = $remarks;
            $this->selected_request->trans_stat = 'Declined';
            $this->selected_request->issued_by = session('user_id');
            $this->selected_request->save();

            $this->reset('selected_request', 'available_drugs', 'issueModal');

            $this->alert('warning', 'Request declined!');
        } else {
            $this->alert('error', 'Request has already been processed. Rolling back changes.');
        }
    }
}