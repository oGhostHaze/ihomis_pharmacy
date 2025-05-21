<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowIar extends Component
{
    public $iarId;
    protected $iar;
    protected $iarDetails;
    protected $relatedRis;

    public function mount($id)
    {
        $this->iarId = $id;
        $this->loadIar();
    }

    public function loadIar()
    {
        // Get IAR details
        $this->iar = DB::connection('pims')
            ->table('tbl_iar')
            ->select([
                'tbl_iar.iarID',
                'tbl_iar.iarNo',
                DB::raw("DATE_FORMAT(tbl_iar.iardate, '%b-%d-%Y') AS formatted_iardate"),
                'tbl_iar.iardate',
                'tbl_iar.supplier',
                'tbl_iar.pono',
                'tbl_iar.prno',
                'tbl_iar.invoiceNo',
                DB::raw("DATE_FORMAT(tbl_iar.invoicedate, '%b-%d-%Y') AS formatted_invoicedate"),
                'tbl_iar.invoicedate',
                'tbl_iar.iartotalprice',
                DB::raw("DATE_FORMAT(tbl_iar.dateInspected, '%b-%d-%Y') AS formatted_inspecteddate"),
                'tbl_iar.dateInspected',
                DB::raw("DATE_FORMAT(tbl_iar.dateReceived, '%b-%d-%Y') AS formatted_receiveddate"),
                'tbl_iar.dateReceived',
                'tbl_iar.acceptStatus',
                'tbl_iar.inspectStatus',
                'tbl_iar.remarks',
                'tbl_iar.accptname',
                'tbl_iar.accptdesig',
                'tbl_iar.ris_in_iar',
                'tbl_iar.risid',
                'tbl_office.officeName',
                'tbl_office.rcc'
            ])
            ->join('tbl_office', 'tbl_office.officeID', '=', 'tbl_iar.officeid')
            ->where('tbl_iar.iarID', $this->iarId)
            ->first();

        if (!$this->iar) {
            session()->flash('error', 'IAR not found');
            return redirect()->route('iar.index');
        }

        // Get IAR details
        $this->iarDetails = DB::connection('pims')
            ->table('tbl_iar_details')
            ->select([
                'tbl_iar_details.iardetailsid',
                'tbl_items.description',
                'tbl_items.unit',
                'tbl_iar_details.quantity',
                'tbl_iar_details.unitprice',
                'tbl_iar_details.totalprice',
                'tbl_iar_details.itemcode',
                'tbl_iar_details.batch_no',
                'tbl_iar_details.expire_date',
                'tbl_iar_details.invoiceno'
            ])
            ->join('tbl_items', 'tbl_items.itemid', '=', 'tbl_iar_details.itemid')
            ->where('tbl_iar_details.iarID', $this->iarId)
            ->where('tbl_iar_details.status', 'A')
            ->get();

        // Get related RIS if exists
        if ($this->iar->risid) {
            $this->relatedRis = DB::connection('pims')
                ->table('tbl_ris')
                ->select([
                    'tbl_ris.risid',
                    'tbl_ris.risno',
                    DB::raw("DATE_FORMAT(tbl_ris.risdate, '%b-%d-%Y') AS formatted_risdate"),
                    'tbl_ris.purpose',
                    'tbl_ris.requestby',
                    'tbl_ris.issuedby',
                    'tbl_office.officeName',
                    'req.fullName AS requested_by_name',
                    'issue.fullName AS issued_by_name'
                ])
                ->join('tbl_office', 'tbl_office.officeID', '=', 'tbl_ris.officeID')
                ->leftJoin('tbl_user AS req', 'req.userID', '=', 'tbl_ris.requestby')
                ->leftJoin('tbl_user AS issue', 'issue.userID', '=', 'tbl_ris.issuedby')
                ->where('tbl_ris.risid', $this->iar->risid)
                ->first();
        }
    }

    public function render()
    {
        return view('livewire.show-iar', [
            'iar' => $this->iar,
            'iarDetails' => $this->iarDetails,
            'relatedRis' => $this->relatedRis ?? null
        ]);
    }
}
