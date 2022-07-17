<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\StripeIntent;

use Mockery;
use Stripe\Event;
use Stripe\Customer;
use Illuminate\Support\Facades\Log;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\Tenancy\Tenant;
use Autepos\AiPayment\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentCustomer;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\Http\Controllers\StripeIntentWebhookController;
use Autepos\AiPayment\Tests\Feature\Providers\StripeIntent\Stubs\StripeIntentWebhookControllerStub;

class StripeIntent_CustomerWebhookEventHandlers_Test extends TestCase
{
    use StripeIntentTestHelpers;
    use RefreshDatabase;

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

    /**
     * The config for the payment provider
     *
     * @var array
     */
    private $rawConfig=[];

    /**
     * The webhook secret that should be set in config.
     */
    const WEBHOOK_SECRET='secret';

    public function setUp(): void
    {
        parent::setUp();

        // Turn off webhook secret to disable webhook verification so we do not 
        // have to sign the request we make to the webhook endpoint.
        $paymentProvider = $this->providerInstance();

        $rawConfig = $paymentProvider->getRawConfig();
        $rawConfig['webhook_secret'] = null;
        $this->rawConfig=$rawConfig;


        //
        $this->mockStripeIntentCustomer = Mockery::mock(StripeIntentCustomer::class);


        // Mock the payment provider
        $partialMockPaymentProvider = Mockery::mock(StripeIntentPaymentProvider::class)->makePartial();
        
        $partialMockPaymentProvider->config($rawConfig,false);
        $partialMockPaymentProvider->shouldReceive('configUsingFcn')
        ->byDefault()
        ->once()
        ->andReturnSelf();

        $partialMockPaymentProvider->shouldReceive('customer')
            ->byDefault()
            ->once()
            ->andReturn($this->mockStripeIntentCustomer);

        // Use the mock to replace the payment provider in the manager and set the modified config
        $this->paymentManager()->extend($this->provider, function () use ($partialMockPaymentProvider) {
            return $partialMockPaymentProvider;
        });

        // Now empty the manager drivers cache to ensure that our new mock will be used 
        // to recreate the payment provider on the next access to the manager driver
        $this->paymentManager()->forgetDrivers();

        //
        $this->partialMockPaymentProvider = $partialMockPaymentProvider;

       
    }


        /**
     * Data provider for config webhook secret
     *
     * @return array
     */
    public function webhookSecretConfigDataProvider(){
        return [
            'webhook secret set in config'=>[static::WEBHOOK_SECRET],
            'NO webhook secret set in config'=>[null]
        ];
    }

