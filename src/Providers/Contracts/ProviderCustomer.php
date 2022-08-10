<?php

namespace Autepos\AiPayment\Providers\Contracts;

use Autepos\AiPayment\CustomerResponse;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Models\PaymentProviderCustomer;


abstract class ProviderCustomer
{

    /**
     *
     * @var PaymentProvider
     */
    protected $provider;


    /**
     * Set the provider.
     */
    public function provider(PaymentProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Check if there is a matching record already created for the given customer data.
     */
    public function has(CustomerData $customerData): bool
    {
        return !!$this->get($customerData);
    }

    /**
     * Get the payment provider customer for the given customer data.
     */
    public function get(CustomerData $customerData): ?PaymentProviderCustomer
    {
        return PaymentProviderCustomer::fromCustomerData($customerData, $this->provider->getProvider());
    }


    /**
     * Create the specified customer
     */
    public abstract function create(CustomerData $customerData): CustomerResponse;


    /**
     * Delete the customer specified
     *
     */
    public abstract function delete(PaymentProviderCustomer $paymentProviderCustomer): CustomerResponse;

    /**
     * Check if the customer given is guest
     * @todo No phpunit test //todo
     */
    public static function isGuest(CustomerData $customerData): bool
    {
        return !($customerData->user_type and  $customerData->user_id);
    }
}
