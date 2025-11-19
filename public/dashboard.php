<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;
use App\Models\QuotaRepository;
use App\Services\QuotaService;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$pdo = db();

$shortLinkRepo = new ShortLinkRepository($pdo);
$quotaRepo = new QuotaRepository($pdo);
$quotaService = new QuotaService($quotaRepo);

$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

// Get quota status
$quotaStatus = $quotaService->getQuotaStatus((int)$user['id'], $currentPlan);

// Get link stats
$totalLinks = $shortLinkRepo->countByUserId((int)$user['id']);
$activeLinks = count($shortLinkRepo->getActiveLinks((int)$user['id']));

// Get total clicks (simplified - would need ClickRepository aggregation)
$recentLinks = $shortLinkRepo->findByUserId((int)$user['id'], 5);

$pageTitle = t('dashboard.title');
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
    ['label' => t('nav.pricing'), 'href' => '/pricing'],
    ['label' => t('nav.my_plan'), 'href' => '/billing'],
];
$navLinksRight = [
    ['label' => t('nav.logout'), 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 800px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2><?= t('dashboard.welcome', ['name' => html($user['name'] ?? 'User')]) ?></h2>
                    <p class="text-muted"><?= t('dashboard.subtitle') ?></p>
                </div>
                <span class="badge <?= $isPremium ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : 'free-badge') ?>">
                    <?= $isEnterprise ? '🏢 ENTERPRISE' : ($isPremium ? '💎 PREMIUM' : '⭐ FREE') ?>
                </span>
            </div>

            <div style="padding: 1.5rem;">
                <!-- Quota Status -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.links_today') ?></div>
                        <div style="font-size: 2rem; font-weight: 700;">
                            <?= $quotaStatus['daily_used'] ?>
                            <?php if ($quotaStatus['daily_limit'] !== null): ?>
                                / <?= $quotaStatus['daily_limit'] ?>
                            <?php else: ?>
                                <span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('dashboard.unlimited') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.links_month') ?></div>
                        <div style="font-size: 2rem; font-weight: 700;">
                            <?= $quotaStatus['monthly_used'] ?>
                            <?php if ($quotaStatus['monthly_limit'] !== null): ?>
                                / <?= $quotaStatus['monthly_limit'] ?>
                            <?php else: ?>
                                <span style="font-size: 1rem; color: var(--color-text-muted);"><?= t('dashboard.unlimited') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.links_total') ?></div>
                        <div style="font-size: 2rem; font-weight: 700;"><?= $totalLinks ?></div>
                    </div>
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.links_active') ?></div>
                        <div style="font-size: 2rem; font-weight: 700;"><?= $activeLinks ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-bottom: 2rem;">
                    <a href="/create-link" class="btn btn-primary" style="margin-right: 1rem;"><?= t('dashboard.create_link') ?></a>
                    <?php if ($isPremium || $isEnterprise): ?>
                        <a href="/links" class="btn btn-outline"><?= t('dashboard.manage_links') ?></a>
                    <?php endif; ?>
                </div>

                <!-- Recent Links -->
                <?php if (!empty($recentLinks)): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h3><?= t('dashboard.recent_links') ?></h3>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($recentLinks as $link): ?>
                                <div style="padding: 0.75rem; background: var(--color-bg-secondary, #1e293b); border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                    <?php $code = $link['short_code'] ?? ''; ?>
                                    <div>
                                        <strong><?= html($link['label'] ?? $code ?: t('dashboard.no_code')) ?></strong><br>
                                        <?php if ($code): ?>
                                            <small style="color: var(--color-text-muted);">
                                                <?= html($appConfig['base_url']) ?>/<?= html($code) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($code): ?>
                                        <a href="/link/<?= html($code) ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem;"><?= t('common.view') ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upgrade Prompt -->
                <?php if (!$isPremium && !$isEnterprise): ?>
                    <div class="alert alert-info">
                        <strong><?= t('pricing.upgrade_premium') ?></strong><br>
                        <?= t('dashboard.upgrade_prompt') ?>
                        <a href="/pricing" class="btn btn-primary" style="margin-top: 1rem;"><?= t('pricing.choose_plan') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

