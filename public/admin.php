<?php
declare(strict_types=1);

use App\Models\UserRepository;
use App\Models\LoginLogRepository;
use App\Models\ShortLinkRepository;

require __DIR__ . '/../config/bootstrap.php';
require_admin(); // Strict admin check

$user = session_user();
$pdo = db();

$userRepo = new UserRepository($pdo);
$loginLogRepo = new LoginLogRepository($pdo);
$shortLinkRepo = new ShortLinkRepository($pdo);

// Search parameters
$searchType = trim($_GET['search_type'] ?? 'ip'); // 'ip', 'google_id', 'name'
$searchValue = trim($_GET['search_value'] ?? '');
$filterPlan = trim($_GET['filter_plan'] ?? 'all');
$filterRole = trim($_GET['filter_role'] ?? 'all');

// Pagination parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPageOptions = [5, 10, 15, 20, 30, 50];
$perPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 10;
}

// Initialize results
$searchResults = [];
$hasSearched = !empty($searchValue);

// Execute search based on type
if ($hasSearched) {
    switch ($searchType) {
        case 'ip':
            $loginLogs = $loginLogRepo->findByIp($searchValue, 1000);
            // Get unique user IDs from login logs
            $userIds = [];
            foreach ($loginLogs as $log) {
                if (!empty($log['user_id'])) {
                    $userIds[(int)$log['user_id']] = true;
                }
            }
            // Fetch full user records
            if (!empty($userIds)) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $statement = $pdo->prepare("SELECT * FROM users WHERE id IN ($placeholders) ORDER BY created_at DESC");
                $statement->execute(array_keys($userIds));
                $searchResults = $statement->fetchAll(PDO::FETCH_ASSOC);
            }
            break;
            
        case 'google_id':
            $loginLogs = $loginLogRepo->findByGoogleId($searchValue, 1000);
            // Get unique user IDs from login logs
            $userIds = [];
            foreach ($loginLogs as $log) {
                if (!empty($log['user_id'])) {
                    $userIds[(int)$log['user_id']] = true;
                }
            }
            // Fetch full user records
            if (!empty($userIds)) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $statement = $pdo->prepare("SELECT * FROM users WHERE id IN ($placeholders) ORDER BY created_at DESC");
                $statement->execute(array_keys($userIds));
                $searchResults = $statement->fetchAll(PDO::FETCH_ASSOC);
            }
            break;
            
        case 'name':
            $searchResults = $userRepo->searchByName($searchValue);
            break;
    }
}

// Apply filters to search results
if ($hasSearched && !empty($searchResults)) {
    if ($filterPlan !== 'all') {
        $searchResults = array_filter($searchResults, function($u) use ($filterPlan) {
            $userPlan = $u['plan'] ?? 'FREE';
            $userPlanStr = strtoupper(trim((string)$userPlan));
            $filterPlanStr = strtoupper(trim((string)$filterPlan));
            return $userPlanStr === $filterPlanStr;
        });
    }
    
    if ($filterRole !== 'all') {
        $searchResults = array_filter($searchResults, function($u) use ($filterRole) {
            $userRole = $u['role'] ?? 'USER';
            $userRoleStr = strtoupper(trim((string)$userRole));
            $filterRoleStr = strtoupper(trim((string)$filterRole));
            return $userRoleStr === $filterRoleStr;
        });
    }
    
    // Re-index array after filtering (important for array_slice to work correctly)
    $searchResults = array_values($searchResults);
}

// Calculate pagination
$totalResults = count($searchResults);
$totalPages = $totalResults > 0 ? (int)ceil($totalResults / $perPage) : 1;
$page = min($page, max(1, $totalPages)); // Ensure page is valid

// Slice results for current page
$offset = ($page - 1) * $perPage;
$paginatedResults = array_slice($searchResults, $offset, $perPage);

// Handle ENTERPRISE assignment
$assignError = null;
$assignSuccess = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_enterprise'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        $assignError = t('admin.invalid_token');
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId > 0) {
            try {
                $userRepo->updatePlan($targetUserId, 'ENTERPRISE', null);
                $assignSuccess = 'Plan ENTERPRISE asignado correctamente.';
                // Redirect to preserve search state
                $queryParams = http_build_query([
                    'search_type' => $searchType,
                    'search_value' => $searchValue,
                    'filter_plan' => $filterPlan,
                    'filter_role' => $filterRole,
                    'per_page' => $perPage,
                    'page' => $page,
                ]);
                redirect('/admin?' . $queryParams);
            } catch (\Throwable $e) {
                error_log('Enterprise assignment error: ' . $e->getMessage());
                $assignError = 'Error al asignar plan ENTERPRISE.';
            }
        }
    }
}

