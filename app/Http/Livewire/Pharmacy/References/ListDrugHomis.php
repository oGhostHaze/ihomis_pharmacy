<?php

namespace App\Http\Livewire\Pharmacy\References;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\DrugForm;
use App\Models\Pharmacy\DrugGeneric;
use App\Models\Pharmacy\DrugRoute;
use App\Models\Pharmacy\DrugStrength;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ListDrugHomis extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $search;
    public $dmdrxot = 'RXX', $grpcode, $brandname, $dmdnost, $strecode, $formcode, $rtecode, $dmdrem, $dmdpndf = 'Y', $dmdstat;

    public function render()
    {
        $drugs = Drug::where('dmdstat', 'A')
            ->has('generic')
            ->with('form')
            ->with('route')
            ->with('strength')
            ->whereRelation('generic', 'gendesc', 'LIKE', '%' . $this->search . '%');

        $generics = DrugGeneric::where('genstat', 'A')->get();
        $strengths = DrugStrength::where('strestat', 'A')->get();
        $forms = DrugForm::where('formstat', 'A')->get();
        $routes = DrugRoute::where('rtestat', 'A')->get();

        return view('livewire.pharmacy.references.list-drug-homis', [
            'drugs' => $drugs->paginate(20),
            'generics' => $generics,
            'strengths' => $strengths,
            'forms' => $forms,
            'routes' => $routes,
        ]);
    }

    public function new_drug()
    {
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
