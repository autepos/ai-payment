<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\MiddleWare;

use Closure;
use Stripe\Webhook;
use Stripe\WebhookSignature;
use Stripe\Exception\SignatureVerificationException;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripeIntentVerifyWebhookSignature
{
    /**
     *
     * @var StripeIntentPaymentProvider $paymentProvider
     */
    private $paymentProvider;

    public function __construct(PaymentProviderFactory $paymentManager)
    {
        /**
         * @var \Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider
         */
        $this->paymentProvider = $paymentManager->driver(StripeIntentPaymentProvider::PROVIDER);
    }
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle($request, Closure $next)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        //
        $config = $this->paymentProvider->getConfig();

        //Stripe::setApiKey($config['secret_key']);// TODO Although this is found on stripe docs, it is not clear why we need to set api key here. The tests are passing with this commented out.
        $endpoint_secret = $config['webhook_secret'];
        $tolerance = $config['webhook_tolerance'] ?? Webhook::DEFAULT_TOLERANCE;

        try {
            WebhookSignature::verifyHeader(
                $payload,
                $sig_header,
                $endpoint_secret,
                $tolerance
            );
        } catch (SignatureVerificationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        return $next($request);
    }
}
