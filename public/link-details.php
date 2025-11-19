<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;
use App\Models\ClickRepository;
use App\Services\AnalyticsService;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
// Get short code from URL or query parameter
$shortCode = $_GET['code'] ?? '';
if (empty($shortCode)) {
    // Try to get from URL path
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    if (count($pathParts) >= 2 && $pathParts[0] === 'link') {
        $shortCode = $pathParts[1];
    }
}

if (empty($shortCode)) {
    redirect('/dashboard');
}

$pdo = db();
$shortLinkRepo = new ShortLinkRepository($pdo);
$clickRepo = new ClickRepository($pdo);
$analyticsService = new AnalyticsService($clickRepo);

$link = $shortLinkRepo->findByShortCode($shortCode);

// Validate link exists and has required fields
if (!$link || !isset($link['short_code']) || !isset($link['user_id']) || !isset($link['original_url']) || $link['user_id'] != $user['id']) {
    redirect('/dashboard');
}

$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

// Get analytics
$analytics = [];
$deviceBreakdown = [];
$countryBreakdown = [];
if (!empty($link['id'])) {
    $analytics = $analyticsService->getLinkAnalytics((int)$link['id'], 30);
    $deviceBreakdown = $analyticsService->getDeviceBreakdown($analytics['device_stats'] ?? []);
    $countryBreakdown = $analyticsService->getCountryBreakdown($analytics['country_stats'] ?? []);
}

$pageTitle = 'Detalles del Enlace';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Enlaces', 'href' => '/links'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

// Store link data before header.php (header.php uses $link in foreach loops which overwrites it)
$linkData = $link;

require __DIR__ . '/../views/partials/header.php';

