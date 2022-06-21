<?php

namespace Autepos\AiPayment\Tests\Feature;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;

class PaymentServiceTest_DELETE extends TestCase
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
        $providerInstance = $paymentService->provider('provider_yi')->providerInstance();
        $this->assertInstanceOf(get_class($mockAbstractPaymentProvider), $providerInstance);
    }

    public function test_can_up(){
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('save'), true);

          //
          $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
          $partialMockPaymentService->shouldReceive('providerInstance->config->up')
            ->andReturn($SimpleResponse);

        // Run up
        $response = $partialMockPaymentService->provider($provider)
            ->up();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }

    public function test_can_down(){
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('save'), true);

          //
          $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
          $partialMockPaymentService->shouldReceive('providerInstance->config->down')
            ->andReturn($SimpleResponse);

        // Run up
        $response = $partialMockPaymentService->provider($provider)
            ->down();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }

    public function test_can_ping(){
        $provider = 'provider_yi';

        $SimpleResponse = new SimpleResponse(SimpleResponse::newType('ping'), true);

          //
          $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
          $partialMockPaymentService->shouldReceive('providerInstance->config->ping')
            ->andReturn($SimpleResponse);

        // Ping;
        $response = $partialMockPaymentService->provider($provider)
            ->ping();

        //
        $this->assertInstanceOf(SimpleResponse::class, $response);
    }

    public function test_can_customer_init_payment()
    {

        $provider = 'provider_yi';

        $mockOrder = $this->mock(Orderable::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);


        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config->init')
            ->andReturn($paymentResponse);


        // Init an order payment;
        $response = $partialMockPaymentService->provider($provider)
            ->init($mockOrder, null);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_can_customer_init_payment_with_optional_arguments(){
        $provider = 'provider_yi';
        $amount=1000;
        $data=['provider_data1'=>'09032022'];
        $transaction=Transaction::factory()->create(['orderable_id'=>1]);

        $mockOrder = $this->mock(Orderable::class);

        
        $partialMockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $partialMockAbstractPaymentProvider->shouldReceive('init')
                                            ->once()
                                            ->withArgs(function($arg1,$arg2,$arg3)use($amount,$data,$transaction){
                                                 //Checking the optional arguments
                                                return (($arg1==$amount) and ($arg2==$data) and ($arg3==$transaction)); 
                                            });

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config')
            ->andReturn($partialMockAbstractPaymentProvider);


        // Init an order payment;
        $response = $partialMockPaymentService->provider($provider)
            ->init($mockOrder, $amount,$data,$transaction);

        
    }
    public function test_can_cashier_init_payment()
    {

        $provider = 'provider_yi';

        $mockOrder = $this->mock(Orderable::class);

        $mockAuthenticatableContract = $this->mock(Authenticatable::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);


        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config->cashierInit')
            ->andReturn($paymentResponse);


        // Init an order payment;
        $response = $partialMockPaymentService->provider($provider)
            ->cashierInit($mockAuthenticatableContract, $mockOrder, null);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_can_cashier_init_payment_with_optional_arguments(){
        $provider = 'provider_yi';

        $amount=1000;
        $data=['provider_data1'=>'09032022'];
        $transaction=Transaction::factory()->create(['orderable_id'=>1]);

        $mockOrder = $this->mock(Orderable::class);

        $mockAuthenticatableContract = $this->mock(Authenticatable::class);

        
        $partialMockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $partialMockAbstractPaymentProvider->shouldReceive('cashierInit')
                                            ->once()
                                            ->withArgs(function($arg1,$arg2,$arg3,$arg4)use($amount,$data,$transaction){
                                                 //Checking the optional arguments
                                                return (($arg2==$amount) and ($arg3==$data) and ($arg4==$transaction)); 
                                            });

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config')
            ->andReturn($partialMockAbstractPaymentProvider);


        // Init an order payment;
        $response = $partialMockPaymentService->provider($provider)
            ->cashierInit($mockAuthenticatableContract, $mockOrder, $amount,$data,$transaction);
    }

    public function test_can_customer_charge_payment()
    {
        $provider = 'provider_yi';

        $mockOrder = $this->mock(Orderable::class);

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config->charge')
            ->andReturn($paymentResponse);

        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->charge($transaction, $mockOrder);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function  test_can_customer_charge_payment_with_optional_arguments(){
        $provider = 'provider_yi';
        $data=['provider_data1'=>'09032022'];

        $mockOrder = $this->mock(Orderable::class);

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);


        
        $partialMockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $partialMockAbstractPaymentProvider->shouldReceive('charge')
                                            ->once()
                                            ->withArgs(function($arg1,$arg2)use($data){
                                                return ($arg2==$data);  //Checking the optional argument
                                            });

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config')
            ->andReturn($partialMockAbstractPaymentProvider);

        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->charge($transaction, $mockOrder,$data);

        //

    }

    public function test_can_cashier_charge_payment()
    {
        $provider = 'provider_yi';


        $mockOrder = $this->mock(Orderable::class);

        $mockAuthenticatableContract = $this->mock(Authenticatable::class);

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);



        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);


        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config->cashierCharge')
            ->andReturn($paymentResponse);


        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction, $mockOrder);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_can_cashier_charge_payment_with_optional_arguments(){
        $provider = 'provider_yi';
        $data=['provider_data1'=>'09032022'];


        $mockOrder = $this->mock(Orderable::class);

        $mockAuthenticatableContract = $this->mock(Authenticatable::class);

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        
        $partialMockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $partialMockAbstractPaymentProvider->shouldReceive('cashierCharge')
                                            ->once()
                                            ->withArgs(function($arg1,$arg2,$arg3)use($data){
                                                return ($arg3==$data); //Checking the optional argument
                                            });

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->order->config')
            ->andReturn($partialMockAbstractPaymentProvider);


        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction, $mockOrder,$data);
    }

    public function test_can_cashier_refund_payment()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('refund'), true);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('validateRefund')
        ->andReturn(true);
        $partialMockPaymentService->shouldReceive('providerInstance->config->refund')
        ->once()
            ->andReturn($paymentResponse);

        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_cashier_cannot_make_invalid_refund_payment()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('refund'), true);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('validateRefund')
        ->once()
        ->andReturn(false);
        $partialMockPaymentService->shouldNotReceive('providerInstance->refund');
            

        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertFalse($response->success);
    }

    public function test_can_sync_transaction()
    {
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('retrieve'), true);

        //
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance->config->syncTransaction')
            ->andReturn($paymentResponse);

        // Charge an order;
        $response = $partialMockPaymentService->provider($provider)
            ->syncTransaction($transaction);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_can_obtain_customer(){
        $provider = 'provider_yi';

        $mockProviderCustomer=Mockery::mock(ProviderCustomer::class);

        //
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('customer')
        ->andReturn($mockProviderCustomer);

        //
        $mockPaymentManager = Mockery::mock(PaymentProviderFactory::class);
        $mockPaymentManager->shouldReceive('driver')->andReturn($mockAbstractPaymentProvider);


        //
        $paymentService = new PaymentService($mockPaymentManager);
        $customer = $paymentService->provider($provider)
        ->customer(new CustomerData(['user_type'=>'test-type','user_id'=>'test-id']));



        $this->assertInstanceOf(ProviderCustomer::class, $customer);
    }

    public function test_can_obtain_payment_method(){
        $provider = 'provider_yi';

        $mockProviderPaymentMethod=Mockery::mock(ProviderPaymentMethod::class);

        //
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('paymentMethod')
        ->with(Mockery::type(CustomerData::class))
        ->andReturn($mockProviderPaymentMethod);

        //
        $mockPaymentManager = Mockery::mock(PaymentProviderFactory::class);
        $mockPaymentManager->shouldReceive('driver')->andReturn($mockAbstractPaymentProvider);


        //
        $paymentService = new PaymentService($mockPaymentManager);
        $paymentMethod = $paymentService->provider($provider)
        ->paymentMethod(new CustomerData(['user_type'=>'test-type','user_id'=>'test-id']));

        

        $this->assertInstanceOf(ProviderPaymentMethod::class, $paymentMethod);
    }
}
