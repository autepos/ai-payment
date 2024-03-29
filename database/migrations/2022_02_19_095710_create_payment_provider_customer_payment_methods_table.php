<?php

use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Models\PaymentProviderCustomerPaymentMethod;
use Illuminate\Support\Facades\Schema;
use Autepos\AiPayment\Tenancy\Tenant;
use Illuminate\Database\Schema\Blueprint;
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
            
            // 
            $table->string('payment_provider_payment_method_id')
            ->collation('utf8_bin') // This is because Stripe recommends case sensitive column: @see https://stripe.com/docs/upgrades#what-changes-does-stripe-consider-to-be-backwards-compatible
            ->nullable();//The id the provider uses to uniquely identify this payment method e.g for Stripe, this will be the PaymentMethod->id

            /**
             * Note that this is for relationship with payment_provider_customers table, 
             * see constraint below. It just happen by chance to have the same name as 
             * 'payment_provider_customer_id' in payment_provider_customers.
             */
            $table->unsignedBigInteger('payment_provider_customer_id');
            $table->string('payment_provider');// Just persisting the payment provider from the parent table, payment_provider_customer.

            //
            $table->string('type')->nullable()->comment('payment method type');
            $table->string('country_code', 2)->nullable()->comment('card country');
            $table->string('brand')->nullable()->comment('card brand');//e.g visa
            $table->string('last_four', 4)->nullable();
            $table->unsignedTinyInteger('expires_at_month')->nullable();
            $table->unsignedSmallInteger('expires_at_year')->nullable();
            $table->boolean('is_default')->default(false);// Tells if this is the default payment method
            $table->boolean('livemode')->default(false);
            //
            $table->json('meta')->nullable();
            

            $table->timestamps();

            $table->foreign('payment_provider_customer_id','autepos_pymt_prvd_cust_pymt_mtd')
            ->references('id')
            ->on((new PaymentProviderCustomer())->getTable())
            ->onDelete('cascade');
 
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
        return (new PaymentProviderCustomerPaymentMethod())->getTable();
    }
};
