<?php

namespace Autepos\AiPayment\Providers\Contracts;


use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\Tenancy\Tenant;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Providers\Contracts\Concerns\RefundUtil;
use Autepos\AiPayment\Providers\Contracts\Concerns\Configuration;
use Autepos\AiPayment\Providers\Contracts\Concerns\InteractWithTransaction;

abstract class PaymentProvider
{

    use Configuration;
    use InteractWithTransaction;
    use RefundUtil;

    /**
     * The order to process.
     *
     * @var \Autepos\AiPayment\Providers\Contracts\Orderable
     */
    protected $order;

    /**
     * Customer data that overrides that provided by orderable. i.e. when this is set then the 
     * the underlying orderable will not be called for customer data.
     *
     * @var \Autepos\AiPayment\Contracts\CustomerData
     */
    protected $customerData;



    /**
     * Set customer data directly to override that provided by orderable.
     */
    public function customerData(CustomerData $customerData): self
    {
        $this->customerData = $customerData;
        return $this;
    }

    /**
     * Get the effective customer data.
     */
    protected function getCustomerData(): ?CustomerData
    {
        return $this->customerData ?? optional($this->order)->getCustomer();
    }

    /**
     * Set the order.
     *
     * @param  \Autepos\AiPayment\Providers\Contracts\Orderable  $order
     * @return self
     */
    public function order(Orderable $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Run script to setup the provider
     */
    public abstract function up(): SimpleResponse;

    /**
     * Run scripts to reverse the setup scripts of the provider
     */
    public abstract function down(): SimpleResponse;

    /**
     * Test the connection with the provider
     */
    public abstract function ping(): SimpleResponse;


    /**
     * Customer: Initiate payment process using an order.
     *
     * @param int $amount A specific orderable amount
     * @param Transaction $transaction A transaction suggested to be used for the process. But there is no obligation to use this suggested transaction
     */
    public abstract function init(int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse;

    /**
     * Administrator: Initiate payment process using an order.
     *
     * @param int $amount A specific orderable amount
     * @param Transaction $transaction A transaction suggested to be used for the process. But there is no obligation to use this suggested transaction.
     */
    public function cashierInit(Authenticatable $cashier, int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        //
        $response = $this->init($amount, $data, $transaction);

        //
        $responseTransaction = $response->getTransaction();
        if ($responseTransaction) {
            $responseTransaction->cashier_id = $cashier->getAuthIdentifier();
            $responseTransaction->save();
        }
        return $response;
    }



    /**
     * Customer: Create a payment charge using, if any, $data from provider.
     */
    public abstract function charge(Transaction $transaction, array $data = []): PaymentResponse;

    /**
     * Administrator: Create a payment charge using, if any, $data from provider.
     */
    public function cashierCharge(Authenticatable $cashier, Transaction $transaction, array $data = []): PaymentResponse
    {
        $transaction->cashier_id = $cashier->getAuthIdentifier();
        return $this->charge($transaction, $data);
    }



    /**
     * Administrator: Refund a payment.
     *
     * @param int $amount An amount to be refunded
     * @param  string  $description The reason for the refund
     */
    public abstract function refund(Authenticatable $cashier, Transaction $transaction, int $amount, string $description): PaymentResponse;




    /**
     * Get the name of the provider.
     */
    public abstract function getProvider(): string;


    /**
     * Get customer implementation
     */
    public function customer(): ?ProviderCustomer
    {
        return null;
    }

    /**
     * Get payment method implementation
     */
    public function paymentMethod(CustomerData $customerData): ?ProviderPaymentMethod
    {
        return null;
    }

    /**
     * Set the current tenant
     * 
     * @param integer|string $tenant_id
     */
    public static function tenant($tenant_id)
    {
        Tenant::set($tenant_id);
    }

    /**
     * Get the current tenant
     * @return integer|string
     */
    public static function getTenant()
    {
        return Tenant::get();
    }
}
