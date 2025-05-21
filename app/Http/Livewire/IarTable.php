<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IarTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'tbl_iar.iardate';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $selectedIar = null;
    public $showViewModal = false;
    public $officeId = 22; // Pharmacy office ID

    protected $queryString = ['search', 'sortField', 'sortDirection', 'perPage'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewIar($iarId)
    {
        // Get IAR details
        $iar = DB::connection('pims')
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
            ->where('tbl_iar.iarID', $iarId)
            ->first();

        // Get IAR details
        $iarDetails = DB::connection('pims')
            ->table('tbl_iar_details')
            ->select([
                'tbl_iar_details.iardetailsid',
                'tbl_iar_details.description',
                'tbl_iar_details.unit',
                'tbl_iar_details.quantity',
                'tbl_iar_details.unitprice',
                'tbl_iar_details.totalprice',
                'tbl_iar_details.itemcode',
                'tbl_iar_details.batch_no',
                'tbl_iar_details.expire_date',
                'tbl_iar_details.invoiceno'
            ])
            ->where('tbl_iar_details.iarID', $iarId)
            ->where('tbl_iar_details.status', 'A')
            ->get();

        $this->selectedIar = [
            'iar' => $iar,
            'details' => $iarDetails
        ];

        $this->showViewModal = true;
    }

    public function render()
    {
        $iars = DB::connection('pims')
            ->table('tbl_iar')
            ->select([
                'tbl_iar.iarID',
                'tbl_iar.iarNo',
                DB::raw("DATE_FORMAT(tbl_iar.iardate, '%b-%d-%Y') AS formatted_iardate"),
                'tbl_iar.iardate',
                'tbl_iar.supplier',
                'tbl_iar.pono',
                'tbl_iar.invoiceNo',
                DB::raw("DATE_FORMAT(tbl_iar.invoicedate, '%b-%d-%Y') AS formatted_invoicedate"),
                'tbl_iar.iartotalprice',
                'tbl_iar.acceptStatus',
                'tbl_iar.inspectStatus',
                'tbl_iar.ris_in_iar',
                'tbl_iar.risid',
                'tbl_office.officeName',
                'ris.risno'
            ])
            ->join('tbl_office', 'tbl_office.officeID', '=', 'tbl_iar.officeid')
            ->join('tbl_ris as ris', 'ris.risid', '=', 'tbl_iar.risid')
            ->where('tbl_iar.officeid', $this->officeId)
            ->where('tbl_iar.status', 'A')
            ->when($this->search, function ($query) {
                return $query->where(function ($query) {
                    $query->where('tbl_iar.iarNo', 'like', '%' . $this->search . '%')
                        ->orWhere('tbl_iar.supplier', 'like', '%' . $this->search . '%')
                        ->orWhere('tbl_iar.pono', 'like', '%' . $this->search . '%')
                        ->orWhere('tbl_iar.invoiceNo', 'like', '%' . $this->search . '%')
                        ->orWhere('ris.risno', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.iar-table', [
            'iars' => $iars
        ]);
    }
}
