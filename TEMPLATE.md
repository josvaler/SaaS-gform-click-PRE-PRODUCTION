# Billing Page Template Refactoring Plan

## Overview

This document outlines the plan to refactor the `billing.php` page by extracting the presentation layer into a reusable template. This separation of concerns will improve maintainability, reusability, and testability.

## Current Structure

**File:** `/var/www/html/public/billing.php`
- **Lines 1-151:** Business logic (Stripe API calls, database operations, session management)
- **Lines 152-221:** Presentation layer (HTML markup)
- **Lines 223-225:** Footer include

## Proposed Structure

### File Organization

```
/var/www/html/
├── public/
│   └── billing.php (Controller - Business Logic)
└── views/
    ├── partials/
    │   ├── header.php (Existing)
    │   └── footer.php (Existing)
    └── billing.php (NEW - Template)
```

## Step-by-Step Implementation

### Step 1: Create the Template File

**File:** `/var/www/html/views/billing.php`

**Purpose:** Contains only the HTML presentation layer

**Template Variables Required:**
- `$user` - User session data array
- `$currentPlan` - Current plan string ('FREE' or 'PREMIUM')
- `$isPremium` - Boolean indicating premium status
- `$status` - Status message string (null, 'success', 'cancelled', 'error', 'portal_error', 'portal_missing_customer', 'customer_missing')
- `$hasScheduledCancellation` - Boolean indicating if cancellation is scheduled
- `$cancelDateFormatted` - Formatted cancellation date string (e.g., "Jan 15, 2024") or null

**Template Code:**

```php
<?php
/**
 * Billing Page Template
 * 
 * Expected variables:
 * @var array $user - User session data
 * @var string $currentPlan - Current plan (FREE/PREMIUM)
 * @var bool $isPremium - Whether user has premium plan
 * @var string|null $status - Status message (success/cancelled/error/etc)
 * @var bool $hasScheduledCancellation - Whether cancellation is scheduled
 * @var string|null $cancelDateFormatted - Formatted cancellation date
 */
?>
<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 640px;">
        <!-- Current Plan Display -->
        <div class="card" style="margin-bottom: 2rem; <?php echo $isPremium ? 'border: 2px solid rgba(34, 211, 238, 0.5);' : 'border: 2px solid rgba(148, 163, 184, 0.3);'; ?>">
            <div style="text-align: center; padding: 1.5rem 0;">
                <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">Current Plan</div>
                <div style="font-size: 3.5rem; font-weight: 700; margin-bottom: 0.5rem; background: <?php echo $isPremium ? 'linear-gradient(135deg, #6366f1, #22d3ee);' : 'linear-gradient(135deg, #94a3b8, #64748b);'; ?> -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    <?php echo $isPremium ? 'PREMIUM' : 'FREE'; ?>
                </div>
                <div style="font-size: 1.1rem; color: var(--color-text-muted);">
                    <?php echo $isPremium ? '🎉 Unlimited Access' : 'Limited Access'; ?>
                </div>
                <?php if ($hasScheduledCancellation): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem; margin-left: 1rem; margin-right: 1rem;">
                        <strong>Subscription Cancellation Scheduled</strong><br>
                        Your subscription will cancel on <?= htmlspecialchars($cancelDateFormatted) ?>. You'll continue to have access until then.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Premium Plan — 250 Credits / Month</h2>
                    <p class="text-muted">$4.99 billed monthly</p>
                </div>
                <span class="badge">Stripe Secure</span>
            </div>

            <?php if ($status === 'success'): ?>
                <div class="alert alert-success">Subscription confirmed. Enjoy unlimited access!</div>
            <?php elseif ($status === 'cancelled'): ?>
                <div class="alert alert-error">Checkout cancelled. You were not charged.</div>
            <?php elseif ($status === 'error'): ?>
                <div class="alert alert-error">We could not reach Stripe. Please try again in a moment.</div>
            <?php elseif ($status === 'portal_error'): ?>
                <div class="alert alert-error">Unable to access billing portal. Please contact support.</div>
            <?php elseif ($status === 'portal_missing_customer'): ?>
                <div class="alert alert-error">We could not locate your subscription in Stripe. Please complete checkout first or contact support to sync your account.</div>
            <?php elseif ($status === 'customer_missing'): ?>
                <div class="alert alert-error">We couldn't find your existing Stripe subscription. Please contact support before trying to upgrade again.</div>
            <?php endif; ?>

            <p style="margin-bottom: 1.5rem;" class="text-muted">
                Premium unlocks up to 250 credits per month for background removals and image transformations, priority processing, and concierge support.
            </p>

            <?php if (!$isPremium): ?>
                <form action="/stripe/checkout.php" method="POST" style="margin-bottom: 1rem;">
                    <button class="btn btn-primary" type="submit" style="width: 100%;">Upgrade with Stripe Checkout</button>
                </form>
            <?php else: ?>
                <div class="alert alert-success">You are currently on the Premium plan.</div>
            <?php endif; ?>

            <?php if ($isPremium): ?>
                <form action="/stripe/portal.php" method="POST">
                    <button class="btn btn-outline" type="submit" style="width: 100%;">Manage Subscription</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
```

