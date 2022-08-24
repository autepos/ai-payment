<?php

namespace Autepos\AiPayment\Tests\ContractTests;


use Autepos\AiPayment\PaymentMethodResponse;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;
use Autepos\AiPayment\Models\PaymentProviderCustomerPaymentMethod;

/**
 * Defines the most BASIC tests a \Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod 
 * implementation must pass.
 * 
 * @see \Autepos\AiPayment\Tests\ContractTests\PaymentProviderContractTest to get idea on usage.
 * 
 * 
 */
trait ProviderPaymentMethodContractTest
{

    /**
     * Get the instance of the implementation of the subject contract. This is the 
     * instance that needs to be tested.
     *
     */
    abstract public function createContract(): ProviderPaymentMethod;

    /**
     * Return data array required to save a payment method.
     *
     * @return array An array of data required to save a payment method.
     */
    abstract function paymentMethodDataForSave(): array;

    public function test_can_init_payment_method()
    {

        $response = $this->createContract()
            ->init([]);
        $this->assertInstanceOf(PaymentMethodResponse::class, $response);
    }

    public function test_can_save_payment_method(): PaymentProviderCustomerAndPaymentMethodPair
    {
        $subjectInstance = $this->createContract();
        $customerData = $subjectInstance->getCustomerData();


        $user_type = null;
        $user_id = null;
        $email = null;
        if ($customerData) {
            $user_type = $customerData->user_type;
            $user_id = $customerData->user_id;
            $email = $customerData->email;
        } else {
            $user_type = 'type-is-test-class';
            $user_id = '21022022';
            $email = 'tester@autepos.com';
            $customerData = new CustomerData(['user_type' => $user_type, 'user_id' => $user_id, 'email' => $email]);
            $subjectInstance->customerData($customerData);
        }



        // Save the payment method
        $response = $subjectInstance->save($this->paymentMethodDataForSave());



        // Check that we have payment method response
        $this->assertInstanceOf(PaymentMethodResponse::class, $response);
        $this->assertTrue($response->success);

        // Check that we have local payment method created
        $paymentProviderCustomerPaymentMethod = $response->getPaymentProviderCustomerPaymentMethod();
        $this->assertTrue($paymentProviderCustomerPaymentMethod->exists);
        $this->assertInstanceOf(PaymentProviderCustomerPaymentMethod::class, $paymentProviderCustomerPaymentMethod);


        $this->assertNotNull($paymentProviderCustomerPaymentMethod->payment_provider_customer_id);
        $this->assertEquals($subjectInstance->getProvider()->getProvider(), $paymentProviderCustomerPaymentMethod->payment_provider);


        return new PaymentProviderCustomerAndPaymentMethodPair(
            $paymentProviderCustomerPaymentMethod->customer,
            $paymentProviderCustomerPaymentMethod
        );
    }



    /**
     * 
     * @depends test_can_save_payment_method
     *
     */
    public function test_can_remove_payment_method(PaymentProviderCustomerAndPaymentMethodPair $pair)
    {


        // Since this is a new test the database would have been refreshed 
        // so we need to re-add items to db.
        PaymentProviderCustomer::factory()
            ->create($pair->paymentProviderCustomer->attributesToArray());
        $paymentProviderCustomerPaymentMethod = PaymentProviderCustomerPaymentMethod::factory()
            ->create($pair->paymentProviderCustomerPaymentMethod->attributesToArray());

        //
        $subjectInstance = $this->createContract();



        // 
        $response = $subjectInstance
            ->remove($paymentProviderCustomerPaymentMethod);

        $this->assertTrue($response->success);

        // It should now be deleted
        $this->assertDatabaseMissing($paymentProviderCustomerPaymentMethod, ['id' => $paymentProviderCustomerPaymentMethod->id]);
    }
}

/**
 * A class that holds a provider payment method model and the corresponding customer model
 */
class PaymentProviderCustomerAndPaymentMethodPair
{
    /**
     *  The corresponding provider customer
     * @var PaymentProviderCustomer
     */
    public $paymentProviderCustomer;

    /**
     * The provider payment method
     * @var PaymentProviderCustomerPaymentMethod 
     */
    public $paymentProviderCustomerPaymentMethod;

    public function __construct(
        PaymentProviderCustomer $paymentProviderCustomer,
        PaymentProviderCustomerPaymentMethod $paymentProviderCustomerPaymentMethod
    ) {
        $this->paymentProviderCustomer = $paymentProviderCustomer;
        $this->paymentProviderCustomerPaymentMethod = $paymentProviderCustomerPaymentMethod;
    }
}
