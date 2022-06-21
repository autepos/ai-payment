<?php

namespace Autepos\AiPayment\Tests\Feature;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class PaymentManagerTest extends TestCase
{

    public function test_can_instantiate_payment_a_provider(){

        $provider='provider_yi';

        //
        $paymentManager=app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        

        // Register the payment provider to use the mocked instance
        $paymentManager->extend($provider,function($app)use($mockAbstractPaymentProvider){
            return $mockAbstractPaymentProvider;
        });

        $paymentProvider=$paymentManager->driver($provider);
       
        //
        $this->assertInstanceOf(PaymentProvider::class,$paymentProvider);

    }

    
    
}
