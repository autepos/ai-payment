# Introduction
AiPayment is a uniform payment interface for Laravel that simplifies payment mechanisms that allow you to implement embeddable UI components that delegate the handling of sensitive  payment data to your payment provider for security purposes. You can keep customers on your application during payment transaction without handling their sensitive payment details.

In the ideal process for AiPayment, payment initialisation is started on the server side which produces an output required to complete the payment process in the frontend. The server is then notified of the completion either through webhook from the provider or directly by the frontend. Since the notification from the frontend cannot be trusted, the provider is contacted by the server for official confirmation of the payment status. Stripe Intent is fully implemented as a provider to demonstrated the full all of these.

The following are the standout features of AiPayment:
- Delegate the handling of sensitive data to your provider
- Split a payment into multiple payments
- Accept payments using multiple providers for a single order (e.g. Offline/Cash + Stripe + etc.)
- Refund all or only part of a transaction 
- Save payment methods for reuse
- Ping provider to verify your integration status
- Dynamically change your payment provider configuration
- Dynamically switch between live and test modes
- Multi-tenancy support
- Implement custom payment providers.

## Installation
```
composer require autepos/ai-payment
php artisan migrate
```

## Usage
To obtain payment service, build it through the container so its dependencies are auto resolved:
```php
$paymentService=app(\Autepos\AiPayment\PaymentService::class)
```

### Init
Initialise a full order payment operation:
```php
/**
 * @var \Autepos\AiPayment\Providers\Contracts\Orderable 
 */ 
$order=new Order;

//
$config=[
    'test_publishable_key'=>'...',
    'test_secret_key'=>'...'
    'webhook_secret'=>'...',
]
$livemode=false;
$paymentResponse = $paymentService->provider('stripe_intent')
                                ->config($config,$livemode)
                                ->order($order)
                                ->init();
```
The payment response object has the details required, in this case, to complete the payment in the frontend. So ```$paymentResponse``` can be serialised and returned to the frontend. The initialisation operation is recorded in a transaction model which can be accessed from the response object,
```php
$transaction=$paymentResponse->getTransaction();
```
That is all. Alternatively, you can initialise part/split order payment operation:
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                ->config($config)
                                ->order($order)
                                ->init(100);


```
Also a cashier can initialise payment on behalf of a customer,
```php
/**
 * @var \Illuminate\Contracts\Auth\Authenticatable
 */ 
$cashier=\App\Models\Admin::find(1);

//
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->order($order)
                                    ->cashierInit($cashier);
```

### Charge
 After the payment is initialised, the next thing is to create a charge,
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->charge($transaction);//$transaction may be returned during init above
```
A cashier can also create the charge on behalf of a customer,
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->cashierCharge($cashier,$transaction);
```

### Refund
A successful charge can be refunded,
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->refund($transaction);
```

### Sync transaction
The local charge data, a.k.a transaction can be syncronised with the corresponding data held by the provider,
```php
$paymentResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->syncTransaction($transaction);
```

### General operations
Run setup scripts
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->up();
```

Run reverse of setup scripts
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->down();
```

Ping the provider
```php
$simpleResponse = $paymentService->provider('stripe_intent')
                                    ->config($config)
                                    ->ping();
```

### Listening to transactions events
Transactions are recorded in an Eloquent model: \Autepos\AiPayment\Models\Transaction. 
Therefore you can listen to the events normally. Additionally whenever a Transaction is saved  \Autepos\AiPayment\Events\OrderableTransactionsTotaled event is dispatched. This event has information for the related orderable and the current total amount summed from all applicable transactions. You can Listen to these events to update the corresponding orderable:
```php
class OnOrderableTransactionsTotaled
{
    use \App\Models\Order;

    /**
     * Handle the event.
     */
    public function handle(OrderableTransactionsTotaled $event)
    {
        $order=Order::find($event->orderable_id);
        $order->total_paid=$event->total_paid;
        $order->save();
    }
}
```

## Provider customer
A payment provider can implement a customer which can be retrieved as follows:
```php
/**
 * @var \Autepos\AiPayment\Providers\Contracts\ProviderCustomer
 */ 
$providerCustomer=$paymentService->provider('stripe_intent')
->config($config)
->customer();
```

