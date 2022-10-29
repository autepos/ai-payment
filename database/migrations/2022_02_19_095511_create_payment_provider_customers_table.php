<?php

use Autepos\AiPayment\Models\PaymentProviderCustomer;
use Autepos\AiPayment\Tenancy\Tenant;
use Illuminate\Support\Facades\Schema;
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
            $table->string('payment_provider');
            $table->string('payment_provider_customer_id')
            ->collation('utf8_bin') // This is because Stripe recommends case sensitive column: @see https://stripe.com/docs/upgrades#what-changes-does-stripe-consider-to-be-backwards-compatible
            ->nullable();

            //
            $table->string('user_type');
            $table->string('user_id');

            //
            $table->json('meta')->nullable();
            
            
            $table->timestamps();
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
        return (new PaymentProviderCustomer())->getTable();
    }
};
