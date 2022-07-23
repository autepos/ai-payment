<?php

namespace Autepos\AiPayment;


use Autepos\AiPayment\Models\PaymentProviderCustomerPaymentMethod;

class PaymentMethodResponse extends BaseResponse
{



    /**
     * The PaymentProviderCustomerPaymentMethod object.
     *
     * @var null|PaymentProviderCustomerPaymentMethod
     */
    private $paymentProviderCustomerPaymentMethod = null;





    /**
     * Set the PaymentProviderCustomerPaymentMethod.
     *
     * @param  PaymentProviderCustomerPaymentMethod  $paymentProviderCustomerPaymentMethod
     */
    public function paymentProviderCustomerPaymentMethod(PaymentProviderCustomerPaymentMethod $paymentProviderCustomerPaymentMethod): self
    {
        $this->paymentProviderCustomerPaymentMethod = $paymentProviderCustomerPaymentMethod;

        return $this;
    }

    /**
     * Get the PaymentProviderCustomerPaymentMethod object.
     */
    public function getPaymentProviderCustomerPaymentMethod(): ?PaymentProviderCustomerPaymentMethod
    {
        return $this->paymentProviderCustomerPaymentMethod;
    }


    /**
     * Convert the instance to array
     */
    protected function toArray(): array
    {
        $data = parent::toArray();


        if ($this->paymentProviderCustomerPaymentMethod) {
            $data['payment_provider_customer_payment_method'] = [
                'pid' => $this->paymentProviderCustomerPaymentMethod->pid,
                'payment_provider' => $this->paymentProviderCustomerPaymentMethod->payment_provider,
                'payment_provider_payment_method_id' => $this->paymentProviderCustomerPaymentMethod->payment_provider_payment_method_id,
            ];
        }

        return $data;
    }
}
