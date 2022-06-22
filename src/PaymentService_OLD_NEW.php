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
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;

/**
 * The payment service is a convenience class of methods for interacting 
 * with payment provider.
 * 
 * The payment service is itself implemented as a payment provider which forwards  
 * most of the calls on it to a set payment provider.
 * 
 * //@method static void tenant(int|string $tenant_id) Set the tenant for the processing.
 * //@method static integer|string getTenant() Get the tenant for the processing.
 */
class PaymentService_OLD_NEW extends PaymentProvider
{
    /**
     * Provider
     *
     * @var string
     */
    protected $provider;



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


    public function getProvider():string{
        return $this->provider;
    }



    /**
     * Get the instance of the provider
     *
     * @return PaymentProvider
     */
    public function providerInstance(): PaymentProvider
    {
        return $this->manager->driver($this->provider)
                ->config($this->config, $this->livemode);
    }

    /**
     * Setup the provider
     *
     */
    public function up(): SimpleResponse
    {
        return $this->providerInstance()
                    ->up();
    }

    /**
     * Reverse provider setup operations
     *
     */
    public function down(): SimpleResponse
    {
        return $this->providerInstance()
                    ->down();
    }

    /**
     * Ping the provider
     *
     */
    public function ping(): SimpleResponse
    {
        return $this->providerInstance()
                    ->ping();
    }


    /**
     * 
     *
     */
    public function init(int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        //TODO: No unit test
        if ($transaction and !$transaction->isUsableFor($this->order)) {
            $transaction=null;
        }
        
        //
        $paymentProvider = $this->providerInstance();
        return $paymentProvider->init($amount, $data, $transaction);
    }

    /**
     * 
     *
     */
    public function cashierInit(Authenticatable $cashier,int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        //TODO: No unit test
        if ($transaction and !$transaction->isUsableFor($this->order)) {
            $transaction=null;
        }
        
        $paymentProvider = $this->providerInstance();
        return $paymentProvider
            ->cashierInit($cashier, $amount, $data, $transaction);


    }


    /**
     * Charge payment
     * @param array $data Arbitrary data for the provider
     */
    public function charge(Transaction $transaction,  array $data = []): PaymentResponse
    {
        //todo: No unit test
        if(!$this->authoriseProviderTransaction($transaction)){
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));
            $paymentResponse->success = false;
            $paymentResponse->errors =$this->hasSameLiveModeAsTransaction($transaction)
            ?['Unauthorised payment transaction with provider']
            :['Livemode mismatch'];
            return $paymentResponse;
        }

        //
        $paymentProvider = $this->providerInstance();
        
        return $paymentProvider
            ->charge($transaction, $data);
    }

    /**
     * Charge payment
     * @param array $data Arbitrary data for the provider
     */
    public function cashierCharge(Authenticatable $cashier, Transaction $transaction,  array $data = []): PaymentResponse
    {
        //TODO: No unit test
        if(!$this->authoriseProviderTransaction($transaction)){
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));
            $paymentResponse->success = false;
            $paymentResponse->errors =$this->hasSameLiveModeAsTransaction($transaction)
            ?['Unauthorised payment transaction with provider']
            :['Livemode mismatch'];
            return $paymentResponse;
        }

        //
        $paymentProvider = $this->providerInstance();
        
        return $paymentProvider
            ->cashierCharge($cashier, $transaction, $data);
    }

    /**
     * Refund payment. If an amount is not provided then the entire amount in the 
     * transaction will be refunded.
     *
     */
    public function refund(Authenticatable $cashier, Transaction $transaction, int $amount = null, string $description = 'duplicate'): PaymentResponse
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('refund'));
        $amount = $amount ?? $transaction->amount;


        //TODO: No unit test for this
        if(!$this->authoriseProviderTransaction($transaction)){
            $paymentResponse->success = false;
            $paymentResponse->errors =$this->hasSameLiveModeAsTransaction($transaction)
            ?['Unauthorised payment transaction with provider']
            :['Livemode mismatch'];
            return $paymentResponse;
        }


        if ($this->validateRefund($transaction, $amount)) {

            return $this->providerInstance()
                ->refund($cashier, $transaction, $amount, $description);
        } else {
            
            $paymentResponse->message = 'Invalid refund. Please check that there is enough fund available';
            $paymentResponse->errors = [
                'Invalid refund',
                'Please check that there is enough fund available'
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

    

    public function validateRefund(Transaction $transaction, int $refund_amount): bool
    {
        return $this->providerInstance()
            ->validateRefund($transaction,$refund_amount);
    }


    public function isCancelable(Transaction $transaction):bool{
        return $this->providerInstance()
            ->isCancelable($transaction);
    }

    public function isRefundable():bool{
        return $this->providerInstance()
            ->isRefundable();
    }

    // /**
    //  * Check if the refund is valid
    //  *  TODO: is this method not meant to be named validateRefund(...)?
    //  * @return boolean
    //  */
    // public function validateTransaction(Transaction $transaction, int $amount): bool
    // {
    //     return $this->providerInstance()
    //         ->config($this->config, $this->livemode)
    //         ->validateRefund($transaction, $amount);
    // }

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



    //     /**
    //  * Forward static calls to payment provider
    //  *
    //  * @param string $name
    //  * @param array $arguments
    //  * @return mixed
    //  */
    // public static function __callStatic($name, $arguments)
    // {
    //     return PaymentProvider::$name(...$arguments);
    // }
}
// $ps=new PaymentService(app()->make(PaymentProviderFactory::class));
// $ps->provider('stripe_intent')
// ->config([])
// ->order(new \App\Models\Order)
// ->init();
