<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$user = session_user();
$currentPlan = $user ? ($user['plan'] ?? 'FREE') : 'FREE';
$isLoggedIn = $user !== null;

$pageTitle = t('pricing.title');
$navLinksLeft = [
    ['label' => t('nav.home'), 'href' => '/'],
];
$navLinksRight = $isLoggedIn
    ? [
        ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
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
            <h1 style="font-size: 3rem; margin-bottom: 1rem;"><?= t('pricing.choose_plan') ?></h1>
            <p style="font-size: 1.25rem; color: var(--color-text-muted);"><?= t('pricing.subtitle') ?></p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- FREE Plan -->
            <div class="card" style="<?= $currentPlan === 'FREE' ? 'border: 2px solid #60a5fa;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.free') ?></h2>
                        <p class="text-muted"><?= t('pricing.free_subtitle') ?></p>
                    </div>
                    <span class="badge free-badge"><?= t('pricing.free_badge') ?></span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        $0<span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.per_month') ?></span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_links_per_day') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_links_per_month') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_random_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_basic_stats') ?></li>
                        <li style="padding: 0.5rem 0;">✗ <?= t('pricing.feature_no_custom_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✗ <?= t('pricing.feature_no_expiration') ?></li>
                    </ul>
                    <?php if ($currentPlan === 'FREE'): ?>
                        <div class="alert alert-info"><?= t('pricing.current_plan') ?></div>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline" style="width: 100%;"><?= t('pricing.start_free') ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PREMIUM Plan -->
            <div class="card" style="<?= $currentPlan === 'PREMIUM' ? 'border: 2px solid #22d3ee;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.premium') ?></h2>
                        <p class="text-muted"><?= t('pricing.premium_subtitle') ?></p>
                    </div>
                    <span class="badge premium-badge"><?= t('pricing.premium_badge') ?></span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        $4.99<span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.per_month') ?></span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_600_links_month') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_no_daily_limit') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_custom_codes') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_expiration') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_advanced_stats') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_link_management') ?></li>
                    </ul>
                    <?php if ($currentPlan === 'PREMIUM'): ?>
                        <div class="alert alert-success"><?= t('pricing.current_plan') ?></div>
                    <?php elseif ($isLoggedIn): ?>
                        <form action="/stripe/checkout" method="POST" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><?= t('pricing.upgrade_premium') ?></button>
                        </form>
                    <?php else: ?>
                        <a href="/login" class="btn btn-primary" style="width: 100%;"><?= t('pricing.start_premium') ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ENTERPRISE Plan -->
            <div class="card" style="<?= $currentPlan === 'ENTERPRISE' ? 'border: 2px solid #a78bfa;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2><?= t('pricing.enterprise') ?></h2>
                        <p class="text-muted"><?= t('pricing.enterprise_subtitle') ?></p>
                    </div>
                    <span class="badge enterprise-badge"><?= t('pricing.enterprise_badge') ?></span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        <span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('pricing.custom') ?></span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_unlimited_links') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_no_limits') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_all_features') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_priority_support') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_custom_domains') ?></li>
                        <li style="padding: 0.5rem 0;">✓ <?= t('pricing.feature_enterprise_billing') ?></li>
                    </ul>
                    <?php if ($currentPlan === 'ENTERPRISE'): ?>
                        <div class="alert alert-success"><?= t('pricing.current_plan') ?></div>
                    <?php else: ?>
                        <a href="mailto:support@gformus.link?subject=Solicitud Enterprise" class="btn btn-outline" style="width: 100%;"><?= t('pricing.contact_sales') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

