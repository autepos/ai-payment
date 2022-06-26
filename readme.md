# Payment
## Usage
To obtain payment service, build it through the container so its dependencies are auto resolved:
```php
$paymentService=app(\Autepos\AiPayment\PaymentService::class)
```

### Orderable
Whenever an order is required in a payment operation, you should provide a class that implements the \Autepos\AiPayment\Providers\Contract\Orderable interface.

### Init
Initialise payment operation:
```php
$order=new Order; // Order must implement \Autepos\AiPayment\Providers\Contract\Orderable interface

$amount=100;
$paymentResponse = $paymentService->provider('stripe_intent')
                                ->init($order,$amount);

$transaction=$paymentResponse->transaction;
```
By a cashier on behalf of a customer,
```php
$cashier=\App\Models\Admin::find(1);//Authenticatable
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->cashierInit($cashier,$order,$amount);
```

### Charge
Charge a transaction
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->charge($transaction);//$transaction may be returned during init above
```
By a cashier on behalf of a customer,
```php
$cashier=\App\Models\Admin::find(1);//Authenticatable
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->cashierCharge($cashier,$transaction);
```

### Refund
Refund a payment
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->refund($transaction);
```

### Sync transaction
Sync local data with data held by the provider:
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->syncTransaction($transaction);
```

### General operations
Run setup scripts
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->up();
```

Run reverse of setup scripts
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->down();
```

Ping the provider
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->ping();
```

### Listening to transactions events
Transactions are recorded in an Eloquent model: \Autepos\AiPayment\Models\Transaction. 
Therefore you can listen to the events normally. Additionally whenever a Transaction is saved  \Autepos\AiPayment\Events\OrderableTransactionsTotaled event is dispatched. This event has information for the related orderable and the current total amount summed from all applicable transactions. You can Listen to these events to update the corresponding orderable:
```php
class OnOrderableTransactionsTotaled
{
    /**
     * Handle the event.
     */
    public function handle(OrderableTransactionsTotaled $event)
    {
        $order=\App\Models\Order::find($event->orderable_id);
        $order->total_paid=$event->total_paid;
        $order->save();
    }
}
```
## Adding an additional payment provider
Define payment provider class e.g for Bitcoin payment:
```php
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class BitcoinPaymentProvider implements PaymentProvider{
    ...
}
```

In a service provider, register the payment provider
```php
$paymentManager=$this->app->make(\Autepos\AiPayment\Contracts\PaymentProviderFactory::class);
$paymentManager->extend('bitcoin', function($app){
    return $app->make(BitcoinPaymentProvider::class);
});
```
That is it, but if you are using the provider in Autepos you should add a record for it:
```php
$pm= new \Autepos\Models\PaymentMethod;
$pm->provider='bitcoin';// unique_provider_tag
$pm->provider_name='Name of provider';
$pm->name='Bitcoin';// What Customers will see
$pm->... e.g. delivery types etc
$provider->save();
```

## Eloquent Models
Since the following are pure Eloquent models, you are free to use them as so.
- **Transaction**: Payment providers should manage transactions using this model. The host app should use this model to determine all payments made for an orderable.
- **PaymentProviderCustomer**:Payment providers should sync their remotely held customers with this model. Host app can use the model to manage a customer's saved details for a payment provider e.g $paymentProviderCustomer->paymentMethods which provides relationship for a saved payment methods;
- **PaymentProviderCustomerPaymentMethod** : Payment providers should sync their remotely  held customer payment methods with this model. The host app can access this model to interact with registered customers' saved payment methods when related with ``PaymentProviderCustomer``.


## Default providers
### Offline
This is Offline payment. It should be used when payment is to be made outside of the system.
```php
// Retrieve instance with payment service
$paymentProvider=$paymentService->provider('offline');
```
### Cash
Cash specific offline payment.
```php
// Retrieve instance with payment service
$paymentProvider=$paymentService->provider('cash');
```
### Pay Later
This is a really simple implementation of payment provider to be used to give a customer an option to pay later.
```php
// Retrieve instance with payment service
$paymentProvider=$paymentService->provider('pay_later');
```
### Stripe intent
This provider Stripe intent payment services.
```php
// Retrieve instance with payment service
$paymentProvider=$paymentService->provider('stripe_intent');
```

## Tenancy
Tenancy is supported but it is disabled by default. You can enable tenancy by setting a 'ai-payment.tenancy' config thus:
```php
....
/**
 * Payment provider
 */
'ai_payment'=>[
    'tenancy'=>[
        'enable_multi_tenant'=>true,
        'column_name'=>'tenant_id',
        'is_column_type_integer'=>true,
        'default'=>1,// Should be neural ID that does not belong to any client when tenancy is in use. If tenancy is not in use then all data is stored with this value.
        'global_scope_name'=>'simple_tenancy_tenant_id',// Eloquent global scope name for scoping related queries
    ]
],
...
```

You should always as the first thing before interacting with Payment provider or Models  of payment provider( eg. model Transaction, etc) call:
```php
\Autepos\AiPayment\Tenancy\Tenant::set('current_tenant_id');
```
Alternatively you can set tenant through the payment Service, thus
```php
\Autepos\AiPayment\PaymentService::tenant('current_tenant_id');
```


### Vue component of the payment provider for Autepos
Every payment provider should provider two view components. The components may be registered globally so that they can be loaded by Payment.vue/Refund.vue (or if exists, PaymentService.vue/PaymentServiceRefund.vue), the vue components of payment service. One of the required components is for processing payment while the other is for refund. Find examples of these in Autepos vue components. 

The registered name of the Payment processing component is suggested to follow the following convention: 
```{provider}PaymentProvider``` where {provider} replaced with the unique provider tag/driver in Pascal case. Example, the Stripe intent with the unique tag/driver of stripe_intent should have its component registered as `StripeIntentPaymentProvider`.

The registered name of the Refund component should follow a similar convention with the only different being that the word **Refund** appears at the end. So for the Stripe intent we will have, `StripeIntentPaymentProviderRefund`

## Testing
Run the tests from the command line 
```
php vendor/phpunit/phpunit/phpunit
```
or
```
composer test
```

## TODO
### Renaming 'init' and 'charge' to 'create' and 'confirm' respectively:
**CONSIDER:** *With the new names `create` and `confirm` it become a little unnatural that we have refund() and syncTransactions within the same namespace the new names. For e.g. is the confirm() for the refund() or the create(). Of course it is for create() but it is not immediately obvious. So although the new names may improve the feel of the api further restructuring may be required to get the full benefit*
1. PaymentProvider contract: rename init() to create() and charge() to confirm()
2. Go through all payment providers to rename init() to create() and charge() to confirm(). 
3. Update phpunit tests in PaymentProviderTest to change references of 'init' and 'charge' to 'create' and 'confirm' respectively.
4. Update phpunit tests of all payment providers to change references of 'init' and 'charge' to 'create' and 'confirm' respectively.
5. Update Cart::chargePayment() and Cart::initPayment() to change references of 'init' and 'charge' to 'create' and 'confirm' respectively.
6. Update app\Http\Controllers\Json\Checkout\Payment\Payment.php to change references of 'init' and 'charge' to 'create' and 'confirm' respectively.
7. In routes/web.php, update the api routes under "Checkout - Payment - payment"  to change references of 'init' and 'charge' to 'create' and 'confirm' respectively.
8. Update payment.md to change references to 'init' and 'charge' to 'create' and 'confirm' respectively.