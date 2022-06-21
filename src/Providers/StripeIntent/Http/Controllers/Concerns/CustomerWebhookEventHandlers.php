<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns;

use Stripe\StripeObject;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;


trait CustomerWebhookEventHandlers
{


    /**
     * Handle customer.deleted
     *
     * @param StripeObject $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(StripeObject $event)
    {

        $customer = $event->data->object;

        if ($customer instanceof Customer) {
            $result = $this->paymentProvider->customer()
                ->webhookDeleted($customer);

            if ($result) {
                return $this->successMethod();
            }
        }

        Log::error('Stripe webhook - received : ' . __METHOD__ . ' - But it could not be handled correctly:', ['webhook_event' => $event]);
        return response('There was an issue with processing the webhook', 422);
    }
}
