<?php
/**
 * Cleanup Stripe Mismatches Script
 * 
 * This script fixes database mismatches where users have PREMIUM plan
 * but no active Stripe subscriptions. It can also optionally delete
 * Stripe customers and clear Stripe metadata from the database.
 * 
 * Usage:
 *   # Dry-run mode (preview changes without applying)
 *   php scripts/cleanup_stripe_mismatches.php --dry-run
 * 
 *   # Fix mismatches only (downgrade PREMIUM users with no active subscriptions)
 *   php scripts/cleanup_stripe_mismatches.php --fix-db
 * 
 *   # Fix mismatches and clear Stripe metadata from database
 *   php scripts/cleanup_stripe_mismatches.php --fix-db --clear-stripe-metadata
 * 
 *   # Delete Stripe customers (WARNING: This deletes customers in Stripe!)
 *   php scripts/cleanup_stripe_mismatches.php --delete-stripe-customers
 * 
 *   # Fix DB and delete Stripe customers
 *   php scripts/cleanup_stripe_mismatches.php --fix-db --delete-stripe-customers
 * 
 *   # Check specific email
 *   php scripts/cleanup_stripe_mismatches.php --email user@example.com --dry-run
 * 
 * WARNING: Deleting Stripe customers is IRREVERSIBLE!
 */

declare(strict_types=1);

// Parse command line arguments
$options = [
    'dry-run' => false,
    'fix-db' => false,
    'clear-stripe-metadata' => false,
    'delete-stripe-customers' => false,
    'email' => null,
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--fix-db') {
        $options['fix-db'] = true;
    } elseif ($arg === '--clear-stripe-metadata') {
        $options['clear-stripe-metadata'] = true;
    } elseif ($arg === '--delete-stripe-customers') {
        $options['delete-stripe-customers'] = true;
    } elseif (str_starts_with($arg, '--email=')) {
        $options['email'] = substr($arg, 8);
    }
}

// Load bootstrap
require __DIR__ . '/../config/bootstrap.php';

use App\Models\UserRepository;
use Stripe\StripeClient;

// Color output for terminal
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
];

