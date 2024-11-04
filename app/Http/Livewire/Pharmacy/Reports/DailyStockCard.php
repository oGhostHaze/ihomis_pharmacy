<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\UserSession;
use App\Models\Pharmacy\Drug;
use Illuminate\Support\Facades\DB;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DailyStockCard extends Component
{
    use LivewireAlert;

    public $date_from, $date_to, $location_id, $drugs, $dmdcomb, $dmdctr, $fund_sources, $selected_drug, $selected_fund, $chrgcode, $chrgdesc;


    public function updatedSelectedDrug()
    {
        $drug = $this->selected_drug;
        $this->reset('dmdcomb', 'dmdctr');
        if ($drug) {
            $selected_drug = explode(',', $drug);
            $this->dmdcomb = $selected_drug[0];
            $this->dmdctr = $selected_drug[1];
        }
    }

    public function updatedSelectedFund()
    {
        $fund = $this->selected_fund;
        $this->reset('chrgcode', 'chrgdesc');
        if ($fund) {
            $selected_fund = explode(',', $fund);
            $this->chrgcode = $selected_fund[0];
            $this->chrgdesc = $selected_fund[1];
        }
    }

    public function render()
    {
        $locations = PharmLocation::all();
        $cards = DrugStockCard::select(DB::raw('SUM(reference) as reference, SUM(rec) as rec, SUM(iss) as iss, SUM(bal) as bal'), 'drug_concat', 'stock_date', 'chrgcode')
            ->where('dmdcomb', $this->dmdcomb)
            ->where('dmdctr', $this->dmdctr)
            ->whereBetween('stock_date', [$this->date_from, $this->date_to])
            ->where('loc_code', $this->location_id)
            ->when($this->chrgcode, function ($query3) {
                $query3->where('chrgcode', $this->chrgcode);
            })
            ->groupBy('dmdcomb', 'dmdctr', 'drug_concat', 'chrgcode', 'stock_date')
            ->orderBy('stock_date', 'ASC')
            ->orderBy('drug_concat', 'ASC')
            ->with('charge')
            ->get();

        return view('livewire.pharmacy.reports.daily-stock-card', compact(
            'locations',
            'cards',
        ));
    }

    public function mount()
    {
        if (isset($_GET['location_id'])) {
            $this->location_id = $_GET['location_id'];
        } else {
            $this->location_id = session('pharm_location_id');
        }
        $this->date_from = Carbon::parse(now())->subDays(2)->format('Y-m-d');
        $this->date_to = Carbon::parse(now())->format('Y-m-d');

        $this->drugs = Drug::where('dmdstat', 'A')
            ->whereNotNull('drug_concat')
            ->has('generic')
            ->orderBy('drug_concat', 'ASC')->get();

        $this->fund_sources = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();
    }

    public function initCard()
    {

        // $locations = PharmLocation::all();
        // foreach($locations as $location){
        //     $location->under_maintenance = true;
        //     $location->save();
        // }


        // $sessions = UserSession::where('user_id', '<>', '1')->get();
        // foreach ($sessions as $session) {
        //     $session->delete();
        // }
        $date_before = Carbon::parse(now())->subDay()->format('Y-m-d');
        $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code')
            ->where('stock_bal', '>', 0)
            ->orWhere(function ($query) use ($date_before) {
                $query->where('stock_bal', '>', 0)
                    ->where('updated_at', '>', $date_before);
            })->get();

        foreach ($stocks as $stock) {
            if ($stock->stock_bal > 0) {
                DrugStockCard::create([
                    'chrgcode' => $stock->chrgcode,
                    'loc_code' => $stock->loc_code,
                    'dmdcomb' => $stock->dmdcomb,
                    'dmdctr' => $stock->dmdctr,
                    'drug_concat' => $stock->drug_concat(),
                    'exp_date' => $stock->exp_date,
                    'stock_date' => date('Y-m-d'),
                    'reference' => $stock->stock_bal,
                    'bal' => $stock->stock_bal,
                ]);
            }


            $card = DrugStockCard::whereNull('reference')
                ->whereNull('rec')
                ->where('chrgcode', $stock->chrgcode)
                ->where('loc_code', $stock->loc_code)
                ->where('dmdcomb', $stock->dmdcomb)
                ->where('dmdctr', $stock->dmdctr)
                ->where('drug_concat', $stock->drug_concat())
                ->where('exp_date', $stock->exp_date)
                ->first();

            if ($card) {
                $card->reference = $stock->stock_bal + $card->iss + $card->rec;
                $card->bal = $stock->stock_bal;
                $card->save();
            }
        }

        // foreach($locations as $location){
        //     $location->under_maintenance = false;
        //     $location->save();
        // }

        $this->alert('success', 'Stock card reference value captured');
    }
}
