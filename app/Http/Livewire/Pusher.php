<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Events\UserUpdated;
use App\Events\IoTransEvent;
use Illuminate\Support\Facades\Auth;
use App\Models\Pharmacy\PharmLocation;
use App\Notifications\IoTranNotification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\InOutTransaction;

class Pusher extends Component
{

    use LivewireAlert;

    public $showUserNotification = false;
    public $user_id;

    public function getListeners(): array
    {
        return [
            "echo:user.{$this->user_id},UserUpdated" => 'notifyUpdatedUser',
        ];
    }

    public function render()
    {
        return view('livewire.pusher');
    }

    public function mount()
    {
        $this->user_id = session('user_id');
    }

    public function notify_user()
    {
        $user = User::find(session('user_id'));
        UserUpdated::dispatch($user);
    }

    public function notify_request()
    {
        $io_tx = InOutTransaction::latest()->first();
        $warehouse = PharmLocation::find('1');
        // IoTransEvent::dispatch($warehouse);
        $warehouse->notify(new IoTranNotification($io_tx, session('user_id')));
        $this->alert('success', 'Dispatched');
    }
}
