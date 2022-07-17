<?php

namespace Autepos\AiPayment\Tests\Feature;


use Mockery;
use Mockery\MockInterface;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class InteractWithTransactionTest extends TestCase
{
    private $provider = 'abstract_provider';
    public function test_can_new_up_transaction()
    {

        $amount = 1000;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrderableInterface->shouldReceive('getAmount')
            ->once()
            ->andReturn($amount);

        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));


        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $mockAbstractPaymentProvider->order($mockOrderableInterface);



        //
        $transaction = $mockAbstractPaymentProvider->newTransaction(); 

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($this->provider, $transaction->payment_provider);

        //
        $this->assertEquals(1, $transaction->orderable_id);
        $this->assertEquals($amount, $transaction->orderable_amount);
        $this->assertEquals(Transaction::LOCAL_STATUS_INIT, $transaction->local_status);
        $this->assertEquals('unknown', $transaction->status);
        $this->assertEquals(false, $transaction->success);
        $this->assertEquals(Transaction::TRANSACTION_FAMILY_PAYMENT, $transaction->transaction_family);
        $this->assertNull($transaction->transaction_family_id);
    }

    public function test_can_new_up_transaction_with_params()
    {

        $amount = 1000;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));



        $partialMockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $partialMockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $partialMockAbstractPaymentProvider->order($mockOrderableInterface);

        //
        $useTransaction = Transaction::factory()->make([ // Just using make() instead of create() to avoid the slow db migration+seeding 
            'id' => 13022022,
        ]);

        //
        $transaction = $partialMockAbstractPaymentProvider->newTransaction(
            $amount,
            Transaction::LOCAL_STATUS_INIT,
            'unknown', //status
            false, //success
            '123_transaction_family',
            '123_transaction_family_id',
            '123_transaction_child_id',
            $useTransaction
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($this->provider, $transaction->payment_provider);

        //
        $this->assertEquals($useTransaction->id, $transaction->id);
        $this->assertEquals(1, $transaction->orderable_id);
        $this->assertEquals($amount, $transaction->orderable_amount);
        $this->assertEquals(Transaction::LOCAL_STATUS_INIT, $transaction->local_status);
        $this->assertEquals('unknown', $transaction->status);
        $this->assertEquals(false, $transaction->success);
        $this->assertEquals('123_transaction_family', $transaction->transaction_family);
        $this->assertEquals('123_transaction_family_id', $transaction->transaction_family_id);
        $this->assertEquals('123_transaction_child_id', $transaction->transaction_child_id);
    }

    public function test_can_get_init_transaction()
    {

        $amount = 1000;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrderableInterface->shouldReceive('getAmount')
            ->once()
            ->andReturn($amount);

        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));


        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $mockAbstractPaymentProvider->order($mockOrderableInterface);

        $transaction = $mockAbstractPaymentProvider->getInitTransaction();
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($this->provider, $transaction->payment_provider);
        $this->assertEquals($amount, $transaction->orderable_amount);
    }
    public function test_can_get_init_transaction_with_a_specified_amount()
    {
        $specified_amount = 500;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrderableInterface->shouldNotReceive('getAmount');

        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));


        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $mockAbstractPaymentProvider->order($mockOrderableInterface);

        $transaction = $mockAbstractPaymentProvider->getInitTransaction($specified_amount);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($this->provider, $transaction->payment_provider);
        $this->assertEquals($specified_amount, $transaction->orderable_amount);
    }
    public function test_can_get_init_transaction_with_unused_transaction()
    {
        $orderable_id = 1;

        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->twice()
            ->andReturn($orderable_id);

        $mockOrderableInterface->shouldNotReceive('getAmount');


        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));



        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $unusedTransaction = Transaction::factory()->make([ // Just using make() instead of create() to avoid the slow db migration+seeding 
            'id' => 13022022,
            'orderable_id' => $orderable_id
        ]);

        $mockAbstractPaymentProvider->order($mockOrderableInterface);

        $transaction = $mockAbstractPaymentProvider->getInitTransaction(1000, $unusedTransaction);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($unusedTransaction->id, $transaction->id);
        $this->assertEquals($unusedTransaction->orderable_id, $transaction->orderable_id);
    }

    public function test_cannot_get_init_transaction_with_used_transaction()
    {


        $mockOrderableInterface = Mockery::mock(Orderable::class);
        $mockOrderableInterface->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrderableInterface->shouldNotReceive('getAmount');

        $mockOrderableInterface->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrderableInterface->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));



        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider')
            ->once()
            ->andReturn($this->provider);

        $usedTransaction = Transaction::factory()->make([ // Just using make() instead of create() to avoid the slow db migration+seeding 
            'id' => 13022022,
            'orderable_id' => 2,
            'success' => true, // mark it as used
        ]);

        $mockAbstractPaymentProvider->order($mockOrderableInterface);

        $transaction = $mockAbstractPaymentProvider->getInitTransaction(1000, $usedTransaction);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertNotEquals($usedTransaction->id, $transaction->id);
    }



    public function test_can_synch_transaction()
    {

        $provider = 'provider_yi';


        //
        $transaction = Transaction::factory()->make([ // Just using make() instead of create() to avoid the slow db migration+seeding 
            'payment_provider' => $provider,
        ]);


        //
        $paymentManager = app(PaymentProviderFactory::class);

        // Create a mock of payment provider 
        $class = $provider . ', ' . PaymentProvider::class; // Mock class with interface 
        $mockAbstractPaymentProvider = $this->mock($class, function (MockInterface $mock) {
            $paymentResponse = new PaymentResponse(new ResponseType('retrieve'), true);

            $mock->shouldReceive('syncTransaction')
                ->once()
                ->with(Mockery::type(Transaction::class))
                ->andReturn($paymentResponse);
        });


        // Register the payment provider to use the mocked provider instance
        $paymentManager->extend($provider, function ($app) use ($mockAbstractPaymentProvider) {
            return $mockAbstractPaymentProvider;
        });



        /**
         * @var \Autepos\AiPayment\Providers\Contracts\PaymentProvider
         */
        $paymentProvider = $paymentManager->driver($provider);
        $paymentResponse = $paymentProvider->syncTransaction($transaction);

        //
        $this->assertInstanceOf(PaymentResponse::class, $paymentResponse);
    }
}
