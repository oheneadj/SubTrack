<?php

use App\Services\Payment\StripeGateway;

return [
    /*
    |--------------------------------------------------------------------------
    | Registered Payment Gateways
    |--------------------------------------------------------------------------
    | Add a new gateway by: (1) creating a class that extends AbstractGateway
    | and implements PaymentGateway, (2) adding its FQCN here.
    | No other files need to change.
    */
    'gateways' => [
        StripeGateway::class,
        // \App\Services\Payment\MobileMoneyGateway::class,  // add when provider is chosen
        // \App\Services\Payment\CryptoGateway::class,
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Add new gateway config blocks here as providers are chosen:
    // 'paystack' => [...],
    // 'coinbase' => [...],
];
