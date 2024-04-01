<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Models\Pharmacy\Drugs\InOutTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IoTransRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The location instance.
     *
     * @var \App\Models\Pharmacy\PharmLocation
     */
    public $location;
    public $message;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(InOutTransaction $io_tx, $message)
    {
        $this->location = $io_tx->location;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ioTrans.'.$this->location->id);
    }
}
