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
$searchIp = trim($_GET['search_ip'] ?? '');
$searchGoogleId = trim($_GET['search_google_id'] ?? '');
$filterPlan = $_GET['filter_plan'] ?? 'all';
$filterRole = $_GET['filter_role'] ?? 'all';

// Get users
$allUsers = $userRepo->findByRole('USER');
$admins = $userRepo->findByRole('ADMIN');
$users = array_merge($allUsers, $admins);

// Apply filters
if ($filterPlan !== 'all') {
    $users = array_filter($users, fn($u) => $u['plan'] === $filterPlan);
}
if ($filterRole !== 'all') {
    $users = array_filter($users, fn($u) => $u['role'] === $filterRole);
}

// Search login logs
$loginResults = [];
if (!empty($searchIp) || !empty($searchGoogleId)) {
    $loginResults = $loginLogRepo->searchByIpAndGoogleId($searchIp ?: null, $searchGoogleId ?: null);
}

// Handle ENTERPRISE assignment
$assignError = null;
$assignSuccess = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_enterprise'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        $assignError = 'Token de seguridad inválido.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId > 0) {
            try {
                $userRepo->updatePlan($targetUserId, 'ENTERPRISE', null);
                $assignSuccess = 'Plan ENTERPRISE asignado correctamente.';
            } catch (\Throwable $e) {
                error_log('Enterprise assignment error: ' . $e->getMessage());
                $assignError = 'Error al asignar plan ENTERPRISE.';
            }
        }
    }
}

$pageTitle = 'Panel de Administración';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1400px;">
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <h2>Panel de Administración</h2>
                    <p class="text-muted">Gestión de usuarios y búsqueda de IPs</p>
                </div>
            </div>

            <?php if ($assignError): ?>
                <div class="alert alert-error"><?= html($assignError) ?></div>
            <?php endif; ?>

            <?php if ($assignSuccess): ?>
                <div class="alert alert-success"><?= html($assignSuccess) ?></div>
            <?php endif; ?>

            <!-- Search Section -->
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-border, #334155);">
                <h3 style="margin-bottom: 1rem;">Búsqueda de Inicios de Sesión</h3>
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Buscar por IP</label>
                        <input 
                            type="text" 
                            name="search_ip" 
                            placeholder="192.168.1.1"
                            value="<?= html($searchIp) ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Buscar por Google ID</label>
                        <input 
                            type="text" 
                            name="search_google_id" 
                            placeholder="123456789"
                            value="<?= html($searchGoogleId) ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                    <div style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Buscar</button>
                    </div>
                </form>

                <?php if (!empty($loginResults)): ?>
                    <div style="margin-top: 2rem;">
                        <h4>Resultados de Búsqueda (<?= count($loginResults) ?> resultados)</h4>
                        <div style="overflow-x: auto; margin-top: 1rem;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                        <th style="padding: 0.75rem; text-align: left;">Usuario</th>
                                        <th style="padding: 0.75rem; text-align: left;">Email</th>
                                        <th style="padding: 0.75rem; text-align: left;">IP</th>
                                        <th style="padding: 0.75rem; text-align: left;">Google ID</th>
                                        <th style="padding: 0.75rem; text-align: left;">Fecha</th>
                                        <th style="padding: 0.75rem; text-align: left;">Plan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loginResults as $log): ?>
                                        <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                            <td style="padding: 0.75rem;"><?= html($log['name'] ?? 'N/A') ?></td>
                                            <td style="padding: 0.75rem;"><?= html($log['email'] ?? 'N/A') ?></td>
                                            <td style="padding: 0.75rem;"><code><?= html($log['ip_address']) ?></code></td>
                                            <td style="padding: 0.75rem;"><code><?= html($log['google_id'] ?? 'N/A') ?></code></td>
                                            <td style="padding: 0.75rem;"><?= !empty($log['logged_in_at']) ? date('d/m/Y H:i', strtotime($log['logged_in_at'])) : '-' ?></td>
                                            <td style="padding: 0.75rem;">
                                                <span class="badge <?= $log['plan'] === 'PREMIUM' ? 'premium-badge' : ($log['plan'] === 'ENTERPRISE' ? 'enterprise-badge' : 'free-badge') ?>">
                                                    <?= html($log['plan'] ?? 'FREE') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Management -->
            <div style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem;">Gestión de Usuarios</h3>
                
                <!-- Filters -->
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <input type="hidden" name="search_ip" value="<?= html($searchIp) ?>">
                    <input type="hidden" name="search_google_id" value="<?= html($searchGoogleId) ?>">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Filtrar por Plan</label>
                        <select 
                            name="filter_plan" 
                            onchange="this.form.submit()"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                            <option value="all" <?= $filterPlan === 'all' ? 'selected' : '' ?>>Todos</option>
                            <option value="FREE" <?= $filterPlan === 'FREE' ? 'selected' : '' ?>>FREE</option>
                            <option value="PREMIUM" <?= $filterPlan === 'PREMIUM' ? 'selected' : '' ?>>PREMIUM</option>
                            <option value="ENTERPRISE" <?= $filterPlan === 'ENTERPRISE' ? 'selected' : '' ?>>ENTERPRISE</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Filtrar por Rol</label>
                        <select 
                            name="filter_role" 
                            onchange="this.form.submit()"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                            <option value="all" <?= $filterRole === 'all' ? 'selected' : '' ?>>Todos</option>
                            <option value="USER" <?= $filterRole === 'USER' ? 'selected' : '' ?>>USER</option>
                            <option value="ADMIN" <?= $filterRole === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                        </select>
                    </div>
                </form>

                <!-- Users Table -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                <th style="padding: 0.75rem; text-align: left;">ID</th>
                                <th style="padding: 0.75rem; text-align: left;">Nombre</th>
                                <th style="padding: 0.75rem; text-align: left;">Email</th>
                                <th style="padding: 0.75rem; text-align: left;">Plan</th>
                                <th style="padding: 0.75rem; text-align: left;">Rol</th>
                                <th style="padding: 0.75rem; text-align: left;">Enlaces</th>
                                <th style="padding: 0.75rem; text-align: left;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                    <td style="padding: 0.75rem;"><?= html($u['id']) ?></td>
                                    <td style="padding: 0.75rem;"><?= html($u['name'] ?? 'N/A') ?></td>
                                    <td style="padding: 0.75rem;"><?= html($u['email'] ?? 'N/A') ?></td>
                                    <td style="padding: 0.75rem;">
                                        <span class="badge <?= $u['plan'] === 'PREMIUM' ? 'premium-badge' : ($u['plan'] === 'ENTERPRISE' ? 'enterprise-badge' : 'free-badge') ?>">
                                            <?= html($u['plan'] ?? 'FREE') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span class="badge <?= $u['role'] === 'ADMIN' ? 'premium-badge' : 'free-badge' ?>">
                                            <?= html($u['role'] ?? 'USER') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem;"><?= $shortLinkRepo->countByUserId((int)$u['id']) ?></td>
                                    <td style="padding: 0.75rem;">
                                        <?php if ($u['plan'] !== 'ENTERPRISE'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="user_id" value="<?= html($u['id']) ?>">
                                                <button type="submit" name="assign_enterprise" class="btn btn-outline" style="padding: 0.25rem 0.75rem;" onclick="return confirm('¿Asignar plan ENTERPRISE a este usuario?')">
                                                    Asignar ENTERPRISE
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--color-text-muted);">Ya es ENTERPRISE</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

