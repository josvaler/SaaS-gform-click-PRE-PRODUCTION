<?php
declare(strict_types=1);

// Prevent output buffering issues
if (ob_get_level()) {
    ob_clean();
}

use App\Models\UserRepository;
use Stripe\StripeClient;
use Stripe\Webhook;

// Enable comprehensive error logging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$bootstrapPath = __DIR__ . '/../../config/bootstrap.php';
require $bootstrapPath;

// Set proper headers
header('Content-Type: application/json');

// Only accept POST requests (Stripe sends POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. This endpoint only accepts POST requests from Stripe.']);
    exit;
}

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Check if signature header is present
if (empty($signature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing Stripe signature header. This endpoint is only accessible by Stripe webhooks.']);
    exit;
}

if (!class_exists(Webhook::class)) {
    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

// Check if webhook secret is configured
if (empty($stripeConfig['webhook_secret'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook secret not configured']);
    exit;
}

try {
    $event = Webhook::constructEvent(
        $payload,
        $signature,
        $stripeConfig['webhook_secret']
    );
} catch (\Stripe\Exception\SignatureVerificationException $exception) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
}

$type = $event->type;
$data = $event->data->object;

// Extract google_id from metadata - location varies by event type
$googleId = null;
$customerId = null;
$subscriptionId = null;

if ($type === 'checkout.session.completed') {
    // For checkout.session.completed, metadata is on the session object
    $googleId = $data->metadata->google_id ?? null;
    $customerId = $data->customer ?? null;
    $subscriptionId = $data->subscription ?? null;
    
    // For subscription checkouts, metadata might not be on session - try to get from subscription
    if (empty($googleId) && $subscriptionId && class_exists(StripeClient::class)) {
        try {
            $stripe = new StripeClient($stripeConfig['secret_key']);
            $subscription = $stripe->subscriptions->retrieve($subscriptionId);
            $googleId = $subscription->metadata->google_id ?? null;
            $customerId = $subscription->customer ?? $customerId;
        } catch (Throwable $e) {
        }
    }
    
    // Also try customer metadata if still no google_id
    if (empty($googleId) && $customerId && class_exists(StripeClient::class)) {
        try {
            $stripe = new StripeClient($stripeConfig['secret_key']);
            $customer = $stripe->customers->retrieve($customerId);
            $googleId = $customer->metadata->google_id ?? null;
        } catch (Throwable $e) {
        }
    }
    
} elseif ($type === 'customer.subscription.created') {
    // For subscription.created, metadata is on the subscription object
    $googleId = $data->metadata->google_id ?? null;
    $customerId = $data->customer ?? null;
    $subscriptionId = $data->id ?? null;
    
} elseif ($type === 'customer.subscription.updated') {
    // For subscription.updated, metadata is on the subscription object
    $googleId = $data->metadata->google_id ?? null;
    $customerId = $data->customer ?? null;
    $subscriptionId = $data->id ?? null;
    
} elseif ($type === 'customer.subscription.deleted') {
    // For subscription.deleted, metadata is on the subscription object
    $googleId = $data->metadata->google_id ?? null;
    $customerId = $data->customer ?? null;
}

if ($googleId) {
    try {
        $pdo = db();
        $userRepo = new UserRepository($pdo);
        $user = $userRepo->findByGoogleId($googleId);

        if ($user) {
            if ($type === 'checkout.session.completed' || $type === 'customer.subscription.created') {
                // Get plan expiration from subscription
                $planExpiration = null;
                if ($subscriptionId && class_exists(StripeClient::class)) {
                    try {
                        $stripe = new StripeClient($stripeConfig['secret_key']);
                        $subscription = $stripe->subscriptions->retrieve($subscriptionId);
                        if (isset($subscription->current_period_end)) {
                            $planExpiration = gmdate('Y-m-d H:i:s', (int)$subscription->current_period_end);
                        }
                    } catch (Throwable $e) {
                    }
                }
                
                // Fallback: use billing_period from metadata
                if (!$planExpiration && $type === 'checkout.session.completed') {
                    $billingPeriod = $data->metadata->billing_period ?? 'monthly';
                    if ($billingPeriod === 'annual') {
                        $planExpiration = date('Y-m-d H:i:s', strtotime('+1 year'));
                    } else {
                        $planExpiration = date('Y-m-d H:i:s', strtotime('+1 month'));
                    }
                }
                
                $userRepo->updatePlan((int)$user['id'], 'PREMIUM', $planExpiration);
                
                // Save customer ID if provided
                if ($customerId) {
                    $userRepo->updateStripeCustomerId((int)$user['id'], $customerId);
                    
                    // Also update customer metadata in Stripe to ensure it's set
                    if (class_exists(StripeClient::class)) {
                        try {
                            $stripe = new StripeClient($stripeConfig['secret_key']);
                            $stripe->customers->update($customerId, [
                                'metadata' => [
                                    'google_id' => $googleId,
                                ],
                            ]);
                        } catch (Throwable $e) {
                            // Silently handle error
                        }
                    }
                }
                
                // Save subscription ID if available
                if ($subscriptionId) {
                    $updates = ['stripe_subscription_id' => $subscriptionId];
                    if ($planExpiration) {
                        $updates['current_period_end'] = $planExpiration;
                    }
                    $userRepo->updateSubscriptionMetadata((int)$user['id'], $updates);
                }
            }

            if ($type === 'customer.subscription.deleted') {
                $userRepo->updatePlan((int)$user['id'], 'FREE', null);
            }
            
            if ($type === 'customer.subscription.updated') {
                $status = $data->status ?? null;
                $currentPeriodEnd = isset($data->current_period_end) && $data->current_period_end
                    ? gmdate('Y-m-d H:i:s', (int)$data->current_period_end)
                    : null;

                // If subscription is past_due, unpaid, or canceled, downgrade to FREE
                if (in_array($status, ['past_due', 'unpaid', 'canceled', 'incomplete_expired'])) {
                    $userRepo->updatePlan((int)$user['id'], 'FREE', null);
                } elseif ($currentPeriodEnd) {
                    // Update plan_expiration to match subscription period end
                    $userRepo->updatePlan((int)$user['id'], 'PREMIUM', $currentPeriodEnd);
                    $updates = [
                        'stripe_subscription_id' => $data->id ?? null,
                        'current_period_end' => $currentPeriodEnd,
                    ];
                    if (isset($data->cancel_at_period_end)) {
                        $updates['cancel_at_period_end'] = $data->cancel_at_period_end ? 1 : 0;
                    }
                    $userRepo->updateSubscriptionMetadata((int)$user['id'], $updates);
                }
            }
        }
    } catch (Throwable $exception) {
        // Log error but still acknowledge the event to Stripe
    }
} else {
    // Still return 200 to acknowledge receipt
}

// Always return 200 OK to acknowledge receipt
http_response_code(200);
echo json_encode(['received' => true, 'event_type' => $type]);
exit;
