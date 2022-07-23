<?php

namespace Autepos\AiPayment;

use Money\Money;
use Money\Currency;
use NumberFormatter;
use Ramsey\Uuid\Uuid;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Autepos\AiPayment\Models\Transaction;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Providers\Contracts\ProviderCustomer;
use \Autepos\AiPayment\Exceptions\LivemodeMismatchException;
use Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod;
use \Autepos\AiPayment\Exceptions\TransactionPaymentProviderMismatchException;

/**
 * The payment service is a convenience class of methods for interacting 
 * with payment provider.
 * 
 * The payment service is itself implemented as a payment provider which forwards  
 * most of the calls on it to a set payment provider.
 * 
 */
class PaymentService extends PaymentProvider
{
    /**
     * The payment service library version.
     *
     * @var string
     */
    const VERSION = '1.0.0-beta4';
    
    /**
     * Provider
     *
     * @var string
     */
    protected $provider;

    /**
     * The custom configuration function.
     *
     * @var callable
     */
    protected static $getConfigUsing;

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
     * Set the provider
     *
     */
    public function provider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }


    public function getProvider(): string
    {
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
     * Set the configuration function
     *
     * @param  callable  $callback
     * @return void
     */
    public static function getConfigUsing(callable $callback){
        static::$getConfigUsing = $callback;
    
    }
    /** 
     * @todo unit test
     * Get config and livemode for the given payment provider using a configuration 
     * function defined by the programmer.
     * 
     * This method is useful when the provider need to automatically set the config and 
     * livemode such as when handling a webhook.
     * 
     * @param string $payment_provider
     * @param mixed $tenant_id
     * 
     * @return array [config(array),livemode(bool)]
     * @throws \InvalidArgumentException if static::$getConfigUsing returns an invalid value.
     */
    public static function getConfigUsingFcn(string $payment_provider,$tenant_id){
        
        if (static::$getConfigUsing) {

            [$config,$livemode] =call_user_func(static::$getConfigUsing, $payment_provider,$tenant_id);

            // Validate return value.
            if(is_array($config) and is_bool($livemode)){
                return [$config,$livemode];
            }else{
                throw new \InvalidArgumentException('The user function, getConfigUsing, must return return an array with config(array) as first item and livemode(bool) as second, i.e. array(array(),bool)');
            }
        }
        return [[],false];
    }


    public function up(): SimpleResponse
    {
        return $this->providerInstance()
            ->up();
    }


    public function down(): SimpleResponse
    {
        return $this->providerInstance()
            ->down();
    }


    public function ping(): SimpleResponse
    {
        return $this->providerInstance()
            ->ping();
    }



    public function init(int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        $paymentProvider = $this->providerInstance();


        if ($transaction and !$paymentProvider->isTransactionUsableFor($transaction, $this->order)) {
            $transaction = null;
        }

        //

        return $paymentProvider->order($this->order)
            ->init($amount, $data, $transaction);
    }


    public function cashierInit(Authenticatable $cashier, int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        $paymentProvider = $this->providerInstance();


        if ($transaction and !$paymentProvider->isTransactionUsableFor($transaction, $this->order)) {
            $transaction = null;
        }

        //
        return $paymentProvider->order($this->order)
            ->cashierInit($cashier, $amount, $data, $transaction);
    }


    /**
     * Make a charge as a customer
     * @param array $data Arbitrary data for the provider
     */
    public function charge(Transaction $transaction,  array $data = []): PaymentResponse
    {
        //
        $paymentProvider = $this->providerInstance();


        if (!$paymentProvider->authoriseProviderTransaction($transaction)) {
            // Instead of returning a response here, we are going to throw an exception 
            // to indicate the magnitude of the concern that this authorisation fails  
            // during customer charge. Throwing an exception also allows a log to be  
            // created by the caller for later inspection since the cashier is not in  
            // the loop when a customer is performing a charge.
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));
            $paymentResponse->success = false;
            $paymentResponse->message = "Transaction-provider authorisation error";

            if ($paymentProvider->hasSameLiveModeAsTransaction($transaction)) {
                $paymentResponse->errors = ['Unauthorised payment transaction with provider'];
                throw TransactionPaymentProviderMismatchException::factory(
                    $paymentResponse->message,
                    $transaction,
                    $paymentProvider,
                    $paymentResponse
                );
            } else {
                $paymentResponse->errors = ['Livemode mismatch'];
                throw LivemodeMismatchException::factory(
                    $paymentResponse->message,
                    $transaction,
                    $this->order,
                    $paymentProvider,
                    $paymentResponse
                );
            }
        }

        // Order is not required to make charge but if the programmer has made the order 
        // available, we will do as we are told and go ahead and pass it to the provider.
        // NOTE: It is up to the programmer to ensure that the the given order is correct for 
        // the given transaction, since it is the programmer who knows better why an 
        // order is available during charge.
        if (!is_null($this->order)) { // TODO: perhaps help the programmer to check whether the order is for the transaction? But the thing is that we do not want to encourage providing the order during charge?
            $paymentProvider = $paymentProvider->order($this->order);
        }

        //
        return $paymentProvider
            ->charge($transaction, $data);
    }

    /**
     * Make charge as a cashier
     * @param array $data Arbitrary data for the provider
     */
    public function cashierCharge(Authenticatable $cashier, Transaction $transaction,  array $data = []): PaymentResponse
    {
        //
        $paymentProvider = $this->providerInstance();


        if (!$paymentProvider->authoriseProviderTransaction($transaction)) {
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));
            $paymentResponse->success = false;
            $paymentResponse->message = "Transaction-provider authorisation error";
            $paymentResponse->errors = $paymentProvider->hasSameLiveModeAsTransaction($transaction)
                ? ['Unauthorised payment transaction with provider']
                : ['Livemode mismatch'];
            return $paymentResponse;
        }

        // Order is not required to make charge but if the programmer has made the order 
        // available, we will do as we are told and go ahead and pass it to the provider.
        // NOTE: It is up to the programmer to ensure that the the given order is correct for 
        // the given transaction, since it is the programmer who knows better why an 
        // order is available during charge.
        if (!is_null($this->order)) { // TODO: perhaps help the programmer to check whether the order is for the transaction? But the thing is that we do not want to encourage providing the order during charge?
            $paymentProvider = $paymentProvider->order($this->order);
        }


        //
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
        //
        $paymentProvider = $this->providerInstance();

        $paymentResponse = new PaymentResponse(PaymentResponse::newType('refund'));
        $amount = $amount ?? $transaction->amount;


        if (!$paymentProvider->authoriseProviderTransaction($transaction)) {
            $paymentResponse->success = false;
            $paymentResponse->message = "Transaction-provider authorisation error";
            $paymentResponse->errors = $paymentProvider->hasSameLiveModeAsTransaction($transaction)
                ? ['Unauthorised payment transaction with provider']
                : ['Livemode mismatch'];
            return $paymentResponse;
        }


        if ($paymentProvider->validateRefund($transaction, $amount)) {

            return $paymentProvider
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
        $paymentProvider = $this->providerInstance();

        if (!$paymentProvider->authoriseProviderTransaction($transaction)) {
            $paymentResponse = new PaymentResponse(PaymentResponse::newType('sync'));
            $paymentResponse->success = false;
            $paymentResponse->message = "Transaction-provider authorisation error";
            $paymentResponse->errors = $paymentProvider->hasSameLiveModeAsTransaction($transaction)
                ? ['Unauthorised payment transaction with provider']
                : ['Livemode mismatch'];
            return $paymentResponse;
        }

        return $paymentProvider
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
            ->validateRefund($transaction, $refund_amount);
    }


    public function isCancelable(Transaction $transaction): bool
    {
        return $this->providerInstance()
            ->isCancelable($transaction);
    }

    public function isRefundable(): bool
    {
        return $this->providerInstance()
            ->isRefundable();
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

    /**
     * Generate the public id for an entity.
     */
    public static function generatePid():string{
        return Uuid::uuid4()->toString();
    }
}