// Restore $link after header.php
$link = $linkData;
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <!-- Link Info -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <h2><?= html($link['label'] ?? $link['short_code'] ?? 'Sin título') ?></h2>
                    <p class="text-muted">Código: <?= html($link['short_code'] ?? $shortCode) ?></p>
                </div>
                <span class="badge <?= (!empty($link['is_active']) && $link['is_active'] == 1) ? 'premium-badge' : 'free-badge' ?>">
                    <?= (!empty($link['is_active']) && $link['is_active'] == 1) ? 'Activo' : 'Inactivo' ?>
                </span>
            </div>
            <div style="padding: 1.5rem;">
                <?php if (!empty($link['original_url'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>URL Original:</strong><br>
                        <a href="<?= html($link['original_url']) ?>" target="_blank" style="color: #60a5fa; word-break: break-all;">
                            <?= html($link['original_url']) ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div style="margin-bottom: 1rem;">
                    <strong>URL Corta:</strong><br>
                    <code style="background: var(--color-bg-secondary, #1e293b); padding: 0.5rem; border-radius: 0.25rem; display: inline-block;">
                        <?= html($appConfig['base_url']) ?>/<?= html($link['short_code'] ?? $shortCode) ?>
                    </code>
                    <button onclick="copyToClipboard('<?= html($appConfig['base_url']) ?>/<?= html($link['short_code'] ?? $shortCode) ?>')" class="btn btn-outline" style="margin-left: 0.5rem; padding: 0.25rem 0.75rem;">Copiar</button>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Código QR:</strong><br>
                    <?php
                    // STEP 1: Check if QR path exists in database
                    // First, verify $link still has the data
                    error_log("QR Template Debug - Link keys: " . implode(', ', array_keys($link ?? [])) . ", qr_code_path exists: " . (isset($link['qr_code_path']) ? 'YES' : 'NO'));
                    
                    $qrPathInDb = null;
                    $hasQrPathInDb = false;
                    
                    // Direct check - verify $link is still an array and has the key
                    if (is_array($link) && array_key_exists('qr_code_path', $link)) {
                        $dbPath = $link['qr_code_path'];
                        if (is_string($dbPath) || (is_scalar($dbPath) && $dbPath !== null)) {
                            $dbPath = trim((string)$dbPath);
                            if (strlen($dbPath) > 0) {
                                $qrPathInDb = $dbPath;
                                $hasQrPathInDb = true;
                            }
                        }
                    }
                    
                    // STEP 2: Check if QR file exists in filesystem
                    $qrFileExists = false;
                    $qrFilePath = null;
                    if ($hasQrPathInDb && $qrPathInDb) {
                        // __DIR__ is /var/www/gforms.click/public
                        $qrFilePath = __DIR__ . $qrPathInDb;
                        $qrFileExists = @file_exists($qrFilePath) && @is_readable($qrFilePath);
                    }
                    
                    // Debug logging
                    error_log("QR Check Debug - Link ID: " . ($link['id'] ?? 'N/A') . 
                        " | Has qr_code_path key: " . (array_key_exists('qr_code_path', $link) ? 'YES' : 'NO') .
                        " | qr_code_path value: " . var_export($link['qr_code_path'] ?? 'NOT SET', true) .
                        " | qrPathInDb: " . var_export($qrPathInDb, true) .
                        " | qrFilePath: " . var_export($qrFilePath, true) .
                        " | qrFileExists: " . ($qrFileExists ? 'YES' : 'NO'));
                    
                    // STEP 3: Display logic - Show QR if both DB path exists AND file exists
                    if ($hasQrPathInDb && $qrPathInDb && $qrFileExists):
                    ?>
                        <div style="margin-top: 0.5rem;">
                            <p style="color: var(--color-text-success, #10b981); margin-bottom: 0.5rem; font-weight: 600;">✓ Show QR</p>
                            <img src="<?= html($qrPathInDb) ?>" alt="QR Code" style="max-width: 200px; display: block; border: 1px solid var(--color-border, #334155); border-radius: 0.25rem;">
                            <a href="/regenerate-qr?code=<?= html($shortCode) ?>" class="btn btn-outline" style="margin-top: 0.5rem; padding: 0.25rem 0.75rem; font-size: 0.875rem;">Regenerar QR</a>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
                            QR code no disponible.
                            <?php if (!$hasQrPathInDb || !$qrPathInDb): ?>
                                <br><small style="font-size: 0.875rem;">No hay ruta de QR en la base de datos.</small>
                                <br><small style="font-size: 0.75rem; color: #666;">Debug: hasQrPathInDb=<?= $hasQrPathInDb ? 'YES' : 'NO' ?>, qrPathInDb=<?= html(var_export($qrPathInDb, true)) ?>, qr_code_path=<?= html(var_export($link['qr_code_path'] ?? 'NOT SET', true)) ?></small>
                            <?php elseif (!$qrFileExists): ?>
                                <br><small style="font-size: 0.875rem;">El archivo QR no existe en el sistema de archivos.</small>
                                <br><small style="font-size: 0.75rem; color: #666;">Debug: Ruta esperada = <?= html($qrFilePath ?? 'N/A') ?>, File exists check = <?= file_exists($qrFilePath ?? '') ? 'YES' : 'NO' ?></small>
                            <?php endif; ?>
                        </p>
                        <a href="/regenerate-qr?code=<?= html($shortCode) ?>" class="btn btn-primary" style="margin-top: 0.5rem; padding: 0.5rem 1rem;">Generar QR Code</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($link['created_at'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($link['created_at'])) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($link['expires_at'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>Expira:</strong> <?= date('d/m/Y H:i', strtotime($link['expires_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analytics -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Analíticas</h2>
                    <p class="text-muted">Estadísticas de clics</p>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                <!-- Total Clicks -->
                <div style="margin-bottom: 2rem;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?= number_format($analytics['total_clicks'] ?? 0) ?>
                    </div>
                    <div style="color: var(--color-text-muted);">Total de Clics</div>
                </div>

                <!-- Daily Clicks Chart -->
                <div style="margin-bottom: 2rem;">
                    <h3>Clics Diarios (últimos 30 días)</h3>
                    <canvas id="dailyChart" style="max-height: 300px;"></canvas>
                </div>

                <!-- Device Breakdown -->
                <?php if (!empty($deviceBreakdown)): ?>
                    <div style="margin-bottom: 2rem;">
                        <h3>Dispositivos</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <?php foreach ($deviceBreakdown as $device): ?>
                                <div class="card" style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= html(ucfirst($device['device'])) ?></div>
                                    <div style="font-size: 1.5rem; margin-top: 0.5rem;"><?= number_format($device['count']) ?></div>
                                    <div style="color: var(--color-text-muted); font-size: 0.9rem;"><?= $device['percentage'] ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Country Breakdown -->
                <?php if (!empty($countryBreakdown)): ?>
                    <div style="margin-bottom: 2rem;">
                        <h3>Países (Top 10)</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <?php foreach (array_slice($countryBreakdown, 0, 10) as $country): ?>
                                <div class="card" style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= html($country['country']) ?></div>
                                    <div style="font-size: 1.5rem; margin-top: 0.5rem;"><?= number_format($country['count']) ?></div>
                                    <div style="color: var(--color-text-muted); font-size: 0.9rem;"><?= $country['percentage'] ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copiada al portapapeles');
    });
}

// Simple chart using Chart.js if available, or basic bar chart
const dailyData = <?= json_encode($analytics['daily_clicks'] ?? []) ?>;
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Clics',
                data: dailyData.map(d => d.clicks),
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
}
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

