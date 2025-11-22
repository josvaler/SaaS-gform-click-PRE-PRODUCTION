<?php
declare(strict_types=1);

/**
 * Stripe Health Check Component
 * Displays Stripe API connection status, customers, webhooks, and subscriptions
 */

// Get Stripe config - bootstrap should already be loaded by admin.php
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
            $customers = $customersResponse->data ?? [];
            
            // Detect duplicates by email
            $emails = [];
            foreach ($customers as $customer) {
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
            $webhooks = $webhooksResponse->data ?? [];
        } catch (Exception $e) {
            // Error fetching webhooks, but continue
        }
        
        // Get subscriptions (limit 50)
        try {
            $subscriptionsResponse = $stripe->subscriptions->all(['limit' => 50]);
            $subscriptions = $subscriptionsResponse->data ?? [];
        } catch (Exception $e) {
            // Error fetching subscriptions, but continue
        }
        
    } catch (\Stripe\Exception\AuthenticationException $e) {
        $apiError = 'Invalid STRIPE_SECRET_KEY: ' . $e->getMessage();
    } catch (Exception $e) {
        $apiError = 'Connection error: ' . $e->getMessage();
    }
}

// Helper function to get status badge color
function getStatusBadgeColor(string $status): string
{
    $status = strtolower($status);
    switch ($status) {
        case 'active':
        case 'enabled':
            return '#10b981'; // green
        case 'canceled':
        case 'cancelled':
        case 'disabled':
            return '#ef4444'; // red
        case 'past_due':
        case 'unpaid':
            return '#f59e0b'; // yellow
        default:
            return '#6b7280'; // gray
    }
}
?>

