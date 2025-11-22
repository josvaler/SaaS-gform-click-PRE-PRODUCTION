<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

require_once __DIR__ . '/../../../config/cache.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $cacheKey = 'diagnostics_stripe';
    $ttl = 60; // 60 seconds cache (Stripe API calls are expensive)
    
    // Try to get from cache
    $cached = cache_get($cacheKey, $ttl);
    $isCached = $cached !== null;
    
    if ($isCached) {
        echo json_encode([
            'success' => true,
            'cached' => true,
            'timestamp' => time(),
            'data' => $cached
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get Stripe config
    $stripeConfig = require __DIR__ . '/../../../config/stripe.php';
    $currentDate = date('Y-m-d H:i:s');
    
    // Initialize variables
    $apiConnected = false;
    $accountId = null;
    $apiError = null;
    $customers = [];
    $duplicates = [];
    $webhooks = [];
    $subscriptions = [];
    
    // Check if Stripe SDK is available
    if (!class_exists(\Stripe\StripeClient::class)) {
        $apiError = 'Stripe SDK not available. Run: composer require stripe/stripe-php';
    } elseif (empty($stripeConfig['secret_key'])) {
        $apiError = 'STRIPE_SECRET_KEY is not set in environment variables';
    } else {
        try {
            $stripe = new \Stripe\StripeClient($stripeConfig['secret_key']);
            
            // Test API connection
            $account = $stripe->accounts->retrieve();
            $apiConnected = true;
            $accountId = $account->id;
            
            // Get customers (limit 100)
            try {
                $customersResponse = $stripe->customers->all(['limit' => 100]);
                $customersData = $customersResponse->data ?? [];
                
                // Convert to array format
                foreach ($customersData as $customer) {
                    $customers[] = [
                        'id' => $customer->id,
                        'email' => $customer->email ?? 'NO_EMAIL',
                        'created' => $customer->created ?? null
                    ];
                }
                
                // Detect duplicates by email
                $emails = [];
                foreach ($customersData as $customer) {
                    $email = $customer->email ?? 'NO_EMAIL';
                    if (!isset($emails[$email])) {
                        $emails[$email] = [];
                    }
                    $emails[$email][] = $customer->id;
                }
                
                foreach ($emails as $email => $ids) {
                    if (count($ids) > 1) {
                        $duplicates[] = [
                            'email' => $email,
                            'customer_ids' => $ids
                        ];
                    }
                }
            } catch (Exception $e) {
                // Error fetching customers, but continue
            }
            
            // Get webhooks (limit 10)
            try {
                $webhooksResponse = $stripe->webhookEndpoints->all(['limit' => 10]);
                $webhooksData = $webhooksResponse->data ?? [];
                
                // Convert to array format
                foreach ($webhooksData as $webhook) {
                    $webhooks[] = [
                        'id' => $webhook->id,
                        'url' => $webhook->url ?? 'N/A',
                        'status' => $webhook->status ?? 'unknown',
                        'enabled_events' => $webhook->enabled_events ?? []
                    ];
                }
            } catch (Exception $e) {
                // Error fetching webhooks, but continue
            }
            
            // Get subscriptions (limit 50)
            try {
                $subscriptionsResponse = $stripe->subscriptions->all(['limit' => 50]);
                $subscriptionsData = $subscriptionsResponse->data ?? [];
                
                // Convert to array format
                foreach ($subscriptionsData as $subscription) {
                    $subscriptions[] = [
                        'id' => $subscription->id,
                        'customer' => $subscription->customer ?? 'N/A',
                        'status' => $subscription->status ?? 'unknown',
                        'created' => $subscription->created ?? null
                    ];
                }
            } catch (Exception $e) {
                // Error fetching subscriptions, but continue
            }
            
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $apiError = 'Invalid STRIPE_SECRET_KEY: ' . $e->getMessage();
        } catch (Exception $e) {
            $apiError = 'Connection error: ' . $e->getMessage();
        }
    }
    
    $data = [
        'current_date' => $currentDate,
        'api_connected' => $apiConnected,
        'account_id' => $accountId,
        'api_error' => $apiError,
        'customers' => [
            'total' => count($customers),
            'list' => $customers,
            'duplicates' => $duplicates
        ],
        'webhooks' => [
            'total' => count($webhooks),
            'list' => $webhooks
        ],
        'subscriptions' => [
            'total' => count($subscriptions),
            'list' => $subscriptions
        ]
    ];
    
    // Cache the results
    cache_set($cacheKey, $data, $ttl);
    
    echo json_encode([
        'success' => true,
        'cached' => false,
        'timestamp' => time(),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

