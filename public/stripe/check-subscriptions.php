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
    // Get all users
    $users = $pdo->query('SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users in database\n\n";
    
    // Get recent Stripe customers (last 24 hours)
    $recentCustomers = $stripe->customers->all([
        'limit' => 100,
        'created' => ['gte' => time() - 86400] // Last 24 hours
    ]);
    
    echo "Found " . count($recentCustomers->data) . " Stripe customers created in last 24 hours\n\n";
    
    $updated = 0;
    $notFound = [];
    
    foreach ($recentCustomers->data as $customer) {
        $email = $customer->email;
        $customerId = $customer->id;
        
        echo "Checking customer: {$email} (ID: {$customerId})\n";
        
        // Find user by email
        $user = $userRepo->findByEmail($email);
        
        if (!$user) {
            echo "  ⚠️  User not found in database for email: {$email}\n";
            $notFound[] = ['email' => $email, 'customer_id' => $customerId];
            continue;
        }
        
        echo "  ✓ User found: ID={$user['id']}, Current plan={$user['plan']}\n";
        
        // Get subscriptions for this customer
        $subscriptions = $stripe->subscriptions->all([
            'customer' => $customerId,
            'status' => 'active',
            'limit' => 10
        ]);
        
        if (count($subscriptions->data) > 0) {
            $subscription = $subscriptions->data[0];
            $subscriptionId = $subscription->id;
            $planExpiration = gmdate('Y-m-d H:i:s', (int)$subscription->current_period_end);
            
            echo "  ✓ Active subscription found: {$subscriptionId}\n";
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
            echo "  ⚠️  No active subscriptions found for this customer\n";
        }
        
        echo "\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "Users updated: {$updated}\n";
    echo "Customers not found in database: " . count($notFound) . "\n";
    
    if (count($notFound) > 0) {
        echo "\nCustomers not in database:\n";
        foreach ($notFound as $nf) {
            echo "  - {$nf['email']} (Customer ID: {$nf['customer_id']})\n";
        }
    }
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='/billing'>Back to Billing</a></p>\n";

