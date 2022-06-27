<?php

namespace Autepos\AiPayment;

use Illuminate\Support\ServiceProvider;
use Autepos\AiPayment\Providers\CashPaymentProvider;
use Autepos\AiPayment\Contracts\PaymentProviderFactory;
use Autepos\AiPayment\Providers\OfflinePaymentProvider;
use Autepos\AiPayment\Providers\PayLaterPaymentProvider;
use Autepos\AiPayment\Providers\StripeIntent\StripeIntentPaymentProvider;


class AiPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentProviderFactory::class, function ($app) {
            return new PaymentManager($app);
        });

        // Merge config with package default config
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-payment.php', 'ai-payment');
    }

    /**
     * Boot he service provider
     *
     * @return void
     */
    public function boot()
    {



        /**
         * Register default payment providers.
         * We are choosing to register payment providers in this manner to demonstrate 
         * how a programmer can register an arbitrary payment provider.
         */
        $paymentManager = $this->app->make(PaymentProviderFactory::class);

        $paymentManager->extend(OfflinePaymentProvider::PROVIDER, function ($app) {
            return $app->make(OfflinePaymentProvider::class);
        });

        $paymentManager->extend(CashPaymentProvider::PROVIDER, function ($app) {
            return $app->make(CashPaymentProvider::class);
        });

        $paymentManager->extend(PayLaterPaymentProvider::PROVIDER, function ($app) {
            return $app->make(PayLaterPaymentProvider::class);
        });

        $paymentManager->extend(StripeIntentPaymentProvider::PROVIDER, function ($app) {
            return $app->make(StripeIntentPaymentProvider::class);
        });



        /**
         * Load and publish
         */
        if ($this->app->runningInConsole()) {
            //
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            //
            $this->publishes([
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'ai-payment-migrations');


            //
            $this->publishes([
                __DIR__ . '/../config/ai-payment.php' => config_path('ai-payment.php'),
            ], 'ai-payment-config');
        }



        /**
         * Load routes for StripeIntent
         */
        $this->loadRoutesFrom(__DIR__ . '/Providers/StripeIntent/routes/routes.php');
    }
}
