<?php

namespace Autepos\AiPayment\Tests\Feature\Providers;

use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\PaymentResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\PayLaterPaymentProvider;

class PayLaterPaymentProviderTest extends TestCase
{


    private $provider = PayLaterPaymentProvider::PROVIDER;

    /**
     * Get the instance of the provider the way the host app would get it
     */
    private function resolveProvider(): PayLaterPaymentProvider
    {
        $paymentManager = app(PaymentProviderFactory::class);
        $paymentProvider = $paymentManager->driver($this->provider);
        return $paymentProvider;
    }

        /**
     * Get the instance of the provider directly
     */
    private function providerInstance(): PayLaterPaymentProvider
    {
        return new PayLaterPaymentProvider;
    }

    public function test_can_get_provider()
    {
        $this->assertEquals($this->provider, $this->resolveProvider()->getProvider());
    }

    public function test_can_instantiate_provider()
    {
        $this->assertInstanceOf(PayLaterPaymentProvider::class, $this->resolveProvider());
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

        /**
         * @var \Autepos\AiPayment\Providers\Contracts\Orderable
         */
        $mockOrder = Mockery::mock(Orderable::class);


        /**
         * @var \Illuminate\Contracts\Auth\Authenticatable
         */
        $mockCashier = Mockery::mock(Authenticatable::class);


        $response = $this->providerInstance()
            ->order($mockOrder)
            ->cashierInit($mockCashier,null);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_INIT, $response->getType()->getName());
        $this->assertTrue($response->success);

        $this->assertNull($response->getTransaction());
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



    public function test_guest_customer_can_charge_payment()
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
        $this->assertTrue($response->success);

        $this->assertNull($response->getTransaction());
    }

    public function test_user_customer_can_charge_payment()
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
        $this->assertTrue($response->success);

        $this->assertNull($response->getTransaction());
    }
}