### Step 2: Refactor billing.php Controller

**File:** `/var/www/html/public/billing.php`

**Changes Required:**
1. Keep all business logic (lines 1-151) unchanged
2. Remove HTML markup (lines 152-221)
3. Add template variable preparation
4. Include the template file

**Refactored Code:**

```php
<?php
declare(strict_types=1);

use Stripe\StripeClient;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();

// ============================================
// BUSINESS LOGIC SECTION (Keep unchanged)
// ============================================
try {
    $pdo = db();
    if (!empty($user['google_id'])) {
        $userRepo = new UserRepository($pdo);
        $dbUser = $userRepo->findByGoogleId($user['google_id']);

        if ($dbUser) {
            $user['id'] = (int)$dbUser['id'];
            $user['plan'] = $dbUser['plan'];
            $user['stripe_customer_id'] = $dbUser['stripe_customer_id'] ?? ($user['stripe_customer_id'] ?? null);
            // Sync Stripe subscription metadata from database
            $user['stripe_subscription_id'] = $dbUser['stripe_subscription_id'] ?? null;
            $user['cancel_at_period_end'] = isset($dbUser['cancel_at_period_end']) ? (bool)$dbUser['cancel_at_period_end'] : null;
            $user['cancel_at'] = $dbUser['cancel_at'] ?? null;
            $user['current_period_end'] = $dbUser['current_period_end'] ?? null;
            $_SESSION['user'] = $user;
            
            // Check Stripe subscription cancellation status
            if (class_exists(StripeClient::class) && !empty($stripeConfig['secret_key'])) {
                $stripeSubscriptionId = $dbUser['stripe_subscription_id'] ?? null;
                $stripeCustomerId = $dbUser['stripe_customer_id'] ?? null;
                
                if ($stripeSubscriptionId || $stripeCustomerId) {
                    try {
                        $stripe = new StripeClient($stripeConfig['secret_key']);
                        $subscription = null;
                        
                        // Fetch subscription by ID if available, otherwise list by customer ID
                        if ($stripeSubscriptionId) {
                            try {
                                $subscription = $stripe->subscriptions->retrieve($stripeSubscriptionId, []);
                            } catch (Throwable $e) {
                                // Subscription might not exist, try listing by customer
                                if ($stripeCustomerId) {
                                    $subscriptions = $stripe->subscriptions->all([
                                        'customer' => $stripeCustomerId,
                                        'status' => 'all',
                                        'limit' => 1,
                                    ]);
                                    if (!empty($subscriptions->data)) {
                                        $subscription = $subscriptions->data[0];
                                    }
                                }
                            }
                        } elseif ($stripeCustomerId) {
                            $subscriptions = $stripe->subscriptions->all([
                                'customer' => $stripeCustomerId,
                                'status' => 'all',
                                'limit' => 1,
                            ]);
                            if (!empty($subscriptions->data)) {
                                $subscription = $subscriptions->data[0];
                            }
                        }
                        
                        if ($subscription && method_exists($subscription, 'toArray')) {
                            $subArray = $subscription->toArray();
                            
                            // Extract cancellation info using array access
                            $cancelAtPeriodEnd = isset($subArray['cancel_at_period_end']) ? (bool)$subArray['cancel_at_period_end'] : false;
                            $cancelAt = isset($subArray['cancel_at']) && $subArray['cancel_at'] 
                                ? gmdate('Y-m-d H:i:s', (int)$subArray['cancel_at']) 
                                : null;
                            $currentPeriodEnd = isset($subArray['current_period_end']) && $subArray['current_period_end']
                                ? gmdate('Y-m-d H:i:s', (int)$subArray['current_period_end'])
                                : null;
                            $subscriptionId = $subArray['id'] ?? null;
                            
                            // Compare with database values and update if different
                            $needsUpdate = false;
                            $updates = [];
                            
                            if ($subscriptionId && ($dbUser['stripe_subscription_id'] ?? null) !== $subscriptionId) {
                                $updates['stripe_subscription_id'] = $subscriptionId;
                                $needsUpdate = true;
                            }
                            
                            $dbCancelAtPeriodEnd = isset($dbUser['cancel_at_period_end']) ? (bool)$dbUser['cancel_at_period_end'] : false;
                            if ($cancelAtPeriodEnd !== $dbCancelAtPeriodEnd) {
                                $updates['cancel_at_period_end'] = $cancelAtPeriodEnd ? 1 : 0;
                                $needsUpdate = true;
                            }
                            
                            if ($cancelAt !== ($dbUser['cancel_at'] ?? null)) {
                                $updates['cancel_at'] = $cancelAt;
                                $needsUpdate = true;
                            }
                            
                            if ($currentPeriodEnd !== ($dbUser['current_period_end'] ?? null)) {
                                $updates['current_period_end'] = $currentPeriodEnd;
                                $needsUpdate = true;
                            }
                            
                            if ($needsUpdate && !empty($updates)) {
                                $userRepo->updateSubscriptionMetadata((int)$dbUser['id'], $updates);
                                
                                // Update session with latest values
                                $user['stripe_subscription_id'] = $subscriptionId ?? $user['stripe_subscription_id'] ?? null;
                                $user['cancel_at_period_end'] = $cancelAtPeriodEnd;
                                $user['cancel_at'] = $cancelAt;
                                $user['current_period_end'] = $currentPeriodEnd;
                                $_SESSION['user'] = $user;
                            } else {
                                // Even if no updates needed, ensure session has latest database values
                                $user['stripe_subscription_id'] = $dbUser['stripe_subscription_id'] ?? null;
                                $user['cancel_at_period_end'] = isset($dbUser['cancel_at_period_end']) ? (bool)$dbUser['cancel_at_period_end'] : null;
                                $user['cancel_at'] = $dbUser['cancel_at'] ?? null;
                                $user['current_period_end'] = $dbUser['current_period_end'] ?? null;
                                $_SESSION['user'] = $user;
                            }
                        }
                    } catch (Throwable $stripeException) {
                        // Silently continue if Stripe API call fails
                        error_log('Billing subscription check error: ' . $stripeException->getMessage());
                    }
                }
            }
        }
    }
} catch (Throwable $exception) {
    // Ignore sync failures; fall back to the session copy
}

// ============================================
// TEMPLATE VARIABLE PREPARATION
// ============================================
$user = session_user();
$pageTitle = 'Billing';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard.php'],
    ['label' => 'Price', 'href' => '/price.php'],
    ['label' => 'My Plan', 'href' => '/billing.php'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout.php'],
];

// Prepare template variables
$status = $_GET['status'] ?? null;
$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$hasScheduledCancellation = !empty($user['cancel_at_period_end']) || !empty($user['cancel_at']);
$cancelDate = $user['cancel_at'] ?? $user['current_period_end'] ?? null;
$cancelDateFormatted = $cancelDate ? date('M j, Y', strtotime($cancelDate)) : 'period end';

// ============================================
// RENDER TEMPLATE
// ============================================
require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/billing.php';
require __DIR__ . '/../views/partials/footer.php';
```

