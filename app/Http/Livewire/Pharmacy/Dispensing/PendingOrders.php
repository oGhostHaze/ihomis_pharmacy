<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PendingOrders extends Component
{
    use WithPagination;

    public $date_from, $date_to, $location_id;

    public function updatingFilterCharge()
    {
        $this->resetPage();
    }
    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->startOfDay()->format('Y-m-d H:i:s');
        $date_to = Carbon::parse($this->date_from)->endOfDay()->format('Y-m-d H:i:s');

        $drugs_ordered = collect(DB::select("
        SELECT rxo.enccode, MIN(rxo.dodate) as dodate, rxo.hpercode, pat.patlast, pat.patfirst, pat.patmiddle, COUNT(docointkey) as total_order, sum(rxo.pcchrgamt) as total_amount, rxo.entryby
            FROM hrxo rxo
            JOIN hperson pat ON rxo.hpercode = pat.hpercode
            WHERE   (dodate BETWEEN ? and ?) AND ((rxo.estatus = 'U' OR rxo.estatus = 'P') OR (rxo.estatus = 'S' AND (rxo.pcchrgcod IS NULL OR rxo.pcchrgcod = '')) AND rxo.loc_code = ?)
            GROUP BY pat.patlast, pat.patfirst, pat.patmiddle, rxo.hpercode, rxo.enccode, rxo.entryby
            ORDER BY rxo.dodate
            ", [$date_from, $date_to, $this->location_id]))->all(10);

        return view('livewire.pharmacy.dispensing.pending-orders', [
            'drugs_ordered' => $drugs_ordered,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
        $this->date_from = Carbon::parse(now())->startOfDay()->format('Y-m-d');
    }

    public function view_enctr($code = null)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $code));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
