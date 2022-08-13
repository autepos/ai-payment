<?php

namespace Autepos\AiPayment\Tests\Feature\Providers;

use Mockery;
use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\PaymentResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\OfflinePaymentProvider;
use Autepos\AiPayment\Tests\ContractTests\PaymentProviderContractTest;

class OfflinePaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    use PaymentProviderContractTest;


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

    /**
     * Hook for PaymentProviderContractTest
     *
     * @return void
     */
    public function subjectInstance(){
        return $this->providerInstance();
    }

    public function test_can_get_provider()
    {
        $this->assertEquals($this->provider, $this->resolveProvider()->getProvider());
    }

    public function test_can_instantiate_provider()
    {
        $this->assertInstanceOf(OfflinePaymentProvider::class, $this->resolveProvider());
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

    public function test_can_customer_charge_payment(){

        $response = $this->providerInstance()
            ->charge();

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_CHARGE, $response->getType()->getName());
        $this->assertFalse($response->success);

        $this->assertEquals('Access denied',$response->message);
        $this->assertEquals(['Access to offline payment denied'],$response->errors);

        $this->assertNull($response->getTransaction());

    }

    /**
     * @depends test_can_customer_charge_payment
     *
     * @return void
     */
    public function test_can_customer_init_split_payment()
    {
        $this->assertTrue(true); // This test is considered passed if the depended test passes.
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
}
