<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class RisList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'tbl_ris.risdate';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $officeId = 22; // Default office ID for Pharmacy
    public $statusFilter = 'all';

    protected $queryString = ['search', 'sortField', 'sortDirection', 'perPage', 'statusFilter'];

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

    public function render()
    {
        $risItems = DB::connection('pims')
            ->table('tbl_ris')
            ->select([
                'tbl_ris.risid',
                'tbl_ris.risno',
                'tbl_ris.purpose',
                DB::raw("DATE_FORMAT(tbl_ris.risdate, '%b-%d-%Y') AS formatted_risdate"),
                'tbl_ris.risdate',
                DB::raw("DATE_FORMAT(tbl_ris.requestdate, '%b-%d-%Y') AS formatted_requestdate"),
                DB::raw("DATE_FORMAT(tbl_ris.apprvddate, '%b-%d-%Y') AS formatted_approveddate"),
                DB::raw("DATE_FORMAT(tbl_ris.issueddate, '%b-%d-%Y') AS formatted_issueddate"),
                'tbl_ris.apprvstat',
                'tbl_ris.issuedstat',
                'tbl_ris.status',
                'tbl_ris.ris_in_iar',
                'tbl_office.officeName',
                'req.fullName AS requested_by',
                'issue.fullName AS issued_by'
            ])
            ->leftJoin('tbl_user AS req', 'req.userID', '=', 'tbl_ris.requestby')
            ->leftJoin('tbl_user AS issue', 'issue.userID', '=', 'tbl_ris.issuedby')
            ->join('tbl_office', 'tbl_office.officeID', '=', 'tbl_ris.officeID')
            ->where('tbl_ris.officeID', $this->officeId)
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'approved') {
                    return $query->where('tbl_ris.apprvstat', 'A');
                } elseif ($this->statusFilter === 'pending') {
                    return $query->where('tbl_ris.apprvstat', 'P');
                } elseif ($this->statusFilter === 'issued') {
                    return $query->where('tbl_ris.issuedstat', 'I');
                } elseif ($this->statusFilter === 'with-iar') {
                    return $query->where('tbl_ris.ris_in_iar', 'Y');
                }
            })
            ->when($this->search, function ($query) {
                return $query->where(function ($query) {
                    $query->where('tbl_ris.risno', 'like', '%' . $this->search . '%')
                        ->orWhere('tbl_ris.purpose', 'like', '%' . $this->search . '%')
                        ->orWhere('req.fullName', 'like', '%' . $this->search . '%')
                        ->orWhere('issue.fullName', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.ris-list', [
            'risItems' => $risItems
        ]);
    }
}
