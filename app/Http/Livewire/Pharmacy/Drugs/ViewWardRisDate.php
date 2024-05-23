<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\WardRisRequest;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ViewWardRisDate extends Component
{
    use LivewireAlert;

    public $date;

    public function render()
    {
        $from = Carbon::parse($this->date)->startOfDay();
        $to = Carbon::parse($this->date)->endOfDay();
        $trans = WardRisRequest::with('charge')
            ->where('loc_code', session('pharm_location_id'))
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        return view('livewire.pharmacy.drugs.view-ward-ris-date', compact(
            'trans',
        ));
    }

    public function mount($date)
    {
        $this->date = $date;
    }
}