function colorize(string $text, string $color): string
{
    global $colors;
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

echo colorize("\n" . str_repeat("=", 70) . "\n", 'cyan');
echo colorize("  STRIPE CLEANUP & MISMATCH FIX SCRIPT\n", 'bold');
echo colorize(str_repeat("=", 70) . "\n\n", 'cyan');

// Load Stripe config
$stripeConfig = require __DIR__ . '/../config/stripe.php';

if (empty($stripeConfig['secret_key'])) {
    echo colorize("❌ ERROR: STRIPE_SECRET_KEY not configured\n", 'red');
    exit(1);
}

$pdo = db();
$userRepo = new UserRepository($pdo);
$stripe = new StripeClient($stripeConfig['secret_key']);

// Show mode
if ($options['dry-run']) {
    echo colorize("🔍 DRY-RUN MODE: No changes will be made\n", 'yellow');
} else {
    echo colorize("⚠️  LIVE MODE: Changes will be applied!\n", 'red');
}

echo "\nOptions:\n";
echo "  - Fix Database: " . ($options['fix-db'] ? colorize('YES', 'green') : colorize('NO', 'red')) . "\n";
echo "  - Clear Stripe Metadata: " . ($options['clear-stripe-metadata'] ? colorize('YES', 'green') : colorize('NO', 'red')) . "\n";
echo "  - Delete Stripe Customers: " . ($options['delete-stripe-customers'] ? colorize('YES', 'red') : colorize('NO', 'green')) . "\n";
if ($options['email']) {
    echo "  - Filter Email: " . colorize($options['email'], 'bold') . "\n";
}
echo "\n";

try {
    // Get users to check
    if ($options['email']) {
        $stmt = $pdo->prepare("
            SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id
            FROM users 
            WHERE stripe_customer_id IS NOT NULL 
            AND LOWER(email) = LOWER(:email)
            LIMIT 1
        ");
        $stmt->execute(['email' => $options['email']]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $users = $pdo->query("
            SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id
            FROM users 
            WHERE stripe_customer_id IS NOT NULL
            ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo colorize("Found " . count($users) . " user(s) with Stripe customer IDs\n\n", 'blue');
    
    $stats = [
        'checked' => 0,
        'mismatches_found' => 0,
        'db_fixed' => 0,
        'metadata_cleared' => 0,
        'stripe_customers_deleted' => 0,
        'errors' => 0,
    ];
    
    $mismatches = [];
    
    foreach ($users as $user) {
        $stats['checked']++;
        $email = $user['email'];
        $customerId = $user['stripe_customer_id'];
        $userId = (int)$user['id'];
        
        echo colorize("[" . $stats['checked'] . "/" . count($users) . "] ", 'cyan');
        echo colorize("Checking: {$email}\n", 'bold');
        echo "  Customer ID: {$customerId}\n";
        echo "  Database Plan: " . colorize($user['plan'], $user['plan'] === 'PREMIUM' ? 'yellow' : 'green') . "\n";
        
        $userMismatch = [
            'email' => $email,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'db_plan' => $user['plan'],
            'stripe_status' => 'UNKNOWN',
            'has_active_subscription' => false,
            'actions' => [],
        ];
        
        try {
            // Verify customer exists in Stripe
            $customer = $stripe->customers->retrieve($customerId);
            
            // Get all subscriptions
            $allSubscriptions = $stripe->subscriptions->all([
                'customer' => $customerId,
                'limit' => 100
            ]);
            
            // Check for active/trialing subscriptions
            $activeSubscriptions = [];
            foreach ($allSubscriptions->data as $sub) {
                if (in_array($sub->status, ['active', 'trialing'])) {
                    $activeSubscriptions[] = $sub;
                }
            }
            
            if (count($activeSubscriptions) > 0) {
                $userMismatch['has_active_subscription'] = true;
                $userMismatch['stripe_status'] = 'ACTIVE';
                $sub = $activeSubscriptions[0];
                echo "  Stripe Status: " . colorize("ACTIVE (Subscription: {$sub->id}, Status: {$sub->status})", 'green') . "\n";
                
                // Check if DB needs update
                if ($user['plan'] !== 'PREMIUM') {
                    echo "  " . colorize("⚠️  MISMATCH: Stripe has active subscription but DB shows {$user['plan']}", 'yellow') . "\n";
                    if ($options['fix-db'] && !$options['dry-run']) {
                        $userRepo->updatePlan($userId, 'PREMIUM', gmdate('Y-m-d H:i:s', (int)$sub->current_period_end));
                        $userRepo->updateSubscriptionMetadata($userId, [
                            'stripe_subscription_id' => $sub->id,
                            'current_period_end' => gmdate('Y-m-d H:i:s', (int)$sub->current_period_end),
                        ]);
                        echo "  " . colorize("✅ Database updated to PREMIUM", 'green') . "\n";
                        $stats['db_fixed']++;
                    } elseif ($options['fix-db']) {
                        echo "  " . colorize("[DRY-RUN] Would update database to PREMIUM", 'yellow') . "\n";
                    }
                } else {
                    echo "  " . colorize("✓ Database and Stripe are in sync", 'green') . "\n";
                }
            } else {
                // No active subscriptions
                if (count($allSubscriptions->data) > 0) {
                    $sub = $allSubscriptions->data[0];
                    $userMismatch['stripe_status'] = $sub->status;
                    echo "  Stripe Status: " . colorize("NO ACTIVE SUBSCRIPTION (Most recent: {$sub->status})", 'yellow') . "\n";
                } else {
                    $userMismatch['stripe_status'] = 'NONE';
                    echo "  Stripe Status: " . colorize("NO SUBSCRIPTIONS", 'red') . "\n";
                }
                
                // Check for mismatch
                if ($user['plan'] === 'PREMIUM') {
                    $stats['mismatches_found']++;
                    $userMismatch['actions'][] = 'downgrade_to_free';
                    echo "  " . colorize("⚠️  MISMATCH: DB shows PREMIUM but no active Stripe subscription", 'red') . "\n";
                    
                    if ($options['fix-db'] && !$options['dry-run']) {
                        // Downgrade to FREE and clear all subscription-related date fields
                        $userRepo->updatePlan($userId, 'FREE', null);
                        
                        // Clear subscription date fields
                        $pdo->prepare("
                            UPDATE users 
                            SET plan_expiration = NULL,
                                current_period_end = NULL,
                                cancel_at = NULL,
                                cancel_at_period_end = 0
                            WHERE id = :id
                        ")->execute(['id' => $userId]);
                        
                        echo "  " . colorize("✅ Database downgraded to FREE and subscription dates cleared", 'green') . "\n";
                        $stats['db_fixed']++;
                    } elseif ($options['fix-db']) {
                        echo "  " . colorize("[DRY-RUN] Would downgrade database to FREE and clear subscription dates", 'yellow') . "\n";
                    }
                }
            }
            
            // Clear Stripe metadata if requested
            if ($options['clear-stripe-metadata']) {
                $userMismatch['actions'][] = 'clear_metadata';
                if (!$options['dry-run']) {
                    $pdo->prepare("
                        UPDATE users 
                        SET stripe_customer_id = NULL,
                            stripe_subscription_id = NULL,
                            cancel_at_period_end = 0,
                            cancel_at = NULL,
                            current_period_end = NULL
                        WHERE id = :id
                    ")->execute(['id' => $userId]);
                    echo "  " . colorize("✅ Stripe metadata cleared from database", 'green') . "\n";
                    $stats['metadata_cleared']++;
                } else {
                    echo "  " . colorize("[DRY-RUN] Would clear Stripe metadata from database", 'yellow') . "\n";
                }
            }
            
            // Delete Stripe customer if requested
            if ($options['delete-stripe-customers']) {
                $userMismatch['actions'][] = 'delete_stripe_customer';
                if (!$options['dry-run']) {
                    try {
                        $stripe->customers->delete($customerId);
                        echo "  " . colorize("✅ Stripe customer deleted", 'green') . "\n";
                        $stats['stripe_customers_deleted']++;
                    } catch (Throwable $e) {
                        echo "  " . colorize("❌ Error deleting Stripe customer: {$e->getMessage()}", 'red') . "\n";
                        $stats['errors']++;
                    }
                } else {
                    echo "  " . colorize("[DRY-RUN] Would delete Stripe customer", 'yellow') . "\n";
                }
            }
            
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            echo "  " . colorize("❌ Customer not found in Stripe: {$e->getMessage()}", 'red') . "\n";
            $userMismatch['stripe_status'] = 'NOT_FOUND';
            $userMismatch['actions'][] = 'customer_not_found';
            $stats['errors']++;
        } catch (Throwable $e) {
            echo "  " . colorize("❌ Error: {$e->getMessage()}", 'red') . "\n";
            $stats['errors']++;
        }
        
        if (!empty($userMismatch['actions'])) {
            $mismatches[] = $userMismatch;
        }
        
        echo "\n";
    }
    
    // Also check for FREE users with leftover date fields (even without stripe_customer_id)
    if ($options['fix-db']) {
        echo colorize("\nChecking for FREE users with leftover subscription date fields...\n", 'blue');
        
        $freeUsersWithDates = $pdo->query("
            SELECT id, email, plan_expiration, current_period_end, cancel_at
            FROM users 
            WHERE plan = 'FREE' 
            AND (plan_expiration IS NOT NULL OR current_period_end IS NOT NULL OR cancel_at IS NOT NULL OR cancel_at_period_end = 1)
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($freeUsersWithDates) > 0) {
            echo "  Found " . count($freeUsersWithDates) . " FREE user(s) with leftover date fields\n";
            
            if (!$options['dry-run']) {
                $cleared = $pdo->exec("
                    UPDATE users 
                    SET plan_expiration = NULL,
                        current_period_end = NULL,
                        cancel_at = NULL,
                        cancel_at_period_end = 0
                    WHERE plan = 'FREE' 
                    AND (plan_expiration IS NOT NULL OR current_period_end IS NOT NULL OR cancel_at IS NOT NULL OR cancel_at_period_end = 1)
                ");
                echo "  " . colorize("✅ Cleared date fields for {$cleared} FREE user(s)", 'green') . "\n";
                $stats['db_fixed'] += $cleared;
            } else {
                echo "  " . colorize("[DRY-RUN] Would clear date fields for " . count($freeUsersWithDates) . " FREE user(s)", 'yellow') . "\n";
            }
        } else {
            echo "  " . colorize("✓ No FREE users with leftover date fields found", 'green') . "\n";
        }
    }
    
    // Summary
    echo colorize("\n" . str_repeat("=", 70) . "\n", 'cyan');
    echo colorize("  SUMMARY\n", 'bold');
    echo colorize(str_repeat("=", 70) . "\n", 'cyan');
    echo "Users checked: " . colorize((string)$stats['checked'], 'bold') . "\n";
    echo "Mismatches found: " . colorize((string)$stats['mismatches_found'], $stats['mismatches_found'] > 0 ? 'yellow' : 'green') . "\n";
    
    if ($options['fix-db']) {
        echo "Database fixes applied: " . colorize((string)$stats['db_fixed'], 'green') . "\n";
    }
    if ($options['clear-stripe-metadata']) {
        echo "Metadata cleared: " . colorize((string)$stats['metadata_cleared'], 'green') . "\n";
    }
    if ($options['delete-stripe-customers']) {
        echo "Stripe customers deleted: " . colorize((string)$stats['stripe_customers_deleted'], 'red') . "\n";
    }
    if ($stats['errors'] > 0) {
        echo "Errors: " . colorize((string)$stats['errors'], 'red') . "\n";
    }
    
    if (count($mismatches) > 0 && !$options['dry-run']) {
        echo "\n" . colorize("⚠️  Mismatches that need attention:\n", 'yellow');
        foreach ($mismatches as $m) {
            echo "  - {$m['email']} (DB: {$m['db_plan']}, Stripe: {$m['stripe_status']})\n";
        }
    }
    
    echo "\n";
    
} catch (Throwable $e) {
    echo colorize("\n❌ Fatal error: " . $e->getMessage() . "\n", 'red');
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";

