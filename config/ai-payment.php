<?php

return [

    'stripe_intent'=>[
        /**
         * Test client Publishable key
         * 
         */
        'test_publishable_key'=>env('STRIPE_TEST_PUBLISHABLE_KEY'),
        
        /**
         * secret API key
         * 
         */
        'test_secret_key'=>env('STRIPE_TEST_SECRET_KEY'),

        /**
         * Test client Publishable key
         */
        'publishable_key'=>env('STRIPE_PUBLISHABLE_KEY'),
        
        /**
         * secret API key
         */
        'secret_key'=>env('STRIPE_SECRET_KEY'),

        /**
         * Webhook secret
         * 
         * If you are testing your webhook locally with the Stripe CLI you
         * can find the endpoint's secret by running `stripe listen`
         * Otherwise, find your endpoint's secret in your webhook settings in the Developer Dashboard.
         */
        'webhook_secret'=>env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance'=>env('STRIPE_WEBHOOK_TOLERANCE',300),
    ],

];
