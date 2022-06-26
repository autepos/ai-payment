<?php
namespace Autepos\AiPayment\Providers\Contracts\Concerns;

use Autepos\AiPayment\Models\Transaction;

trait RefundUtil{
    /**
     * Check if the refund is valid
     */
    public function validateRefund(Transaction $transaction, int $refund_amount): bool
    {
        return (
            $this->isValidRefundAmount($transaction, $refund_amount)
            and
            ($this->isOwnTransaction($transaction))
        );
    }

    /**
     * Check if the given refund amount is valid for the given transaction
     *
     */
    protected function isValidRefundAmount(Transaction $transaction, int $refund_amount): bool
    {
        return $transaction->isValidRefundAmount($refund_amount);
    }

    /**
     * Checks if the transaction can be cancelled
     * This can used to check if the underlying orderable can be cancel after 
     * payment transaction. E.g if payment is made in error, an orderable may be 
     * canceled so that the associated order items may be marked as unpaid. A refund 
     * may then be processed by the cashier following the cancellation.
     */
    public function isCancelable(Transaction $transaction):bool{
        return $transaction->isCancelable();
    }
    /**
     * Check if the provider is able to make refunds
     */
    public function isRefundable():bool{
        return true;
    }
}