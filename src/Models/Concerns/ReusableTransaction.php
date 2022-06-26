<?php

namespace Autepos\AiPayment\Models\Concerns;

use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Providers\Contracts\Orderable;

/**
 * Group behavior for supporting s transaction to be re used to avoid waste.
 */
trait ReusableTransaction
{
    /**
     * Check if the instance is already used and therefore should not be overwritten 
     * to make another transaction
     */
    private function isUsed(): bool
    {

        return ($this->success
            or (($this->local_status ?? Transaction::LOCAL_STATUS_INIT) != Transaction::LOCAL_STATUS_INIT)
            or $this->cashier_id // i.e. do not reuse those created by admin, not that it matters though.
        );
    }

    /**
     * Check if the instance is not already used and therefore can be overwritten 
     * to make another transaction
     */
    private function isUnused(): bool
    {
        return !$this->isUsed();
    }


    /**
     * Check that the given orderable is able to use for the transaction instance 
     * for a new transaction.
     *
     */
    public function isUsableFor(Orderable $order): bool
    {
        return ($this->isUnused()
            and ($this->orderable_id == $order->getKey())
        );
    }
}
