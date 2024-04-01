<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\DeliveryDetail;
use App\Models\Pharmacy\DeliveryItems;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class DeliverySummary extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $from, $to, $search;

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $deliveries = DeliveryDetail::where('status', 'locked')
            ->with('items')->with('charge')
            ->whereBetween('delivery_date', [$from, $to])
            ->whereHas('items', function ($query) {
                $query->whereHas('drug', function ($query2) {
                    $query2->where('drug_concat', 'LIKE', $this->search . '%');
                });
            })
            ->latest()
            ->get();

        return view('livewire.pharmacy.reports.delivery-summary', [
            'deliveries' => $deliveries,
        ]);
    }

    public function mount()
    {
        $this->from = date('Y-m-d', strtotime(now()));
        $this->to = date('Y-m-d', strtotime(now()));
    }
}