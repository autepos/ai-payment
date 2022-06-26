<?php

namespace Autepos\AiPayment\Tests\Feature\Providers\Contracts\Concerns;



use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;


class RefundUtilTest extends TestCase
{
    

    public function test_can_validate_refund()
    {
        $refund_amount=500;
        $transactionStub = new TransactionStub;

        // Payment provider
        $mockAbstractPaymentProvider = Mockery::mock(PaymentProvider::class)->makePartial();
        $mockAbstractPaymentProvider->shouldReceive('getProvider');

        $transactionStub->refundAMountValidationResult=true;
        $transactionStub->isForPaymentProviderResult=true;
        $result=$mockAbstractPaymentProvider->validateRefund($transactionStub, $refund_amount);
        $this->assertTrue($result);

        //
        $transactionStub->refundAMountValidationResult=false;
        $transactionStub->isForPaymentProviderResult=true;
        $result=$mockAbstractPaymentProvider->validateRefund($transactionStub, $refund_amount);
        $this->assertFalse($result);


        //
        $transactionStub->refundAMountValidationResult=true;
        $transactionStub->isForPaymentProviderResult=false;
        $result=$mockAbstractPaymentProvider->validateRefund($transactionStub, $refund_amount);
        $this->assertFalse($result);

        //
        $transactionStub->refundAMountValidationResult=false;
        $transactionStub->isForPaymentProviderResult=false;
        $result=$mockAbstractPaymentProvider->validateRefund($transactionStub, $refund_amount);
        $this->assertFalse($result);
    }
}
class TransactionStub extends Transaction{
    /**
     * Force refund amount validation result. The validation amount result 
     * will always be this value.
     *
     * @var boolean
     */
    public $refundAMountValidationResult=true;

    /**
     * The result of checking if isForPaymentProvider method  will always be 
     * this value.
     *
     * @var boolean
     */
    public $isForPaymentProviderResult=true;

    public function isValidRefundAmount(int $refund_amount):bool{
        return $this->refundAMountValidationResult;
    }

    public function isForPaymentProvider(string $payment_provider): bool
    {
        return $this->isForPaymentProviderResult;
    }
}