<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
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

// Normalize filters
$status = ($status === 'all' || empty($status)) ? null : $status;
$search = empty($search) ? null : $search;
$dateFrom = empty($dateFrom) ? null : $dateFrom;
$dateTo = empty($dateTo) ? null : $dateTo;

// Get links with filters
$links = $shortLinkRepo->searchByUserWithFilters(
    (int)$user['id'],
    $search,
    $status,
    $dateFrom,
    $dateTo,
    $perPage,
    $offset
);

// Get total count
$totalLinks = $shortLinkRepo->countByUserWithFilters(
    (int)$user['id'],
    $search,
    $status,
    $dateFrom,
    $dateTo
);

$totalPages = (int)ceil($totalLinks / $perPage);

$pageTitle = t('explore.title');
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
                    <h2><?= t('explore.title') ?></h2>
                    <p class="text-muted"><?= t('explore.subtitle') ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <form method="GET" id="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary);">
                            <i class="fas fa-search" style="margin-right: 0.5rem;"></i><?= t('common.search') ?>
                        </label>
                        <input 
                            type="text" 
                            name="search" 
                            id="search-input"
                            placeholder="<?= t('links.label') ?> <?= t('common.of') ?> <?= t('links.original_url') ?>..."
                            value="<?= html($search ?? '') ?>"
                            class="form-input"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.15); background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); color: var(--text-primary); transition: all 0.25s ease;"
                        >
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary);">
                            <i class="fas fa-filter" style="margin-right: 0.5rem;"></i><?= t('links.status') ?>
                        </label>
                        <select 
                            name="status" 
                            id="status-filter"
                            class="form-select"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.15); background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); color: var(--text-primary); transition: all 0.25s ease;"
                        >
                            <option value="all" <?= ($status === null || $status === 'all') ? 'selected' : '' ?>><?= t('links.filter_all') ?></option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= t('links.filter_active') ?></option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= t('links.filter_inactive') ?></option>
                            <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>><?= t('links.filter_expired') ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary);">
                            <i class="fas fa-calendar" style="margin-right: 0.5rem;"></i><?= t('explore.date_from') ?>
                        </label>
                        <input 
                            type="date" 
                            name="date_from" 
                            id="date-from"
                            value="<?= html($dateFrom ?? '') ?>"
                            class="form-input"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.15); background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); color: var(--text-primary); transition: all 0.25s ease;"
                        >
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary);">
                            <i class="fas fa-calendar" style="margin-right: 0.5rem;"></i><?= t('explore.date_to') ?>
                        </label>
                        <input 
                            type="date" 
                            name="date_to" 
                            id="date-to"
                            value="<?= html($dateTo ?? '') ?>"
                            class="form-input"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.15); background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); color: var(--text-primary); transition: all 0.25s ease;"
                        >
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search" style="margin-right: 0.5rem;"></i><?= t('common.filter') ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Links Table -->
            <div style="padding: 1.5rem;">
                <?php if (empty($links)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        <?= t('links.no_links') ?> <a href="/create-link"><?= t('links.create_first') ?></a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid rgba(148, 163, 184, 0.15);">
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.label') ?></th>
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.code') ?></th>
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.original_url') ?></th>
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.status') ?></th>
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.created') ?></th>
                                    <th style="padding: 0.75rem; text-align: left; color: var(--text-secondary); font-weight: 600;"><?= t('links.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody id="links-table-body">
                                <?php foreach ($links as $link): ?>
                                    <?php
                                    $isExpired = $link['expires_at'] !== null && strtotime($link['expires_at']) <= time();
                                    $isActive = $link['is_active'] == 1 && !$isExpired;
                                    $linkId = (int)$link['id'];
                                    $code = $link['short_code'] ?? '';
                                    ?>
                                    <tr data-link-id="<?= $linkId ?>" style="border-bottom: 1px solid rgba(148, 163, 184, 0.1); transition: all 0.25s ease;">
                                        <td style="padding: 0.75rem;"><?= html($link['label'] ?: '-') ?></td>
                                        <td style="padding: 0.75rem;">
                                            <?php if ($code): ?>
                                                <a href="/<?= html($code) ?>" target="_blank" style="color: var(--accent-primary); text-decoration: none;">
                                                    <i class="fas fa-external-link-alt" style="margin-right: 0.25rem; font-size: 0.75rem;"></i><?= html($code) ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span style="max-width: 300px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= html($link['original_url']) ?>">
                                                <?= html($link['original_url']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span class="badge <?= $isActive ? 'premium-badge' : 'free-badge' ?>" 
                                                  data-status="<?= $isActive ? 'active' : ($isExpired ? 'expired' : 'inactive') ?>" 
                                                  data-active-text="<?= html(t('common.active')) ?>"
                                                  data-inactive-text="<?= html(t('common.inactive')) ?>"
                                                  data-expired-text="<?= html(t('common.expired')) ?>">
                                                <?= $isExpired ? t('common.expired') : ($link['is_active'] == 1 ? t('common.active') : t('common.inactive')) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; color: var(--text-secondary);">
                                            <?= !empty($link['created_at']) ? date('d/m/Y', strtotime($link['created_at'])) : '-' ?>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <?php if ($code): ?>
                                                    <a href="/link/<?= html($code) ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;" title="<?= t('common.view') ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button 
                                                    class="btn btn-outline toggle-link-btn" 
                                                    data-link-id="<?= $linkId ?>"
                                                    data-current-status="<?= $link['is_active'] ?>"
                                                    style="padding: 0.25rem 0.75rem; font-size: 0.8rem;"
                                                    title="<?= $link['is_active'] == 1 ? t('explore.toggle_deactivate') : t('explore.toggle_activate') ?>"
                                                >
                                                    <i class="fas fa-<?= $link['is_active'] == 1 ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-outline delete-link-btn" 
                                                    data-link-id="<?= $linkId ?>"
                                                    data-link-code="<?= html($code) ?>"
                                                    style="padding: 0.25rem 0.75rem; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"
                                                    title="<?= t('common.delete') ?>"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&status=<?= html($status ?? 'all') ?>&date_from=<?= urlencode($dateFrom ?? '') ?>&date_to=<?= urlencode($dateTo ?? '') ?>" class="btn btn-outline">
                                    <i class="fas fa-chevron-left" style="margin-right: 0.5rem;"></i><?= t('common.previous') ?>
                                </a>
                            <?php endif; ?>
                            <span style="padding: 0.5rem 1rem; display: inline-block; color: var(--text-secondary);">
                                <?= t('common.page') ?> <?= $page ?> <?= t('common.of') ?> <?= $totalPages ?>
                            </span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&status=<?= html($status ?? 'all') ?>&date_from=<?= urlencode($dateFrom ?? '') ?>&date_to=<?= urlencode($dateTo ?? '') ?>" class="btn btn-outline">
                                    <?= t('common.next') ?><i class="fas fa-chevron-right" style="margin-left: 0.5rem;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?= t('explore.delete_confirm_title') ?></h2>
            <button class="modal-close" id="close-delete-modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 1rem; color: var(--text-primary);">
                <?= t('explore.delete_confirm_message') ?>
            </p>
            <div class="alert alert-error" style="margin-bottom: 1rem;">
                <strong><i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i><?= t('explore.delete_warning_irreversible') ?></strong>
            </div>
            <p style="color: var(--text-secondary);">
                <?= t('explore.delete_warning_access_lost') ?>
            </p>
            <p style="margin-top: 1rem; color: var(--text-muted); font-size: 0.85rem;" id="delete-link-info"></p>
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(148, 163, 184, 0.1);">
            <button class="btn btn-outline" id="cancel-delete" style="background: rgba(148, 163, 184, 0.1); color: var(--text-primary);">
                <?= t('explore.delete_button_cancel') ?>
            </button>
            <button class="btn btn-primary" id="confirm-delete" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; color: white;">
                <i class="fas fa-trash" style="margin-right: 0.5rem;"></i><?= t('explore.delete_button_confirm') ?>
            </button>
        </div>
    </div>
</div>

<script src="/assets/js/explore.js"></script>
<script>
    // Pass CSRF token to JavaScript
    window.csrfToken = '<?= generate_csrf_token() ?>';
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

