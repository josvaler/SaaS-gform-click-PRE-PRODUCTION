<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$user = session_user();
$currentPlan = $user ? ($user['plan'] ?? 'FREE') : 'FREE';
$isLoggedIn = $user !== null;
$isEnterprise = $currentPlan === 'ENTERPRISE';

// Check Early Bird promotion
$earlyBirdData = get_early_bird_count();
$promotionsEnabled = env('GFORMS_PROMOTIONS', 'false') === 'true';
$isEarlyBird = $promotionsEnabled && $earlyBirdData['is_available'];

$pageTitle = t('pricing.title');
$navLinksLeft = [
    ['label' => t('nav.home'), 'href' => '/'],
];
if ($isLoggedIn) {
    $navLinksLeft[] = ['label' => t('nav.dashboard'), 'href' => '/dashboard'];
}
$navLinksRight = $isLoggedIn
    ? [
        ['label' => t('nav.my_plan'), 'href' => '/billing'],
        ['label' => t('nav.logout'), 'href' => '/logout'],
    ]
    : [
        ['label' => t('nav.login'), 'href' => '/login'],
    ];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 3rem; margin-bottom: 1rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?= t('pricing.choose_plan_new') ?></h1>
            <p style="font-size: 1.25rem; color: var(--color-text-muted); max-width: 800px; margin: 0 auto;"><?= t('pricing.subtitle_new') ?></p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: stretch;">
            <!-- FREE Plan -->
            <div class="card" style="<?= $currentPlan === 'FREE' ? 'border: 2px solid #60a5fa;' : '' ?> display: flex; flex-direction: column; height: 100%;">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.free') ?></h2>
                        <p class="text-muted"><?= t('pricing.free_subtitle') ?></p>
                    </div>
                    <span class="badge free-badge"><?= t('pricing.free_badge') ?></span>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        $0<span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.per_month') ?></span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; flex: 1;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_links_per_day') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_links_per_month') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_random_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_basic_stats_new') ?></li>
                        <li style="padding: 0.5rem 0;">✗ <?= t('pricing.feature_no_custom_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✗ <?= t('pricing.feature_no_expiration') ?></li>
                        <li style="padding: 0.5rem 0;">✗ <?= t('pricing.feature_no_link_dashboard') ?></li>
                    </ul>
                    <div style="margin-top: auto;">
                        <?php if ($currentPlan === 'FREE'): ?>
                            <div class="alert alert-info" style="padding: 0.75rem 1rem; text-align: center; min-height: 48px; display: flex; align-items: center; justify-content: center;"><?= t('pricing.current_plan') ?></div>
                        <?php elseif ($isEnterprise): ?>
                            <div class="alert alert-info" style="padding: 0.75rem 1rem; text-align: center; min-height: 48px; display: flex; align-items: center; justify-content: center; opacity: 0.7;"><?= t('pricing.highest_plan') ?></div>
                        <?php elseif ($isLoggedIn): ?>
                            <a href="/dashboard" class="btn btn-outline" style="width: 100%; padding: 0.75rem 1rem; min-height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 600;"><?= t('pricing.continue_free') ?></a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-outline" style="width: 100%; padding: 0.75rem 1rem; min-height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 600;"><?= t('pricing.start_free') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PREMIUM Plan -->
            <div class="card premium-plan-card" style="<?= $currentPlan === 'PREMIUM' ? 'border: 2px solid #22d3ee;' : 'border: 2px solid rgba(14, 165, 233, 0.3);'; ?> display: flex; flex-direction: column; height: 100%;">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.premium') ?></h2>
                        <p class="text-muted"><?= t('pricing.premium_subtitle') ?></p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span class="badge premium-badge" style="font-size: 0.65rem; padding: 0.25rem 0.75rem; font-weight: 600;"><?= t('pricing.premium_badge') ?></span>
                        <span class="badge" style="background: rgba(14, 165, 233, 0.2); color: var(--accent-primary); font-size: 0.65rem; padding: 0.25rem 0.75rem; font-weight: 600; border-radius: 0.5rem;"><?= t('pricing.premium_personal_use') ?></span>
                    </div>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                    <!-- Billing Period Toggle -->
                    <?php if (!$isEnterprise): ?>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 1rem; padding: 0.5rem; background: rgba(148, 163, 184, 0.1); border-radius: 0.75rem;">
                        <label style="display: flex; align-items: center; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                            <input type="radio" name="premium_billing" value="monthly" checked class="premium-billing-toggle" style="margin-right: 0.5rem; cursor: pointer;">
                            <?= t('pricing.billing_monthly') ?>
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); position: relative;">
                            <input type="radio" name="premium_billing" value="annual" class="premium-billing-toggle" style="margin-right: 0.5rem; cursor: pointer;">
                            <?= t('pricing.billing_annual') ?>
                            <span class="premium-discount-badge" style="display: none; margin-left: 0.5rem; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-weight: 700;">20% OFF</span>
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Price Display -->
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <?php if ($isEarlyBird): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <span class="badge" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1f2937; font-size: 0.7rem; padding: 0.3rem 0.75rem; border-radius: 0.5rem; font-weight: 700;">🔥 <?= t('promo.early_bird_badge') ?></span>
                            </div>
                        <?php endif; ?>
                        <div id="premium-price" style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <?php if ($isEarlyBird): ?>
                                $1.99<span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.per_month') ?></span>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted); text-decoration: line-through; margin-top: 0.25rem;">$4.99/mes</div>
                            <?php else: ?>
                                $4.99<span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.per_month') ?></span>
                            <?php endif; ?>
                        </div>
                        <div id="premium-annual-savings" style="display: none; font-size: 0.85rem; color: #10b981; font-weight: 600;">
                            <?php if ($isEarlyBird): ?>
                                <?= t('promo.save_per_year_early_bird') ?>
                            <?php else: ?>
                                Save $3.89 per year
                            <?php endif; ?>
                        </div>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; flex: 1;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_600_links_month') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_no_daily_limit') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_custom_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_expiration') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_advanced_stats_new') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_link_management_new') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_custom_qr') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_team_roles') ?></li>
                    </ul>
                    <div style="margin-top: auto;">
                        <?php if ($currentPlan === 'PREMIUM'): ?>
                            <div class="alert alert-success" style="padding: 0.75rem 1rem; text-align: center; min-height: 48px; display: flex; align-items: center; justify-content: center;"><?= t('pricing.current_plan') ?></div>
                        <?php elseif ($isEnterprise): ?>
                            <div class="alert alert-info" style="padding: 0.75rem 1rem; text-align: center; min-height: 48px; display: flex; align-items: center; justify-content: center; opacity: 0.7;"><?= t('pricing.highest_plan') ?></div>
                        <?php elseif ($isLoggedIn): ?>
                            <form action="/stripe/checkout" method="POST" id="premium-checkout-form" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="billing_period" id="premium-billing-period" value="monthly">
                                <button type="submit" id="premium-submit-button" class="btn btn-primary" style="width: 100%; padding: 0.75rem 1rem; min-height: 48px; font-weight: 600;"><?= t('pricing.start_now_premium') ?></button>
                            </form>
                        <?php else: ?>
                            <a href="/login" id="premium-login-link" class="btn btn-primary" style="width: 100%; padding: 0.75rem 1rem; min-height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 600;"><?= t('pricing.start_now_premium') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ENTERPRISE Plan -->
            <div class="card enterprise-plan-card" style="<?= $currentPlan === 'ENTERPRISE' ? 'border: 2px solid #a78bfa;' : 'border: 2px solid rgba(167, 139, 250, 0.3);'; ?> display: flex; flex-direction: column; height: 100%;">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.enterprise') ?></h2>
                        <p class="text-muted"><?= t('pricing.enterprise_subtitle') ?></p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span class="badge enterprise-badge" style="font-size: 0.65rem; padding: 0.25rem 0.75rem; font-weight: 600;"><?= t('pricing.enterprise_badge') ?></span>
                        <span class="badge" style="background: rgba(167, 139, 250, 0.2); color: #a78bfa; font-size: 0.65rem; padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-weight: 600;"><?= t('pricing.enterprise_business_only') ?></span>
                    </div>
                </div>
                <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.custom') ?></span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1.5rem; font-style: italic;">
                        <?= t('pricing.enterprise_custom_price_desc') ?>
                    </p>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; flex: 1;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_custom_pricing') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_unlimited_links') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_no_business_limits') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_multilingual_support') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_flexible_billing') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_roi_optimized') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_enterprise_billing') ?></li>
                    </ul>
                    <div style="margin-top: auto;">
                        <?php if ($currentPlan === 'ENTERPRISE'): ?>
                            <div class="alert alert-success" style="padding: 0.75rem 1rem; text-align: center; min-height: 48px; display: flex; align-items: center; justify-content: center;"><?= t('pricing.current_plan') ?></div>
                        <?php else: ?>
                            <a href="mailto:support@gformus.link?subject=Solicitud Enterprise" class="btn btn-outline" style="width: 100%; padding: 0.75rem 1rem; min-height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 600;"><?= t('pricing.contact_sales') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    const perMonthText = <?= json_encode(t('pricing.per_month')) ?>;
    const monthlyButtonText = <?= json_encode(t('pricing.start_now_premium')) ?>;
    const annualButtonText = <?= json_encode(t('pricing.start_saving_annual')) ?>;
    const billingToggles = document.querySelectorAll('.premium-billing-toggle');
    const priceDisplay = document.getElementById('premium-price');
    const savingsDisplay = document.getElementById('premium-annual-savings');
    const discountBadge = document.querySelector('.premium-discount-badge');
    const billingPeriodInput = document.getElementById('premium-billing-period');
    const submitButton = document.getElementById('premium-submit-button');
    const loginLink = document.getElementById('premium-login-link');
    const isEarlyBird = <?= json_encode($isEarlyBird) ?>;
    
    // Only check for essential elements - billingPeriodInput is optional (only exists when logged in)
    if (!billingToggles.length || !priceDisplay) {
        return; // Elements not found, exit
    }
    
    function updatePricing(billingPeriod) {
        if (billingPeriod === 'annual') {
            // Annual pricing
            if (isEarlyBird) {
                priceDisplay.innerHTML = '$19.99<span style="font-size: 1rem; color: var(--color-text-muted);">/year</span><div style="font-size: 0.75rem; color: var(--color-text-muted); text-decoration: line-through; margin-top: 0.25rem;">$49.99/año</div>';
            } else {
                priceDisplay.innerHTML = '$49.99<span style="font-size: 1rem; color: var(--color-text-muted);">/year</span>';
            }
            if (savingsDisplay) {
                savingsDisplay.style.display = 'block';
            }
            if (discountBadge) {
                discountBadge.style.display = 'inline-block';
            }
            if (billingPeriodInput) {
                billingPeriodInput.value = 'annual';
            }
            // Update button text for annual
            if (submitButton) {
                submitButton.textContent = annualButtonText;
            }
            if (loginLink) {
                loginLink.textContent = annualButtonText;
            }
        } else {
            // Monthly pricing
            if (isEarlyBird) {
                priceDisplay.innerHTML = '$1.99<span style="font-size: 1rem; color: var(--color-text-muted);">' + perMonthText + '</span><div style="font-size: 0.75rem; color: var(--color-text-muted); text-decoration: line-through; margin-top: 0.25rem;">$4.99/mes</div>';
            } else {
                priceDisplay.innerHTML = '$4.99<span style="font-size: 1rem; color: var(--color-text-muted);">' + perMonthText + '</span>';
            }
            if (savingsDisplay) {
                savingsDisplay.style.display = 'none';
            }
            if (discountBadge) {
                discountBadge.style.display = 'none';
            }
            if (billingPeriodInput) {
                billingPeriodInput.value = 'monthly';
            }
            // Update button text for monthly
            if (submitButton) {
                submitButton.textContent = monthlyButtonText;
            }
            if (loginLink) {
                loginLink.textContent = monthlyButtonText;
            }
        }
    }
    
    // Add event listeners to all toggle radio buttons
    billingToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            if (this.checked) {
                updatePricing(this.value);
            }
        });
    });
    
    // Initialize with monthly (default)
    const defaultToggle = document.querySelector('.premium-billing-toggle[value="monthly"]');
    if (defaultToggle && defaultToggle.checked) {
        updatePricing('monthly');
    }
});
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

