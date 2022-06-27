<?php

namespace Autepos\AiPayment\Providers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Autepos\AiPayment\SimpleResponse;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Autepos\AiPayment\Providers\Contracts\PaymentProvider;

class OfflinePaymentProvider extends PaymentProvider
{
    /**
     * The Provider tag
     * @var string
     */
    public const PROVIDER = 'offline';
    /**
     * The provider library version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    public function up(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('save'), true);
    }

    public function down(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('save'), true);
    }

    public function ping(): SimpleResponse
    {
        return new SimpleResponse(SimpleResponse::newType('ping'), true);
    }

    public function init(int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {
        $response = new PaymentResponse(PaymentResponse::newType('init'), true, 'Offline payment');

        return $response;
    }

    public function cashierInit(Authenticatable $cashier, int $amount = null, array $data = [], Transaction $transaction = null): PaymentResponse
    {

        $response = new PaymentResponse(PaymentResponse::newType('init'), true, 'Offline payment');

        // Amount
        $amount = $amount ?? $this->order->getAmount();

        $trans_id = optional($transaction)->transaction_family_id;
        $transaction = $this->getInitTransaction($amount, $transaction);


        $trans_id = $trans_id ?? (string)Str::uuid();

        //
        /*
        * Note that this will be overwritten when the charge is performed since what is 
        * important is cashier who carried out the charge not who initiated it.
        */
        $transaction->cashier_id = $cashier->getAuthIdentifier();

        //
        $transaction->transaction_family = Transaction::TRANSACTION_FAMILY_PAYMENT;
        $transaction->transaction_family_id = $trans_id;
        $transaction->save();

        return $response->transaction($transaction);
    }

    public function charge(Transaction $transaction = null, array $data = []): PaymentResponse
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));

        $paymentResponse->message = 'Access denied';
        $paymentResponse->httpStatusCode = 403;
        $paymentResponse->errors = ['Access to offline payment denied'];

        return $paymentResponse;
    }

    public function cashierCharge(Authenticatable $cashier, Transaction $transaction = null, array $data = []): PaymentResponse
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('charge'));



        //
        if (is_null($transaction)) {
            $transaction = $this->cashierInit($cashier, null)->getTransaction();
        }


        //
        if (!$this->authoriseProviderTransaction($transaction)) { // TODO: this should already have been taken care of by the PaymentService
            $paymentResponse->success = false;
            $paymentResponse->errors = $this->hasSameLiveModeAsTransaction($transaction)
                ? ['Unauthorised payment transaction with provider']
                : ['Livemode mismatch'];
            return $paymentResponse;
        }

        $transaction->amount = $transaction->orderable_amount; // Set the actual paid amount 
        $transaction->amount_refunded = 0;
        $transaction->success = true;
        $transaction->status = 'success';
        $transaction->refund = false;

        $transaction->cashier_id = $cashier->getAuthIdentifier();
        $transaction->save();

        $paymentResponse->transaction($transaction);
        $paymentResponse->success = $transaction->success;
        $paymentResponse->message = 'Success';

        return $paymentResponse;
    }

    public function refund(Authenticatable $cashier, Transaction $transaction, int $amount, string $description): PaymentResponse
    {

        $response = new PaymentResponse(PaymentResponse::newType('refund'));




        //
        if (!$this->authoriseProviderTransaction($transaction)) { //TODO: This should already been taken care of by PaymentService
            $response->success = false;

            $response->errors = $this->hasSameLiveModeAsTransaction($transaction)
                ? ['Unauthorised payment transaction with provider']
                : ['Livemode mismatch'];
            return $response;
        }

        if ($this->validateRefund($transaction, $amount)) { //TODO: Note that validating the refund should already been taken care of by PaymentService
            DB::beginTransaction();
            try {

                $trans_id = (string)Str::uuid();
                $refundTransaction = $this->newTransaction(0);

                $refundTransaction->orderable_id = $transaction->orderable_id;

                // Set the amounts according to refund rules
                $refundTransaction->amount = 0;
                $refundTransaction->amount_refunded = -abs($amount);
                $refundTransaction->refund = true;
                $refundTransaction->display_only = true; // This prevent doubly applying the refund record since we will also record it in the transaction used to initiate the refund.
                $refundTransaction->parent_id = $transaction->id;
                //
                $refundTransaction->cashier_id = $cashier->getAuthIdentifier();
                $refundTransaction->transaction_family = Transaction::TRANSACTION_FAMILY_REFUND;
                $refundTransaction->transaction_family_id = $trans_id;

                $refundTransaction->success = true;
                $refundTransaction->status = 'success';
                $refundTransaction->local_status = Transaction::LOCAL_STATUS_COMPLETE;
                $refundTransaction->description = $description;


                // Update the transaction used to make the refund
                $transaction->amount_refunded = - (abs($transaction->amount_refunded) + abs($refundTransaction->amount_refunded));

                //
                $refundTransaction->save();
                $transaction->save();

                //
                $response->success = true;
                $response->transaction($refundTransaction);
                DB::commit();
            } catch (Exception $ex) {
                DB::rollBack();
                $response->success = false;
                $response->errors = [$ex->getMessage()];
                $response->transaction(null);
            }
        } else {
            $response->message = 'Invalid refund';
            $response->errors = ['Refund was invalid'];
        }

        return $response;
    }

    public function getProvider(): string
    {
        return static::PROVIDER;
    }
    public function isCancelable(Transaction $transaction): bool
    {
        return true;
    }
}
