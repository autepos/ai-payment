<?php

namespace Autepos\AiPayment\Models;


use Autepos\AiPayment\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Autepos\AiPayment\Tenancy\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Autepos\AiPayment\Models\Factories\PaymentProviderCustomerPaymentMethodFactory;

/**
 * @property int $id Model key
 * @property string $pid the universally unique id that can be shared with the public
 * @property string|int ${tenant-id}  the id of the owner tenant
 * @property string $payment_provider The payment provider tag. A persistance of the payment provider from the parent table, payment_provider_customer.
 * @property string $payment_provider_payment_method_id The id the provider uses to uniquely identify this payment method e.g for Stripe, this will be the PaymentMethod->id.
 * @property integer $payment_provider_customer_id Note that this is for relationship with payment_provider_customers table. It just happen by chance to have the same name as 'payment_provider_customer_id' in payment_provider_customers.
 * @property string $type Payment method type
 * @property string $country_code The 2-letter ISO country code of the payment method
 * @property string $brand Card brand . e.g visa
 * @property string $last_four Card last 4 number
 * @property integer $expires_at_month Month of expiry
 * @property integer $expires_at_year Year of expiry
 * @property boolean $is_default True for default payment method
 * @property boolean $livemode True if the payment method is in live mode
 * @property array $meta Arbitrary data as key value pair 
 */
class PaymentProviderCustomerPaymentMethod extends Model
{
    use HasFactory;
    use Tenantable;

    protected $hidden=[
        'created_at',
        'updated_at'
    ];

    protected $casts=[
        'is_default'=>'boolean',
        'livemode'=>'boolean',
        'meta'=>'array',
    ];

        /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($paymentProviderCustomerPaymentMethod) {
            // Add pid
            if(!$paymentProviderCustomerPaymentMethod->pid){
                $paymentProviderCustomerPaymentMethod->pid=PaymentService::generatePid();
            }
        });
    }
    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return PaymentProviderCustomerPaymentMethodFactory::new();
    }

    /**
     * Relationship with PaymentProviderCustomer model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(){
        //
        return $this->belongsTo(PaymentProviderCustomer::class,'payment_provider_customer_id');
    }
    
}
