<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockReorderLevel;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReorderLevel extends Component
{
    use LivewireAlert;

    protected $listeners = ['update_reorder', 'bulk_request'];
    public $search, $location_id;
    public $prev_week_start, $prev_week_end;

    public function render()
    {

        $from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i:s');
        $to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i:s');
        $prev_from = Carbon::parse(now())->subWeek()->startOfWeek()->format('Y-m-d H:i:s');
        $prev_to = Carbon::parse(now())->subWeek()->endOfWeek()->format('Y-m-d H:i:s');

        $stocks = DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                            (SELECT reorder_point
                                FROM pharm_drug_stock_reorder_levels as level
                                WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                (SELECT SUM(card.iss) as average FROM pharm_drug_stock_cards card
                            WHERE card.dmdcomb = pds.dmdcomb
                            AND card.dmdctr = pds.dmdctr
                            AND card.loc_code = pds.loc_code
                            AND card.iss > 0
                            AND card.stock_date BETWEEN '" . $this->prev_week_start . "' AND '" . now() . "') as average,
                                (SELECT SUM(card.iss) as average FROM pharm_drug_stock_cards card
                            WHERE card.dmdcomb = pds.dmdcomb
                            AND card.dmdctr = pds.dmdctr
                            AND card.loc_code = pds.loc_code
                            AND card.iss > 0
                            AND card.stock_date BETWEEN '" . $from . "' AND '" . $to . "') as cur_average,
                                (SELECT SUM(card.iss) as average FROM pharm_drug_stock_cards card
                            WHERE card.dmdcomb = pds.dmdcomb
                            AND card.dmdctr = pds.dmdctr
                            AND card.loc_code = pds.loc_code
                            AND card.iss > 0
                            AND card.stock_date BETWEEN '" . $prev_from . "' AND '" . $prev_to . "') as prev_average,
                                pds.dmdcomb, pds.dmdctr
                            FROM pharm_drug_stocks as pds
                            JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                            WHERE pds.loc_code = " . $this->location_id . "
                                AND pds.drug_concat LIKE '%" . $this->search . "%'
                            GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                            ORDER BY pds.drug_concat ASC
                    ");

        $locations = PharmLocation::all();

        $current_io = InOutTransaction::where('remarks_request', 'Reorder level')
            ->where('trans_stat', 'Requested')
            ->where('loc_code', session('pharm_location_id'))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return view('livewire.pharmacy.drugs.reorder-level', [
            'stocks' => $stocks,
            'locations' => $locations,
            'current_io' => $current_io,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->prev_week_start = Carbon::parse(now())->subWeek()->startOfWeek();
        $this->prev_week_end = Carbon::parse(now())->subWeek()->endOfWeek();
    }

    public function update_reorder($dmdcomb, $dmdctr, $reorder_point)
    {
        DrugStockReorderLevel::updateOrCreate([
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'loc_code' => $this->location_id,
        ], [
            'reorder_point' => $reorder_point,
            'user_id' => session('user_id'),
        ]);

        $this->alert('success', 'Reorder level updated');
    }

    public function bulk_request()
    {
        $stocks = DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                            (SELECT reorder_point
                                FROM pharm_drug_stock_reorder_levels as level
                                WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                pds.dmdcomb, pds.dmdctr
                            FROM pharm_drug_stocks as pds
                            JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                            WHERE pds.loc_code = " . session('pharm_location_id') . "
                            GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                    ");

        $reference_no = Carbon::now()->format('y-m-') . (sprintf("%04d", InOutTransaction::count() + 1));
        foreach ($stocks as $stk) {
            $issued = collect(
                DB::select(
                    "SELECT SUM(card.iss) as average FROM pharm_drug_stock_cards card
                WHERE card.dmdcomb = '" .
                        $stk->dmdcomb .
                        "'
                AND card.dmdctr = '" .
                        $stk->dmdctr .
                        "'
                AND card.loc_code = '" .
                        $this->location_id .
                        "'
                AND card.iss > 0
                AND card.stock_date BETWEEN '" .
                        $this->prev_week_start .
                        "' AND '" .
                        $this->prev_week_end .
                        "'",
                )
            )->first();

            $max_level = $issued->average ? $issued->average * 2 : 0;
            if ($stk->reorder_point) {
                $order_qty = $stk->reorder_point;
            } else {
                $order_qty = $max_level - $stk->stock_bal;
            }

            if ($max_level > $stk->stock_bal and $stk->stock_bal >= 1) {
                $this->add_request($stk->dmdcomb, $stk->dmdctr, $order_qty, $reference_no);
            }
        }

        $this->alert('success', 'Request added!');
    }

    public function add_request($dmdcomb, $dmdctr, $requested_qty, $reference_no)
    {
        $current_qty = DrugStock::whereRelation('location', 'description', 'LIKE', '%Warehouse%')
            ->where('dmdcomb', $dmdcomb)->where('dmdctr', $dmdctr)
            ->where('stock_bal', '>', '0')->where('exp_date', '>', now())
            ->groupBy('dmdcomb', 'dmdctr')->sum('stock_bal');

        if ($requested_qty <= $current_qty) {
            InOutTransaction::create([
                'trans_no' => $reference_no,
                'dmdcomb' => $dmdcomb,
                'dmdctr' => $dmdctr,
                'requested_qty' => $requested_qty,
                'requested_by' => session('user_id'),
                'loc_code' => session('pharm_location_id'),
                'remarks_request' => 'Reorder level',
            ]);
        }
        return;
    }
}
