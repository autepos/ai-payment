<?php

namespace Autepos\AiPayment;



use Autepos\AiPayment\Models\PaymentProviderCustomer;

class CustomerResponse extends BaseResponse
{



    /**
     * The PaymentProviderCustomer object.
     *
     * @var null|PaymentProviderCustomer
     */
    private $paymentProviderCustomer = null;


    /**
     * Set the PaymentProviderCustomer.
     *
     * @param  PaymentProviderCustomer  $paymentProviderCustomer
     */
    public function paymentProviderCustomer(PaymentProviderCustomer $paymentProviderCustomer): self
    {
        $this->paymentProviderCustomer = $paymentProviderCustomer;

        return $this;
    }

    /**
     * Get the PaymentProviderCustomer object.
     */
    public function getPaymentProviderCustomer(): ?PaymentProviderCustomer
    {
        return $this->paymentProviderCustomer;
    }


    /**
     * Convert the instance to array
     */
    protected function toArray(): array
    {
        $data = parent::toArray();


        if ($this->paymentProviderCustomer) {
            $data['payment_provider_customer'] = [
                'id' => $this->paymentProviderCustomer->id,
                'user_type' => $this->paymentProviderCustomer->user_type,
                'user_id' => $this->paymentProviderCustomer->user_id,
                'payment_provider' => $this->paymentProviderCustomer->payment_provider,
                'payment_provider_customer_id' => $this->paymentProviderCustomer->payment_provider_customer_id,
            ];
        }

        return $data;
    }
}
