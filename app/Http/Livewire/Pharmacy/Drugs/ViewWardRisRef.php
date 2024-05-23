<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\WardRisRequest;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ViewWardRisRef extends Component
{
    use LivewireAlert;

    public $reference_no;

    public function render()
    {
        $trans = WardRisRequest::with('charge')
            ->where('loc_code', session('pharm_location_id'))
            ->where('trans_no', $this->reference_no)
            ->latest()
            ->get();

        return view('livewire.pharmacy.drugs.view-ward-ris-ref', compact(
            'trans',
        ));
    }

    public function mount($reference_no)
    {
        $this->reference_no = $reference_no;
    }
}
