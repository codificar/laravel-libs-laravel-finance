<?php

namespace Codificar\Finance\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Requests, Provider;


/**
 * Class Requests
 *
 * @package UberClone
 *
 * @OA\Schema(
 *         schema="PixUpdate",
 *         type="object",
 *         description="Atualização do pagamento do pix",
 *         title="Pix Update Resource",
 *         allOf={
 *           @OA\Schema(ref="#/components/schemas/PixUpdate"),
 *           @OA\Schema(
 *              @OA\Property(property="Provider", ref="#/components/schemas/Provider"),
 *              @OA\Property(property="User", ref="#/components/schemas/User"),
 *           )
 *       }
 * )
 */

class PixUpdate implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $transaction_id;
    public $is_paid;
    public $payment_change;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($transaction_id, $is_paid, $payment_change)
    {   
        $this->transaction_id = $transaction_id;
        $this->is_paid = $is_paid;
        $this->payment_change = $payment_change;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('pix.' . $this->transaction_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {   

      
        return [
            'transaction_id'    => $this->transaction_id,
            'is_paid'           => $this->is_paid,
            'payment_change'    => $this->payment_change
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'pixUpate';
    }

    /**
     * Add a Tag to Laravel Horizon
     */
    public function tags()
    {
        return ['pix_update', 'pix.' . $this->transaction_id];
    }
}