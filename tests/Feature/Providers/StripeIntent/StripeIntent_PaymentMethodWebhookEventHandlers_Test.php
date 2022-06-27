<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\StripeIntent;

use Mockery;
use Stripe\Event;
use Autepos\AiPayment\Tests\TestCase;
use Stripe\PaymentMethod;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentMethod;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;

class StripeIntent_PaymentMethodWebhookEventHandlers_Test extends TestCase
{
    use StripeIntentTestHelpers;


    private $provider = StripeIntentPaymentProvider::PROVIDER;

    /**
     * Mock of StripeIntentPaymentProvider
     *
     * @var \Mockery\MockInterface
     */
    private $partialMockPaymentProvider;

    /**
     * Mock of StripeIntentPaymentMethod
     *
     * @var \Mockery\MockInterface
     */
    private $mockStripIntentPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Turn off webhook secret to disable webhook verification so we do not 
        // have to sign the request we make to the webhook endpoint.
        $paymentProvider = $this->providerInstance();

        $rawConfig = $paymentProvider->getRawConfig();
        $rawConfig['webhook_secret'] = null;

        //
        $this->mockStripIntentPaymentMethod = Mockery::mock(StripeIntentPaymentMethod::class);


        // Mock the payment provider
        $partialMockPaymentProvider = Mockery::mock(StripeIntentPaymentProvider::class)->makePartial();
        $partialMockPaymentProvider->shouldReceive('paymentMethodForWebhook')
            ->byDefault()
            ->once()
            ->andReturn($this->mockStripIntentPaymentMethod);

        // Use the mock to replace the payment provider in the manager and set the modified config
        $this->paymentManager()->extend($this->provider, function () use ($partialMockPaymentProvider, $rawConfig) {
            return $partialMockPaymentProvider->config($rawConfig);
        });

        // Now empty the manager drivers cache to ensure that our new mock will be used 
        // to recreate the payment provider on the next access to the manager driver
        $this->paymentManager()->forgetDrivers();

        //
        $this->partialMockPaymentProvider = $partialMockPaymentProvider;
    }


    public function test_can_handle_payment_method_updated_webhook_event()
    {
        $this->mockStripIntentPaymentMethod->shouldReceive('webhookUpdatedOrAttached')
            ->byDefault()
            ->with(Mockery::type(PaymentMethod::class))
            ->once()
            ->andReturn(true);

        $data = [
            'object' => [
                'id' => 'pm_id',
                'object' => PaymentMethod::OBJECT_NAME,
                'customer' => 'cus_id'
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_METHOD_UPDATED,
            'data' => $data,
        ];


        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertOk();
        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_cannot_handle_payment_method_updated_webhook_event_on_error()
    {

        // Set a specific expectations
        $this->mockStripIntentPaymentMethod->shouldNotReceive('webhookUpdatedOrAttached');
        $this->partialMockPaymentProvider->shouldNotReceive('paymentMethodForWebhook');

        $data = [
            'object' => [
                'id' => 'pm_id',
                'object' => 'not_payment_method',
                'customer' => 'cus_id'
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_METHOD_UPDATED,
            'data' => $data,
        ];

        //
        Log::shouldReceive('error')
            ->once();

        //
        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertStatus(422);
        $this->assertEquals('There was an issue with processing the webhook', $response->getContent());
    }

    public function test_can_attached_webhook_event()
    {
        $this->mockStripIntentPaymentMethod->shouldReceive('webhookUpdatedOrAttached')
            ->byDefault()
            ->with(Mockery::type(PaymentMethod::class))
            ->once()
            ->andReturn(true);

        $data = [
            'object' => [
                'id' => 'pm_id',
                'object' => PaymentMethod::OBJECT_NAME,
                'customer' => 'cus_id'
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_METHOD_ATTACHED,
            'data' => $data,
        ];


        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertOk();
        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_can_handle_detached_webhook_event()
    {
        $this->mockStripIntentPaymentMethod->shouldReceive('webhookDetached')
            ->with(Mockery::type(PaymentMethod::class))
            ->once()
            ->andReturn(true);

        $data = [
            'object' => [
                'id' => 'pm_id',
                'object' => PaymentMethod::OBJECT_NAME,
                'customer' => null
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_METHOD_DETACHED,
            'data' => $data,
        ];


        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertOk();
        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_cannot_handle_detached_webhook_event_when_on_error()
    {
        // Set a specific expectations
        $this->partialMockPaymentProvider->shouldNotReceive('paymentMethodForWebhook');

        $data = [
            'object' => [
                'id' => 'pm_id',
                'object' => 'not_payment_method',
                'customer' => null
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_METHOD_DETACHED,
            'data' => $data,
        ];


        //
        Log::shouldReceive('error')
            ->once();

        //
        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertStatus(422);
        $this->assertEquals('There was an issue with processing the webhook', $response->getContent());
    }
}