<div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Header -->
    <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
        <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
            <?= t('admin.diagnostics.stripe_health_check') ?>
        </h3>
        <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
            <?= htmlspecialchars($currentDate) ?>
        </p>
    </div>

    <div style="display: grid; gap: 1.5rem;">
        <!-- API Connection Status Card -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-plug" style="color: <?= $apiConnected ? '#10b981' : '#ef4444' ?>;"></i>
                <span>API Connection</span>
            </h4>
            
            <?php if ($apiConnected): ?>
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fas fa-check-circle" style="color: #10b981; font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <div style="color: #10b981; font-weight: 600; margin-bottom: 0.25rem;">
                            <?= t('admin.diagnostics.stripe_api_connected') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                            <?= t('admin.diagnostics.stripe_account_id') ?>: <code style="background: rgba(0, 0, 0, 0.2); padding: 0.125rem 0.375rem; border-radius: 0.25rem;"><?= htmlspecialchars($accountId) ?></code>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3);">
                    <i class="fas fa-times-circle" style="color: #ef4444; font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.25rem;">
                            <?= t('admin.diagnostics.stripe_api_error') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                            <?= htmlspecialchars($apiError ?? 'Unknown error') ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($apiConnected): ?>
            <!-- Customers Card -->
            <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                    🔶 <?= t('admin.diagnostics.stripe_customers') ?>
                </h4>
                
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                        <i class="fas fa-users" style="color: var(--text-primary); font-size: 1.25rem;"></i>
                        <div style="flex: 1;">
                            <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                <?= t('admin.diagnostics.stripe_customers_total') ?>
                            </div>
                            <div style="color: var(--text-primary); font-size: 1.5rem; font-weight: 700;">
                                <?= count($customers) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($duplicates)): ?>
                    <div style="margin-top: 1rem;">
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.75rem; font-weight: 600;">
                            <?= t('admin.diagnostics.stripe_customers_duplicates') ?> (<?= count($duplicates) ?>)
                        </div>
                        <div style="display: grid; gap: 0.5rem;">
                            <?php foreach ($duplicates as $dup): ?>
                                <div style="padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border-radius: 0.5rem; border-left: 3px solid #f59e0b;">
                                    <div style="color: var(--text-primary); font-weight: 600; margin-bottom: 0.5rem;">
                                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 0.5rem;"></i>
                                        <?= htmlspecialchars($dup['email']) ?>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        <?php foreach ($dup['customer_ids'] as $customerId): ?>
                                            <span style="background: rgba(245, 158, 11, 0.2); color: var(--text-primary); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-family: monospace;">
                                                <?= htmlspecialchars($customerId) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 0.75rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; border-left: 3px solid #10b981; color: var(--text-secondary); font-size: 0.875rem;">
                        <i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i>
                        <?= t('admin.diagnostics.stripe_no_duplicates') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Webhooks Card -->
            <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                    🔶 <?= t('admin.diagnostics.stripe_webhooks') ?>
                </h4>
                
                <?php if (!empty($webhooks)): ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($webhooks as $webhook): ?>
                            <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; border-left: 3px solid <?= getStatusBadgeColor($webhook->status ?? 'disabled') ?>;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                            <?= t('admin.diagnostics.stripe_webhook_url') ?>
                                        </div>
                                        <div style="color: var(--text-primary); font-family: monospace; font-size: 0.875rem; word-break: break-all;">
                                            <?php 
                                            $webhookUrl = $webhook->url ?? 'N/A';
                                            // Only warn if webhook is for gforms.click but has wrong format
                                            $isGformsClick = strpos($webhookUrl, 'gforms.click') !== false;
                                            $expectedPattern = '/gforms\.click\/stripe\/webhook$/';
                                            $hasPhpExtension = strpos($webhookUrl, '.php') !== false;
                                            $isCorrectFormat = preg_match($expectedPattern, $webhookUrl);
                                            
                                            // Only show warning if it's for gforms.click but has wrong format
                                            if ($webhookUrl !== 'N/A' && $isGformsClick && (!$isCorrectFormat || $hasPhpExtension)):
                                            ?>
                                                <div style="padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border-radius: 0.5rem; border-left: 3px solid #f59e0b; margin-bottom: 0.75rem;">
                                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                                        <span style="color: #f59e0b; font-size: 0.875rem; font-weight: 600;">Webhook URL Configuration Issue</span>
                                                    </div>
                                                    <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                                        Current URL (incorrect):
                                                    </div>
                                                    <div style="color: #ef4444; font-family: monospace; font-size: 0.75rem; margin-bottom: 0.5rem;">
                                                        <?= htmlspecialchars($webhookUrl) ?>
                                                    </div>
                                                    <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                                        Should be:
                                                    </div>
                                                    <div style="color: #10b981; font-family: monospace; font-size: 0.75rem; margin-bottom: 0.5rem;">
                                                        https://gforms.click/stripe/webhook
                                                    </div>
                                                    <div style="color: var(--text-secondary); font-size: 0.7rem; font-style: italic;">
                                                        Update this in your Stripe Dashboard → Developers → Webhooks
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <?= htmlspecialchars($webhookUrl) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span style="background: <?= getStatusBadgeColor($webhook->status ?? 'disabled') ?>; color: white; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                            <?= htmlspecialchars($webhook->status ?? 'disabled') ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($webhook->enabled_events)): ?>
                                    <div style="margin-top: 0.75rem;">
                                        <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.5rem;">
                                            <?= t('admin.diagnostics.stripe_webhook_enabled_events') ?>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            <?php foreach (array_slice($webhook->enabled_events, 0, 10) as $event): ?>
                                                <span style="background: rgba(148, 163, 184, 0.2); color: var(--text-primary); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                                    <?= htmlspecialchars($event) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($webhook->enabled_events) > 10): ?>
                                                <span style="color: var(--text-secondary); font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                    +<?= count($webhook->enabled_events) - 10 ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(148, 163, 184, 0.1);">
                                    <div style="color: var(--text-secondary); font-size: 0.75rem;">
                                        ID: <code style="background: rgba(0, 0, 0, 0.2); padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.75rem;"><?= htmlspecialchars($webhook->id) ?></code>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        <?= t('admin.diagnostics.stripe_no_webhooks') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Subscriptions Card -->
            <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                    🔶 <?= t('admin.diagnostics.stripe_subscriptions') ?>
                </h4>
                
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                        <i class="fas fa-credit-card" style="color: var(--text-primary); font-size: 1.25rem;"></i>
                        <div style="flex: 1;">
                            <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                <?= t('admin.diagnostics.stripe_subscriptions_total') ?>
                            </div>
                            <div style="color: var(--text-primary); font-size: 1.5rem; font-weight: 700;">
                                <?= count($subscriptions) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($subscriptions)): ?>
                    <div style="display: grid; gap: 0.75rem; max-height: 400px; overflow-y: auto;">
                        <?php foreach ($subscriptions as $sub): ?>
                            <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; border-left: 3px solid <?= getStatusBadgeColor($sub->status ?? 'unknown') ?>;">
                                <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
                                    <div style="flex: 1;">
                                        <div style="margin-bottom: 0.5rem;">
                                            <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                                <?= t('admin.diagnostics.stripe_subscription_id') ?>
                                            </div>
                                            <div style="color: var(--text-primary); font-family: monospace; font-size: 0.875rem;">
                                                <?= htmlspecialchars($sub->id) ?>
                                            </div>
                                        </div>
                                        <div style="margin-bottom: 0.5rem;">
                                            <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                                <?= t('admin.diagnostics.stripe_subscription_customer') ?>
                                            </div>
                                            <div style="color: var(--text-primary); font-family: monospace; font-size: 0.875rem;">
                                                <?= htmlspecialchars($sub->customer ?? 'N/A') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span style="background: <?= getStatusBadgeColor($sub->status ?? 'unknown') ?>; color: white; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; white-space: nowrap;">
                                            <?= htmlspecialchars($sub->status ?? 'unknown') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        <?= t('admin.diagnostics.stripe_no_subscriptions') ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

