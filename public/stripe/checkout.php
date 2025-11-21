<?php
declare(strict_types=1);

use App\Models\UserRepository;
use Stripe\StripeClient;

require __DIR__ . '/../../config/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/billing');
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    redirect('/billing?status=error');
}

if (!class_exists(StripeClient::class)) {
    error_log("ERROR: Stripe SDK not available. Run: composer require stripe/stripe-php");
    redirect('/billing?status=error');
}

// Validate Stripe secret key before using it
$secretKey = $stripeConfig['secret_key'] ?? '';
if (empty($secretKey)) {
    error_log("ERROR: STRIPE_SECRET_KEY is not set in environment variables");
    redirect('/billing?status=error');
}

if ($secretKey === 'sk_test_xxx' || $secretKey === 'sk_live_xxx' || strpos($secretKey, 'sk_') !== 0) {
    error_log("ERROR: STRIPE_SECRET_KEY appears to be invalid or placeholder");
    redirect('/billing?status=error');
}

try {
    $stripe = new StripeClient($secretKey);
} catch (Throwable $exception) {
    error_log("ERROR: Failed to initialize StripeClient: " . $exception->getMessage());
    redirect('/billing?status=error');
}

$user = session_user();
$customerId = $user['stripe_customer_id'] ?? null;

try {
    $pdo = db();
    $userRepo = new UserRepository($pdo);
    if (!$customerId && !empty($user['google_id'])) {
        $dbUser = $userRepo->findByGoogleId($user['google_id']);
        if ($dbUser) {
            $customerId = $dbUser['stripe_customer_id'] ?? null;
            $_SESSION['user']['id'] = (int)$dbUser['id'];
            $_SESSION['user']['stripe_customer_id'] = $customerId;
        }
    }
} catch (Throwable $exception) {
    error_log("Database lookup error: " . $exception->getMessage());
}

$googleId = $user['google_id'] ?? null;

// Get billing period from form (default to monthly)
$billingPeriod = $_POST['billing_period'] ?? 'monthly';

// Select the appropriate price ID based on billing period
if ($billingPeriod === 'annual') {
    $priceId = $stripeConfig['price_id_year'] ?? '';
    if (empty($priceId)) {
        error_log('ERROR: STRIPE_PRICE_ID_YEAR is not set in environment variables for annual billing');
        redirect('/billing?status=error');
    }
} else {
    $priceId = $stripeConfig['price_id'] ?? '';
    if (empty($priceId)) {
        error_log('ERROR: STRIPE_PRICE_ID is not set in environment variables for monthly billing');
        redirect('/billing?status=error');
    }
}

try {
    $payload = [
        'mode' => 'subscription',
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'success_url' => $stripeConfig['success_url'],
        'cancel_url' => $stripeConfig['cancel_url'],
        'customer_email' => $user['email'] ?? null,
        'metadata' => [
            'google_id' => $googleId,
            'billing_period' => $billingPeriod,
        ],
        'subscription_data' => [
            'metadata' => [
                'google_id' => $googleId,
                'billing_period' => $billingPeriod,
            ],
        ],
    ];

    if ($customerId) {
        $payload['customer'] = $customerId;
        unset($payload['customer_email']);
    }

    // Attempt to create checkout session with retry logic for network errors
    $maxRetries = 3;
    $retryCount = 0;
    $session = null;
    $lastException = null;
    
    while ($retryCount < $maxRetries) {
        try {
            $session = $stripe->checkout->sessions->create($payload);
            break; // Success, exit retry loop
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network/connection error - retry
            $retryCount++;
            $lastException = $e;
            if ($retryCount < $maxRetries) {
                error_log("Stripe API connection error (attempt $retryCount/$maxRetries): " . $e->getMessage());
                sleep(1); // Wait 1 second before retry
            }
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Rate limit error - retry with longer delay
            $retryCount++;
            $lastException = $e;
            if ($retryCount < $maxRetries) {
                error_log("Stripe rate limit error (attempt $retryCount/$maxRetries): " . $e->getMessage());
                sleep(2); // Wait 2 seconds before retry
            }
        } catch (Throwable $e) {
            // Other errors - don't retry
            $lastException = $e;
            break;
        }
    }
    
    if ($session === null && $lastException !== null) {
        // All retries failed or non-retryable error
        $errorMessage = $lastException->getMessage();
        $errorType = get_class($lastException);
        
        error_log("Stripe checkout error for billing period '$billingPeriod': $errorType - $errorMessage");
        error_log("Price ID used: $priceId");
        error_log("Secret key prefix: " . substr($secretKey, 0, 7) . "...");
        
        // Check for specific error types
        if ($lastException instanceof \Stripe\Exception\AuthenticationException) {
            error_log("ERROR: Stripe authentication failed - check your STRIPE_SECRET_KEY");
        } elseif ($lastException instanceof \Stripe\Exception\InvalidRequestException) {
            error_log("ERROR: Invalid Stripe request - check price ID: $priceId");
        } elseif ($lastException instanceof \Stripe\Exception\ApiConnectionException) {
            error_log("ERROR: Could not connect to Stripe API - check network connectivity");
        }
        
        redirect('/billing?status=error');
    }
    
    if ($session === null) {
        error_log("ERROR: Failed to create Stripe checkout session after $maxRetries attempts");
        redirect('/billing?status=error');
    }
    
    redirect($session->url);
} catch (Throwable $exception) {
    error_log('Unexpected error in Stripe checkout: ' . $exception->getMessage());
    error_log('Exception details: ' . $exception->getTraceAsString());
    redirect('/billing?status=error');
}

