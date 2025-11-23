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
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700;"><?= t('admin.title') ?></h2>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <button id="sendEmailBtn" class="send-email-button" onclick="openSendEmailModal()" title="Send Email">
                        <i class="fas fa-envelope"></i>
                        <span>Send Email</span>
                    </button>
                    <button id="minitopBtn" class="minitop-button" onclick="openMiniTopModal()" title="Open Mini-TOP System Monitor">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Mini-TOP</span>
                    </button>
                </div>
            </div>

            <!-- Tabs Container -->
            <div class="tabs-container">
                <div class="tab-header" role="tablist">
                    <button class="tab-button active" role="tab" aria-selected="true" aria-controls="tab-search" id="tab-button-search" data-tab="search" onclick="if(typeof window.switchTab==='function'){window.switchTab('search');}return false;">
                        <?= t('admin.tab.search') ?>
                    </button>
                    <button class="tab-button" role="tab" aria-selected="false" aria-controls="tab-diagnostics" id="tab-button-diagnostics" data-tab="diagnostics" onclick="if(typeof window.switchTab==='function'){window.switchTab('diagnostics');}return false;">
                        <?= t('admin.tab.diagnostics') ?>
                    </button>
                    <button class="tab-button" role="tab" aria-selected="false" aria-controls="tab-environment" id="tab-button-environment" data-tab="environment" onclick="if(typeof window.switchTab==='function'){window.switchTab('environment');}return false;">
                        <?= t('admin.tab.environment') ?>
                    </button>
                </div>

                <?php if ($assignError): ?>
                    <div class="alert alert-error" style="margin: 1.5rem;"><?= html($assignError) ?></div>
                <?php endif; ?>

                <?php if ($assignSuccess): ?>
                    <div class="alert alert-success" style="margin: 1.5rem;"><?= html($assignSuccess) ?></div>
                <?php endif; ?>

                <!-- Tab 1: Search -->
                <div id="tab-search" class="tab-content active" role="tabpanel" aria-labelledby="tab-button-search">

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
                    <p style="font-size: 1.25rem; margin: 0;"><?= t('common.search') ?></p>
                    <p style="font-size: 0.875rem; margin: 0.5rem 0 0 0; opacity: 0.7;">
                        <?= t('admin.search_by_ip') ?>, <?= t('admin.search_by_google_id') ?>, <?= t('admin.search_by_name') ?>
                    </p>
                </div>
            <?php endif; ?>
                </div>

                <!-- Tab 2: Diagnostics -->
                <div id="tab-diagnostics" class="tab-content" role="tabpanel" aria-labelledby="tab-button-diagnostics">
                    <div style="padding: 2rem;">
                        <div class="accordion-container">
                            <!-- Connectivity Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-connectivity" id="accordion-header-connectivity" data-component="connectivity">
                                    <i class="fas fa-network-wired" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.connectivity') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-connectivity" aria-labelledby="accordion-header-connectivity" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/diagnostics/server-ping.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- OS Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-os" id="accordion-header-os" data-component="os">
                                    <i class="fas fa-server" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.os') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-os" aria-labelledby="accordion-header-os" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/diagnostics/os-health.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Database Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-database" id="accordion-header-database" data-component="database">
                                    <i class="fas fa-database" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.database') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-database" aria-labelledby="accordion-header-database" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/diagnostics/database-health.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Stripe Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-stripe" id="accordion-header-stripe" data-component="stripe">
                                    <i class="fab fa-stripe" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.stripe') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-stripe" aria-labelledby="accordion-header-stripe" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/diagnostics/stripe-health.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Environment Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-environment" id="accordion-header-environment">
                                    <i class="fas fa-cog" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.environment') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-environment" aria-labelledby="accordion-header-environment" aria-hidden="true">
                                    <div class="accordion-body">
                                        <div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
                                            <p style="margin: 0 0 0.5rem 0; opacity: 0.8;">Environment diagnostics content will be displayed here.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Misc Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="accordion-misc" id="accordion-header-misc">
                                    <i class="fas fa-info-circle" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.diagnostics.misc') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="accordion-misc" aria-labelledby="accordion-header-misc" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/diagnostics/reports.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Environment -->
                <div id="tab-environment" class="tab-content" role="tabpanel" aria-labelledby="tab-button-environment">
                    <div style="padding: 2rem;">
                        <div class="accordion-container">
                            <!-- .env Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="env-accordion-env" id="env-accordion-header-env">
                                    <i class="fas fa-file-code" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.environment.env') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="env-accordion-env" aria-labelledby="env-accordion-header-env" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/environment/env-editor.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- .htaccess Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="env-accordion-htaccess" id="env-accordion-header-htaccess">
                                    <i class="fas fa-file-alt" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.environment.htaccess') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="env-accordion-htaccess" aria-labelledby="env-accordion-header-htaccess" aria-hidden="true">
                                    <div class="accordion-body">
                                        <?php require __DIR__ . '/../views/admin/environment/htaccess-editor.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Stripe Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="env-accordion-stripe" id="env-accordion-header-stripe">
                                    <i class="fab fa-stripe" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.environment.stripe') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="env-accordion-stripe" aria-labelledby="env-accordion-header-stripe" aria-hidden="true">
                                    <div class="accordion-body">
                                        <div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
                                            <p style="margin: 0 0 0.5rem 0; opacity: 0.8;">Stripe environment configuration content will be displayed here.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Misc Accordion -->
                            <div class="accordion-item">
                                <button class="accordion-header" aria-expanded="false" aria-controls="env-accordion-misc" id="env-accordion-header-misc">
                                    <i class="fas fa-info-circle" style="margin-right: 0.75rem;"></i>
                                    <span><?= t('admin.environment.misc') ?></span>
                                    <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
                                </button>
                                <div class="accordion-content" id="env-accordion-misc" aria-labelledby="env-accordion-header-misc" aria-hidden="true">
                                    <div class="accordion-body">
                                        <div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
                                            <p style="margin: 0 0 0.5rem 0; opacity: 0.8;">Miscellaneous environment content will be displayed here.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Tab switching functionality - define FIRST and make globally accessible
