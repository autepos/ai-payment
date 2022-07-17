<?php

namespace Autepos\AiPayment\Providers\Contracts;

use Autepos\AiPayment\PaymentMethodResponse;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Models\PaymentProviderCustomerPaymentMethod;



abstract class ProviderPaymentMethod
{

    /**
     *
     * @var PaymentProvider
     */
    protected $provider;


    /**
     * The customer data
     *
     * @var CustomerData
     */
    protected $customerData;

    /**
     * Set the provider.
     */
    public function provider(PaymentProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }


    /**
     * Set the customer data
     */
    public function customerData(CustomerData $customerData): self
    {
        $this->customerData = $customerData;

        return $this;
    }

    public abstract function init(array $data): PaymentMethodResponse;

    /**
     * Go to provider to save a payment method for the underlying customer. Also creates a 
     * local record for the payment method.
     */
    public abstract function save(array $data): PaymentMethodResponse;


    /**
     * Go to provider to remove payment method specified. Also remove a 
     * local record for the payment method.
     *
     * @todo this should be named delete() to match PaymentProviderCustomer::delete().
     */
    public abstract function remove(PaymentProviderCustomerPaymentMethod $paymentMethod): PaymentMethodResponse;


    /**
     * Synchronise all local payment method data with the provider
     *
     */
    public function syncAll(array $data = []): bool
    {
        return false;
    }
}
