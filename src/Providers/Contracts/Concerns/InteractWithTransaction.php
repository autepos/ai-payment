<?php

namespace Autepos\AiPayment\Providers\Contracts\Concerns;

use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Providers\Contracts\Orderable;


trait InteractWithTransaction
{
    /**
     * Use the instance details to new up a transaction without persisting it to db. If a 
     * transaction is given it will be used instead of newing up another. It is up to 
     * the caller to decide the finale and adequate values for the transaction attributes.
     *
     */
    protected function newTransaction(
        int $orderable_amount = null,
        string $local_status = null,
        string $status = 'unknown',
        bool $success = false,
        string $transaction_family = null,
        string $transaction_family_id = null,
        string $transaction_child_id = null,
        Transaction $transaction = null
    ): Transaction {
        if (is_null($transaction)) {
            $transaction = new Transaction;
        }
        //
        $transaction->local_status = $local_status ?? Transaction::LOCAL_STATUS_INIT;
        $transaction->orderable_amount = $orderable_amount;

        if ($this->order) {

            if (is_null($orderable_amount)) {
                $transaction->orderable_amount = $this->order->getAmount();
            }

            $transaction->currency = $this->order->getCurrency();
            $transaction->orderable_id = $this->order->getKey();
        }

        //
        if ($customer = $this->getCustomerData()) {
            $transaction->user_type = $customer->user_type;
            $transaction->user_id = $customer->user_id;
        }

        //
        $transaction->payment_provider = $this->getProvider();

        //
        $transaction->success = $success;
        $transaction->status = $status;

        //
        $transaction->transaction_family = $transaction_family ?? Transaction::TRANSACTION_FAMILY_PAYMENT;
        $transaction->transaction_family_id = $transaction_family_id ?? $transaction->transaction_family_id;
        $transaction->transaction_child_id = $transaction_child_id ?? $transaction->transaction_child_id;

        //
        $transaction->livemode = $this->livemode;



        return $transaction;
    }


    /**
     * Get a transaction that can be used to initiate payment.
     * @param integer|null $amount Orderable amount
     * @param Transaction|null $transaction A suggested transaction object to be used to record the transaction. This is to encourage usage of unused transactions instead of creating new ones every time.
     * @return Transaction This will be an updated suggested transaction it was used.
     */
    protected function getInitTransaction(int $amount = null, Transaction $transaction = null): Transaction
    {

        // If no transaction exists then make a new one
        if ($transaction and $transaction->isUsableFor($this->order)) { //NOTE: The PaymentService will have already performed Transaction::isUsableFor check, so this is repeating task when PaymentService is in use.
            $transaction = $this->newTransaction(
                $amount,
                Transaction::LOCAL_STATUS_INIT,
                'unknown',
                false,
                Transaction::TRANSACTION_FAMILY_PAYMENT,
                $transaction->transaction_family_id,
                $transaction->transaction_child_id,
                $transaction
            );
        } else {
            $transaction = $this->newTransaction(
                $amount,
                Transaction::LOCAL_STATUS_INIT,
                'unknown',
                false,
                Transaction::TRANSACTION_FAMILY_PAYMENT
            );
        }

        return $transaction;
    }
    /**
     * Check that the given orderable is able to use for the transaction instance 
     * for a new transaction.
     *
     */
    protected function isTransactionUsableFor(Transaction $transaction, Orderable $order): bool
    {
        return $transaction->isUsableFor($order);
    }

    /**
     * Check if this provider provided the given transaction
     */
    protected function isOwnTransaction(Transaction $transaction): bool
    {
        return $transaction->isForPaymentProvider($this->getProvider());
    }


    /**
     * Authorise the provider to work on the given transaction
     */
    protected function authoriseProviderTransaction(Transaction $transaction): bool
    {
        return (
            ($this->isOwnTransaction($transaction))
            and
            $this->hasSameLiveModeAsTransaction($transaction)
        );
    }

    /**
     * Check if the provider and the given transaction have the same live mode value.
     *
     */
    protected function hasSameLiveModeAsTransaction(Transaction $transaction): bool
    {
        return ($transaction->isLivemode() == $this->isLivemode());
    }


    /**
     * Sync the local data with the data the provider holds for this transaction
     */
    public function syncTransaction(Transaction $transaction): PaymentResponse
    {
        return new PaymentResponse(PaymentResponse::newType('retrieve'), false, 'Not implemented', ['Sync is not implemented']);
    }
}
