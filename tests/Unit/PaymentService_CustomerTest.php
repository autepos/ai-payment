<?php

namespace Autepos\AiPayment\Tests\Unit;


use Mockery;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use \Autepos\AiPayment\Exceptions\LivemodeMismatchException;
use \Autepos\AiPayment\Exceptions\TransactionPaymentProviderMismatchException;

class PaymentService_CustomerTest extends TestCase
{

    public function test_can_customer_init_payment()
    {
        $provider = 'provider_yi';
        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        // Order
        $mockOrder = Mockery::mock(Orderable::class);
        
        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('order')
        ->with($mockOrder)
        ->once()
        ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('init')
                                            ->once()
                                            ->with(null,null,null)
                                            ->andReturn($paymentResponse);
        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Init an order payment;
        $response = $partialMockPaymentService
        ->provider($provider)
        ->order($mockOrder)
        ->init(null);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_can_customer_init_payment_with_optional_arguments(){
        $provider = 'provider_yi';
        $amount=1000;
        $data=['provider_data1'=>'09032022'];
        $transaction=Transaction::factory()->create(['orderable_id'=>1]);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        // Order
        $mockOrder = Mockery::mock(Orderable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('order')
        ->with($mockOrder)
        ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('init')
                                            ->once()
                                            ->with($amount,$data,$transaction)
                                            ->andReturn($paymentResponse);

        $mockAbstractPaymentProvider->shouldReceive('isTransactionUsableFor')
                                            ->once()
                                            ->andReturn(true);

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Init an order payment;
        $response = $partialMockPaymentService->provider($provider)
            ->order($mockOrder)
            ->init($amount,$data,$transaction);
 
    }
    
    public function test_sets_unusable_suggested_transaction_to_null_when_customer_init_payment()
    {
        $provider = 'provider_yi';
        $amount=1000;
        $transaction=Transaction::factory()->create(['orderable_id'=>1]);

        
        // Order
        $mockOrder = Mockery::mock(Orderable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('isTransactionUsableFor')
        ->once()
        ->andReturn(false);


        $mockAbstractPaymentProvider->shouldReceive('order')
        ->andReturnSelf();
        $mockAbstractPaymentProvider->shouldReceive('init')
        ->once()
        ->with($amount,[],null);// the main assertion that the last param=>transaction is null


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Init an order payment
        $response = $partialMockPaymentService->provider($provider)
            ->order($mockOrder)
            ->init($amount,[],$transaction);
    }

    
    public function test_can_customer_charge_payment()
    {
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        
        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
        ->once()
        ->andReturn(true); 
        $mockAbstractPaymentProvider->shouldReceive('charge')
                                            ->once()
                                            ->with($transaction,[])
                                            ->andReturn($paymentResponse);
                                           
        
        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
        ->provider($provider)
        ->charge($transaction);
        $this->assertInstanceOf(PaymentResponse::class, $response);

    }
    
    public function  test_can_customer_charge_payment_with_optional_arguments(){
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);
        $data=['provider_data1'=>'09032022'];

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        
        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
        ->once()
        ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('charge')
                                            ->once()
                                            ->with($transaction,$data)
                                            ->andReturn($paymentResponse);
        

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
        ->provider($provider)
        ->charge($transaction,$data);

        $this->assertInstanceOf(PaymentResponse::class, $response);


    }
    
    
    public function test_if_order_is_given_then_it_is_passed_to_provider_during_customer_charge()
    {
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

       
        // Order
        $mockOrder = Mockery::mock(Orderable::class);
        
        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
        ->once()
        ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('order')
        ->with($mockOrder)
        ->once()
        ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('charge')
                                            ->once();

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Make a charge with order
        $response = $partialMockPaymentService
        ->provider($provider)
        ->order($mockOrder)
        ->charge($transaction);

    }

    public function test_customer_cannot_charge_if_provider_transaction_is_not_authorised(){

        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);
        

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
        ->once()
        ->with($transaction)
        ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
        ->andReturn(true);
        $mockAbstractPaymentProvider->shouldNotReceive('charge');


        //
        $this->expectException(TransactionPaymentProviderMismatchException::class);
        

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Make a charge 
        $response=null;
        try{
            $partialMockPaymentService
            ->provider($provider)
            ->charge($transaction);
        }catch(TransactionPaymentProviderMismatchException $ex){
            $response=$ex->getPaymentResponse();
            throw $ex;// rethrow the exception as we are expecting it; if we do not want to rethrow it then we can remove its expectation above.
        }



        $this->assertInstanceOf(PaymentResponse::class, $response);
       
    
        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error',$response->message);
        $this->assertSame(['Unauthorised payment transaction with provider'],$response->errors);
    }
    public function test_customer_cannot_charge_if_provider_transaction_is_not_authorised_because_of_livemode_mismatch(){
        
        
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);
        

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
        ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
        ->once()
        ->with($transaction)
        ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
        ->andReturn(false);
        $mockAbstractPaymentProvider->shouldNotReceive('charge');

        //
        $this->expectException(LivemodeMismatchException::class);
        

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
        ->andReturn($mockAbstractPaymentProvider);


        // Make a charge
        $response=null;
        try{
            $partialMockPaymentService
            ->provider($provider)
            ->charge($transaction);
        }catch(LivemodeMismatchException $ex){
            $response=$ex->getPaymentResponse();
            throw $ex;// rethrow the exception as we are expecting it; if we do not want to rethrow it then we can remove its expectation above.
        }



        $this->assertInstanceOf(PaymentResponse::class, $response);
       
    
        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error',$response->message);
        $this->assertSame(['Livemode mismatch'],$response->errors);
    }

}
