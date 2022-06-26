<?php

namespace Autepos\AiPayment\Models;

use Autepos\AiPayment\Contracts\CustomerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Autepos\AiPayment\PaymentService;
use Autepos\AiPayment\Tenancy\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Autepos\AiPayment\Events\OrderableTransactionsTotaled;
use Autepos\AiPayment\Models\Concerns\ReusableTransaction;
use Autepos\AiPayment\Models\Factories\TransactionFactory;


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
        'escrow_expires_at' => 'date',
        'escrow_claimed_at'=>'date',
        //'orderable_detail_ids'=>'array',
        'livemode'=>'boolean',
        'refund'=>'boolean',
        'through_webhook'=>'boolean',
        'display_only'=>'boolean',
        'success'=>'boolean',
        'retrospective'=>'boolean',
        'meta'=>'array',


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
    public function isLivemode(){
        return $this->livemode;
    }

    /**
     * Set the effective total paid for the order
     *
     */
    private function updateOrder(): bool
    {
        $total_paid = self::totalPaid($this->orderable_id,$this->livemode??false);
        OrderableTransactionsTotaled::dispatch($this->orderable_id, $total_paid, $this);
        return true;
    }


    /**
     * Compute the effective amount for an orderable given by id.  
     */
    public static function totalPaid(string $orderable_id,bool $livemode=false): int
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
        return PaymentService::formatAmount($this->amount,$this->currency);
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
    public function isForPaymentProvider(string $payment_provider):bool{
        return $this->payment_provider==$payment_provider;
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
        return (($this->amount-abs($this->amount_refunded)) >= $refund_amount);
    }

    //     /**
    //  * Checks if the transaction can be cancelled
    //  * This can used to check if the underlying orderable can be cancel after 
    //  * payment transaction. E.g if payment is made in error, an orderable may be 
    //  * canceled so that the associated order items may be marked as unpaid. A refund 
    //  * may then be processed by the cashier following the cancellation.
    //  */
    // public function isCancelable():bool{
    //     return count($this->orderable_detail_ids??[])==0;
    // }

    /**
     * Get customer data DTO from the details available in the transaction
     * @return CustomerData|null Null is returned if there is not enough detail to create the customer data which happens if the transaction is made by/for guest customer
     * @todo No phpunit test
     */
    public function toCustomerData():?CustomerData{

        if ($this->user_type and $this->user_id) {
            return new CustomerData([
                'user_type'=>$this->user_type??'',
                'user_id'=>$this->user_id
            ]);
        }
        return null;
    }
}
