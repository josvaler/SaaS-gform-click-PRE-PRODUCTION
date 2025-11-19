<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/config.php';
$baseUrl = rtrim($appConfig['base_url'], '/');

return [
    'secret_key' => env('STRIPE_SECRET_KEY', ''),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
    'price_id' => env('STRIPE_PRICE_ID', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'success_url' => env('STRIPE_SUCCESS_URL', $baseUrl . '/billing?status=success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', $baseUrl . '/billing?status=cancelled'),
    'portal_configuration_id' => env('STRIPE_PORTAL_CONFIGURATION_ID', null),
];

