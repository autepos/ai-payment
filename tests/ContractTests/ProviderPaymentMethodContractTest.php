<?php

namespace Autepos\AiPayment\Tests\ContractTests;

use Illuminate\Support\Str;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;

/**
 * Defines the most BASIC tests a \Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod 
 * implementation must pass.
 * 
 * @see \Autepos\AiPayment\Tests\ContractTests\PaymentProviderContractTest to get idea on usage.
 * 
 * TODO: These tests have themselves not been tested
 */
trait ProviderPaymentMethodContractTest 
{
    
    use ContractTestBase;

    protected $subjectContract=ProviderPaymentMethod::class;
        /**
     * Get the data required to save a payment method
     * 
     * @return array An array of data required to save a payment method.
     * 
     * @throws  \Exception If paymentMethodSaveData() is not defined in the user test or the method is not retuning an array.
     */
    private function paymentMethodSaveDataOrFail()
    {
        if (method_exists($this, 'paymentMethodSaveData')) {
            $data= $this->paymentMethodSaveData();
            if (is_array($data)) {
                return $data;
            }
        }
        throw new \Exception('paymentMethodSaveData() is missing or is returning not returning an array. Tips: Override the paymentMethodSaveData() method in your test. You should then return an data array required to save a payment method from the paymentMethodSaveData()');
    }
        /**
     * Create an instance of payment provider customer for Stripe
     *
     * @param string $user_type @see PaymentProviderCustomer table
     * @param string $user_id @see PaymentProviderCustomer table
     * @param string $name The name of the customer
     * @param string $email
     */
    private function createTestPaymentProviderCustomer(string $user_type = 'test-payment-provider-customer', string $user_id = '1', string $name = null, string $email = null): PaymentProviderCustomer
    {
        return PaymentProviderCustomer::factory()->create([
            'payment_provider' => $this->subjectInstanceOrFail($this->subjectContract)->provider->getProvider(),
            'payment_provider_customer_id' => (string)Str::uuid(),
            'user_type' => $user_type,
            'user_id' => $user_id,
        ]);
    }
    
    public function test_can_init_payment_method()
    {

        $response = $this->subjectInstanceOrFail($this->subjectContract)
            ->init([]);
        $this->assertInstanceOf(PaymentMethodResponse::class, $response);
    }

    public function test_can_save_payment_method()
    {
        $user_type = 'type-is-test-class';
        $user_id = '21022022';
        $email = 'tester@autepos.com';

        // Create a payment-provider-customer that will be used under the hood
        $paymentProviderCustomer = $this->createTestPaymentProviderCustomer(
            $user_type,
            $user_id,
            'name is tester',
            $email
        );

       
        //

        $subjectInstance=$this->subjectInstanceOrFail($this->subjectContract);

        if(is_null($subjectInstance->getCustomerData())){
            $customerData = new CustomerData(['user_type' => $user_type, 'user_id' => $user_id, 'email' => $email]);
            $subjectInstance->customerData($customerData);
        }
        // Save the payment method
        $response = $subjectInstance->save($this->getPaymentMethodSaveDataOrFail());



        // Check that we have payment method response
        $this->assertInstanceOf(PaymentMethodResponse::class, $response);
        $this->assertTrue($response->success);

        // Check that we have local payment method created
        $paymentProviderCustomerPaymentMethod = $response->getPaymentProviderCustomerPaymentMethod();
        $this->assertTrue($paymentProviderCustomerPaymentMethod->exists);
        $this->assertInstanceOf(PaymentProviderCustomerPaymentMethod::class, $paymentProviderCustomerPaymentMethod);

        $this->assertEquals($paymentProviderCustomer->id, $paymentProviderCustomerPaymentMethod->payment_provider_customer_id);
        $this->assertEquals($subjectInstance->provider->getProvider(), $paymentProviderCustomerPaymentMethod->payment_provider);

        $this->assertNotNull($paymentProviderCustomerPaymentMethod->type);// TODO do we really need this?


    }


    /**
     * If the dependent test fails there is no need to run this test: that is the only point of the dependence here.
     * @depends test_can_save_payment_method
     *
     */
    public function test_can_remove_payment_method()
    {
        $user_type = 'type-is-test-class';
        $user_id = '21022022';
        $email = 'tester@autepos.com';

        // Create a payment-provider-customer that will be used under the hood
        $paymentProviderCustomer = $this->createTestPaymentProviderCustomer(
            $user_type,
            $user_id,
            'name is tester',
            $email
        );

        $subjectInstance=$this->subjectInstanceOrFail($this->subjectContract);

        if(is_null($subjectInstance->getCustomerData())){
            $customerData = new CustomerData(['user_type' => $user_type, 'user_id' => $user_id, 'email' => $email]);
            $subjectInstance->customerData($customerData);
        }
        // Save the payment method
        $response = $subjectInstance->save($this->getPaymentMethodSaveDataOrFail());


        // Now that it has been saved we should try to remove it
        $paymentProviderCustomerPaymentMethod = $response->getPaymentProviderCustomerPaymentMethod();
        $response = $subjectInstance()
            ->remove($paymentProviderCustomerPaymentMethod);

        // It should now be deleted
        $this->assertDatabaseMissing($paymentProviderCustomerPaymentMethod, ['id' => $paymentProviderCustomerPaymentMethod->id]);

    }
}
