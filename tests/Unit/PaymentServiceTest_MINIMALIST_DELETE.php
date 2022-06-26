<?php

namespace Autepos\AiPayment\Tests\Unit;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\PaymentService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;


class PaymentServiceTest_MINIMALIST_DELETE extends TestCase
{


    public function test_can_obtain_provider_instance()
    {

        //
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        //
        $mockPaymentManager = Mockery::mock(PaymentProviderFactory::class);
        $mockPaymentManager->shouldReceive('driver')->with('provider_yi')->andReturn($mockAbstractPaymentProvider);


        //
        $paymentService = new PaymentService($mockPaymentManager);
        $providerInstance = $paymentService->provider('provider_yi');
        $this->assertInstanceOf(get_class($mockAbstractPaymentProvider), $providerInstance);
    }

    public function test_can_forward_static_calls_to_payment_provider(){
        // We test this by just making example static calls
        PaymentService::tenant('eg_tenant_id');// This static call should be forwarded
        $tenant_id=PaymentService::getTenant();// This static call should be forwarded
        $this->assertEquals('eg_tenant_id',$tenant_id);

        
    }
}
