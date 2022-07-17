<?php

namespace Autepos\AiPayment\Exceptions;

use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;


class LivemodeMismatchException extends \Exception implements ExceptionInterface
{

    /**
     * The transaction relating the mismatched
     *
     * @var \Autepos\AiPayment\Models\Transaction
     */
    protected $transaction = null;

    /**
     * The orderable that relates to the mismatched 
     *
     * @var \Autepos\AiPayment\Providers\Contracts\Orderable
     */
    protected $order = null;

    /**
     * The processing provider during the mismatch
     *
     * @var \Autepos\AiPayment\Providers\Contracts\PaymentProvider
     */
    protected $paymentProvider = null;

    /**
     * The Payment response relating to the mismatch
     *
     * @var \Autepos\AiPayment\PaymentResponse
     */
    protected $paymentResponse = null;


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
    public function setTransaction(?Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Set the orderable that mismatched with the transaction
     *
     */
    public function setOrder(?Orderable $order)
    {
        $this->order = $order;
    }

    /**
     * Set the processing payment provider during the mismatch
     *
     */
    public function setPaymentProvider(?PaymentProvider $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;
    }

    /**
     * Set the payment response relating to the mismatch
     *
     */
    public function setPaymentResponse(?PaymentResponse $paymentResponse)
    {
        $this->paymentResponse = $paymentResponse;
    }

    /**
     * Get transaction that mismatched with order
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Get the orderable that mismatched with the transaction
     *
     */
    public function getOrder(): ?Orderable
    {
        return $this->order;
    }

    /**
     * Get the processing payment provider during the mismatch
     *
     */
    public function getPaymentProvider(): ?PaymentProvider
    {
        return $this->paymentProvider;
    }


    /**
     * Get the payment response relating to the mismatch
     *
     */
    public function getPaymentResponse(): ?PaymentResponse
    {
        return $this->paymentResponse;
    }

    /**
     * Returns the string representation of the exception.
     *
     * @return string
     */
    public function __toString()
    {
        $transaction_id = $this->transaction ? $this->transaction->id : null;
        $transaction_livemode = $this->transaction ? intval($this->transaction->livemode) : '-';
        $transaction_payment_provider = $this->transaction ? $this->transaction->payment_provider :  null;

        $order_key = $this->order ? $this->order->getKey() : '';
        $order_livemode = $this->order ? intval($this->order->livemode) : '-';
        
        $processing_payment_provider = $this->paymentProvider ? $this->paymentProvider->getProvider() : null;
        $processing_payment_provider_livemode = $this->paymentProvider ? intval($this->paymentProvider->isLivemode()) : '-';

        $payment_response_errors = $this->paymentResponse ? implode('. ', $this->paymentResponse->errors) : null;

        $parent_str= parent::__toString();

        return "Transaction: {$transaction_id},
        Transaction livemode, {$transaction_livemode},
        Transaction payment provider, {$transaction_payment_provider},
        Order: {$order_key},
        Order livemode: {$order_livemode}, 
        processing payment provider: '{$processing_payment_provider}, 
        processing payment provider live mode: {$processing_payment_provider_livemode}, 
        payment response errors: {$payment_response_errors}, 
        Message: {$this->getMessage()}
        ------------
        {$parent_str}
        ";

    }
}
