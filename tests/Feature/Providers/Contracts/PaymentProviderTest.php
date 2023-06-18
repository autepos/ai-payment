<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\Contracts;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\ResponseType;
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

/**
 * This test is only documenting an implementation of a Payment Provider. The abstract
 * methods 'ping', 'up' and 'down' are not included.
 */
class PaymentProviderTest extends TestCase
{

    public function test_can_cashier_init_payment()
    {
        $provider = 'provider_yi';

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->expects()
            ->cashierInit(Mockery::type(Authenticatable::class))
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Init an order payment;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->cashierInit($mockAuthenticatableContract);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_can_cashier_init_split_payment()
    {
        $provider = 'provider_yi';

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        $split_payment_amount = 1000;

        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('cashierInit')
            ->once()
            ->withArgs(function ($cashier, $amount) use ($split_payment_amount) {
                return (is_a($cashier, Authenticatable::class) and ($amount === $split_payment_amount));
            })
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked provider instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Init an order payment;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->cashierInit($mockAuthenticatableContract, $split_payment_amount);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_can_customer_init_payment()
    {

        $provider = 'provider_yi';

        $mockOrderableInterface = Mockery::mock(Orderable::class);

        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->expects()
            ->init(null) // Null for regular/non-split payment
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Init an order payment;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->init(null);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }


    public function test_can_customer_init_split_payment()
    {

        $provider = 'provider_yi';

        $mockOrderableInterface = Mockery::mock(Orderable::class);

        $split_payment_amount = 1000;

        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('init')
            ->once()
            ->withArgs(function ($arg) use ($split_payment_amount) {
                return ($arg === $split_payment_amount);
            })
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked provider instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Init an order payment;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->init($split_payment_amount);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }


    public function test_can_customer_charge_payment()
    {

        $provider = 'provider_yi';


        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getTotal')
            ->once()
            ->andReturn(2000);


        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $mockOrderableInterface->getTotal(),
        ]);


        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('charge')
            ->once()
            ->with(Mockery::type(Transaction::class))
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked provider instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Charge an order;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->charge($transaction);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
    public function test_can_cashier_charge_payment()
    {
        $provider = 'provider_yi';
        $amount = 1000;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        $transaction = Transaction::factory()->make([
            'orderable_id' => 1,
            'orderable_amount' => $amount,
        ]);

        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->expects()
            ->cashierCharge(Mockery::type(Authenticatable::class), Mockery::type(Transaction::class))
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Init an order payment;
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->cashierCharge($mockAuthenticatableContract, $transaction);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertTrue($response->success);
    }

    public function test_can_cashier_refund_payment()
    {

        $provider = 'provider_yi';


        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getTotal')
            ->once()
            ->andReturn(2000);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);


        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $mockOrderableInterface->getTotal(),
        ]);

        //
        $amount = round($transaction->amount / 2);


        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        $paymentResponse = new PaymentResponse(new ResponseType('refund'), true);

        $mockAbstractPaymentProvider->expects()
            ->order(Mockery::type(Orderable::class))
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('refund')
            ->once()
            ->with(Mockery::type(Authenticatable::class), Mockery::type(Transaction::class), Mockery::type('int'), Mockery::type('string'))
            ->andReturn($paymentResponse);



        // Register the payment provider to use the mocked provider instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });

        // Charge an order;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\PaymentProvider
         */
        $paymentProvider = $paymentManager->driver($provider);
        $response = $paymentProvider->order($mockOrderableInterface)
            ->refund($mockAuthenticatableContract, $transaction, $amount, 'Overpayment');

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_can_instantiate_customer()
    {
        $mockProviderCustomer = Mockery::mock(ProviderCustomer::class);
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        // Now demonstration how the customer method should work, using expectations
        $mockAbstractPaymentProvider->shouldReceive('customer')
            ->once()
            ->andReturn($mockProviderCustomer); // Tests the return type


        $mockAbstractPaymentProvider->customer();
    }

    public function test_can_instantiate_payment_method()
    {
        $mockProviderPaymentMethod = Mockery::mock(ProviderPaymentMethod::class);
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);

        // Now demonstration how the paymentMethod method should work, using expectations
        $mockAbstractPaymentProvider->shouldReceive('paymentMethod')
            ->once()
            ->with(Mockery::type(CustomerData::class)) // Testes the input
            ->andReturn($mockProviderPaymentMethod); // Tests the return type


        $mockAbstractPaymentProvider->paymentMethod(new CustomerData(['user_type' => 'test-user', 'user_id' => 'test-id', 'email' => 'test@test.com']));
    }
}
