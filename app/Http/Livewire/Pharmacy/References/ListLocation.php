<?php

namespace App\Http\Livewire\Pharmacy\References;

use App\Models\Pharmacy\PharmLocation;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ListLocation extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['save'];

    public $search;
    public $description;

    public function render()
    {
        $locations = PharmLocation::where('description', 'LIKE', '%'.$this->search.'%');

        return view('livewire.pharmacy.references.list-location', [
            'locations' => $locations->paginate(10),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function save($loc_id = null)
    {
        $this->validate(['description' => ['required', 'string']]);

        if($loc_id){
            $location = PharmLocation::find($loc_id);
            $location->description = $this->description;
            $location->save();
        }else{
            PharmLocation::create(['description' => $this->description]);
        }
        $this->resetExcept('search');
        $this->alert('success', 'Saved!');
    }
}
