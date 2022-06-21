<?php

namespace Autepos\AiPayment\Tests\Feature\Providers;

use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\OfflinePaymentProvider;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;

class OfflinePaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    private $provider = OfflinePaymentProvider::PROVIDER;

    /**
     * Get the instance of the provider the way the host app would get it
     */
    private function resolveProvider(): OfflinePaymentProvider
    {
        $paymentManager = app(PaymentProviderFactory::class);
        $paymentProvider = $paymentManager->driver($this->provider);
        return $paymentProvider;
    }

    /**
     * Get the instance of the provider directly
     */
    private function providerInstance(): OfflinePaymentProvider
    {
        return new OfflinePaymentProvider;
    }

    public function test_can_get_provider()
    {
        $this->assertEquals($this->provider, $this->resolveProvider()->getProvider());
    }

    public function test_can_instantiate_provider()
    {
        $this->assertInstanceOf(OfflinePaymentProvider::class, $this->resolveProvider());
    }
    public function test_can_up(){
        $response = $this->providerInstance()->up();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_SAVE, $response->getType()->getName());
        $this->assertTrue($response->success);
    }

    public function test_can_down(){
        $response = $this->providerInstance()->down();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_SAVE, $response->getType()->getName());
        $this->assertTrue($response->success);
    }

    public function test_can_ping(){
        $response = $this->providerInstance()->ping();

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_PING, $response->getType()->getName());
        $this->assertTrue($response->success);
    }


    public function test_can_cashier_init_payment()
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getAmount')
            ->once()
            ->andReturn($amount);

        $mockOrder->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');
        
        $mockOrder->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type'=>'test-user','user_id'=>'1','email'=>'test@test.com']));

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->once()
            ->andReturn(1);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->cashierInit($mockCashier,null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($this->provider, $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->orderable_amount);
        $this->assertEquals(1, $response->getTransaction()->orderable_id);
        $this->assertEquals(1, $response->getTransaction()->cashier_id);
        $this->assertTrue($response->getTransaction()->exists,'Failed asserting that transaction not stored');
    }


    
    public function test_can_cashier_init_split_payment()
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getKey')
            ->once()
            ->andReturn(1);
        
        $mockOrder->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type'=>'test-user','user_id'=>'1','email'=>'test@test.com']));

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->once()
            ->andReturn(1);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->cashierInit($mockCashier,$amount);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($this->provider, $response->getTransaction()->payment_provider);
        $this->assertEquals($amount,$response->getTransaction()->orderable_amount);
        $this->assertTrue($response->getTransaction()->exists,'Failed asserting that transaction not stored');
    }
    public function test_can_customer_init_payment()
    {


        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->init();

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertNull($response->getTransaction());
    }
    

    public function test_can_customer_init_split_payment()
    {
        // It does not make sense for a customer to init split cash payment. So no
        // test needed here.
        $this->assertTrue(true);
    }

    public function test_can_cashier_charge_payment()
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldReceive('getAmount')
            ->once()
            ->andReturn($amount);

        $mockOrder->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type'=>'test-user','user_id'=>'1','email'=>'test@test.com']));

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->twice() // 
            ->andReturn(1);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->cashierCharge($mockCashier,null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertInstanceOf(Transaction::class, $response->getTransaction());
        $this->assertEquals($this->provider, $response->getTransaction()->payment_provider);
        $this->assertEquals($amount, $response->getTransaction()->amount);
        $this->assertEquals(1, $response->getTransaction()->orderable_id);
        $this->assertEquals(1, $response->getTransaction()->cashier_id);
        $this->assertDatabaseHas($response->getTransaction(), ['id' => $response->getTransaction()->id]);
    }

    /**
     * Transaction must have the same livemode value as offline payment provider. This
     * is to ensure that payment made in livemode=true is charged in livemode=true 
     * and vise versa
     *
     * @return void
     */
    public function test_cashier_cannot_charge_payment_on_livemode_mismatch()
    {
        
        
        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);

        $transaction=Transaction::factory()->create([
            'orderable_id'=>1,
            'payment_provider'=>$this->provider,
            'amount'=>1000,
            'livemode'=>false,
        ]);

        //
        $provider=$this->providerInstance();

        //
        $provider->livemode(true);
        $response = $provider->cashierCharge($mockCashier,$transaction);
        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Livemode',implode(' .',$response->errors));

        // Try the other way round
        $transaction->livemode=true;
        $transaction->save();
        $provider->livemode(false);
        $response = $provider->cashierCharge($mockCashier,$transaction);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Livemode',implode(' .',$response->errors)); 
    }

    /**
     *
     * Transaction's payment provider must be the current provider
     */
    public function test_cashier_cannot_charge_payment_when_provider_mismatch()
    {
        
        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);

        $transaction=Transaction::factory()->create([
            'orderable_id'=>1,
            'payment_provider'=>'wrong_provider',
            'amount'=>1000,
        ]);


        $response = $this->providerInstance()
        ->cashierCharge($mockCashier,$transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Unauthorised',implode(' .',$response->errors));

    }

    public function test_guest_customer_cannot_charge_payment()
    {
        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->charge(null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertFalse($response->success);

        $this->assertNull($response->getTransaction());
    }

    public function test_user_customer_cannot_charge_payment()
    {

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldNotReceive('getUserId');

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->charge(null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertFalse($response->success);

        $this->assertNull($response->getTransaction());
    }


    public function test_cashier_cannot_refund_more_than_transaction_amount()
    {
        $amount = 1000;
        $too_much_refund_amount = $amount + 1;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);

        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $this->provider,
            'amount' => $amount,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldNotReceive('getAuthIdentifier');


        $response = $this->providerInstance()
            ->order($mockOrder)
            ->refund( $mockCashier,$parentTransaction, $too_much_refund_amount, 'Overpayment');

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_REFUND, $response->getType()->getName());
        $this->assertFalse($response->success);
    }

    public function test_can_cashier_refund_payment()
    {
        $amount = 1000;

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldNotReceive('getAmount');

        $mockOrder->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type'=>'test-user','user_id'=>'1','email'=>'test@test.com']));

        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $this->provider,
            'amount' => $amount,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->once() // 
            ->andReturn(1);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->refund($mockCashier,$parentTransaction, $amount, 'Refund');

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_REFUND, $response->getType()->getName());
        $this->assertTrue($response->success);

        //
        $this->assertEquals($amount, $parentTransaction->amount);
        $this->assertEquals(-$amount, $parentTransaction->amount_refunded);
        

        //
        $refundTransaction = $response->getTransaction();
        $this->assertInstanceOf(Transaction::class, $refundTransaction);
        $this->assertEquals($this->provider, $refundTransaction->payment_provider);
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

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);
        $mockOrder->shouldNotReceive('getAmount');

        $mockOrder->shouldReceive('getKey')
            ->once()
            ->andReturn(1);

        $mockOrder->shouldReceive('getCurrency')
            ->once()
            ->andReturn('gbp');

        $mockOrder->shouldReceive('getCustomer')
            ->once()
            ->andReturn(new CustomerData(['user_type'=>'test-user','user_id'=>'1','email'=>'test@test.com']));

        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $this->provider,
            'amount' => $amount,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);
        $mockCashier->shouldReceive('getAuthIdentifier')
            ->once()
            ->andReturn(1);

        $response = $this->providerInstance()
            ->order($mockOrder)
            ->refund( $mockCashier,$parentTransaction, $part_refund_amount, 'Overpayment');

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_REFUND, $response->getType()->getName());
        $this->assertTrue($response->success);

        //
        $this->assertEquals($amount, $parentTransaction->amount);
        $this->assertEquals(-$part_refund_amount, $parentTransaction->amount_refunded);

        //
        $refundTransaction = $response->getTransaction();
        $this->assertInstanceOf(Transaction::class, $refundTransaction);
        $this->assertEquals($this->provider, $refundTransaction->payment_provider);
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
     * Transaction must have the same livemode value as offline payment provider. This
     * is to ensure that payment made in livemode=true is charged in livemode=true 
     * and vise versa
     *
     * @return void
     */
    public function test_cashier_cannot_refund_payment_on_livemode_mismatch()
    {
        
        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' => $this->provider,
            'amount' => 1000,
            'livemode'=>false,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);


        $provider=$this->providerInstance();
        $provider->livemode(true);
        $response = $provider->refund($mockCashier,$parentTransaction, 1000, 'Refund');
        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Livemode',implode(' .',$response->errors));

        // Try the other way round
        $parentTransaction->livemode=true;
        $parentTransaction->save();
        $provider->livemode(false);
        $response = $provider->refund($mockCashier,$parentTransaction, 1000, 'Refund');
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Livemode',implode(' .',$response->errors));


    }

        /**
     * Transaction's payment provider must be the current payment provider
     *
     * @return void
     */
    public function test_cashier_cannot_refund_payment_on_provider_mismatch()
    {
        
        $parentTransaction = Transaction::factory()->create([
            'orderable_id' => 1,
            'payment_provider' =>'wrong_provider',
            'amount' => 1000,
        ]);

        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);


        $response = $this->providerInstance()
        ->refund($mockCashier,$parentTransaction, 1000, 'Refund');
        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('Unauthorised',implode(' .',$response->errors));

    }


    public function test_can_sync_transaction()
    {

        $paymentProvider = $this->providerInstance();
        $transaction = Transaction::factory()->make([
            'payment_provider' => $this->provider,
        ]);
        $response = $paymentProvider->syncTransaction($transaction);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals($response->getType()->getName(), ResponseType::TYPE_RETRIEVE);
    }
}
