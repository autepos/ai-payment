<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\StripeIntent;

use Mockery;
use Stripe\Event;
use Autepos\AiPayment\Tests\TestCase;
use Stripe\Customer;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentCustomer;

class StripeIntent_CustomerWebhookEventHandlers_Test extends TestCase
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
     * Mock of StripIntentProviderCustomer
     *
     * @var \Mockery\MockInterface
     */
    private $mockStripeIntentCustomer;

    public function setUp(): void
    {
        parent::setUp();

        // Turn off webhook secret to disable webhook verification so we do not 
        // have to sign the request we make to the webhook endpoint.
        $paymentProvider = $this->providerInstance();

        $rawConfig = $paymentProvider->getRawConfig();
        $rawConfig['webhook_secret'] = null;

        //
        $this->mockStripeIntentCustomer = Mockery::mock(StripeIntentCustomer::class);


        // Mock the payment provider
        $partialMockPaymentProvider = Mockery::mock(StripeIntentPaymentProvider::class)->makePartial();
        $partialMockPaymentProvider->shouldReceive('customer')
            ->byDefault()
            ->once()
            ->andReturn($this->mockStripeIntentCustomer);

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


    public function test_can_handle_deleted_webhook_event()
    {
        $this->mockStripeIntentCustomer->shouldReceive('webhookDeleted')
            ->byDefault()
            ->with(Mockery::type(Customer::class))
            ->once()
            ->andReturn(true);

        $data = [
            'object' => [
                'id' => 'cus_id',
                'object' => Customer::OBJECT_NAME,
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::CUSTOMER_DELETED,
            'data' => $data,
        ];


        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertOk();
        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_cannot_handle_deleted_webhook_event_on_error()
    {
        // Set a specific expectations
        $this->partialMockPaymentProvider->shouldNotReceive('customer');

        $data = [
            'object' => [
                'id' => 'cus_id',
                'object' => 'not-customer',
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::CUSTOMER_DELETED,
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
