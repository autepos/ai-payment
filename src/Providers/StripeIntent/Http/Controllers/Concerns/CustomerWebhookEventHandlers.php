<?php

namespace Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\Concerns;

use Stripe\Customer;
use Stripe\StripeObject;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\Tenancy\Tenant;
use Autepos\AiPayment\Models\PaymentProviderCustomer;


trait CustomerWebhookEventHandlers
{

    /**
     * Get the tenant id for the given Stripe customer
     *
     * @return string
     */
    protected function stripeCustomerToTenantId(Customer $customer){
        return $customer->metadata->tenant_id;

        // // If Stripe $customer->id is not universally unique then it is possible for us to 
        // // have more than one tenant with different customers that happen to have the 
        // // same $customer->id. This means that we cannot uniquely identify the correct 
        // // tenant. In this super SUPER extremely RARE case we will halt and log the error.
        // $tenant_column_name=Tenant::getColumnName();
        // $paymentProviderCustomer=PaymentProviderCustomer::query()
        // ->where('payment_provider_customer_id',$customer->id)
        // ->where('payment_provider',$this->paymentProvider->getProvider())
        // ->where('user_type',$customer->metadata->user_type)
        // ->where('user_id',$customer->metadata->user_id)
        // ->get();

        // if($paymentProviderCustomer->count()>1){
        //     $msg='A rare error while: ' . __METHOD__ . '- It was not possible to identify 
        //     tenant for stripe webhook because more 
        //     than one customer have the same Stripe customer id. Stripe Customer id may not be universally unique after all';
        //     Log::critical($msg,[
        //         'paymentProviderCustomer'=>$paymentProviderCustomer
        //     ]);
        //     $paymentProviderCustomer=collect();
        // }elseif($paymentProviderCustomer->count()==0){
        //     $msg='A strange error in: ' . __METHOD__ . '- It was not possible to identify because there was no matching PaymentProviderCustomer model';
        //     Log::alert($msg,[
        //         'customer'=>$customer
        //     ]);
        // }

        // //
        // $paymentProviderCustomer=$paymentProviderCustomer->first();

        // return $paymentProviderCustomer
        //             ? $paymentProviderCustomer->{$tenant_column_name}
        //             :null;
    }

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
            //
            $tenant_id=$this->stripeCustomerToTenantId($customer);
            //if($tenant_id){
                $this->prepareToHandleRequest($tenant_id);
                //
                $result = $this->paymentProvider->customer()
                    ->webhookDeleted($customer);

                if ($result) {
                    return $this->successMethod();
                }
            //}
        }

        Log::error('Stripe webhook - received : ' . __METHOD__ . ' - But it could not be handled correctly:', ['webhook_event' => $event]);
        return response('There was an issue with processing the webhook', 422);
    }
}
