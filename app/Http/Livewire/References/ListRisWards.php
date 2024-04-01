<?php

namespace App\Http\Livewire\References;

use App\Models\RisWard;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ListRisWards extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['save'];

    public $search;
    public $ward_name;

    public function render()
    {
        $locations = RisWard::where('ward_name', 'LIKE', '%' . $this->search . '%');

        return view('livewire.references.list-ris-wards', [
            'locations' => $locations->paginate(10),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function save($loc_id = null)
    {
        $this->validate(['ward_name' => ['required', 'string']]);

        if ($loc_id) {
            $location = RisWard::find($loc_id);
            $location->ward_name = $this->ward_name;
            $location->save();
        } else {
            RisWard::create(['ward_name' => $this->ward_name]);
        }
        $this->resetExcept('search');
        $this->alert('success', 'Saved!');
    }
}
