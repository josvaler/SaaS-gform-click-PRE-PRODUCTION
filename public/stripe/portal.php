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
    redirect('/billing?status=portal_error');
}

$secretKey = $stripeConfig['secret_key'] ?? '';
if (empty($secretKey)) {
    error_log("ERROR: STRIPE_SECRET_KEY is not set in environment variables");
    redirect('/billing?status=portal_error');
}

if ($secretKey === 'sk_test_xxx' || $secretKey === 'sk_live_xxx' || strpos($secretKey, 'sk_') !== 0) {
    error_log("ERROR: STRIPE_SECRET_KEY appears to be invalid or placeholder");
    redirect('/billing?status=portal_error');
}

try {
    $stripe = new StripeClient($secretKey);
} catch (Throwable $exception) {
    error_log("ERROR: Failed to initialize StripeClient: " . $exception->getMessage());
    redirect('/billing?status=portal_error');
}

$user = session_user();
$customerId = $user['stripe_customer_id'] ?? null;
$userId = $user['id'] ?? null;

try {
    $pdo = db();
    $userRepo = new UserRepository($pdo);
    if (!$userId && !empty($user['google_id'])) {
        $dbUser = $userRepo->findByGoogleId($user['google_id']);
        if ($dbUser) {
            $userId = (int)$dbUser['id'];
            $customerId = $dbUser['stripe_customer_id'] ?? $customerId;
            $_SESSION['user']['id'] = $userId;
            $_SESSION['user']['stripe_customer_id'] = $customerId;
        }
    }

    if (!$customerId) {
        // Attempt to create customer with retry logic for network errors
        $maxRetries = 3;
        $retryCount = 0;
        $customer = null;
        $lastException = null;
        
        while ($retryCount < $maxRetries) {
            try {
                $customer = $stripe->customers->create([
                    'email' => $user['email'] ?? null,
                    'name' => $user['name'] ?? null,
                    'metadata' => [
                        'google_id' => $user['google_id'] ?? null,
                    ],
                ]);
                break; // Success, exit retry loop
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                $retryCount++;
                $lastException = $e;
                if ($retryCount < $maxRetries) {
                    error_log("Stripe API connection error creating customer (attempt $retryCount/$maxRetries): " . $e->getMessage());
                    sleep(1);
                }
            } catch (\Stripe\Exception\RateLimitException $e) {
                $retryCount++;
                $lastException = $e;
                if ($retryCount < $maxRetries) {
                    error_log("Stripe rate limit error creating customer (attempt $retryCount/$maxRetries): " . $e->getMessage());
                    sleep(2);
                }
            } catch (Throwable $e) {
                $lastException = $e;
                break;
            }
        }
        
        if ($customer === null && $lastException !== null) {
            $errorType = get_class($lastException);
            error_log("Stripe Portal Error (Customer): $errorType - " . $lastException->getMessage());
            if ($lastException instanceof \Stripe\Exception\ApiConnectionException) {
                error_log("ERROR: Could not connect to Stripe API - check network connectivity");
            }
            redirect('/billing?status=error');
        }
        
        if ($customer === null) {
            error_log("ERROR: Failed to create Stripe customer after $maxRetries attempts");
            redirect('/billing?status=error');
        }
        
        $customerId = $customer->id;

        if ($userId) {
            $userRepo->updateStripeCustomerId($userId, $customerId);
        }

        $_SESSION['user']['stripe_customer_id'] = $customerId;
    }
} catch (Throwable $exception) {
    error_log('Stripe Portal Error (Customer): ' . $exception->getMessage());
    error_log('Exception type: ' . get_class($exception));
    redirect('/billing?status=error');
}

try {
    // Attempt to create portal session with retry logic for network errors
    $maxRetries = 3;
    $retryCount = 0;
    $session = null;
    $lastException = null;
    
    while ($retryCount < $maxRetries) {
        try {
            $session = $stripe->billingPortal->sessions->create([
                'customer' => $customerId,
                'return_url' => $stripeConfig['success_url'],
            ]);
            break; // Success, exit retry loop
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $retryCount++;
            $lastException = $e;
            if ($retryCount < $maxRetries) {
                error_log("Stripe API connection error creating portal session (attempt $retryCount/$maxRetries): " . $e->getMessage());
                sleep(1);
            }
        } catch (\Stripe\Exception\RateLimitException $e) {
            $retryCount++;
            $lastException = $e;
            if ($retryCount < $maxRetries) {
                error_log("Stripe rate limit error creating portal session (attempt $retryCount/$maxRetries): " . $e->getMessage());
                sleep(2);
            }
        } catch (Throwable $e) {
            $lastException = $e;
            break;
        }
    }
    
    if ($session === null && $lastException !== null) {
        $errorMessage = $lastException->getMessage();
        $errorType = get_class($lastException);
        
        error_log("Stripe Portal Error: $errorType - $errorMessage");
        error_log("Customer ID: $customerId");
        
        if ($lastException instanceof \Stripe\Exception\AuthenticationException) {
            error_log("ERROR: Stripe authentication failed - check your STRIPE_SECRET_KEY");
        } elseif ($lastException instanceof \Stripe\Exception\InvalidRequestException) {
            error_log("ERROR: Invalid Stripe request - customer may not exist or portal not configured");
        } elseif ($lastException instanceof \Stripe\Exception\ApiConnectionException) {
            error_log("ERROR: Could not connect to Stripe API - check network connectivity");
        }
        
        redirect('/billing?status=portal_error');
    }
    
    if ($session === null) {
        error_log("ERROR: Failed to create Stripe portal session after $maxRetries attempts");
        redirect('/billing?status=portal_error');
    }

    redirect($session->url);
} catch (Throwable $exception) {
    error_log('Unexpected error in Stripe portal: ' . $exception->getMessage());
    error_log('Exception type: ' . get_class($exception));
    redirect('/billing?status=portal_error');
}

