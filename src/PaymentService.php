<?php

namespace Autepos\AiPayment;

use Money\Money;
use Money\Currency;
use NumberFormatter;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;



/**
 * The payment service is a convenience class of methods for interacting 
 * with payment provider.
 * 
 * @method static void tenant(int|string $tenant_id) Set the tenant for the processing.
 * @method static integer|string getTenant() Get the tenant for the processing.
 */
class PaymentService
{
   

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
     * Retrieve provider
     *
     */
    public function provider(string $provider): PaymentProvider
    {
        return $this->manager->driver($provider);

    }

    
    
    
    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     * 
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
     * Forward static calls to payment provider
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return PaymentProvider::$name(...$arguments);
    }
}
