<?php

namespace Autepos\AiPayment\Models;

use Illuminate\Support\Facades\DB;
use Autepos\AiPayment\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Autepos\AiPayment\Tenancy\Tenantable;
use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Autepos\AiPayment\Events\OrderableTransactionsTotaled;
use Autepos\AiPayment\Models\Concerns\ReusableTransaction;
use Autepos\AiPayment\Models\Factories\TransactionFactory;

/**
 * Transaction information.
 * 
 * @property int $id
 * @property string|int ${tenant-id}  the id of the owner tenant
 * @property int $parent_id the transaction the this transaction is related to, i.e for self join
 * @property string $cashier_id the id of an admin user/if any, who processed the transaction.
 * @property string $orderable_id relationship with orderable. 
 * @property int $orderable_amount the max amount that need to be captured for this transaction
 * @property string $currency
 * @property int  $amount_escrow the total amount confirmed available that can be received from this transaction. The amount may be held by the provider or a third-party. It can be requested for on a later date.
 * @property int  $amount_escrow_claimed the total amount claimed from escrow. NOTE: This should only be used to only locally determine the remaining amount to be claimed; it should not be used for any other calculations.
 * @property \Carbon\Carbon  $escrow_claimed_at the date of last escrow claim
 * @property \Carbon\Carbon  $escrow_expires_at the expiry date of escrow
 * @property int $amount total amount received from this transaction. Must be 0 for a refund-only transaction(i.e refund=true). Must always be positive.
 * @property int $amount_refunded total refunded for this transaction. Must always be negative.
 * @property string $transaction_family the transaction family/object name. E.g Stripe's 'payment_intent','refund', 'charge' etc 
 * @property string transaction_family_id this should uniquely identify the family/group within this transaction. E.g the corresponding Stripe's payment_intent_id
 * @property string $transaction_child_id This represents a unique item belonging to transaction family with id of
 * transaction_family_id. 
 * E.g a charge_id on the corresponding Stripe's payment_intent_id. Typically 
 * for Stripe a payment intent will have an array of charge objects. So the 
 * id of the charge objects goes here.        
 * @property bool $success determines if transaction has succeeded
 * @property bool $refund  the transaction is a refund-only transaction when this is TRUE.
 * @property bool $display_only when TRUE the transaction is a dummy and should not be involved in calculations      
 * @property string $status transaction overall status as defined by the payment provider;
 * @property string $local_status transaction overall status defined by the app/package
 * @property bool $retrospective determines whether this is created either through webhook or going to the provider to confirm that payment was successful 
 * @property bool $through_webhook is this created through a webhook
 * @property bool $address_matched when TRUE the payment provider fraud checker system has matched the postcode  of the address used to process the transaction.
 * @property bool $postcode_matched when TRUE the payment provider fraud checker system has matched the postcode  of the payment method used to process the transaction.
 * @property bool $cvc_matched when TRUE the payment provider fraud checker system has matched the postcode  of the CVC used to process the transaction.
 * @property bool $threed_secure determines if the transaction was thread secure according to the payment provider
 * @property string $description transaction description
 * @property string $notes additional notes
 * @property array $meta  arbitrary transaction data
 * @property bool $livemode determines whether the transaction is a live transaction
 * @property string $user_type the class/type of the customer (aka logged user)  who own this transaction
 * @property string $user_id the identifier of the customer customer (aka logged user) who own this transaction
 * @property string $card_type type of card used to process the transaction
 * @property string $last_four the last 4 number of the card used to process the transaction
 * @property string $payment_provider the payment provider who processed the transaction
 * @property \Carbon\Carbon created_at the date transaction was created
 * @property \Carbon\Carbon updated_at The date of last update
 */
class Transaction extends Model
{
    use HasFactory;
    use ReusableTransaction;
    use Tenantable;

    /**
     * The value of payment transaction family
     */
    public const TRANSACTION_FAMILY_PAYMENT = 'payment';

    /**
     * The value of refund transaction family
     */
    public const TRANSACTION_FAMILY_REFUND = 'refund';

    /**
     * The value of the local status of a transaction for 'init'.
     * It is local because it is not set by a payment provider.
     */
    public const LOCAL_STATUS_INIT = 'init';

    /**
     * The value of the local status of a transaction for 'complete'. 
     * It is local because it is not set by a payment provider.
     */
    public const LOCAL_STATUS_COMPLETE = 'complete';


    protected $casts = [
        'escrow_expires_at' => 'datetime',
        'escrow_claimed_at' => 'datetime',
        'livemode' => 'boolean',
        'refund' => 'boolean',
        'through_webhook' => 'boolean',
        'display_only' => 'boolean',
        'success' => 'boolean',
        'retrospective' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($transaction) {
            $transaction->updateOrder();
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    /**
     * Retrieve escrow rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscrow($query)
    {
        return $query->where('amount_escrow', '>', 0);
    }

    /**
     * Check if th model is in live mode
     *
     * @return boolean
     */
    public function isLivemode()
    {
        return $this->livemode;
    }

    /**
     * Set the effective total paid for the order
     *
     */
    private function updateOrder(): bool
    {
        $total_paid = self::totalPaid($this->orderable_id, $this->livemode ?? false);
        OrderableTransactionsTotaled::dispatch($this->orderable_id, $total_paid, $this);
        return true;
    }


    /**
     * Compute the effective amount for an orderable given by id.  
     */
    public static function totalPaid(string $orderable_id, bool $livemode = false): int
    {

        /**
         * Use this sql to leave it to the responsibility of the programmer/data-processor 
         * to ensure that data has been entered according to rules.
         */
        $sql = 'SUM(amount + amount_refunded) AS total_paid';

        /**
         * Use this sql to try to correct data that is not inserted correctly.
         */
        $sql_force_rule = 'SUM(amount - ABS(amount_refunded)) AS total_paid';


        //
        $totalTransaction = Transaction::select(DB::raw($sql))
            ->where('success', true)
            ->where('display_only', false)
            ->where('orderable_id', $orderable_id)
            ->where('livemode', $livemode)
            ->first();

        return intval($totalTransaction->total_paid);
    }

    /**
     * Humans' formatted value of 'amount' of the transaction
     *
     * @return string
     */
    public function getHumansAttribute(): string
    {
        return PaymentService::formatAmount($this->amount, $this->currency);
    }
    /**
     * The calculated the effective total amount. 
     */
    public function getTotalAmountAttribute(): int
    {
        return ($this->amount - $this->amount_refunded);
    }

    /**
     * Check if this transaction is provided by the given provider
     *
     */
    public function isForPaymentProvider(string $payment_provider): bool
    {
        return $this->payment_provider == $payment_provider;
    }

    /**
     * Check if the instance is escrow.
     *
     */
    public function isEscrow(): bool
    {
        return !!$this->amount_escrow;
    }

    /**
     * Check if the given refund amount is valid 
     */
    public function isValidRefundAmount(int $refund_amount): bool
    {
        return (($this->amount - abs($this->amount_refunded)) >= $refund_amount);
    }


    /**
     * Get customer data DTO from the details available in the transaction
     * @return CustomerData|null Null is returned if there is not enough detail to create the customer data which happens if the transaction is made by/for guest customer
     * @todo No phpunit test //todo
     */
    public function toCustomerData(): ?CustomerData
    {

        if ($this->user_type and $this->user_id) {
            return new CustomerData([
                'user_type' => $this->user_type ?? '',
                'user_id' => $this->user_id
            ]);
        }
        return null;
    }
}
