<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns;

use Stripe\StripeObject;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

trait PaymentProviderWebhookEventHandlers
{
    /**
     * Handle payment_intent.succeeded
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentIntentSucceeded(StripeObject $event)
    {

        $paymentIntent = $event->data->object;

        if ($paymentIntent instanceof PaymentIntent) {

            $paymentResponse = $this->paymentProvider
                ->webhookChargeByRetrieval($paymentIntent);

            if ($paymentResponse->success) {
                return $this->successMethod();
            } else {
                Log::error('Stripe webhook - received : payment_intent.succeeded - But could successfully not record the transaction :', ['paymentIntent' => $paymentIntent, 'paymentResponse' => $paymentResponse]);
            }
        } else {
            Log::error('Stripe webhook - received : payment_intent.succeeded - But payment intent could not be extracted:', ['webhook_event' => $event]);
        }

        return response('There was an issue with processing the webhook', 422);
    }
}
