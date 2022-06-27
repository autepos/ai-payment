<?php

return [

    'stripe_intent' => [
        /**
         * Test client Publishable key
         * 
         */
        'test_publishable_key' => env('STRIPE_TEST_PUBLISHABLE_KEY'),

        /**
         * secret API key
         * 
         */
        'test_secret_key' => env('STRIPE_TEST_SECRET_KEY'),

        /**
         * Test client Publishable key
         */
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),

        /**
         * secret API key
         */
        'secret_key' => env('STRIPE_SECRET_KEY'),

        /**
         * Webhook secret
         * 
         * If you are testing your webhook locally with the Stripe CLI you
         * can find the endpoint's secret by running `stripe listen`
         * Otherwise, find your endpoint's secret in your webhook settings in the Developer Dashboard.
         */
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /**
     * Tenancy
     */
    'tenancy' => [
        'enable_multi_tenant' => false,

        //
        'column_name' => 'tenant_id',

        //
        'is_column_type_integer' => true,

        // Should be a neutral ID that does not belong to any client when tenancy 
        // is in use. If tenancy is not in use then all data is stored with this 
        // value.
        //
        // The default tenant id. This default ID should belong to the 
        // system Admin i.e not for a client since data is stored 
        // with this tenant_id when the programmer has not defined a 
        // specific tenant_id or a tenant_id cannot be derived which 
        // may happen in problematic webhook calls for examples(e.g 
        // when an underlying Transaction model is somehow missing).
        'default' => 1,

        // Eloquent global scope name for scoping related queries
        'global_scope_name' => 'simple_tenancy_tenant_id',
    ]

];
