<?php

namespace Autepos\AiPayment\Providers;

use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class PayLaterPaymentProvider extends PaymentProvider
{

    /**
     * The Provider tag
     * @var string
     */
    public const PROVIDER = 'pay_later';
    /**
     * The provider library version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    public function up(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('save'), true);
    }

    public function down(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('save'), true);
    }

    public function ping(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('ping'), true);
    }

    public function init(int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {

        return new PaymentResponse(PaymentResponse::newType('init'), true);
    }


    public function charge(Transaction $transaction = null, array $data = []): PaymentResponse
    {
        return new PaymentResponse(PaymentResponse::newType('charge'), true, 'Pay later');
    }

    public function cashierCharge(Authenticatable $cashier, Transaction $transaction = null, array $data = []): PaymentResponse
    {
        return new PaymentResponse(PaymentResponse::newType('charge'), true, 'Pay later');
    }


    public function refund(Authenticatable $cashier, Transaction $transaction = null, int $amount, string $description): PaymentResponse
    {
        return new PaymentResponse(PaymentResponse::newType('refund'), true);
    }


    public function getProvider(): string
    {
        return self::PROVIDER;
    }
}
