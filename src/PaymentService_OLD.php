<?php

namespace Autepos\AiPayment;

use Money\Money;
use Money\Currency;
use NumberFormatter;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Contracts\CustomerData;
use Autepos\AiPayment\Providers\Contracts\Orderable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;

/**
 * The payment service is a convenience class of methods for interacting 
 * with payment provider.
 * 
 * @method \Autepos\AiPayment\Contracts\CustomerData|null getCustomerData() Get the effective customer data
 * @method \Autepos\AiPayment\Providers\Contracts\PaymentProvider customerData(\Autepos\AiPayment\Contracts\CustomerData $customerData) Set customer data to override that provided by orderable.
 * @method static void tenant(int|string $tenant_id) Set the tenant for the processing.
 * @method static integer|string getTenant() Get the tenant for the processing.
 */
class PaymentService_OLD
{
    /**
     * Provider
     *
     * @var string
     */
    protected $provider;

    /**
     * The configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * The livemode
     *
     * @var bool
     */
    protected $livemode = true;


    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Payment Manager.
     *
     * @var PaymentProviderFactory
     */
    protected $manager;

    public function __construct(PaymentProviderFactory $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Set te provider
     *
     */
    public function provider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Set configurations
     */
    public function config(array $config, bool $livemode): self
    {
        $this->config = $config;
        $this->livemode = $livemode;

        return $this;
    }


    /**
     * Get the instance of the provider
     *
     * @return PaymentProvider
     */
    public function providerInstance(): PaymentProvider
    {
        return $this->manager->driver($this->provider);
    }

    /**
     * Setup the provider
     *
     */
    public function up(): SimpleResponse
    {
        return $this->providerInstance()
                    ->config($this->config, $this->livemode)
                    ->up();
    }

    /**
     * Reverse provider setup operations
     *
     */
    public function down(): SimpleResponse
    {
        return $this->providerInstance()
                    ->config($this->config, $this->livemode)
                    ->down();
    }

    /**
     * Ping the provider
     *
     */
    public function ping(): SimpleResponse
    {
        return $this->providerInstance()
                    ->config($this->config, $this->livemode)
                    ->ping();
    }


    /**
     * Initiate payment
     *
     */
    public function init(Orderable $order, int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {

        $paymentProvider = $this->providerInstance();
        return $paymentProvider->order($order)
            ->config($this->config, $this->livemode)
            ->init($amount, $data, $transaction);
    }

    /**
     * Initiate payment
     *
     */
    public function cashierInit(Authenticatable $cashier, Orderable $order, int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {

        $paymentProvider = $this->providerInstance();
        return $paymentProvider->order($order)
            ->config($this->config, $this->livemode)
            ->cashierInit($cashier, $amount, $data, $transaction);


    }


    /**
     * Charge payment
     * @param array $data Arbitrary data for the provider
     */
    public function charge(Transaction $transaction, Orderable $order = null, array $data = []): PaymentResponse
    {

        $paymentProvider = $this->providerInstance();
        if ($order) {
            $paymentProvider=$paymentProvider->order($order);
        }
        return $paymentProvider->config($this->config, $this->livemode)
            ->charge($transaction, $data);
    }

    /**
     * Charge payment
     * @param array $data Arbitrary data for the provider
     */
    public function cashierCharge(Authenticatable $cashier, Transaction $transaction, Orderable $order = null, array $data = []): PaymentResponse
    {
        $paymentProvider = $this->providerInstance();
        if ($order) {
            $paymentProvider=$paymentProvider->order($order);
        }
        return $paymentProvider->config($this->config, $this->livemode)
            ->cashierCharge($cashier, $transaction, $data);
    }

    /**
     * Refund payment. If an amount is not provided then the entire amount in the 
     * transaction will be refunded.
     *
     */
    public function refund(Authenticatable $cashier, Transaction $transaction, int $amount = null, string $description = 'duplicate'): PaymentResponse
    {
        $amount = $amount ?? $transaction->amount;

        if ($this->validateRefund($transaction, $amount)) {

            return $this->providerInstance()
                ->config($this->config, $this->livemode)
                ->refund($cashier, $transaction, $amount, $description);
        } else {
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('refund'));
            $paymentResponse->message = 'Invalid refund. Please check that their is enough fund available';
            $paymentResponse->errors = [
                'Invalid refund',
                'Please check that their enough fund available'
            ];
            return $paymentResponse;
        }
    }

    /**
     * Sync local transaction data with the data held by the provider.
     *
     */
    public function syncTransaction(Transaction $transaction): PaymentResponse
    {
        return $this->providerInstance()
            ->config($this->config, $this->livemode)
            ->syncTransaction($transaction);
    }

    /**
     * Get a payment provider's customer instance.
     *
     */
    public function customer(): ?ProviderCustomer
    {
        return $this->providerInstance()
            ->customer();
    }

    /**
     * Get a payment provider's payment method instance.
     *
     */
    public function paymentMethod(CustomerData $customerData): ?ProviderPaymentMethod
    {
        return $this->providerInstance()
            ->paymentMethod($customerData);
    }

    /**
     * Check if the refund is valid
     *  TODO: is this method not meant to be named validateRefund(...)?
     * @return boolean
     */
    public function validateTransaction(Transaction $transaction, int $amount): bool
    {
        return $this->providerInstance()
            ->config($this->config, $this->livemode)
            ->validateRefund($transaction, $amount);
    }
    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public static function formatAmount($amount, $currency = null, $locale = null)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency);
        }

        $money = new Money($amount, new Currency(strtoupper($currency ?? config('app.currency', 'gbp'))));

        $locale = $locale ?? config('app.currency_locale', 'en');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    //     /**
    //  * Forward calls to the underlying payment provider
    //  *
    //  * @param string $name
    //  * @param array $arguments
    //  * @return mixed
    //  */
    // public function __call($name, $arguments)
    // {
    //     return $this->providerInstance()->{$name}(...$arguments);
    // }

    // /**
    //  * Forward static calls to payment provider
    //  *
    //  * @param string $name
    //  * @param array $arguments
    //  * @return mixed
    //  */
    // public function __callStatic($name, $arguments)
    // {
    //     return PaymentProvider::$name(...$arguments);
    // }
}
