<?php

namespace Autepos\AiPayment\Tests\ContractTests;

use Mockery;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;


/**
 * Defines the most BASIC tests a \Autepos\AiPayment\Providers\Contracts\PaymentProvider 
 * implementation must pass.
 * 
 * NOTE that the payment provider implementation must define its own 
 * specific tests that are not covered here. It can also override 
 * any of the tests defined to suit the implementation.
 * 
 * USAGE:
 * Use this trait in a PHPUnit test case for a PaymentProvider and then define, within the test case, 
 * the method name getInstance() which returns an instance of the payment 
 * provider under test.
 */
trait PaymentProviderContractTest
{

    /**
     * Get the instance of the implementation of the subject contract. This is the 
     * instance that needs to be tested.
     *
     */
    abstract public function createContract():PaymentProvider;

    public function test_can_up()
    {
        $response = $this->createContract()->up();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_SAVE, $response->getType()->getName());
        $this->assertTrue($response->success);
    }

    public function test_can_down()
    {
        $response = $this->createContract()->down();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_SAVE, $response->getType()->getName());
        $this->assertTrue($response->success);
    }

    public function test_can_ping()
    {
        $response = $this->createContract()->ping();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_PING, $response->getType()->getName());
        $this->assertTrue($response->success);
    }


    public function test_can_cashier_init_payment(): Transaction
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getAmount')
            ->atLeast()
            ->once()
            ->andReturn($amount);

        $mockOrder->shouldReceive('getKey')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->atLeast()
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->atLeast()
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));

        $mockOrder->shouldReceive('getDescription')
            //->once() // Uncomment out to make calling getDescription mandatory.
            ->andReturn('test_can_cashier_init_payment');
        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->order($mockOrder)
            ->cashierInit($mockCashier, null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->orderable_amount);
        $this->assertEquals(1, $response->getTransaction()->orderable_id);
        $this->assertEquals(1, $response->getTransaction()->cashier_id);
        $this->assertTrue($response->getTransaction()->exists, 'Failed asserting that transaction is stored');

        return $response->getTransaction();
    }



    public function test_can_cashier_init_split_payment()
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getKey')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->atLeast()
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->atLeast()
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']));

        $mockOrder->shouldReceive('getDescription')
            //->once() // Uncomment out to make calling getDescription mandatory.
            ->andReturn('test_can_cashier_init_payment');

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->order($mockOrder)
            ->cashierInit($mockCashier, $amount);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->orderable_amount);
        $this->assertTrue($response->getTransaction()->exists, 'Failed asserting that transaction is stored');
    }

    public function test_can_customer_init_payment(): Transaction
    {


        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getAmount')
            ->atLeast()
            ->once()
            ->andReturn($amount);

        $mockOrder->shouldReceive('getKey')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->atLeast()
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->atLeast()
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'customer', 'user_id' => '1', 'email' => 'test@test.com']));

        $mockOrder->shouldReceive('getDescription')
            ->atLeast()
            ->once()
            ->andReturn('test_can_customer_init_payment');


        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->order($mockOrder)
            ->init(null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->orderable_amount);
        $this->assertEquals('gbp', $response->getTransaction()->currency);
        $this->assertEquals(1, $response->getTransaction()->orderable_id);
        $this->assertNull($response->getTransaction()->cashier_id);
        $this->assertTrue($response->getTransaction()->exists, 'Failed asserting that transaction is stored');

        return $response->getTransaction();
    }


    public function test_can_customer_init_split_payment()
    {

        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getKey')
            ->atLeast()
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->atLeast()
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->atLeast()
            ->once()
            ->andReturn(new CustomerData(['user_type' => 'customer', 'user_id' => null, 'email' => 'test@test.com']));

        $mockOrder->shouldReceive('getDescription')
            ->atLeast()
            ->once()
            ->andReturn('test_can_cashier_init_payment');


        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->order($mockOrder)
            ->init($amount);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->orderable_amount);
        $this->assertTrue($response->getTransaction()->exists, 'Failed asserting that transaction is stored');
    }

    /**
     *
     * @depends test_can_cashier_init_payment
     * @return void
     */
    public function test_can_cashier_charge_payment(Transaction $transaction)
    {
        // Since this is a new test the database would have been refreshed 
        // so we need to re-add this transaction to db.
        $transaction = Transaction::factory()->create($transaction->attributesToArray());


        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->atLeast()
            ->once() // 
            ->andReturn(1);

        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->cashierCharge($mockCashier, $transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertTrue($response->success);


        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertEquals($transaction->orderable_amount, $response->getTransaction()->orderable_amount);
        $this->assertEquals($transaction->orderable_amount, $response->getTransaction()->amount);
        $this->assertEquals($transaction->orderable_id, $response->getTransaction()->orderable_id);
        $this->assertEquals(1, $response->getTransaction()->cashier_id);

        $this->assertDatabaseHas($response->getTransaction(), ['id' => $response->getTransaction()->id]);
    }

    /**
     * @depends test_can_customer_init_payment
     */
    public function test_can_customer_charge_payment(Transaction $transaction): Transaction
    {
        // Since this is a new test the database would have been refreshed 
        // so we need to re-add this transaction to db.
        $transaction = Transaction::factory()->create($transaction->attributesToArray());


        $providerInstance=$this->createContract();
        $response = $providerInstance
            ->charge($transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($providerInstance->getProvider(), $response->getTransaction()->payment_provider);
        $this->assertTrue($response->getTransaction()->success);
        $this->assertEquals($transaction->orderable_amount, $response->getTransaction()->orderable_amount);
        $this->assertEquals($transaction->orderable_amount, $response->getTransaction()->amount);
        $this->assertEquals($transaction->orderable_id, $response->getTransaction()->orderable_id);
        $this->assertNull($response->getTransaction()->cashier_id);

        $this->assertDatabaseHas(new Transaction, ['id' => $response->getTransaction()->id]);

        return $transaction;
    }




    public function test_can_cashier_refund_payment()
    {
        $amount = 1000;

        $providerInstance=$this->createContract();

        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $providerInstance->getProvider(),
            'amount' => $amount,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->atLeast()
            ->once() // 
            ->andReturn(1);

        
        $response = $providerInstance
            ->refund($mockCashier, $parentTransaction, $amount, 'Refund');

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_REFUND, $response->getType()->getName());
        $this->assertTrue($response->success);

        //
        $this->assertEquals($amount, $parentTransaction->amount);
        $this->assertEquals(-$amount, $parentTransaction->amount_refunded);


        //
        $refundTransaction = $response->getTransaction();
        $this->assertInstanceOf(Transaction::class, $refundTransaction);
        $this->assertEquals($providerInstance->getProvider(), $refundTransaction->payment_provider);
        $this->assertTrue($refundTransaction->refund);
        $this->assertEquals(0, $refundTransaction->amount);
        $this->assertEquals(-$amount, $refundTransaction->amount_refunded);
        $this->assertEquals('Refund', $refundTransaction->description);
        $this->assertEquals(1, $refundTransaction->orderable_id);
        $this->assertEquals(1, $refundTransaction->cashier_id);
        $this->assertDatabaseHas($refundTransaction, ['id' => $refundTransaction->id]);

        $this->assertEquals($parentTransaction->id, $refundTransaction->parent_id);
    }

    public function test_can_cashier_refund_part_of_payment()
    {
        $amount = 1000;
        $part_refund_amount = 500;

        $providerInstance=$this->createContract();

        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $providerInstance->getProvider(),
            'amount' => $amount,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->atLeast()
            ->once()
            ->andReturn(1);

        
        $response = $providerInstance
            ->refund($mockCashier, $parentTransaction, $part_refund_amount, 'Overpayment');

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_REFUND, $response->getType()->getName());
        $this->assertTrue($response->success);

        //
        $this->assertEquals($amount, $parentTransaction->amount);
        $this->assertEquals(-$part_refund_amount, $parentTransaction->amount_refunded);

        //
        $refundTransaction = $response->getTransaction();
        $this->assertInstanceOf(Transaction::class, $refundTransaction);
        $this->assertEquals($providerInstance->getProvider(), $refundTransaction->payment_provider);
        $this->assertTrue($refundTransaction->refund);
        $this->assertEquals(0, $refundTransaction->amount);
        $this->assertEquals(-$part_refund_amount, $refundTransaction->amount_refunded);
        $this->assertEquals('Overpayment', $refundTransaction->description);
        $this->assertEquals(1, $refundTransaction->orderable_id);
        $this->assertEquals(1, $refundTransaction->cashier_id);
        $this->assertDatabaseHas($refundTransaction, ['id' => $refundTransaction->id]);

        $this->assertEquals($parentTransaction->id, $refundTransaction->parent_id);
    }


    /**
     * @depends test_can_cashier_init_payment
     *
     */
    public function test_can_sync_transaction(Transaction $transaction)
    {
        // Since this is a new test the database would have been refreshed 
        // so we need to re-add this transaction to db.
        $transaction = Transaction::factory()->create($transaction->attributesToArray());
        /** OR ->tell Laravel that the model does not exists and then save it.
         * $transaction->exists=false;
         * $transaction->save();
         */


        $paymentInstance = $this->createContract();

        $response = $paymentInstance->syncTransaction($transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals($response->getType()->getName(), ResponseType::TYPE_RETRIEVE);
    }
}
