<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param string $type The type of update (e.g., 'receipt', 'issue', 'return')
     * @param mixed $payload The data associated with the update
     * @return void
     */
    public function __construct(string $type, $payload)
    {
        $this->type = $type;
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Using a public channel for demonstration, but private channel is preferred in production
        return new Channel('inventory');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'inventory.updated';
    }
}
