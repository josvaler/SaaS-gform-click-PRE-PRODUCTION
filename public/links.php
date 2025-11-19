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

// Get links
if (!empty($search)) {
    $links = $shortLinkRepo->searchByUser((int)$user['id'], $search, $perPage, $offset);
    $totalLinks = count($shortLinkRepo->searchByUser((int)$user['id'], $search, 1000, 0));
} else {
    $links = $shortLinkRepo->findByUserId((int)$user['id'], $perPage, $offset);
    $totalLinks = $shortLinkRepo->countByUserId((int)$user['id']);
}

// Filter by status
if ($status !== 'all') {
    $links = array_filter($links, function($link) use ($status) {
        if ($status === 'active') {
            return $link['is_active'] == 1 && ($link['expires_at'] === null || strtotime($link['expires_at']) > time());
        } elseif ($status === 'expired') {
            return $link['expires_at'] !== null && strtotime($link['expires_at']) <= time();
        } elseif ($status === 'inactive') {
            return $link['is_active'] == 0;
        }
        return true;
    });
}

$totalPages = ceil($totalLinks / $perPage);

$pageTitle = 'Gestionar Enlaces';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Crear Enlace', 'href' => '/create-link'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Gestionar Enlaces</h2>
                    <p class="text-muted">Administra todos tus enlaces cortos</p>
                </div>
            </div>

            <!-- Filters -->
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-border, #334155);">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Buscar</label>
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Etiqueta o URL..."
                            value="<?= html($search) ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Estado</label>
                        <select 
                            name="status" 
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Activos</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactivos</option>
                            <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expirados</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filtrar</button>
                    </div>
                </form>
            </div>

            <!-- Links Table -->
            <div style="padding: 1.5rem;">
                <?php if (empty($links)): ?>
                    <div class="alert alert-info">
                        No se encontraron enlaces. <a href="/create-link">Crea tu primer enlace</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                    <th style="padding: 0.75rem; text-align: left;">Etiqueta</th>
                                    <th style="padding: 0.75rem; text-align: left;">Código</th>
                                    <th style="padding: 0.75rem; text-align: left;">URL Original</th>
                                    <th style="padding: 0.75rem; text-align: left;">Estado</th>
                                    <th style="padding: 0.75rem; text-align: left;">Creado</th>
                                    <th style="padding: 0.75rem; text-align: left;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr style="border-bottom: 1px solid var(--color-border, #334155);">
                                        <td style="padding: 0.75rem;"><?= html($link['label'] ?: '-') ?></td>
                                        <td style="padding: 0.75rem;">
                                            <a href="/<?= html($link['short_code']) ?>" target="_blank" style="color: #60a5fa;">
                                                <?= html($link['short_code']) ?>
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
                                                <?= $isExpired ? 'Expirado' : ($link['is_active'] == 1 ? 'Activo' : 'Inactivo') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;"><?= date('d/m/Y', strtotime($link['created_at'])) ?></td>
                                        <td style="padding: 0.75rem;">
                                            <a href="/link/<?= html($link['short_code']) ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; margin-right: 0.5rem;">Ver</a>
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
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= html($status) ?>" class="btn btn-outline">Anterior</a>
                            <?php endif; ?>
                            <span style="padding: 0.5rem 1rem; display: inline-block;">Página <?= $page ?> de <?= $totalPages ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= html($status) ?>" class="btn btn-outline">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