    /**
     * @param string|null $webhook_secret The webhook secret that should be set in config.
     * @dataProvider webhookSecretConfigDataProvider
     *
     * @return void
     */
    public function test_can_handle_deleted_webhook_event(?string $webhook_secret)
    {
        // Set specific assertions
        if($webhook_secret){
            // If webhook secret is set in config, we must ensure that webhook is verified. 
            
            $this->partialMockPaymentProvider->shouldReceive('verifyWebhookHeader')
            ->once()
            ->andReturn(true);
        }
        
        // Set the required configuration
        $this->rawConfig['webhook_secret'] = $webhook_secret;
        $this->partialMockPaymentProvider->config($this->rawConfig,false);

        // Set more specific expectation
        $this->mockStripeIntentCustomer->shouldReceive('webhookDeleted')
            ->byDefault()
            ->with(Mockery::type(Customer::class))
            ->once()
            ->andReturn(true);

        $data = [
            'object' => [
                'id' => 'cus_id',
                'object' => Customer::OBJECT_NAME,
                'metadata'=>['tenant_id'=>2]
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
        $this->partialMockPaymentProvider->shouldNotReceive('configUsingFcn');
        $this->partialMockPaymentProvider->shouldNotReceive('customer');

        $data = [
            'object' => [
                'id' => 'cus_id',
                'object' => 'not-customer',
                'metadata'=>['tenant_id'=>2]
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

    // /**
    //  * Test that we can retrieve a tenant id with a given stripe customer
    //  *
    //  * @return void
    //  */
    // public function test_perform_stripe_customer_to_tenant_id(){
    //     // Set a specific expectations
    //     $this->partialMockPaymentProvider->shouldNotReceive('configUsingFcn');
    //     $this->partialMockPaymentProvider->shouldNotReceive('customer');
        
    //     $tenant_id=1007;
    //     $user_type='testing-user';
    //     $user_id='testing-user-id';

    //     $customer_id='cus_id';
    //     $data = [
    //         'object' => [
    //             'id' =>  $customer_id,
    //             'object' => Customer::OBJECT_NAME,
    //             'metadata'=>[
    //                 'user_type'=>$user_type,
    //                 'user_id'=>$user_id,
    //             ]
    //         ]
    //     ];

    //     $customer=Customer::constructFrom($data['object']);
        
    //     //
    //     PaymentProviderCustomer::factory()->create([
    //         'payment_provider' => $this->providerInstance()->getProvider(),
    //         'payment_provider_customer_id' =>  $customer_id,
    //         'user_type' => $user_type,
    //         'user_id' => $user_id,
    //         Tenant::getColumnName()=>$tenant_id

    //     ]);

    //     //
    //     $stripeIntentWebhookControllerStubWithProxy=new class extends StripeIntentWebhookController{
    //         public function __construct()
    //         {
                
    //         }
    //         public function stripeCustomerToTenantIdProxy(Customer $customer,PaymentProvider $paymentProvider){
    //             $this->paymentProvider=$paymentProvider;
                
    //             return $this->stripeCustomerToTenantId($customer);
    //         }

    //     };


    //     $result=$stripeIntentWebhookControllerStubWithProxy->stripeCustomerToTenantIdProxy($customer,$this->providerInstance());

    //     $this->assertEquals($tenant_id,$result);

    // }

    //     /**
    //  * 
    //  *
    //  * @return void
    //  */
    // public function test_can_log_critical_for_multiple_models_in_stripe_customer_to_tenant_id(){
    //     // Set a specific expectations
    //     $this->partialMockPaymentProvider->shouldNotReceive('configUsingFcn');
    //     $this->partialMockPaymentProvider->shouldNotReceive('customer');

        

    //     $tenant_id=1007;
    //     $user_type='testing-user';
    //     $user_id='testing-user-id';

    //     $customer_id='cus_id';
    //     $data = [
    //         'object' => [
    //             'id' =>  $customer_id,
    //             'object' => Customer::OBJECT_NAME,
    //             'metadata'=>[
    //                 'user_type'=>$user_type,
    //                 'user_id'=>$user_id,
    //             ]
    //         ]
    //     ];

    //     $customer=Customer::constructFrom($data['object']);
        
    //     //
    //     PaymentProviderCustomer::factory()->create([
    //         'payment_provider' => $this->providerInstance()->getProvider(),
    //         'payment_provider_customer_id' =>  $customer_id,
    //         'user_type' => $user_type,
    //         'user_id' => $user_id,
    //         Tenant::getColumnName()=>$tenant_id

    //     ]);
    //     PaymentProviderCustomer::factory()->create([
    //         'payment_provider' => $this->providerInstance()->getProvider(),
    //         'payment_provider_customer_id' =>  $customer_id,
    //         'user_type' => $user_type,
    //         'user_id' => $user_id,
    //         Tenant::getColumnName()=>$tenant_id

    //     ]);

    //     //
    //     $stripeIntentWebhookControllerStubWithProxy=new class extends StripeIntentWebhookController{
    //         public function __construct()
    //         {
                
    //         }
    //         public function stripeCustomerToTenantIdProxy(Customer $customer,PaymentProvider $paymentProvider){
    //             $this->paymentProvider=$paymentProvider;
                
    //             return $this->stripeCustomerToTenantId($customer);
    //         }

    //     };

    //     Log::shouldReceive('critical')->once();

    //     $result=$stripeIntentWebhookControllerStubWithProxy->stripeCustomerToTenantIdProxy($customer,$this->providerInstance());

    //     $this->assertNull($result);
    // }

    //     /**
    //  * Test that we can retrieve a tenant id with a given stripe customer
    //  *
    //  * @return void
    //  */
    // public function test_can_log_alert_for_missing_model_in_stripe_customer_to_tenant_id(){
    //     // Set a specific expectations
    //     $this->partialMockPaymentProvider->shouldNotReceive('configUsingFcn');
    //     $this->partialMockPaymentProvider->shouldNotReceive('customer');
        

    //     $user_type='testing-user';
    //     $user_id='testing-user-id';

    //     $customer_id='cus_id';
    //     $data = [
    //         'object' => [
    //             'id' =>  $customer_id,
    //             'object' => Customer::OBJECT_NAME,
    //             'metadata'=>[
    //                 'user_type'=>$user_type,
    //                 'user_id'=>$user_id,
    //             ]
    //         ]
    //     ];

    //     $customer=Customer::constructFrom($data['object']);
        

    //     //
    //     $stripeIntentWebhookControllerStubWithProxy=new class extends StripeIntentWebhookController{
    //         public function __construct()
    //         {
                
    //         }
    //         public function stripeCustomerToTenantIdProxy(Customer $customer,PaymentProvider $paymentProvider){
    //             $this->paymentProvider=$paymentProvider;
                
    //             return $this->stripeCustomerToTenantId($customer);
    //         }

    //     };
        
    //     Log::shouldReceive('alert')->once();

    //     $result=$stripeIntentWebhookControllerStubWithProxy->stripeCustomerToTenantIdProxy($customer,$this->providerInstance());
        
    //     $this->assertNull($result);

    // }
}
