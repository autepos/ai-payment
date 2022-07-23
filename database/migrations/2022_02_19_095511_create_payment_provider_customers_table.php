<?php

use Autepos\AiPayment\Tenancy\Tenant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentProviderCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_provider_customers', function (Blueprint $table) {
            $table->id();
            $table->string('pid',36)->unique();// The id that can be shared with the public
            Tenant::addSchemaColumn($table);
            //
            $table->string('payment_provider');
            $table->string('payment_provider_customer_id')->nullable();

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
        Schema::dropIfExists('payment_provider_customers');
    }
}