The methods exposed by the provider customer allows for creating a customer with the payment provider to link the local account details. For example, to create a Stripe customer for a local user, you can do the following:

```php
use \Autepos\AiPayment\Contracts\CustomerData;

$customerData=new CustomerData([
    'user_id'=>1,
    'user_type'=>'customer'
]);
$customerResponse=$providerCustomer->create($customerData);
$paymentProviderCustomer=$customerResponse->getPaymentProviderCustomer();
```
That's it. This will create a local record linked with a newly created Stripe customer object. The object ```$paymentProviderCustomer``` is your new local record of the customer. It is an Eloquent model introduced later. You can delete the customer by thus:

```php
$customerResponse=$providerCustomer->delete($paymentProviderCustomer);
if($customerResponse->success){
    // both local and Stripe records have been removed
}
```




## Provider payment method
A payment provider can implement a payment method which can be retrieved as follows:
```php
use \Autepos\AiPayment\Contracts\CustomerData;

$customerData=new CustomerData([
    'user_id'=>null,
    'user_type'=>'guest'
])

/**
 * @var \Autepos\AiPayment\Providers\Contracts\ProviderPaymentMethod
 */ 
$providerPaymentMethod=$paymentService->provider('stripe_intent')
->config($config)
->paymentMethod($customerData);
```
The method exposed by the provider payment method allow you to save a payment method for a customer for reuse in a future payment. To save a payment method, you will need to provided any data required by the payment method implementation. For Stripe intent, the required data is the payment method id.

```php
$data=['payment_method_id'=>'pm_1LFRDP2eZvKYlo2C6NTvKmDS'];
$paymentMethodResponse=$providerPaymentMethod->save($data);
$paymentProviderCustomerPaymentMethod=$paymentMethodResponse->getPaymentProviderCustomerPaymentMethod();
```
Note that the API saves rather create a new payment method. The creation of the payment can be handled elsewhere or in the frontend. The creation process should acquire the $data input required to save the payment method against the customer. If the creation processing requires a server-side initialisation, this can be performed using the ```init()``` method of the ```$providerPaymentMethod``` which should return the required initialisation data as part of a response object. For Stripe intent, a customer will be created for the underlying $customerData if one does not exists. 

The object, $paymentProviderCustomerPaymentMethod is an Eloquent model introduced later. It stores the local record of the provider payment. The host app can directly access the model to use it for a payment in the future. As an example, for Stripe intent, you could do the following to reuse a saved payment method for a new payment:

```php
$data=[
    'payment_provider_payment_method_id'=>$paymentProviderCustomerPaymentMethod->payment_provider_payment_method_id
    ];
$paymentResponse = $paymentService->provider('stripe_intent')
                                ->config($config,$livemode)
                                ->order($order)
                                ->init(null,$data);
$paymentResponse = $paymentService->charge($transaction);

if($paymentResponse->success){
    // payment was successful.
}
```

To remove the payment method you can use the ```remove``` method,
```php
$paymentMethodResponse=$payment$providerPaymentMethod->remove($paymentProviderCustomerPaymentMethod);
if($paymentMethodResponse->success){
    // the record has been removed locally and at Stripe.
}
```

## Adding an additional payment provider
Define payment provider class e.g for Bitcoin payment:
```php
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class BitcoinPaymentProvider extends PaymentProvider{
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
That is it, but if you are using the provider in Autepos, you should add a record for it:
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

Even with tenancy enabled, a given implementation of payment provider may not support it. The default payment providers support tenancy. 

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
### Add a public id to tables
Instead of sharing id of models to the world, we should add a column to tables which stores a unique hash. The column values should be fairly random. The column names can be one of the following:
1. pid - public id/persistent id
2. iid - internet id
3. rid - resource id
4. uid - unique

This change involves adding the new column to all tables and hiding the id column in all model array serialisations (i.e. $hidden=[..., 'id']). Add a unique index($tenant_id-$uid) Following these changes the StripeIntentPaymentProvider should be updated to add as Stripe metadata the new column value rather than the model id, e.g ['metadata'=>['transaction_id'=>$transaction->$uid].
Also when saving method for StripeIntentPaymentProvider the PaymentProviderCustomerPaymentMethod::id should not be used instead the new column should be used.


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