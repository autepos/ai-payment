<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\Events\StripeIntentWebhookHandled;
use Autepos\AiPayment\Providers\StripeIntent\Events\StripeIntentWebhookReceived;
use Autepos\AiPayment\Providers\StripeIntent\Http\MiddleWare\StripeIntentVerifyWebhookSignature;
use Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns\CustomerWebhookEventHandlers;
use Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns\PaymentMethodWebhookEventHandlers;
use Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns\PaymentProviderWebhookEventHandlers;

class StripeIntentWebhookController extends Controller
{
    use  PaymentProviderWebhookEventHandlers;
    use  PaymentMethodWebhookEventHandlers;
    use  CustomerWebhookEventHandlers;

    /**
     * @var \Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider
     */
    private $paymentProvider;

    public function __construct(PaymentProviderFactory $paymentManager)
    {

        /**
         * @var \Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider
         */
        $this->paymentProvider = $paymentManager->driver(StripeIntentPaymentProvider::PROVIDER);

        $config = $this->paymentProvider->getConfig();

        if ($config['webhook_secret']) {
            $this->middleware(StripeIntentVerifyWebhookSignature::class);
        }
    }


    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {

        $payload = $request->getContent();
        $data = \json_decode($payload, true);
        $jsonError = \json_last_error();
        if (null === $data && \JSON_ERROR_NONE !== $jsonError) {

            Log::error('Error decoding Stripe webhook', ['payload' => $payload, 'json_last_error' => $jsonError]);
            return new Response('Invalid input', 400);
        }

        $event = \Stripe\Event::constructFrom($data);


        $method = 'handle' . Str::studly(str_replace('.', '_', $event->type));

        StripeIntentWebhookReceived::dispatch($data);

        if (method_exists($this, $method)) {
            $response = $this->{$method}($event);

            StripeIntentWebhookHandled::dispatch($data);

            return $response;
        }

        return $this->missingMethod($data);
    }


    /**
     * Handle successful calls on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return response('Unknown webhook - it may not have been set up', 404);
    }
}
