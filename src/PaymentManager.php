<?php

namespace Autepos\AiPayment;

use InvalidArgumentException;
use Illuminate\Support\Manager;

class PaymentManager extends Manager implements Contracts\PaymentProviderFactory
{
    /**
     * Get the default driver/provider name.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No payment provider was specified.');
    }

    /**
     * Get a driver instance.
     *
     * @param  string  $driver
     * @return \Autepos\AiPaymentProvider\Contracts\PaymentProvider
     */
    public function with(string $driver)
    {
        return $this->driver($driver);
    }
}
