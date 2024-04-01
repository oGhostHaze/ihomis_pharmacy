<?php

namespace App\Http\Livewire\References;

use App\Models\PharmManual;
use Livewire\Component;

class Manual extends Component
{
    public $view_img;

    public function render()
    {
        $manuals = PharmManual::all();
        return view('livewire.references.manual', [
            'manuals' => $manuals,
        ]);
    }
}
