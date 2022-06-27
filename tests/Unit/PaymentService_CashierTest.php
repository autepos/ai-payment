<?php

namespace Autepos\AiPayment\Tests\Unit;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class PaymentService_CashierTest extends TestCase
{

    public function test_can_cashier_init_payment()
    {

        $provider = 'provider_yi';
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);
        $mockOrder = Mockery::mock(Orderable::class);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class);
        $mockAbstractPaymentProvider->shouldReceive('order')
            ->with($mockOrder)
            ->once()
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('cashierInit')
            ->once()
            ->with($mockAuthenticatableContract, null, null, null)
            ->andReturn($paymentResponse);
        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Init an order payment;
        $response = $partialMockPaymentService
            ->provider($provider)
            ->order($mockOrder)
            ->cashierInit($mockAuthenticatableContract, null);

        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    public function test_can_cashier_init_payment_with_optional_arguments()
    {

        $provider = 'provider_yi';
        $amount = 1000;
        $data = ['provider_data1' => '09032022'];
        $transaction = Transaction::factory()->create(['orderable_id' => 1]);

        $paymentResponse = new PaymentResponse(new ResponseType('init'), true);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Order
        $mockOrder = Mockery::mock(Orderable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('order')
            ->with($mockOrder)
            ->andReturnSelf();

        $mockAbstractPaymentProvider->shouldReceive('cashierInit')
            ->once()
            ->with($mockAuthenticatableContract, $amount, $data, $transaction)
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
            ->cashierInit($mockAuthenticatableContract, $amount, $data, $transaction);
    }


    public function test_sets_unusable_suggested_transaction_to_null_when_cashier_init_payment()
    {
        $provider = 'provider_yi';
        $amount = 1000;
        $transaction = Transaction::factory()->create(['orderable_id' => 1]);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

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
        $mockAbstractPaymentProvider->shouldReceive('cashierInit')
            ->once()
            ->with($mockAuthenticatableContract, $amount, [], null); // the main assertion that the last param=>transaction is null


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Init an order payment
        $response = $partialMockPaymentService->provider($provider)
            ->order($mockOrder)
            ->cashierInit($mockAuthenticatableContract, $amount, [], $transaction);
    }


    public function test_can_cashier_charge_payment()
    {
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('cashierCharge')
            ->once()
            ->with($mockAuthenticatableContract, $transaction, [])
            ->andReturn($paymentResponse);


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
            ->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction);
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }


    public function test_can_cashier_charge_payment_with_optional_arguments()
    {

        //
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);
        $data = ['provider_data1' => '09032022'];

        $paymentResponse = new PaymentResponse(new ResponseType('charge'), true);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('cashierCharge')
            ->once()
            ->with($mockAuthenticatableContract, $transaction, $data)
            ->andReturn($paymentResponse);


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
            ->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction, $data);

        $this->assertInstanceOf(PaymentResponse::class, $response);
    }


    public function test_if_order_is_given_then_it_is_passed_to_provider_during_cashier_charge()
    {
        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

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

        $mockAbstractPaymentProvider->shouldReceive('cashierCharge')
            ->once();

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge with order
        $response = $partialMockPaymentService
            ->provider($provider)
            ->order($mockOrder)
            ->cashierCharge($mockAuthenticatableContract, $transaction);
    }

    public function test_cashier_cannot_charge_if_provider_transaction_is_not_authorised()
    {

        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);

        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->with($transaction)
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldNotReceive('cashierCharge');


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
            ->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);


        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error', $response->message);
        $this->assertSame(['Unauthorised payment transaction with provider'], $response->errors);
    }
    public function test_cashier_cannot_charge_if_provider_transaction_is_not_authorised_because_of_livemode_mismatch()
    {


        $provider = 'provider_yi';
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
        ]);


        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();

        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->with($transaction)
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldNotReceive('cashierCharge');


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge without order
        $response = $partialMockPaymentService
            ->provider($provider)
            ->cashierCharge($mockAuthenticatableContract, $transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);


        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error', $response->message);
        $this->assertSame(['Livemode mismatch'], $response->errors);
    }

    public function test_refund_is_validated()
    {
        $amount = 1000;
        $provider = 'provider_yi';
        $refund_description = 'duplicate';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $amount
        ]);


        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);

        // Provider - the validation that we are testing
        $mockAbstractPaymentProvider->shouldReceive('validateRefund')
            ->with($transaction, $amount);

        // Provider - actual refund - i.e may or may not receive 'refund' depending th on the result of 'validateRefund'
        $mockAbstractPaymentProvider->shouldReceive('refund');


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->once()
            ->andReturn($mockAbstractPaymentProvider);

        // Make the refund
        $response = $partialMockPaymentService->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction, null, $refund_description);
    }

    public function test_can_cashier_refund_payment()
    {
        $amount = 1000;
        $provider = 'provider_yi';
        $refund_description = 'duplicate';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $amount
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('refund'), true);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('validateRefund')
            ->once()
            ->andReturn(true);

        $mockAbstractPaymentProvider->shouldReceive('refund')
            ->with($mockAuthenticatableContract, $transaction, $amount, $refund_description)
            ->once()
            ->andReturn($paymentResponse);

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->once()
            ->andReturn($mockAbstractPaymentProvider);

        // Make the refund
        $response = $partialMockPaymentService->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction, null, $refund_description);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }

    /**
     * Test that part of the payment can be refunded.
     */
    public function test_can_cashier_refund_payment_partly()
    {
        $amount = 1000;
        $refund_amount = 500;
        $provider = 'provider_yi';
        $refund_description = 'duplicate';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $amount
        ]);

        $paymentResponse = new PaymentResponse(new ResponseType('refund'), true);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('validateRefund')
            ->with($transaction, $refund_amount)
            ->once()
            ->andReturn(true);
        $mockAbstractPaymentProvider->shouldReceive('refund')
            ->with($mockAuthenticatableContract, $transaction, $refund_amount, $refund_description)
            ->once()
            ->andReturn($paymentResponse);

        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->once()
            ->andReturn($mockAbstractPaymentProvider);

        // Make the refund
        $response = $partialMockPaymentService->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction, $refund_amount, $refund_description);


        //
        $this->assertInstanceOf(PaymentResponse::class, $response);
    }



    public function test_cashier_cannot_refund_if_provider_transaction_is_not_authorised()
    {

        $amount = 1000;
        $provider = 'provider_yi';

        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $amount
        ]);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->with($transaction)
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(true);

        $mockAbstractPaymentProvider->shouldNotReceive('validateRefund');
        $mockAbstractPaymentProvider->shouldNotReceive('refund');


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge 
        $response = $partialMockPaymentService
            ->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);


        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error', $response->message);
        $this->assertSame(['Unauthorised payment transaction with provider'], $response->errors);
    }
    public function test_cashier_cannot_refund_if_provider_transaction_is_not_authorised_because_of_livemode_mismatch()
    {

        $amount = 1000;
        $provider = 'provider_yi';


        //
        $transaction = Transaction::factory()->make([
            'payment_provider' => $provider,
            'amount' => $amount
        ]);

        //
        $mockAuthenticatableContract = Mockery::mock(Authenticatable::class);

        // Provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)
            ->shouldAllowMockingProtectedMethods();
        $mockAbstractPaymentProvider->shouldReceive('authoriseProviderTransaction')
            ->once()
            ->with($transaction)
            ->andReturn(false);
        $mockAbstractPaymentProvider->shouldReceive('hasSameLiveModeAsTransaction')
            ->andReturn(false);

        $mockAbstractPaymentProvider->shouldNotReceive('validateRefund');
        $mockAbstractPaymentProvider->shouldNotReceive('refund');


        // Payment service
        $partialMockPaymentService = Mockery::mock(PaymentService::class)->makePartial();
        $partialMockPaymentService->shouldReceive('providerInstance')
            ->andReturn($mockAbstractPaymentProvider);


        // Make a charge
        $response = $partialMockPaymentService
            ->provider($provider)
            ->refund($mockAuthenticatableContract, $transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);


        $this->assertFalse($response->success);
        $this->assertSame('Transaction-provider authorisation error', $response->message);
        $this->assertSame(['Livemode mismatch'], $response->errors);
    }
}
