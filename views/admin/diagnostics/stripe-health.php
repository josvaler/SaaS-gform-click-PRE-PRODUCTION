<?php
declare(strict_types=1);

/**
 * Stripe Health Check Component (Lazy-Loaded)
 * Displays Stripe API connection status, customers, webhooks, and subscriptions
 * Data is loaded via AJAX when accordion opens
 */
?>

<div id="stripe-content" style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Loading State -->
    <div id="stripe-loading" style="text-align: center; padding: 3rem 1rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
        <p style="color: var(--text-secondary); opacity: 0.8;"><?= t('admin.diagnostics.loading') ?></p>
    </div>
    
    <!-- Error State -->
    <div id="stripe-error" style="display: none; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 1rem;">
        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
            <?= t('admin.diagnostics.error_loading') ?>
        </div>
        <div id="stripe-error-message" style="color: var(--text-secondary); font-size: 0.875rem;"></div>
        <button onclick="loadStripeData()" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
            <i class="fas fa-redo" style="margin-right: 0.5rem;"></i><?= t('admin.diagnostics.retry') ?>
        </button>
    </div>
    
    <!-- Content (hidden until loaded) -->
    <div id="stripe-data" style="display: none;">
        <!-- Header -->
        <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
            <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                <?= t('admin.diagnostics.stripe_health_check') ?>
            </h3>
            <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
                <span id="stripe-date"></span>
                <span id="stripe-cache-info" style="margin-left: 1rem; font-size: 0.75rem; opacity: 0.7;"></span>
            </p>
        </div>

        <div id="stripe-metrics" style="display: grid; gap: 1.5rem;">
            <!-- Metrics will be inserted here -->
        </div>
    </div>
</div>

