<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\StripeIntent;

use Mockery;
use Stripe\Event;
use Autepos\AiPayment\Tests\TestCase;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;

class StripeIntent_PaymentProviderWebhookEventHandlers_Test extends TestCase
{
    use StripeIntentTestHelpers;

    private $provider = StripeIntentPaymentProvider::PROVIDER;

    /**
     * Mock of StripeIntentPaymentProvider
     *
     * @var \Mockery\MockInterface
     */
    private $partialMockPaymentProvider;

    public function setUp(): void
    {
        parent::setUp();

        // Turn off webhook secret to disable webhook verification so we do not 
        // have to sign the request we make to the webhook endpoint.
        $paymentProvider = $this->providerInstance();

        $rawConfig = $paymentProvider->getRawConfig();
        $rawConfig['webhook_secret'] = null;


        // Mock the payment provider
        $partialMockPaymentProvider = Mockery::mock(StripeIntentPaymentProvider::class)->makePartial();
        $partialMockPaymentProvider->shouldReceive('webhookChargeByRetrieval')
            ->byDefault()
            ->with(Mockery::type(PaymentIntent::class))
            ->once()
            ->andReturn(new PaymentResponse(PaymentResponse::newType('charge'), true));

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

    public function test_can_handle_payment_intent_succeeded_webhook_event()
    {

        // Now post an intent with a succeeded status
        $data = [
            'object' => [
                'id' => 'pm_id',
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'object' => PaymentIntent::OBJECT_NAME,
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'data' => $data,
        ];


        $response = $this->postJson(StripeIntentPaymentProvider::$webhookEndpoint, $payload);
        $response->assertOk();
        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_can_handle_payment_intent_succeeded_webhook_event_when_data_recording_fails()
    {

        // Set a specific expectation on the payment provider partial mock
        $this->partialMockPaymentProvider->shouldReceive('webhookChargeByRetrieval')
            ->once()
            ->with(Mockery::type(PaymentIntent::class))
            ->andReturn(new PaymentResponse(PaymentResponse::newType('charge'), false));

        // Now post an intent with a succeeded status
        $data = [
            'object' => [
                'id' => 'pm_id',
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'object' => PaymentIntent::OBJECT_NAME,
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
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

    public function test_cannot_handle_payment_intent_succeeded_webhook_event_when_the_intent_cannot_be_extracted()
    {

        // Set a specific expectation on the payment provider partial mock
        $this->partialMockPaymentProvider->shouldNotReceive('webhookChargeByRetrieval');

        // Now post an intent with a succeeded status
        $data = [
            'object' => [
                'id' => 'pm_id',
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'object' => 'not_payment_intent',
            ]
        ];

        $payload = [
            'id' => 'test_event',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
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