## Template Variable Reference

| Variable | Type | Description | Example Values |
|----------|------|-------------|----------------|
| `$user` | array | User session data | `['id' => 1, 'plan' => 'PREMIUM', ...]` |
| `$currentPlan` | string | Current plan name | `'FREE'` or `'PREMIUM'` |
| `$isPremium` | bool | Premium status flag | `true` or `false` |
| `$status` | string\|null | Status message | `null`, `'success'`, `'cancelled'`, `'error'`, `'portal_error'`, `'portal_missing_customer'`, `'customer_missing'` |
| `$hasScheduledCancellation` | bool | Cancellation scheduled flag | `true` or `false` |
| `$cancelDateFormatted` | string\|null | Formatted cancellation date | `"Jan 15, 2024"` or `null` |

## Usage Examples

### Example 1: Standard Usage (Current Implementation)

```php
// In billing.php
$status = $_GET['status'] ?? null;
$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$hasScheduledCancellation = !empty($user['cancel_at_period_end']) || !empty($user['cancel_at']);
$cancelDateFormatted = $cancelDate ? date('M j, Y', strtotime($cancelDate)) : 'period end';

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/billing.php';
require __DIR__ . '/../views/partials/footer.php';
```

### Example 2: Preview/Demo Page

Create `/var/www/html/public/billing-preview.php`:

```php
<?php
require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$pageTitle = 'Billing Preview';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard.php'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout.php'],
];

// Override template variables for preview
$status = 'success';
$currentPlan = 'PREMIUM';
$isPremium = true;
$hasScheduledCancellation = false;
$cancelDateFormatted = null;

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/billing.php';
require __DIR__ . '/../views/partials/footer.php';
```