<script>
function loadStripeData() {
    const loadingEl = document.getElementById('stripe-loading');
    const errorEl = document.getElementById('stripe-error');
    const dataEl = document.getElementById('stripe-data');
    const metricsEl = document.getElementById('stripe-metrics');
    const dateEl = document.getElementById('stripe-date');
    const cacheInfoEl = document.getElementById('stripe-cache-info');
    
    // Show loading, hide error and data
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    dataEl.style.display = 'none';
    
    fetch('/admin/diagnostics/stripe.php')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }
            
            const data = result.data;
            const age = result.cached ? Math.floor((Date.now() / 1000 - result.timestamp) / 1000) : 0;
            
            // Hide loading, show data
            loadingEl.style.display = 'none';
            dataEl.style.display = 'block';
            
            // Show date and cache info
            dateEl.textContent = data.current_date || '';
            if (result.cached && age > 0) {
                cacheInfoEl.textContent = `(Cached, ${age}s ago)`;
            } else {
                cacheInfoEl.textContent = '(Live data)';
            }
            
            // Render metrics
            let html = '';
            
            // API Connection Status
            html += `
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-plug" style="color: ${data.api_connected ? '#10b981' : '#ef4444'};"></i>
                        <span>API Connection</span>
                    </h4>
            `;
            
            if (data.api_connected) {
                html += `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-check-circle" style="color: #10b981; font-size: 1.5rem;"></i>
                        <div style="flex: 1;">
                            <div style="color: #10b981; font-weight: 600; margin-bottom: 0.25rem;">
                                <?= t('admin.diagnostics.stripe_api_connected') ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                <?= t('admin.diagnostics.stripe_account_id') ?>: <code style="background: rgba(0, 0, 0, 0.2); padding: 0.125rem 0.375rem; border-radius: 0.25rem;">${escapeHtml(data.account_id || 'N/A')}</code>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-times-circle" style="color: #ef4444; font-size: 1.5rem;"></i>
                        <div style="flex: 1;">
                            <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.25rem;">
                                <?= t('admin.diagnostics.stripe_api_error') ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                ${escapeHtml(data.api_error || 'Unknown error')}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            
            // Customers (only if API connected)
            if (data.api_connected && data.customers) {
                html += `
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
                                        ${data.customers.total || 0}
                                    </div>
                                </div>
                            </div>
                        </div>
                `;
                
                // Duplicates
                if (data.customers.duplicates && data.customers.duplicates.length > 0) {
                    html += `
                        <div style="margin-top: 1rem;">
                            <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.75rem; font-weight: 600;">
                                <?= t('admin.diagnostics.stripe_customers_duplicates') ?> (${data.customers.duplicates.length})
                            </div>
                            <div style="display: grid; gap: 0.5rem;">
                    `;
                    
                    data.customers.duplicates.forEach(dup => {
                        html += `
                            <div style="padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border-radius: 0.5rem; border-left: 3px solid #f59e0b;">
                                <div style="color: var(--text-primary); font-weight: 600; margin-bottom: 0.5rem;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 0.5rem;"></i>
                                    ${escapeHtml(dup.email)}
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        `;
                        
                        dup.customer_ids.forEach(customerId => {
                            html += `
                                <span style="background: rgba(245, 158, 11, 0.2); color: var(--text-primary); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-family: monospace;">
                                    ${escapeHtml(customerId)}
                                </span>
                            `;
                        });
                        
                        html += `
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div style="padding: 0.75rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; border-left: 3px solid #10b981; color: var(--text-secondary); font-size: 0.875rem;">
                            <i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i>
                            <?= t('admin.diagnostics.stripe_no_duplicates') ?>
                        </div>
                    `;
                }
                
                html += '</div>';
            }
            
            // Webhooks (only if API connected)
            if (data.api_connected && data.webhooks && data.webhooks.list && data.webhooks.list.length > 0) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 <?= t('admin.diagnostics.stripe_webhooks') ?>
                        </h4>
                        <div style="display: grid; gap: 1rem;">
                `;
                
                data.webhooks.list.forEach(webhook => {
                    const statusColor = getStatusBadgeColor(webhook.status || 'disabled');
                    const isGformsClick = webhook.url && webhook.url.indexOf('gforms.click') !== -1;
                    const expectedPattern = /gforms\.click\/stripe\/webhook$/;
                    const hasPhpExtension = webhook.url && webhook.url.indexOf('.php') !== -1;
                    const isCorrectFormat = webhook.url && expectedPattern.test(webhook.url);
                    const showWarning = webhook.url !== 'N/A' && isGformsClick && (!isCorrectFormat || hasPhpExtension);
                    
                    html += `
                        <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; border-left: 3px solid ${statusColor};">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                        <?= t('admin.diagnostics.stripe_webhook_url') ?>
                                    </div>
                                    <div style="color: var(--text-primary); font-family: monospace; font-size: 0.875rem; word-break: break-all;">
                    `;
                    
                    if (showWarning) {
                        html += `
                            <div style="padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border-radius: 0.5rem; border-left: 3px solid #f59e0b; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                    <span style="color: #f59e0b; font-size: 0.875rem; font-weight: 600;">Webhook URL Configuration Issue</span>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                    Current URL (incorrect):
                                </div>
                                <div style="color: #ef4444; font-family: monospace; font-size: 0.75rem; margin-bottom: 0.5rem;">
                                    ${escapeHtml(webhook.url)}
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
                        `;
                    } else {
                        html += escapeHtml(webhook.url || 'N/A');
                    }
                    
                    html += `
                                    </div>
                                </div>
                                <div>
                                    <span style="background: ${statusColor}; color: white; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                        ${escapeHtml(webhook.status || 'disabled')}
                                    </span>
                                </div>
                            </div>
                    `;
                    
                    if (webhook.enabled_events && webhook.enabled_events.length > 0) {
                        html += `
                            <div style="margin-top: 0.75rem;">
                                <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.5rem;">
                                    <?= t('admin.diagnostics.stripe_webhook_enabled_events') ?>
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        `;
                        
                        webhook.enabled_events.slice(0, 10).forEach(event => {
                            html += `
                                <span style="background: rgba(148, 163, 184, 0.2); color: var(--text-primary); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                    ${escapeHtml(event)}
                                </span>
                            `;
                        });
                        
                        if (webhook.enabled_events.length > 10) {
                            html += `
                                <span style="color: var(--text-secondary); font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                    +${webhook.enabled_events.length - 10} more
                                </span>
                            `;
                        }
                        
                        html += `
                                </div>
                            </div>
                        `;
                    }
                    
                    html += `
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(148, 163, 184, 0.1);">
                                <div style="color: var(--text-secondary); font-size: 0.75rem;">
                                    ID: <code style="background: rgba(0, 0, 0, 0.2); padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.75rem;">${escapeHtml(webhook.id || 'N/A')}</code>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            } else if (data.api_connected) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 <?= t('admin.diagnostics.stripe_webhooks') ?>
                        </h4>
                        <div style="padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                            <?= t('admin.diagnostics.stripe_no_webhooks') ?>
                        </div>
                    </div>
                `;
            }
            
            // Subscriptions (only if API connected)
            if (data.api_connected && data.subscriptions) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 <?= t('admin.diagnostics.stripe_subscriptions') ?>
                        </h4>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                                <i class="fas fa-credit-card" style="color: var(--text-primary); font-size: 1.25rem;"></i>
                                <div style="flex: 1;">
                                    <div style="color: var(--text-secondary); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                        Total Subscriptions
                                    </div>
                                    <div style="color: var(--text-primary); font-size: 1.5rem; font-weight: 700;">
                                        ${data.subscriptions.total || 0}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            metricsEl.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading Stripe data:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('stripe-error-message').textContent = error.message || 'Failed to load Stripe data';
        });
}

function getStatusBadgeColor(status) {
    status = status.toLowerCase();
    switch (status) {
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

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Auto-load when this component is visible (accordion opens)
if (typeof window !== 'undefined') {
    window.loadStripeData = loadStripeData;
}
</script>