window.switchTab = function(tabName) {
    console.log('switchTab called with:', tabName);
    
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
        content.setAttribute('aria-hidden', 'true');
        // Remove inline styles that might interfere
        content.style.display = '';
    });
    
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
        button.setAttribute('aria-selected', 'false');
    });
    
    // Show selected tab content
    const selectedTab = document.getElementById('tab-' + tabName);
    const selectedButton = document.getElementById('tab-button-' + tabName);
    
    console.log('Selected tab:', selectedTab);
    console.log('Selected button:', selectedButton);
    
    if (selectedTab && selectedButton) {
        selectedTab.classList.add('active');
        selectedTab.setAttribute('aria-hidden', 'false');
        selectedTab.style.display = '';
        selectedButton.classList.add('active');
        selectedButton.setAttribute('aria-selected', 'true');
        
        console.log('Tab switched successfully');
        
        // Re-initialize accordions after tab is shown (in case they're in a hidden tab)
        setTimeout(function() {
            if (typeof window.initAccordions === 'function') {
                window.initAccordions();
            }
        }, 50);
    } else {
        console.error('Tab or button not found!', 'tab:', selectedTab, 'button:', selectedButton);
    }
};

// Accordion initialization function - make it globally accessible
window.initAccordions = function() {
    // Use event delegation on the document to handle all accordions
    // This way we don't need to worry about duplicate handlers
    if (!window.accordionInitialized) {
        document.addEventListener('click', function(e) {
            const header = e.target.closest('.accordion-header');
            if (!header) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = header.getAttribute('aria-expanded') === 'true';
            const accordionId = header.getAttribute('aria-controls');
            const accordionContent = document.getElementById(accordionId);
            
            if (!accordionContent) return;
            
            // Toggle current accordion
            if (isExpanded) {
                header.setAttribute('aria-expanded', 'false');
                accordionContent.setAttribute('aria-hidden', 'true');
            } else {
                header.setAttribute('aria-expanded', 'true');
                accordionContent.setAttribute('aria-hidden', 'false');
                
                // Trigger lazy-loading for diagnostic components
                const componentType = header.getAttribute('data-component');
                if (componentType) {
                    // Load data when accordion opens for first time
                    if (!header.dataset.loaded) {
                        setTimeout(() => {
                            if (componentType === 'connectivity' && typeof window.loadConnectivityData === 'function') {
                                window.loadConnectivityData();
                            } else if (componentType === 'os' && typeof window.loadOsData === 'function') {
                                window.loadOsData();
                            } else if (componentType === 'database' && typeof window.loadDatabaseData === 'function') {
                                window.loadDatabaseData();
                            } else if (componentType === 'stripe' && typeof window.loadStripeData === 'function') {
                                window.loadStripeData();
                            }
                            header.dataset.loaded = 'true';
                        }, 100);
                    }
                }
            }
        });
        
        window.accordionInitialized = true;
    }
    
    // Ensure all accordions start collapsed
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    accordionHeaders.forEach(header => {
        const accordionId = header.getAttribute('aria-controls');
        const accordionContent = document.getElementById(accordionId);
        
        if (accordionContent) {
            // Only set collapsed if not already set
            if (!header.hasAttribute('aria-expanded')) {
                header.setAttribute('aria-expanded', 'false');
                accordionContent.setAttribute('aria-hidden', 'true');
            }
        }
    });
};


// Initialize tabs on page load
(function() {
    function initTabs() {
        // Add click handlers to tab buttons
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            const tabName = button.getAttribute('data-tab');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const tabName = this.getAttribute('data-tab');
                if (tabName && typeof window.switchTab === 'function') {
                    window.switchTab(tabName);
                }
            });
        });
        
        // Ensure search tab is visible by default
        const searchTab = document.getElementById('tab-search');
        const diagnosticsTab = document.getElementById('tab-diagnostics');
        const environmentTab = document.getElementById('tab-environment');
        
        if (searchTab) {
            searchTab.classList.add('active');
            searchTab.setAttribute('aria-hidden', 'false');
        }
        
        if (diagnosticsTab) {
            diagnosticsTab.classList.remove('active');
            diagnosticsTab.setAttribute('aria-hidden', 'true');
        }
        
        if (environmentTab) {
            environmentTab.classList.remove('active');
            environmentTab.setAttribute('aria-hidden', 'true');
        }
        
        // Check for hash in URL to open specific tab
        const hash = window.location.hash;
        if (hash === '#diagnostics' && typeof window.switchTab === 'function') {
            window.switchTab('diagnostics');
        } else if (hash === '#environment' && typeof window.switchTab === 'function') {
            window.switchTab('environment');
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
})();