$pageTitle = t('admin.title');
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
];
$navLinksRight = [
    ['label' => t('nav.logout'), 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 2rem 0; min-height: 80vh;">
    <div class="container" style="max-width: 1400px;">
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700;"><?= t('admin.title') ?></h2>
                    <p class="text-muted" style="margin: 0.5rem 0 0 0;"><?= t('admin.search_users') ?></p>
                </div>
            </div>

            <?php if ($assignError): ?>
                <div class="alert alert-error" style="margin: 1.5rem;"><?= html($assignError) ?></div>
            <?php endif; ?>

            <?php if ($assignSuccess): ?>
                <div class="alert alert-success" style="margin: 1.5rem;"><?= html($assignSuccess) ?></div>
            <?php endif; ?>

            <!-- Search Section -->
            <div style="padding: 2rem; border-bottom: 1px solid var(--color-border, #334155);">
                <form method="GET" id="searchForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Search Type Selector -->
                    <div>
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">
                            <?= t('common.search') ?>
                        </label>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: <?= $searchType === 'ip' ? 'rgba(14, 165, 233, 0.15)' : 'transparent' ?>; transition: all 0.25s ease;">
                                <input type="radio" name="search_type" value="ip" <?= $searchType === 'ip' ? 'checked' : '' ?> style="margin: 0; cursor: pointer;">
                                <span><?= t('admin.search_by_ip') ?></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: <?= $searchType === 'google_id' ? 'rgba(14, 165, 233, 0.15)' : 'transparent' ?>; transition: all 0.25s ease;">
                                <input type="radio" name="search_type" value="google_id" <?= $searchType === 'google_id' ? 'checked' : '' ?> style="margin: 0; cursor: pointer;">
                                <span><?= t('admin.search_by_google_id') ?></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: <?= $searchType === 'name' ? 'rgba(14, 165, 233, 0.15)' : 'transparent' ?>; transition: all 0.25s ease;">
                                <input type="radio" name="search_type" value="name" <?= $searchType === 'name' ? 'checked' : '' ?> style="margin: 0; cursor: pointer;">
                                <span><?= t('admin.search_by_name') ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Search Input and Controls -->
                    <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">
                                <?= $searchType === 'ip' ? t('admin.ip_address') : ($searchType === 'google_id' ? t('admin.google_id') : t('admin.name')) ?>
                            </label>
                            <input 
                                type="text" 
                                name="search_value" 
                                value="<?= html($searchValue) ?>"
                                placeholder="<?= $searchType === 'ip' ? '192.168.1.1' : ($searchType === 'google_id' ? '123456789' : t('admin.name')) ?>"
                                style="width: 100%; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: var(--color-bg-secondary); color: var(--color-text); font-size: 0.875rem;"
                                autofocus
                            >
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">
                                <?= t('admin.rows_per_page') ?>
                            </label>
                            <select 
                                name="per_page" 
                                style="padding: 0.875rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: var(--color-bg-secondary); color: var(--color-text); font-size: 0.875rem; min-width: 80px;"
                            >
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" style="padding: 0.875rem 2rem; white-space: nowrap;">
                                <i class="fas fa-search" style="margin-right: 0.5rem;"></i><?= t('admin.search') ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Section -->
            <?php if ($hasSearched): ?>
                <div style="padding: 2rem;">
                    <!-- Results Header and Filters -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">
                                <?= t('common.search') ?>: <?= html($searchValue) ?>
                            </h3>
                            <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                                <?= $totalResults ?> <?= $totalResults === 1 ? t('admin.users') : t('admin.users') ?>
                            </p>
                        </div>
                        
                        <!-- Filters -->
                        <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                            <input type="hidden" name="search_type" value="<?= html($searchType) ?>">
                            <input type="hidden" name="search_value" value="<?= html($searchValue) ?>">
                            <input type="hidden" name="per_page" value="<?= $perPage ?>">
                            
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">
                                    <?= t('admin.plan') ?>
                                </label>
                                <select 
                                    name="filter_plan" 
                                    onchange="this.form.submit()"
                                    style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: var(--color-bg-secondary); color: var(--color-text); font-size: 0.875rem; min-width: 120px;"
                                >
                                    <option value="all"><?= t('common.filter') ?>: <?= t('admin.plan') ?></option>
                                    <option value="FREE" <?= $filterPlan === 'FREE' ? 'selected' : '' ?>>FREE</option>
                                    <option value="PREMIUM" <?= $filterPlan === 'PREMIUM' ? 'selected' : '' ?>>PREMIUM</option>
                                    <option value="ENTERPRISE" <?= $filterPlan === 'ENTERPRISE' ? 'selected' : '' ?>>ENTERPRISE</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">
                                    Rol
                                </label>
                                <select 
                                    name="filter_role" 
                                    onchange="this.form.submit()"
                                    style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border); background: var(--color-bg-secondary); color: var(--color-text); font-size: 0.875rem; min-width: 120px;"
                                >
                                    <option value="all"><?= t('common.filter') ?>: Rol</option>
                                    <option value="USER" <?= $filterRole === 'USER' ? 'selected' : '' ?>>USER</option>
                                    <option value="ADMIN" <?= $filterRole === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- Results Table -->
                    <?php if (empty($paginatedResults)): ?>
                        <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p style="font-size: 1.125rem; margin: 0;"><?= t('common.no_results') ?></p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid var(--color-border);">
                            <table style="width: 100%; border-collapse: collapse; background: var(--color-bg-secondary);">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--color-border); background: rgba(14, 165, 233, 0.1);">
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);">ID</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= t('admin.name') ?></th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= t('admin.email') ?></th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= t('admin.plan') ?></th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);">Rol</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= t('admin.links') ?></th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= t('common.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paginatedResults as $u): ?>
                                        <tr style="border-bottom: 1px solid var(--color-border); transition: background 0.2s ease;">
                                            <td style="padding: 1rem; font-size: 0.875rem;"><?= html((string)($u['id'] ?? '')) ?></td>
                                            <td style="padding: 1rem; font-size: 0.875rem;"><?= html($u['name'] ?? 'N/A') ?></td>
                                            <td style="padding: 1rem; font-size: 0.875rem;"><?= html($u['email'] ?? 'N/A') ?></td>
                                            <td style="padding: 1rem;">
                                                <span class="badge <?= $u['plan'] === 'PREMIUM' ? 'premium-badge' : ($u['plan'] === 'ENTERPRISE' ? 'enterprise-badge' : 'free-badge') ?>">
                                                    <?= html($u['plan'] ?? 'FREE') ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <span class="badge <?= $u['role'] === 'ADMIN' ? 'premium-badge' : 'free-badge' ?>">
                                                    <?= html($u['role'] ?? 'USER') ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; font-size: 0.875rem;"><?= $shortLinkRepo->countByUserId((int)$u['id']) ?></td>
                                            <td style="padding: 1rem;">
                                                <?php if ($u['plan'] !== 'ENTERPRISE'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="user_id" value="<?= html((string)($u['id'] ?? '')) ?>">
                                                        <input type="hidden" name="search_type" value="<?= html($searchType) ?>">
                                                        <input type="hidden" name="search_value" value="<?= html($searchValue) ?>">
                                                        <input type="hidden" name="filter_plan" value="<?= html($filterPlan) ?>">
                                                        <input type="hidden" name="filter_role" value="<?= html($filterRole) ?>">
                                                        <input type="hidden" name="per_page" value="<?= $perPage ?>">
                                                        <input type="hidden" name="page" value="<?= $page ?>">
                                                        <button type="submit" name="assign_enterprise" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;" onclick="return confirm('¿Asignar plan ENTERPRISE a este usuario?')">
                                                            <?= t('admin.assign_enterprise') ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.875rem;"><?= t('admin.plan') ?> ENTERPRISE</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                <?php 
                                $paginationParams = [
                                    'search_type' => $searchType,
                                    'search_value' => $searchValue,
                                    'filter_plan' => $filterPlan,
                                    'filter_role' => $filterRole,
                                    'per_page' => $perPage,
                                ];
                                ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($paginationParams, ['page' => $page - 1])) ?>" class="btn btn-outline">
                                        <i class="fas fa-chevron-left" style="margin-right: 0.5rem;"></i><?= t('common.previous') ?>
                                    </a>
                                <?php endif; ?>
                                <span style="padding: 0.75rem 1.5rem; display: inline-block; color: var(--text-secondary); font-size: 0.875rem;">
                                    <?= t('common.page') ?> <?= $page ?> <?= t('common.of') ?> <?= $totalPages ?>
                                </span>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($paginationParams, ['page' => $page + 1])) ?>" class="btn btn-outline">
                                        <?= t('common.next') ?><i class="fas fa-chevron-right" style="margin-left: 0.5rem;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Initial State -->
                <div style="padding: 4rem 2rem; text-align: center; color: var(--text-secondary);">
                    <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <p style="font-size: 1.25rem; margin: 0;"><?= t('admin.search_users') ?></p>
                    <p style="font-size: 0.875rem; margin: 0.5rem 0 0 0; opacity: 0.7;">
                        <?= t('admin.search_by_ip') ?>, <?= t('admin.search_by_google_id') ?>, <?= t('admin.search_by_name') ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
