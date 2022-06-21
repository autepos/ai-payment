<?php
namespace Autepos\AiPayment\Contracts;



interface PaymentProviderFactory
{
    /**
     * Get a payment provider implementation.
     *
     * @param  string  $driver Note that this is the same as $provider
     * @return \Autepos\AiPayment\Providers\Contracts\PaymentProvider
     */
    public function driver($driver = null);
}