// Initialize accordions on page load
(function() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initAccordions === 'function') {
                window.initAccordions();
            }
        });
    } else {
        if (typeof window.initAccordions === 'function') {
            window.initAccordions();
        }
    }
})();
</script>

<!-- Send Email Modal -->
<div id="sendEmailModal" class="send-email-modal" style="display: none;">
    <div class="send-email-modal-overlay" onclick="closeSendEmailModal()"></div>
    <div class="send-email-modal-container">
        <div class="send-email-modal-header">
            <h3>Send Email</h3>
            <button class="send-email-modal-close" onclick="closeSendEmailModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="send-email-modal-body">
            <?php require __DIR__ . '/send-email.php'; ?>
        </div>
    </div>
</div>

<!-- Mini-TOP Modal -->
<div id="minitopModal" class="minitop-modal" style="display: none;">
    <div class="minitop-modal-overlay" onclick="closeMiniTopModal()"></div>
    <div class="minitop-modal-container">
        <div class="minitop-modal-header">
            <h3>Mini-TOP System Monitor</h3>
            <button class="minitop-modal-close" onclick="closeMiniTopModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="minitop-modal-body">
            <iframe id="minitopIframe" src="/admin/mini-top.php" frameborder="0" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>

<style>
/* Send Email Button Styles */
.send-email-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3), 0 2px 4px -1px rgba(16, 185, 129, 0.2);
    position: relative;
    overflow: hidden;
}

.send-email-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.send-email-button:hover::before {
    left: 100%;
}

.send-email-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4), 0 4px 6px -2px rgba(16, 185, 129, 0.3);
}

.send-email-button:active {
    transform: translateY(0);
}

.send-email-button i {
    font-size: 1rem;
}

/* Send Email Modal Styles */
.send-email-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.send-email-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(4px);
}

.send-email-modal-container {
    position: relative;
    width: 95%;
    max-width: 1000px;
    height: 85vh;
    max-height: 800px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(148, 163, 184, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

.send-email-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.8);
}

.send-email-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.send-email-modal-close {
    background: transparent;
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #e2e8f0;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.send-email-modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
    color: #ef4444;
}

.send-email-modal-body {
    flex: 1;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* Mini-TOP Button Styles */
.minitop-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
    color: #0f172a;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(96, 165, 250, 0.3), 0 2px 4px -1px rgba(96, 165, 250, 0.2);
    position: relative;
    overflow: hidden;
}

.minitop-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.minitop-button:hover::before {
    left: 100%;
}

.minitop-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(96, 165, 250, 0.4), 0 4px 6px -2px rgba(96, 165, 250, 0.3);
}

.minitop-button:active {
    transform: translateY(0);
}

.minitop-button i {
    font-size: 1rem;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
    }
}

/* Mini-TOP Modal Styles */
.minitop-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.minitop-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(4px);
}

.minitop-modal-container {
    position: relative;
    width: 95%;
    max-width: 1400px;
    height: 90vh;
    max-height: 900px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(148, 163, 184, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.minitop-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.8);
}

.minitop-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.minitop-modal-close {
    background: transparent;
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #e2e8f0;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.minitop-modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
    color: #ef4444;
}

.minitop-modal-body {
    flex: 1;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

@media (max-width: 768px) {
    .minitop-modal-container {
        width: 100%;
        height: 100vh;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .minitop-button span {
        display: none;
    }
    
    .minitop-button {
        padding: 0.75rem;
        min-width: 2.5rem;
    }
    
    .send-email-button {
        padding: 0.75rem;
        min-width: 2.5rem;
    }
}
</style>

<script>
// Send Email Modal Functions
function openSendEmailModal() {
    const modal = document.getElementById('sendEmailModal');
    
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeSendEmailModal() {
    const modal = document.getElementById('sendEmailModal');
    
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Mini-TOP Modal Functions
function openMiniTopModal() {
    const modal = document.getElementById('minitopModal');
    const iframe = document.getElementById('minitopIframe');
    
    if (modal && iframe) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reload iframe to ensure fresh data
        iframe.src = iframe.src;
    }
}

function closeMiniTopModal() {
    const modal = document.getElementById('minitopModal');
    
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sendEmailModal = document.getElementById('sendEmailModal');
        const minitopModal = document.getElementById('minitopModal');
        
        if (sendEmailModal && sendEmailModal.style.display !== 'none') {
            closeSendEmailModal();
        } else if (minitopModal && minitopModal.style.display !== 'none') {
            closeMiniTopModal();
        }
    }
});
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
