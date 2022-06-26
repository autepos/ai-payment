<?php
namespace Autepos\AiPayment\Exceptions;

use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

/**
 * TODO: this exception is currently not in use internally
 */
class TransactionOrderMismatchException extends \Exception implements ExceptionInterface{

    /**
     * The transaction that mismatched with order
     *
     * @var \Autepos\AiPayment\Models\Transaction
     */
    protected $transaction=null;

    /**
     * The orderable that mismatched with the transaction
     *
     * @var \Autepos\AiPayment\Providers\Contracts\Orderable
     */
    protected $order=null;

    /**
     * The processing provider during the mismatch
     *
     * @var \Autepos\AiPayment\Providers\Contracts\PaymentProvider
     */
    protected $paymentProvider=null;

        /**
     * The Payment response relating to the mismatch
     *
     * @var \Autepos\AiPayment\PaymentResponse
     */
    protected $paymentResponse=null;


    /**
     * Creates a new  exception.
     *
     * @return static
     */
    public static function factory(
        $message,
        Transaction $transaction = null,
        Orderable $order = null,
        PaymentProvider $paymentProvider = null,
        PaymentResponse $paymentResponse = null
    ) {
        $instance = new static($message);
        $instance->setTransaction($transaction);
        $instance->setOrder($order);
        $instance->setPaymentProvider($paymentProvider);
        $instance->setPaymentResponse($paymentResponse);
        return $instance;
    }
    


    /**
     * Set transaction that mismatched with order
     */
    public function setTransaction(Transaction $transaction){
        $this->transaction=$transaction;
    }

    /**
     * Set the orderable that mismatched with the transaction
     *
     */
    public function setOrder(Orderable $order){
        $this->order=$order;
    }

    /**
     * Set the processing payment provider during the mismatch
     *
     */
    public function setPaymentProvider(PaymentProvider $paymentProvider){
        $this->paymentProvider=$paymentProvider;
    }

    /**
     * Set the payment response relating to the mismatch
     *
     */
    public function setPaymentResponse(?PaymentResponse $paymentResponse){
        $this->paymentResponse=$paymentResponse;
    }


    /**
     * Get transaction that mismatched with order
     */
    public function getTransaction():Transaction{
        return $this->transaction;
    }

    /**
     * Get the orderable that mismatched with the transaction
     *
     */
    public function getOrder():Orderable{
        return $this->order;
    }

    /**
     * Get the processing payment provider during the mismatch
     *
     */
    public function getPaymentProvider():PaymentProvider{
        return $this->paymentProvider;
    }

        /**
     * Get the payment response relating to the mismatch
     *
     */
    public function getPaymentResponse():?PaymentResponse{
        return $this->paymentResponse;
    }


        /**
     * Returns the string representation of the exception.
     *
     * @return string
     */
    public function __toString()
    {
        $payment_response_errors=$this->paymentResponse ? implode('. ',$this->paymentResponse->errors): null;

        return "Transaction: {$this->transaction->id},
        Transaction livemode, {intval($this->transaction->livemode)},
        Transaction payment provider, {intval($this->transaction->payment_provider)},
        Order: {$this->orderable->getKey()},
        Order livemode: {intval($this->order->livemode)}, 
        processor payment provider: '{$this->paymentProvider->getProvider()}, 
        processing payment provider live mode: {intval($this->paymentProvider->isLivemode())},
        payment response errors: {$payment_response_errors},  
        Message: {$this->getMessage()}";
    }
}