<?php

namespace App\Events;

use App\Models\Pharmacy\Drugs\InOutTransaction;
use Illuminate\Broadcasting\Channel;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IoTransNewRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The location instance.
     *
     * @var \App\Models\Pharmacy\PharmLocation
     */
    public $location;
    public $requestor;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PharmLocation $location, InOutTransaction $io_tx)
    {
        $this->location = $location;
        $this->requestor = $io_tx->location->description;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ioTrans.' . $this->location->id);
    }
}
