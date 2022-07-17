<?php

namespace Autepos\AiPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Autepos\AiPayment\Tenancy\Tenantable;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;
use Autepos\AiPayment\Models\Factories\PaymentProviderCustomerFactory;

/**
 * @property int $id Model key
 * @property string|int ${tenant-id}  the id of the owner tenant 
 * @property string $payment_provider The payment provider tag
 * @property string $payment_provider_customer_id The id the payment provider uses to identify this customer
 * @property string $user_type The local user type
 * @property string $user_id The local user identifier
 */
class PaymentProviderCustomer extends Model
{
    use HasFactory;
    use Tenantable;

    protected $hidden = [
        'payment_provider_customer_id', // This can be removed if required
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return PaymentProviderCustomerFactory::new();
    }

    /**
     * Relationship with PaymentProviderCustomerPaymentMethod model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentMethods()
    {
        //
        return $this->hasMany(PaymentProviderCustomerPaymentMethod::class);
    }

    /**
     * Get an instance from CustomerData for a given payment provider
     */
    public static function fromCustomerData(CustomerData $customerData, string $payment_provider): ?self
    {
        return static::where('user_type', $customerData->user_type)
            ->where('user_id', $customerData->user_id)
            ->where('payment_provider', $payment_provider)
            ->first();
    }


    /**
     * Get an instance from payment provider customer id for a given payment provider
     */
    public static function fromPaymentProviderId(string $payment_provider_customer_id, PaymentProvider $paymentProvider): ?self
    {
        return static::where('payment_provider_customer_id', $payment_provider_customer_id)
            ->where('payment_provider', $paymentProvider->getProvider())
            ->first();
    }
}
