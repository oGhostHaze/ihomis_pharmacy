<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowRis extends Component
{
    public $risId;
    protected $ris;
    protected $risDetails;
    protected $relatedIar;

    public function mount($id)
    {
        $this->risId = $id;
        $this->loadRis();
    }

    public function loadRis()
    {
        // Get RIS details
        $this->ris = DB::connection('pims')
            ->table('tbl_ris')
            ->select([
                'tbl_ris.risid',
                'tbl_ris.risno',
                'tbl_ris.purpose',
                DB::raw("DATE_FORMAT(tbl_ris.risdate, '%b-%d-%Y') AS formatted_risdate"),
                'tbl_ris.officeID',
                DB::raw("DATE_FORMAT(tbl_ris.requestdate, '%b-%d-%Y') AS formatted_requestdate"),
                'tbl_ris.apprvdby',
                'tbl_ris.apprvdby_desig',
                DB::raw("DATE_FORMAT(tbl_ris.apprvddate, '%b-%d-%Y') AS formatted_approveddate"),
                DB::raw("DATE_FORMAT(tbl_ris.issueddate, '%b-%d-%Y') AS formatted_issueddate"),
                'tbl_ris.receivedby',
                'tbl_ris.receivedby_desig',
                DB::raw("DATE_FORMAT(tbl_ris.receiveddate, '%b-%d-%Y') AS formatted_receiveddate"),
                'tbl_ris.apprvstat',
                'tbl_ris.issuedstat',
                'tbl_ris.status',
                'tbl_ris.ris_in_iar',
                'tbl_ris.iarid',
                'tbl_office.officeName',
                'tbl_office.rcc',
                'req.fullName AS requested_by_name',
                'req.designation AS requested_by_desig',
                'issue.fullName AS issued_by_name',
                'issue.designation AS issued_by_desig'
            ])
            ->leftJoin('tbl_user AS req', 'req.userID', '=', 'tbl_ris.requestby')
            ->leftJoin('tbl_user AS issue', 'issue.userID', '=', 'tbl_ris.issuedby')
            ->join('tbl_office', 'tbl_office.officeID', '=', 'tbl_ris.officeID')
            ->where('tbl_ris.risid', $this->risId)
            ->first();

        if (!$this->ris) {
            session()->flash('error', 'RIS not found');
            return redirect()->route('ris.index');
        }

        // Get RIS items
        $this->risDetails = DB::connection('pims')
            ->table('tbl_ris_details')
            ->select([
                'tbl_ris_details.risdetid',
                'tbl_ris_details.stockno',
                'tbl_ris_details.onhand',
                'tbl_ris_details.itmqty',
                'tbl_items.itemID',
                'tbl_items.description',
                'tbl_items.unit'
            ])
            ->join('tbl_items', 'tbl_items.itemID', '=', 'tbl_ris_details.itemID')
            ->where('tbl_ris_details.risid', $this->risId)
            ->where('tbl_ris_details.status', 'A')
            ->get();

        // Get fund source information for each item if available
        foreach ($this->risDetails as $detail) {
            $detail->fundSources = DB::connection('pims')
                ->table('tbl_ris_release')
                ->select([
                    'tbl_ris_release.releaseqty',
                    'tbl_ris_release.fsid',
                    'tbl_ris_release.unitprice'
                ])
                ->where('tbl_ris_release.risdetid', $detail->risdetid)
                ->where('tbl_ris_release.status', 'A')
                ->get();
        }

        // Get related IAR if exists
        if ($this->ris->iarid) {
            $this->relatedIar = DB::connection('pims')
                ->table('tbl_iar')
                ->select([
                    'tbl_iar.iarID',
                    'tbl_iar.iarNo',
                    DB::raw("DATE_FORMAT(tbl_iar.iardate, '%b-%d-%Y') AS formatted_iardate"),
                    'tbl_iar.supplier'
                ])
                ->where('tbl_iar.iarID', $this->ris->iarid)
                ->first();
        }
    }

    public function render()
    {
        return view('livewire.show-ris', [
            'ris' => $this->ris,
            'risDetails' => $this->risDetails,
            'relatedIar' => $this->relatedIar ?? null
        ]);
    }
}
