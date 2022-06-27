<?php

namespace Autepos\AiPayment;


use Autepos\AiPayment\Models\Transaction;

class PaymentResponse extends BaseResponse
{



    /**
     * The transaction object.
     *
     * @var null|Transaction
     */
    protected $transaction = null;


    /**
     * Set the transaction.
     *
     * @param  Transaction  $transaction
     */
    public function transaction($transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get the transaction object.
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }



    /**
     * Convert the instance to array
     */
    protected function toArray(): array
    {
        $data = parent::toArray();


        if ($this->transaction) {
            $data['transaction'] = [
                'id' => $this->transaction->id,
                'orderable_id' => $this->transaction->orderable_id,
                'refund' => $this->transaction->refund,
                'success' => $this->transaction->success,
                'status' => $this->transaction->status,
                'humans' => $this->transaction->humans,
                'amount' => $this->transaction->amount,
            ];
        }

        return $data;
    }
}
