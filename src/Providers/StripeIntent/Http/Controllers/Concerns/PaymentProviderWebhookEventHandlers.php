<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns;

use Stripe\StripeObject;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\Tenancy\Tenant;
use Autepos\AiPayment\Models\Transaction;

trait PaymentProviderWebhookEventHandlers
{
    /**
     * Use a stripe payment intent to get tenant id
     *
     * @return int|string|null
     */
    protected function stripePaymentIntentToTenantId(PaymentIntent $paymentIntent){
        return $paymentIntent->metadata->tenant_id;
        // $tenant_column_name=Tenant::getColumnName();
        // $transaction=Transaction::select([$tenant_column_name])
        // ->find($paymentIntent->metadata->transaction_id);
        
        // if(!$transaction){
        //     $msg='A strange error in: ' . __METHOD__ . '- It was not possible to identify because there was no matching Transaction model';
        //     Log::alert($msg,[
        //         'paymentIntent'=>$paymentIntent
        //     ]);
        // }

        
        // //
        // return $transaction
        //         ? $transaction->{$tenant_column_name}
        //         : null;
    }
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

            $tenant_id=$this->stripePaymentIntentToTenantId($paymentIntent);
            //if($tenant_id){
                $this->prepareToHandleRequest($tenant_id);

                //
                $paymentResponse = $this->paymentProvider
                    ->webhookChargeByRetrieval($paymentIntent);

                if ($paymentResponse->success) {
                    return $this->successMethod();
                } else {
                    Log::error('Stripe webhook - received : payment_intent.succeeded - But could successfully not record the transaction :', ['paymentIntent' => $paymentIntent, 'paymentResponse' => $paymentResponse]);
                }
            //}
        } else {
            Log::error('Stripe webhook - received : payment_intent.succeeded - But payment intent could not be extracted:', ['webhook_event' => $event]);
        }

        return response('There was an issue with processing the webhook', 422);
    }
}
