<?php

namespace App\Http\Livewire\Records;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Crypt;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Prescriptions\Prescription;

class PrescriptionEr extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $filter_date;

    public function render()
    {
        $from = Carbon::parse($this->filter_date)->startOfDay();
        $to = Carbon::parse($this->filter_date)->endOfDay();

        $prescriptions = Prescription::has('active_er')->has('data_active')
            ->with('active_er')->with('data_active')
            ->with('active_g24')->with('active_or')->with('active_basic')
            ->where('stat', 'A')
            ->whereBetween('created_at', [$from, $to]);

        return view('livewire.records.prescription-er', [
            'prescriptions' => $prescriptions->get(),
        ]);
    }

    public function mount()
    {
        $this->filter_date = date('Y-m-d');
    }

    public function view_enctr($enccode)
    {
        $enccode = Crypt::encrypt(str_replace(' ', '--', $enccode));
        return redirect()->route('dispensing.view.enctr', ['enccode' => $enccode]);
    }
}
