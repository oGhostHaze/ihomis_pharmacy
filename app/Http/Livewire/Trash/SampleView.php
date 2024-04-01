<?php

namespace App\Http\Livewire\Trash;

use Livewire\Component;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Support\Facades\Auth;

class SampleView extends Component
{
    public $search;
    public $location_id;

    public function render()
    {
        $stocks = DrugStock::with('charge')->with('current_price')->has('current_price')
            ->where('loc_code', $this->location_id)
            ->groupBy('dmdcomb', 'dmdctr', 'chrgcode', 'dmdprdte', 'drug_concat')
            ->select('dmdcomb', 'dmdctr', 'drug_concat', 'chrgcode', 'dmdprdte')
            ->selectRaw('SUM(stock_bal) as stock_bal, MAX(id) as id');

        return view('livewire.trash.sample-view', [
            'stocks' => $stocks->get(),
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
    }
}
