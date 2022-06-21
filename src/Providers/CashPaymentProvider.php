<?php

namespace Autepos\AiPayment\Providers;

class CashPaymentProvider extends OfflinePaymentProvider
{
    /**
     * The Provider tag
     * @var string
     */
    public const PROVIDER = 'cash';
    /**
     * The provider library version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    
}
