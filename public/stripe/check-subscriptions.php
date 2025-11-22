<?php
declare(strict_types=1);

/**
 * Diagnostic script to check Stripe subscriptions and update database
 * This helps identify users who have active subscriptions but database wasn't updated
 * 
 * Usage: Visit https://gforms.click/stripe/check-subscriptions
 * Or run: php public/stripe/check-subscriptions.php
 */

require __DIR__ . '/../../config/bootstrap.php';

use App\Models\UserRepository;
use Stripe\StripeClient;

header('Content-Type: text/html; charset=utf-8');

$stripeConfig = require __DIR__ . '/../../config/stripe.php';
$pdo = db();
$userRepo = new UserRepository($pdo);

if (empty($stripeConfig['secret_key'])) {
    die('ERROR: STRIPE_SECRET_KEY not configured');
}

$stripe = new StripeClient($stripeConfig['secret_key']);

echo "<h1>Stripe Subscription Check</h1>\n";
echo "<pre>\n";

try {
    // Get all users with Stripe customer IDs
    $users = $pdo->query('SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id FROM users WHERE stripe_customer_id IS NOT NULL ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users with Stripe customer IDs in database\n\n";
    
    $updated = 0;
    $notFound = [];
    $mismatches = [];
    
    foreach ($users as $user) {
        $email = $user['email'];
        $customerId = $user['stripe_customer_id'];
        
        echo "Checking user: {$email} (Customer ID: {$customerId})\n";
        echo "  Database plan: {$user['plan']}\n";
        
        try {
            // Verify customer exists in Stripe
            $customer = $stripe->customers->retrieve($customerId);
            
            // Get all subscriptions (not just active) to see what's there
            $allSubscriptions = $stripe->subscriptions->all([
                'customer' => $customerId,
                'limit' => 100
            ]);
            
            // Get active/trialing subscriptions
            $activeSubscriptions = [];
            foreach ($allSubscriptions->data as $sub) {
                if (in_array($sub->status, ['active', 'trialing'])) {
                    $activeSubscriptions[] = $sub;
                }
            }
            
            if (count($activeSubscriptions) > 0) {
                $subscription = $activeSubscriptions[0];
                $subscriptionId = $subscription->id;
                $planExpiration = gmdate('Y-m-d H:i:s', (int)$subscription->current_period_end);
                
                echo "  ✓ Active subscription found: {$subscriptionId} (Status: {$subscription->status})\n";
                echo "  ✓ Plan expiration: {$planExpiration}\n";
                
                // Check if database needs update
                if ($user['plan'] !== 'PREMIUM' || $user['stripe_customer_id'] !== $customerId || $user['stripe_subscription_id'] !== $subscriptionId) {
                    echo "  🔄 Updating database...\n";
                    
                    // Update user plan
                    $userRepo->updatePlan((int)$user['id'], 'PREMIUM', $planExpiration);
                    
                    // Update Stripe IDs
                    $userRepo->updateStripeCustomerId((int)$user['id'], $customerId);
                    $userRepo->updateSubscriptionMetadata((int)$user['id'], [
                        'stripe_subscription_id' => $subscriptionId,
                        'current_period_end' => $planExpiration,
                    ]);
                    
                    echo "  ✅ Database updated!\n";
                    $updated++;
                } else {
                    echo "  ✓ Database already up to date\n";
                }
            } else {
                // No active subscriptions
                if (count($allSubscriptions->data) > 0) {
                    $sub = $allSubscriptions->data[0];
                    echo "  ⚠️  No active subscriptions. Most recent subscription status: {$sub->status}\n";
                } else {
                    echo "  ⚠️  No subscriptions found for this customer\n";
                }
                
                // If DB shows PREMIUM but no active subscription, this is a mismatch
                if ($user['plan'] === 'PREMIUM') {
                    echo "  ⚠️  MISMATCH: DB shows PREMIUM but no active Stripe subscription\n";
                    $mismatches[] = [
                        'email' => $email,
                        'customer_id' => $customerId,
                        'db_plan' => $user['plan'],
                        'stripe_status' => count($allSubscriptions->data) > 0 ? $allSubscriptions->data[0]->status : 'NONE'
                    ];
                }
            }
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            echo "  ❌ ERROR: Customer not found in Stripe: {$e->getMessage()}\n";
            $notFound[] = ['email' => $email, 'customer_id' => $customerId, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            echo "  ❌ ERROR: {$e->getMessage()}\n";
            $notFound[] = ['email' => $email, 'customer_id' => $customerId, 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "Users checked: " . count($users) . "\n";
    echo "Users updated: {$updated}\n";
    echo "Mismatches found: " . count($mismatches) . "\n";
    echo "Customers not found in Stripe: " . count($notFound) . "\n";
    
    if (count($mismatches) > 0) {
        echo "\n⚠️  MISMATCHES (DB shows PREMIUM but no active Stripe subscription):\n";
        foreach ($mismatches as $m) {
            echo "  - {$m['email']} (Customer: {$m['customer_id']}, DB Plan: {$m['db_plan']}, Stripe Status: {$m['stripe_status']})\n";
        }
    }
    
    if (count($notFound) > 0) {
        echo "\n❌ Customers not found in Stripe:\n";
        foreach ($notFound as $nf) {
            echo "  - {$nf['email']} (Customer ID: {$nf['customer_id']}, Error: {$nf['error']})\n";
        }
    }
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='/billing'>Back to Billing</a></p>\n";

