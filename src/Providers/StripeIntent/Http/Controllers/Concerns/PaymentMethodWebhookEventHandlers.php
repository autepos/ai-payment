<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns;

use Stripe\StripeObject;
use Stripe\PaymentMethod;
use Illuminate\Support\Facades\Log;


trait PaymentMethodWebhookEventHandlers
{
    /**
     * Handle payment_method.updated
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentMethodUpdated(StripeObject $event)
    {

        $paymentMethod = $event->data->object;

        if ($paymentMethod instanceof PaymentMethod) {
            $result = $this->paymentProvider->paymentMethodForWebhook()
                ->webhookUpdatedOrAttached($paymentMethod);

            if ($result) {
                return $this->successMethod();
            }
        }
        Log::error('Stripe webhook - received : ' . __METHOD__ . ' - But it could not be handled correctly:', ['webhook_event' => $event]);

        return response('There was an issue with processing the webhook', 422);
    }
    /**
     * Handle payment_method.automatically_updated
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentMethodAutomaticallyUpdated(StripeObject $event)
    {
        return $this->handlePaymentMethodUpdated($event);
    }

    /**
     * Handle payment_method.attached
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentMethodAttached(StripeObject $event)
    {
        return $this->handlePaymentMethodUpdated($event);
    }

    /**
     * Handle payment_method.detached
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentMethodDetached(StripeObject $event)
    {

        $paymentMethod = $event->data->object;

        if ($paymentMethod instanceof PaymentMethod) {
            $result = $this->paymentProvider->paymentMethodForWebhook()
                ->webhookDetached($paymentMethod);

            if ($result) {
                return $this->successMethod();
            }
        }

        Log::error('Stripe webhook - received : ' . __METHOD__ . ' - But it could not be handled correctly:', ['webhook_event' => $event]);
        return response('There was an issue with processing the webhook', 422);
    }
}
