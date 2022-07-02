<?php

namespace Autepos\AiPayment\Providers;

use Autepos\AiPayment\PaymentService;

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
    const VERSION = PaymentService::VERSION;
}
