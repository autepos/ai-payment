<?php

namespace Autepos\AiPayment\Events;

use Autepos\AiPayment\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderableTransactionsTotaled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The orderable for which the total has been computed.
     *
     * @var integer|string
     */
    public $orderable_id;

    /**
     * The total amount resulting from this totaling all transactions fro an orderable.
     *
     * @var integer
     */
    public $total_paid = 0;
    /**
     * The transaction that triggered this event.
     *
     * @var Transaction
     */
    public $triggeringTransaction;


    public $afterCommit = true;


    /**
     * Create a new event instance.
     * 
     * @param integer|string $orderable_id The orderable for which the total has been computed.
     * @param integer $total_paid The total amount resulting from this totaling all transactions fro an orderable.
     * @param Transaction $triggeringTransaction The transaction that triggered this event.
     * 
     * @return void
     */
    public function __construct($orderable_id, int $total_paid, Transaction $triggeringTransaction)
    {
        $this->orderable_id = $orderable_id;
        $this->total_paid = $total_paid;
        $this->triggeringTransaction = $triggeringTransaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
