<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;
use App\Models\QuotaRepository;
use App\Models\ClickRepository;
use App\Services\QuotaService;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$pdo = db();

$shortLinkRepo = new ShortLinkRepository($pdo);
$quotaRepo = new QuotaRepository($pdo);
$clickRepo = new ClickRepository($pdo);
$quotaService = new QuotaService($quotaRepo);

$currentPlan = ($user['plan'] ?? 'FREE');
$currentRole = ($user['role'] ?? 'USER');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');
$isAdmin = ($currentRole === 'ADMIN');

// Get quota status
$quotaStatus = $quotaService->getQuotaStatus((int)$user['id'], $currentPlan);

// Get link stats
$totalLinks = $shortLinkRepo->countByUserId((int)$user['id']);
$activeLinks = count($shortLinkRepo->getActiveLinks((int)$user['id']));
$totalClicks = $clickRepo->getTotalClicksByUserId((int)$user['id']);

// Get recent links
$recentLinks = $shortLinkRepo->findByUserId((int)$user['id'], 5);

$pageTitle = t('dashboard.title');
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
    ['label' => t('nav.pricing'), 'href' => '/pricing'],
    ['label' => t('nav.my_plan'), 'href' => '/billing'],
];
if ($isAdmin) {
    $navLinksLeft[] = ['label' => t('nav.admin'), 'href' => '/admin'];
}
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
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <?php if ($isAdmin): ?>
                        <span class="badge premium-badge" style="font-size: 0.65rem; padding: 0.25rem 0.75rem; font-weight: 600;">
                            👑 ADMIN
                        </span>
                    <?php endif; ?>
                    <span class="badge <?= $isEnterprise ? 'enterprise-badge' : ($isPremium ? 'premium-badge' : 'free-badge') ?>" style="font-size: 0.65rem; padding: 0.25rem 0.75rem; font-weight: 600;">
                        <?= $isEnterprise ? '🏢 ENTERPRISE' : ($isPremium ? '💎 PREMIUM' : '⭐ FREE') ?>
                    </span>
                </div>
            </div>

            <div style="padding: 1.5rem;">
                <!-- Quota Banner (discrete, only for FREE users) -->
                <?php if (!$isPremium && !$isEnterprise && !$isAdmin): ?>
                    <div class="quota-banner" style="background: rgba(17, 24, 39, 0.6); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 0.75rem; padding: 1rem; margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;"><?= t('dashboard.quota_usage') ?></span>
                            <?php if ($quotaStatus['daily_limit'] !== null || $quotaStatus['monthly_limit'] !== null): ?>
                                <a href="/pricing" style="font-size: 0.8rem; color: var(--accent-primary); text-decoration: none;"><?= t('pricing.upgrade_premium') ?> →</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($quotaStatus['daily_limit'] !== null): ?>
                            <div style="margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= t('dashboard.quota_daily') ?></span>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= $quotaStatus['daily_used'] ?> / <?= $quotaStatus['daily_limit'] ?></span>
                                </div>
                                <div style="background: rgba(148, 163, 184, 0.1); border-radius: 0.5rem; height: 6px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary)); height: 100%; width: <?= min(100, ($quotaStatus['daily_used'] / $quotaStatus['daily_limit']) * 100) ?>%; transition: width 0.3s ease; border-radius: 0.5rem;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($quotaStatus['monthly_limit'] !== null): ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= t('dashboard.quota_monthly') ?></span>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= $quotaStatus['monthly_used'] ?> / <?= $quotaStatus['monthly_limit'] ?></span>
                                </div>
                                <div style="background: rgba(148, 163, 184, 0.1); border-radius: 0.5rem; height: 6px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary)); height: 100%; width: <?= min(100, ($quotaStatus['monthly_used'] / $quotaStatus['monthly_limit']) * 100) ?>%; transition: width 0.3s ease; border-radius: 0.5rem;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Key Performance Indicators -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.total_clicks') ?></div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--accent-primary);"><?= number_format($totalClicks) ?></div>
                    </div>
                    <div class="card" style="padding: 1rem;">
                        <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem;"><?= t('dashboard.links_created') ?></div>
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
                    <a href="/explore" class="btn btn-outline" style="margin-right: 1rem;"><?= t('dashboard.explore_links') ?></a>
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
                    <div class="premium-upgrade-banner" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(45, 212, 191, 0.1)); border: 2px solid rgba(14, 165, 233, 0.3); border-radius: 1rem; padding: 2rem; margin-top: 2rem; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; right: 0; width: 200px; height: 200px; background: radial-gradient(circle, rgba(14, 165, 233, 0.1) 0%, transparent 70%); pointer-events: none;"></div>
                        <div style="position: relative; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                                <span style="font-size: 1.5rem;">💎</span>
                                <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                    <?= t('dashboard.upgrade_title') ?>
                                </h3>
                            </div>
                            <p style="font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.6;">
                                <?= t('dashboard.upgrade_value_prop') ?>
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <span style="font-size: 1.25rem; flex-shrink: 0;">📊</span>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                            <?= t('dashboard.upgrade_benefit_analytics') ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            <?= t('dashboard.upgrade_benefit_analytics_desc') ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <span style="font-size: 1.25rem; flex-shrink: 0;">🔗</span>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                            <?= t('dashboard.upgrade_benefit_codes') ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            <?= t('dashboard.upgrade_benefit_codes_desc') ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <span style="font-size: 1.25rem; flex-shrink: 0;">👥</span>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                            <?= t('dashboard.upgrade_benefit_teams') ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            <?= t('dashboard.upgrade_benefit_teams_desc') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <a href="/pricing" class="btn btn-primary premium-upgrade-button" style="font-size: 1.1rem; padding: 0.875rem 2.5rem; font-weight: 600; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);">
                                    <?= t('dashboard.view_premium_plans') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

