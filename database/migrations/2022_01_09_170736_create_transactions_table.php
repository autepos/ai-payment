<?php


use Illuminate\Support\Facades\Schema;
use Autepos\AiPayment\Tenancy\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create($this->getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('pid',36)->unique();// The id that can be shared with the public
            Tenant::addSchemaColumn($table);
            $table->unsignedBigInteger('parent_id')->nullable(); // The transaction the this transaction is related to, i.e for self join

            $table->string('cashier_id')->nullable(); // The id of an admin user/if any, who collected the payment.

            /**
             * Relationship with orderable. 
             */
            //$table->string('orderable_type');// TODO: implement to allow app to define multiple different order classes.
            $table->string('orderable_id');
            $table->bigInteger('orderable_amount')->default(0);

            $table->string('currency')->nullable();

            
            /**
             * The total amount confirmed available that can be received from this 
             * transaction. The amount may be held by the provider or a third-party. 
             * It can be requested for on a later date.
             */
            $table->bigInteger('amount_escrow')->default(0);
            $table->bigInteger('amount_escrow_claimed')->default(0);
            $table->timestamp('escrow_claimed_at')->nullable();
            $table->timestamp('escrow_expires_at')->nullable();

            /**
             * Total amount received from this transaction. Must be 0 for a refund-only 
             * transaction(i.e refund=true). Must always be positive.
             */
            $table->bigInteger('amount')->default(0);

            /**
             * Total refunded for this transaction. Must always be negative.
             */
            $table->bigInteger('amount_refunded')->default(0); //


            /**
             * The transaction family/object name
             * E.g Stripe's 'payment_intent','refund', 'charge' etc 
             */
            $table->string('transaction_family')->default(Transaction::TRANSACTION_FAMILY_PAYMENT);

            /**
             * This should uniquely identify the family/group within this transaction
             * E.g the corresponding Stripe's payment_intent_id
             */
            $table->string('transaction_family_id')
            ->collation('utf8_bin') // This is because Stripe recommends case sensitive column: @see https://stripe.com/docs/upgrades#what-changes-does-stripe-consider-to-be-backwards-compatible
            ->nullable();

            /**
             * This represents a unique item belonging to transaction family with id of
             * transaction_family_id. 
             * E.g a charge_id on the corresponding Stripe's payment_intent_id. Typically 
             * for Stripe a payment intent will have an array of charge objects. So the 
             * id of the charge objects goes here.
             */
            $table->string('transaction_child_id')
            ->collation('utf8_bin') // This is because Stripe recommends case sensitive column: @see https://stripe.com/docs/upgrades#what-changes-does-stripe-consider-to-be-backwards-compatible
            ->nullable();

            //
            $table->boolean('success')->default(false);
            $table->boolean('refund')->default(false); // This is a refund-only transaction when TRUE.
            $table->boolean('display_only')->default(false);
            
            $table->string('status')->default('unknown');
            $table->string('local_status')->default(Transaction::LOCAL_STATUS_INIT);
            $table->boolean('retrospective')->default(false); // i.e is this created either through webhook or going to the provider to confirm that payment was successful 
            $table->boolean('through_webhook')->default(false); // is this created through a webhook

            $table->boolean('address_matched')->default(false);
            $table->boolean('postcode_matched')->default(false);
            $table->boolean('cvc_matched')->default(false);
            $table->boolean('threed_secure')->default(false);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();

            $table->json('meta')->nullable(); // For arbitrary transaction data

            //
            $table->boolean('livemode')->default(false);


            $table->string('user_type')->nullable(); // eg. the user class

            /**
             * We call this {user}_id but it is up to the programmer whatever they will call {user} table
             * it in their users(i.e. logged in customers) table.
             * 
             * NOTE: To make it general we just make the column key type a string.
             */
            $table->string('user_id')->nullable();


            $table->string('card_type')->nullable();
            $table->string('last_four')->nullable();

            $table->string('payment_provider');

            $table->timestamps();

            $table->index(['payment_provider']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->getTableName());
    }

    /**
     * Return table name for the migration.
     *
     * @return string
     */
    private function getTableName(){
        return (new Transaction())->getTable();
    }
};
