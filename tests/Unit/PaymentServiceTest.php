<?php

namespace Autepos\AiPayment\Tests\Unit;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;

class PaymentServiceTest extends TestCase
{


    public function test_can_obtain_provider_instance()
    {
        //
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('config')->with(Mockery::type('array'), Mockery::type('bool'))
            ->andReturnSelf();

        //
        $mockPaymentManager = Mockery::mock(PaymentProviderFactory::class);
        $mockPaymentManager->shouldReceive('driver')->with('provider_yi')
            ->andReturn($mockAbstractPaymentProvider);


        //
        $paymentService = new PaymentService($mockPaymentManager);
        $providerInstance = $paymentService->provider('provider_yi')->providerInstance();
        $this->assertInstanceOf(get_class($mockAbstractPaymentProvider), $providerInstance);
    }

    public function test_can_up()
    {
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('save'), true);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->up')
            ->andReturn($SimpleResponse);

        // Run up
        $response = $partialMockPaymentService->provider($provider)
            ->up();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }

    public function test_can_down()
    {
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('save'), true);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->down')
            ->andReturn($SimpleResponse);

        // Run up
        $response = $partialMockPaymentService->provider($provider)
            ->down();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }

    public function test_can_ping()
    {
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('ping'), true);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->ping')
            ->andReturn($SimpleResponse);

        // Ping;
        $response = $partialMockPaymentService->provider($provider)
            ->ping();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }


    public function test_can_sync_transaction()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);

        $mockAbstractPaymentProvider->shouldReceive('syncTransaction')
            ->once()
            ->with($transaction);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);

        // 
        $response = $partialMockPaymentService->provider($provider)
            ->syncTransaction($transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_cannot_sync_transaction_if_provider_transaction_is_not_authorised()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(true);

        $mockAbstractPaymentProvider->shouldNotReceive('syncTransaction');

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);

        // 
        $response = $partialMockPaymentService->provider($provider)
            ->syncTransaction($transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
       
    
        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error',$response->message);
        $this->assertSame(['Unauthorised payment transaction with provider'],$response->errors);
    }
    public function test_cannot_sync_transaction_if_provider_transaction_is_not_authorised_because_of_livemode_mismatch()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(false);

        $mockAbstractPaymentProvider->shouldNotReceive('syncTransaction');

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);

        // 
        $response = $partialMockPaymentService->provider($provider)
            ->syncTransaction($transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
       
    
        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error',$response->message);
        $this->assertSame(['Livemode mismatch'],$response->errors);
    }

    public function test_can_obtain_customer_implementation()
    {

        $provider = 'provider_yi';

        $mockProviderCustomer = Mockery::mock(ProviderCustomer::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('customer')
            ->once()
            ->withNoArgs()
            ->andReturn($mockProviderCustomer);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);

        // 
        $response = $partialMockPaymentService->provider($provider)
            ->customer();

        //
        $this->assertInstanceOf(ProviderCustomer::class, $response);
    }


    public function test_can_obtain_payment_method_implementation()
    {

        $provider = 'provider_yi';

        $customerData = new CustomerData(['user_type' => 'test-type', 'user_id' => 'test-id']);
        $mockProviderPaymentMethod = Mockery::mock(ProviderPaymentMethod::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('paymentMethod')
            ->once()
            ->with($customerData)
            ->andReturn($mockProviderPaymentMethod);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);

        // 
        $response = $partialMockPaymentService->provider($provider)
            ->paymentMethod($customerData);

        //
        $this->assertInstanceOf(ProviderPaymentMethod::class, $response);
    }
}
