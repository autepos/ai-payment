<?php

namespace Autepos\AiPayment\Tests\ContractTests;

use Autepos\AiPayment\ResponseType;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;

/**
 * Defines the most BASIC tests a \Autepos\AiPayment\Providers\Contracts\ProviderCustomer 
 * implementation must pass.
 * 
 * @see \Autepos\AiPayment\Tests\ContractTests\PaymentProviderContractTest to get idea on usage.
 * 
 * TODO: These tests have themselves not been tested
 */
class ProviderCustomerContractTest
{
    use ContractTestBase;
    
    protected $subjectContract=ProviderCustomer::class;

    public function test_can_create_customer()
    {
        $customerData = new CustomerData(['user_type' => 'test-user', 'user_id' => '1', 'email' => 'test@test.com']);
        
        $subjectInstance=$this->subjectInstanceOrFail($this->subjectContract);
        $response = $subjectInstance->create($customerData);


        $this->assertInstanceOf(CustomerResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_SAVE, $response->getType()->getName());
        $this->assertTrue($response->success);

        // Check that it was created in db locally
        $paymentProviderCustomer = $response->getPaymentProviderCustomer();
        $this->assertTrue($paymentProviderCustomer->exists);

        return $paymentProviderCustomer;
    }


    /**
     * @depends test_can_create_customer
     *
     */
    public function test_can_delete_customer(ProviderCustomer $paymentProviderCustomer)
    {
        // We have to save this again as migration may be freshing every test.
        $paymentProviderCustomer = PaymentProviderCustomer::factory()
        ->create($paymentProviderCustomer->attributesToArray());
        


        // Delete it
        $subjectInstance=$this->subjectInstanceOrFail($this->subjectContract);
        $response = $subjectInstance->delete($paymentProviderCustomer);

        $this->assertInstanceOf(CustomerResponse::class, $response);
        $this->assertEquals(ResponseType::TYPE_DELETE, $response->getType()->getName());
        $this->assertTrue($response->success);

        // Check that it is removed locally
        $this->assertDatabaseMissing(new PaymentProviderCustomer(), [
            'id' => $paymentProviderCustomer->id,
        ]);

    }
}
