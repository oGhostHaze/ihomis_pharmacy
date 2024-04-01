<?php

namespace App\Events;

use App\Models\Pharmacy\PharmLocation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class IoTransEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The location instance.
     *
     * @var \App\Models\Pharmacy\PharmLocation
     */
    public $location;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PharmLocation $location)
    {
        $this->location = $location;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('io-trans.'.$this->location->id);
    }
}
