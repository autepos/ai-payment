<?php
namespace Autepos\AiPayment\Tests\Feature\Providers\StripeIntent;

use Autepos\AiPayment\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\Http\MiddleWare\StripeIntentVerifyWebhookSignature;

/**
 * TODO: Delete file as no more in use
 */
class StripeIntentVerifyWebhookSignatureTest_DELETE extends TestCase
{
    use StripeIntentTestHelpers;

    private $provider = StripeIntentPaymentProvider::PROVIDER;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * Webhook endpoint secret
     *
     * @var string
     */
    private $webhookEndpointSecret='secret';

    /**
     * Webhook endpoint tolerance in seconds
     *
     * @var string
     */
    private $webhookEndpointTolerance=300;

    public function setUp(): void
    {
        parent::setUp();

        // Set the config on the payment provider
        $paymentProvider=$this->resolveProvider();
        $rawConfig = $paymentProvider->getRawConfig();
        $rawConfig['webhook_secret'] = $this->webhookEndpointSecret;
        $rawConfig['webhook_tolerance']=$this->webhookEndpointTolerance;
        $paymentProvider->config($rawConfig);

        
        //
        $this->request = new Request([], [], [], [], [], [], 'Signed Body');
    }

    public function test_response_is_received_when_secret_matches()
    {
        
        $this->withTimestamp(time());
        $this->withSignedSignature($this->webhookEndpointSecret);

        $response = (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
                return new Response('OK');
            });

        $this->assertEquals('OK', $response->content());

    }

    public function test_response_is_received_when_timestamp_is_within_tolerance_zone()
    {
        $this->withTimestamp(time() - 300);
        $this->withSignedSignature($this->webhookEndpointSecret);

        $response = (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
                return new Response('OK');
            });

        $this->assertEquals('OK', $response->content());
    }

    public function test_app_aborts_when_timestamp_is_too_old()
    {
        $this->withTimestamp(time() - 301);
        $this->withSignedSignature($this->webhookEndpointSecret);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Timestamp outside the tolerance zone');

        $response = (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
            });
    }

    public function test_app_aborts_when_timestamp_is_invalid()
    {
        $this->withTimestamp('invalid');
        $this->withSignedSignature($this->webhookEndpointSecret);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unable to extract timestamp and signatures from header');

        $response = (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
            });
    }

    public function test_app_aborts_when_secret_does_not_match()
    {
        $this->withTimestamp(time());
        $this->withSignature('fail');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('No signatures found matching the expected signature for payload');

        (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
            });
    }

    public function test_app_aborts_when_no_secret_was_provided()
    {
        $this->withTimestamp(time());
        $this->withSignedSignature('');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('No signatures found matching the expected signature for payload');

        (new StripeIntentVerifyWebhookSignature($this->paymentManager()))
            ->handle($this->request, function ($request) {
            });
    }

    public function withTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function withSignedSignature($secret)
    {
        return $this->withSignature(
            $this->sign($this->request->getContent(), $secret)
        );
    }

    public function withSignature($signature)
    {
        // 'Stripe-Signature' becomes $_SERVER['HTTP_STRIPE_SIGNATURE'] in the request
        $this->request->headers->set('Stripe-Signature', 't='.$this->timestamp.',v1='.$signature);

        return $this;
    }

    private function sign($payload, $secret)
    {
        return hash_hmac('sha256', $this->timestamp.'.'.$payload, $secret);
    }
}