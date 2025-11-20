<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

// Only PREMIUM and ENTERPRISE can access
if (!$isPremium && !$isEnterprise) {
    redirect('/dashboard');
}

$pdo = db();
$shortLinkRepo = new ShortLinkRepository($pdo);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Normalize status filter - use null for 'all' to match repository method signature
$statusFilter = ($status === 'all') ? null : $status;
$searchFilter = (!empty($search)) ? $search : null;
$dateFromFilter = (!empty($dateFrom)) ? $dateFrom : null;
$dateToFilter = (!empty($dateTo)) ? $dateTo : null;

// Initialize variables
$links = [];
$totalLinks = 0;

try {
    // Get links using the repository method that handles all filters at database level
    $links = $shortLinkRepo->searchByUserWithFilters(
        (int)$user['id'],
        $searchFilter,
        $statusFilter,
        $dateFromFilter,
        $dateToFilter,
        $perPage,
        $offset
    );

    // Get total count with same filters
    $totalLinks = $shortLinkRepo->countByUserWithFilters(
        (int)$user['id'],
        $searchFilter,
        $statusFilter,
        $dateFromFilter,
        $dateToFilter
    );
} catch (\Throwable $e) {
    error_log('Links page error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    $links = [];
    $totalLinks = 0;
}

// Ensure $links is always an array and properly indexed
if (!is_array($links)) {
    $links = [];
} else {
    // Re-index array to ensure sequential keys (in case of any filtering issues)
    $links = array_values($links);
}

$totalPages = max(1, (int)ceil($totalLinks / $perPage));

$pageTitle = t('links.manage');
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
    ['label' => t('nav.create_link'), 'href' => '/create-link'],
];
$navLinksRight = [
    ['label' => t('nav.logout'), 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2><?= t('links.manage') ?></h2>
                    <p class="text-muted"><?= t('links.manage_subtitle') ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-border, #334155);">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?= t('common.search') ?></label>
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="<?= t('links.label') ?> <?= t('common.of') ?> <?= t('links.original_url') ?>..."
                            value="<?= html($search) ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?= t('links.status') ?></label>
                        <select 
                            name="status" 
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>><?= t('links.filter_all') ?></option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= t('links.filter_active') ?></option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= t('links.filter_inactive') ?></option>
                            <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>><?= t('links.filter_expired') ?></option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;"><?= t('common.filter') ?></button>
                    </div>
                </form>
            </div>

            <!-- Links Table -->
            <div style="padding: 1.5rem;">
                <?php if (empty($links)): ?>
                    <div class="alert alert-info">
                        <?= t('links.no_links') ?> <a href="/create-link"><?= t('links.create_first') ?></a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.label') ?></th>
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.code') ?></th>
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.original_url') ?></th>
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.status') ?></th>
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.created') ?></th>
                                    <th style="padding: 0.75rem; text-align: left;"><?= t('links.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                        <td style="padding: 0.75rem;"><?= html($link['label'] ?: '-') ?></td>
                                        <td style="padding: 0.75rem;">
                                            <?php $code = $link['short_code'] ?? ''; ?>
                                            <a href="/<?= html($code) ?>" target="_blank" style="color: #60a5fa;">
                                                <?= html($code) ?>
                                            </a>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span style="max-width: 300px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= html($link['original_url']) ?>">
                                                <?= html($link['original_url']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <?php
                                            $isExpired = $link['expires_at'] !== null && strtotime($link['expires_at']) <= time();
                                            $isActive = $link['is_active'] == 1 && !$isExpired;
                                            ?>
                                            <span class="badge <?= $isActive ? 'premium-badge' : 'free-badge' ?>">
                                                <?= $isExpired ? t('common.expired') : ($link['is_active'] == 1 ? t('common.active') : t('common.inactive')) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;"><?= !empty($link['created_at']) ? date('d/m/Y', strtotime($link['created_at'])) : '-' ?></td>
                                        <td style="padding: 0.75rem;">
                                            <?php $code = $link['short_code'] ?? ''; ?>
                                            <?php if ($code): ?>
                                                <a href="/link/<?= html($code) ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; margin-right: 0.5rem;"><?= t('common.view') ?></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= html($status) ?>" class="btn btn-outline"><?= t('common.previous') ?></a>
                            <?php endif; ?>
                            <span style="padding: 0.5rem 1rem; display: inline-block;"><?= t('common.page') ?> <?= $page ?> <?= t('common.of') ?> <?= $totalPages ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= html($status) ?>" class="btn btn-outline"><?= t('common.next') ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

