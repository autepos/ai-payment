<?php

namespace Autepos\AiPayment\Providers\Contracts\Concerns;

use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

trait Configuration
{


    /**
     * The configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * The livemode
     *
     * @var bool
     */
    protected $livemode = false;


    /**
     * Set configurations
     */
    public function config(array $config, bool $livemode = null): PaymentProvider
    {
        $this->config = $config;

        if (!is_null($livemode)) {
            $this->livemode($livemode);
        }

        return $this;
    }

    /**
     * Set configuration including livemode using a user function.
     *
     */
    public function configUsingFcn(): PaymentProvider{
        [$config,$livemode]=PaymentService::getConfigUsingFcn($this->getProvider(),static::getTenant());
        return $this->config($config,$livemode);
    }

    /**
     * Get the configurations that may not be changed at runtime i.e through 
     * the self::config() method.
     */
    public function getStaticConfig(): array
    {
        return [];
    }

    /**
     * Set the livemode
     */
    public function livemode(bool $livemode): PaymentProvider
    {
        $this->livemode = $livemode;

        return $this;
    }

    /**
     * Check if livemode is true
     *
     * @return boolean
     */
    public function isLivemode()
    {
        return $this->livemode;
    }
}
