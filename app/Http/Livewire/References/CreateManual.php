<?php

namespace App\Http\Livewire\References;

use App\Models\PharmManual;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateManual extends Component
{
    use WithFileUploads;
    use LivewireAlert;

    protected $listeners = ['save'];

    public $title, $description, $photos = [];

    protected $messages = [
        'photos.max' => 'Uploaded images must not be greater than 5 items.'
    ];

    public function render()
    {
        return view('livewire.references.create-manual');
    }

    public function save()
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable'],
            'photos.*' => ['image', 'max:5120', 'mimes:png,jpg'], // 5MB Max
            'photos' => ['max:5'],
        ]);

        $photos = [];

        foreach ($this->photos as $photo) {
            $fn = $photo->store('photos', 'public');
            array_push($photos, $fn);
        }

        PharmManual::create([
            'title' => $this->title,
            'description' => $this->description,
            'photos' => implode(',', $photos),
        ]);

        session()->flash('flash.banner', 'Success!');
        session()->flash('flash.bannerStyle', 'success');

        return redirect(route('ref.manual'));
    }
}