### Example 3: Using render() Helper Function

Alternative approach using the existing `render()` helper:

```php
// After preparing variables
render('billing', [
    'user' => $user,
    'currentPlan' => $currentPlan,
    'isPremium' => $isPremium,
    'status' => $status,
    'hasScheduledCancellation' => $hasScheduledCancellation,
    'cancelDateFormatted' => $cancelDateFormatted,
]);

require __DIR__ . '/../views/partials/footer.php';
```

**Note:** If using `render()`, the template file should be updated to extract variables from the local scope or use the `extract()` function that `render()` provides.

## Benefits

### 1. Separation of Concerns
- **Business Logic** stays in controller (`billing.php`)
- **Presentation** isolated in template (`views/billing.php`)
- Easier to locate and modify specific functionality

### 2. Reusability
- Template can be included in multiple pages
- Can create preview/demo pages easily
- Can be used in different contexts (admin panel, user dashboard, etc.)

### 3. Maintainability
- HTML changes only require editing the template file
- Business logic changes don't affect presentation
- Clearer code organization

### 4. Testability
- Template can be tested with mock data
- Business logic can be unit tested independently
- Easier to create test fixtures

### 5. Consistency
- Same UI structure across different pages
- Centralized styling and markup
- Easier to maintain design consistency

## Implementation Checklist

- [ ] Create `/var/www/html/views/billing.php` template file
- [ ] Copy HTML markup (lines 152-221) from `billing.php` to template
- [ ] Add template variable documentation comments
- [ ] Refactor `/var/www/html/public/billing.php`:
  - [ ] Keep business logic unchanged (lines 1-151)
  - [ ] Add template variable preparation section
  - [ ] Replace HTML markup with template include
  - [ ] Test that all variables are properly set
- [ ] Test the refactored page:
  - [ ] Verify FREE plan display
  - [ ] Verify PREMIUM plan display
  - [ ] Test all status messages
  - [ ] Test cancellation warning display
  - [ ] Test upgrade button functionality
  - [ ] Test manage subscription button functionality
- [ ] Optional: Create preview/demo page using the template
- [ ] Update documentation if needed

## Testing Scenarios

### Test Case 1: Free User View
```
Variables:
- $isPremium = false
- $currentPlan = 'FREE'
- $status = null
- $hasScheduledCancellation = false

Expected: Shows FREE plan card, upgrade button visible
```

### Test Case 2: Premium User View
```
Variables:
- $isPremium = true
- $currentPlan = 'PREMIUM'
- $status = null
- $hasScheduledCancellation = false

Expected: Shows PREMIUM plan card, "You are currently on Premium" message, manage subscription button
```

### Test Case 3: Success Status
```
Variables:
- $status = 'success'

Expected: Green success alert displayed
```

### Test Case 4: Scheduled Cancellation
```
Variables:
- $hasScheduledCancellation = true
- $cancelDateFormatted = "Jan 15, 2024"

Expected: Yellow warning alert with cancellation date displayed
```

### Test Case 5: Error Statuses
```
Variables:
- $status = 'error' | 'portal_error' | 'portal_missing_customer' | 'customer_missing'

Expected: Appropriate error message displayed
```

## Migration Notes

### Before Refactoring
- All code in single file (`billing.php`)
- HTML mixed with business logic
- Hard to reuse presentation layer

### After Refactoring
- Business logic in controller (`billing.php`)
- Presentation in template (`views/billing.php`)
- Clear separation of concerns
- Template is reusable

### Backward Compatibility
- No breaking changes to functionality
- Same URL endpoints
- Same user experience
- Only internal structure changes

## Future Enhancements

1. **Template Inheritance**: Consider implementing a template inheritance system for shared layouts
2. **Component System**: Break down template into smaller reusable components (plan card, status alerts, etc.)
3. **CSS Extraction**: Move inline styles to external CSS classes
4. **Internationalization**: Add i18n support for template strings
5. **Template Engine**: Consider using a template engine (Twig, Blade, etc.) for more advanced features

## Related Files

- `/var/www/html/public/billing.php` - Controller file
- `/var/www/html/views/partials/header.php` - Header partial
- `/var/www/html/views/partials/footer.php` - Footer partial
- `/var/www/html/config/helpers.php` - Contains `render()` helper function

## Notes

- Template uses PHP's variable scope - variables set before `require` are available in the template
- All user input should be sanitized before passing to template (use `htmlspecialchars()`)
- Template assumes header and footer partials are included separately
- Status messages are hardcoded in template - consider moving to a translation system
- Inline styles are preserved for now - can be refactored to CSS classes later

---

**Created:** [Date]
**Last Updated:** [Date]
**Status:** Planning Phase

