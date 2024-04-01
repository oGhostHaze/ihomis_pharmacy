<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IoTransReceivedReport extends Component
{

    use WithPagination;

    public $from, $to, $search, $filter_charge = 'DRUME';

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        $trans = DB::select("SELECT pit.trans_no, loc.description, pit.updated_at, pit.created_at, pit.issued_by, pit.issued_qty,
                                (SELECT drug_concat FROM hdmhdr WHERE hdmhdr.dmdcomb = pit.dmdcomb AND hdmhdr.dmdctr = pit.dmdctr) drug_concat
                            FROM pharm_io_trans pit
                            JOIN pharm_locations loc ON pit.request_from = loc.id
                            WHERE pit.trans_stat = 'Received' AND pit.loc_code = '" . session('pharm_location_id') . "'
                                AND EXISTS (SELECT * FROM pharm_io_trans_items WHERE pit.id = pharm_io_trans_items.iotrans_id AND chrgcode = '" . $this->filter_charge . "')
                                AND pit.updated_at between '" . $from . "' and '" . $to . "'
                            ORDER BY drug_concat
                            ");

        return view('livewire.pharmacy.reports.io-trans-received-report', [
            'trans' => $trans,
            'charge_codes' => $charge_codes,
        ]);
    }

    public function mount()
    {
        $this->from = date('Y-m-d', strtotime(now()));
        $this->to = date('Y-m-d', strtotime(now()));
    }
}
