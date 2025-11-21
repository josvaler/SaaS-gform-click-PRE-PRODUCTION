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

// Get analytics with comparison
$analyticsData = [];
$analytics = [];
$deviceBreakdown = [];
$countryBreakdown = [];
$trends = [];
$selectedDays = isset($_GET['days']) ? (int)$_GET['days'] : 30;
if (!empty($link['id'])) {
    $analyticsData = $analyticsService->getLinkAnalyticsWithComparison((int)$link['id'], $selectedDays);
    $analytics = $analyticsData['current'] ?? [];
    $trends = $analyticsData['trends'] ?? [];
    $deviceBreakdown = $analyticsService->getDeviceBreakdown($analytics['device_stats'] ?? []);
    $countryBreakdown = $analyticsService->getCountryBreakdown($analytics['country_stats'] ?? []);
}

$pageTitle = 'Detalles del Enlace';
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
    ['label' => t('nav.links'), 'href' => '/links'],
];
$navLinksRight = [
    ['label' => t('nav.logout'), 'href' => '/logout'],
];

// Store link data before header.php (header.php uses $link in foreach loops which overwrites it)
$linkData = $link;

require __DIR__ . '/../views/partials/header.php';

// Restore $link after header.php
$link = $linkData;
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <!-- Link Header -->
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
        </div>

        <!-- Tabs Container -->
        <div class="tabs-container">
            <div class="tab-header" role="tablist">
                <button class="tab-button active" role="tab" aria-selected="true" aria-controls="tab-details" id="tab-button-details" data-tab="details" onclick="if(typeof window.switchTab==='function'){window.switchTab('details');}return false;">
                    Detalles del Enlace
                </button>
                <button class="tab-button" role="tab" aria-selected="false" aria-controls="tab-analytics" id="tab-button-analytics" data-tab="analytics" onclick="if(typeof window.switchTab==='function'){window.switchTab('analytics');}return false;">
                    Analíticas
                </button>
            </div>

            <!-- Tab 1: Link Details -->
            <div id="tab-details" class="tab-content active" role="tabpanel" aria-labelledby="tab-button-details" style="display: block;">
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
                    debug_log("QR Template Debug - Link keys: " . implode(', ', array_keys($link ?? [])) . ", qr_code_path exists: " . (isset($link['qr_code_path']) ? 'YES' : 'NO'));
                    
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
                    debug_log("QR Check Debug - Link ID: " . ($link['id'] ?? 'N/A') . 
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
                            <?php elseif (!$qrFileExists): ?>
                                <br><small style="font-size: 0.875rem;">El archivo QR no existe en el sistema de archivos.</small>
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

            <!-- Tab 2: Analytics -->
            <div id="tab-analytics" class="tab-content" role="tabpanel" aria-labelledby="tab-button-analytics" style="display: none;">
                <?php if (empty($analytics) || empty($link['id'])): ?>
                    <div style="text-align: center; padding: 3rem 1rem;">
                        <p style="color: var(--color-text-muted); font-size: 1.1rem; margin-bottom: 1rem;">
                            No hay datos de analíticas disponibles aún.
                        </p>
                        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
                            Las estadísticas aparecerán aquí una vez que el enlace reciba clics.
                        </p>
                    </div>
                <?php else: ?>
                <!-- Total Clicks -->
                <div style="margin-bottom: 2rem;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?= number_format($analytics['total_clicks'] ?? 0) ?>
                    </div>
                    <div style="color: var(--color-text-muted);">Total de Clics</div>
                </div>

                <!-- Trend Indicators -->
                <?php if (!empty($trends)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <!-- Total Clicks Trend -->
                    <div class="card" style="padding: 1.25rem; border-left: 3px solid <?= ($trends['total_clicks']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <div style="color: var(--color-text-muted); font-size: 0.875rem; font-weight: 500;">Total de Clics</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: <?= ($trends['total_clicks']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                                <?= ($trends['total_clicks']['is_positive'] ?? true) ? '↑' : '↓' ?>
                                <?= abs($trends['total_clicks']['change_percent'] ?? 0) ?>%
                            </div>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <?= number_format($trends['total_clicks']['current'] ?? 0) ?>
                        </div>
                        <div style="color: var(--color-text-muted); font-size: 0.875rem;">
                            Anterior: <?= number_format($trends['total_clicks']['previous'] ?? 0) ?>
                        </div>
                    </div>

                    <!-- Average Daily Trend -->
                    <div class="card" style="padding: 1.25rem; border-left: 3px solid <?= ($trends['average_daily']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <div style="color: var(--color-text-muted); font-size: 0.875rem; font-weight: 500;">Promedio Diario</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: <?= ($trends['average_daily']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                                <?= ($trends['average_daily']['is_positive'] ?? true) ? '↑' : '↓' ?>
                                <?= abs($trends['average_daily']['change_percent'] ?? 0) ?>%
                            </div>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <?= number_format($trends['average_daily']['current'] ?? 0, 1) ?>
                        </div>
                        <div style="color: var(--color-text-muted); font-size: 0.875rem;">
                            Anterior: <?= number_format($trends['average_daily']['previous'] ?? 0, 1) ?>
                        </div>
                    </div>

                    <!-- Peak Day Trend -->
                    <div class="card" style="padding: 1.25rem; border-left: 3px solid <?= ($trends['peak_day']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <div style="color: var(--color-text-muted); font-size: 0.875rem; font-weight: 500;">Día Pico</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: <?= ($trends['peak_day']['is_positive'] ?? true) ? '#10b981' : '#ef4444' ?>;">
                                <?= ($trends['peak_day']['is_positive'] ?? true) ? '↑' : '↓' ?>
                                <?= abs($trends['peak_day']['change_percent'] ?? 0) ?>%
                            </div>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <?= number_format($trends['peak_day']['current'] ?? 0) ?>
                        </div>
                        <div style="color: var(--color-text-muted); font-size: 0.875rem;">
                            <?php if (!empty($trends['peak_day']['current_date'])): ?>
                                <?= date('d/m/Y', strtotime($trends['peak_day']['current_date'])) ?>
                            <?php else: ?>
                                Sin datos
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Daily Clicks Chart -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin: 0;">Clics Diarios</h3>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button onclick="changePeriod(7)" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem; <?= $selectedDays === 7 ? 'background: var(--color-primary, #60a5fa); color: white;' : '' ?>">
                                7 días
                            </button>
                            <button onclick="changePeriod(30)" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem; <?= $selectedDays === 30 ? 'background: var(--color-primary, #60a5fa); color: white;' : '' ?>">
                                30 días
                            </button>
                            <button onclick="changePeriod(90)" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem; <?= $selectedDays === 90 ? 'background: var(--color-primary, #60a5fa); color: white;' : '' ?>">
                                90 días
                            </button>
                            <button onclick="toggleComparison()" class="btn btn-outline" id="comparisonToggle" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                Comparar
                            </button>
                        </div>
                    </div>
                    <div style="position: relative; height: 350px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Debug logging function - only logs if SYSTEM_CODE_DEBUG is enabled
const SYSTEM_CODE_DEBUG = <?= json_encode(env('SYSTEM_CODE_DEBUG', 'false') === 'true' || env('SYSTEM_CODE_DEBUG', 'false') === '1') ?>;
function debugLog(...args) {
    if (SYSTEM_CODE_DEBUG) {
        console.log('[DEBUG]', ...args);
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copiada al portapapeles');
    });
}

// Tab switching functionality - define immediately and make globally accessible
window.switchTab = function(tabName) {
    debugLog('Switching to tab:', tabName);
    
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
        content.setAttribute('aria-hidden', 'true');
        content.style.display = 'none';
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
    
    debugLog('Selected tab element:', selectedTab);
    debugLog('Selected button element:', selectedButton);
    
    if (selectedTab && selectedButton) {
        selectedTab.classList.add('active');
        selectedTab.setAttribute('aria-hidden', 'false');
        selectedTab.style.display = 'block';
        selectedButton.classList.add('active');
        selectedButton.setAttribute('aria-selected', 'true');
        
        debugLog('Tab switched successfully');
        
        // If switching to analytics tab, ensure chart is properly rendered
        if (tabName === 'analytics') {
            setTimeout(() => {
                debugLog('Initializing chart for analytics tab');
                // Initialize chart if it doesn't exist
                if (typeof chartInstance === 'undefined' || chartInstance === null) {
                    if (typeof initializeChart === 'function') {
                        initializeChart();
                    }
                } else {
                    // Resize existing chart
                    if (chartInstance.resize) {
                        chartInstance.resize();
                    }
                    if (typeof updateGradient === 'function') {
                        updateGradient(chartInstance);
                    } else if (chartInstance.chartArea) {
                        const gradient = createGradient(chartInstance);
                        if (gradient && chartInstance.data.datasets[0]) {
                            chartInstance.data.datasets[0].backgroundColor = gradient;
                            chartInstance.update('none');
                        }
                    }
                }
            }, 150);
        }
    } else {
        debugLog('Tab elements not found:', { selectedTab, selectedButton });
    }
};

// Initialize tabs on page load
(function() {
    function initTabs() {
        debugLog('Initializing tabs');
        
        // Add click handlers to tab buttons
        const tabButtons = document.querySelectorAll('.tab-button');
        debugLog('Found tab buttons:', tabButtons.length);
        tabButtons.forEach(button => {
            const tabName = button.getAttribute('data-tab');
            debugLog('Attaching listener to button:', tabName);
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const tabName = this.getAttribute('data-tab');
                debugLog('Button clicked, tab name:', tabName);
                if (tabName && typeof window.switchTab === 'function') {
                    window.switchTab(tabName);
                } else {
                    debugLog('switchTab function not available or invalid tab name');
                }
            });
        });
        
        // Ensure details tab is visible by default
        const detailsTab = document.getElementById('tab-details');
        const analyticsTab = document.getElementById('tab-analytics');
        
        if (detailsTab) {
            detailsTab.classList.add('active');
            detailsTab.style.display = 'block';
            detailsTab.setAttribute('aria-hidden', 'false');
        }
        
        if (analyticsTab) {
            analyticsTab.classList.remove('active');
            analyticsTab.style.display = 'none';
            analyticsTab.setAttribute('aria-hidden', 'true');
        }
        
        // Check for hash in URL to open specific tab
        const hash = window.location.hash;
        if (hash === '#analytics' && typeof window.switchTab === 'function') {
            window.switchTab('analytics');
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
})();

function changePeriod(days) {
    const url = new URL(window.location.href);
    url.searchParams.set('days', days);
    window.location.href = url.toString();
}

let showComparison = false;
let chartInstance = null;

function toggleComparison() {
    showComparison = !showComparison;
    const btn = document.getElementById('comparisonToggle');
    if (showComparison) {
        btn.style.background = 'var(--color-primary, #60a5fa)';
        btn.style.color = 'white';
    } else {
        btn.style.background = '';
        btn.style.color = '';
    }
    updateChart();
}

function updateChart() {
    if (!chartInstance) return;
    
    const datasets = [{
        label: 'Clics',
        data: dailyData.map(d => d.clicks),
        borderColor: '#60a5fa',
        backgroundColor: 'rgba(96, 165, 250, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: '#60a5fa',
        pointHoverBorderColor: '#ffffff',
        pointHoverBorderWidth: 2,
    }];
    
    if (showComparison && previousData && previousData.length > 0) {
        // Align previous data to match current period length
        // Take the last N days from previous period to match current period
        const alignedPreviousData = previousData.slice(-dailyData.length);
        datasets.push({
            label: 'Período Anterior',
            data: alignedPreviousData.map(d => d.clicks),
            borderColor: 'rgba(96, 165, 250, 0.5)',
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 5,
        });
    }
    
    chartInstance.data.datasets = datasets;
    chartInstance.update();
    
    // Update gradient after chart update
    setTimeout(() => {
        if (chartInstance.chartArea) {
            const gradient = createGradient(chartInstance);
            if (gradient && chartInstance.data.datasets[0]) {
                chartInstance.data.datasets[0].backgroundColor = gradient;
                chartInstance.update('none');
            }
        }
    }, 50);
}

function createGradient(chart) {
    const ctx = chart.ctx;
    const chartArea = chart.chartArea;
    
    if (!chartArea) {
        return 'rgba(96, 165, 250, 0.1)';
    }
    
    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    gradient.addColorStop(0, 'rgba(96, 165, 250, 0.3)');
    gradient.addColorStop(0.5, 'rgba(96, 165, 250, 0.15)');
    gradient.addColorStop(1, 'rgba(96, 165, 250, 0.05)');
    return gradient;
}

// Chart data
var dailyData = <?php 
    $dailyClicks = isset($analytics['daily_clicks']) && is_array($analytics['daily_clicks']) ? $analytics['daily_clicks'] : [];
    $json = json_encode($dailyClicks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    echo ($json === false) ? '[]' : $json;
?>;
var previousData = <?php 
    $previousClicks = [];
    if (isset($analyticsData['previous']) && is_array($analyticsData['previous']) && isset($analyticsData['previous']['daily_clicks'])) {
        $previousClicks = $analyticsData['previous']['daily_clicks'];
    }
    $json = json_encode($previousClicks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    echo ($json === false) ? '[]' : $json;
?>;

// Format dates for display
function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
}

function initializeChart() {
    if (typeof Chart === 'undefined') {
        debugLog('Chart.js not loaded');
        return;
    }
    
    const chartCanvas = document.getElementById('dailyChart');
    if (!chartCanvas) {
        debugLog('Chart canvas not found');
        return;
    }
    
    // Check if data is available
    if (!dailyData || dailyData.length === 0) {
        debugLog('No chart data available');
        return;
    }
    
    // Don't initialize if chart already exists
    if (chartInstance !== null && chartInstance !== undefined) {
        // Just resize if it exists
        setTimeout(() => {
            if (chartInstance.resize) {
                chartInstance.resize();
            }
        }, 100);
        return;
    }
    
    const ctx = chartCanvas.getContext('2d');
    
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => formatDate(d.date)),
            datasets: [{
                label: 'Clics',
                data: dailyData.map(d => d.clicks),
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#60a5fa',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'var(--color-text, #e2e8f0)',
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500',
                        },
                        usePointStyle: true,
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 12,
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(96, 165, 250, 0.3)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            const dateStr = dailyData[index].date;
                            const date = new Date(dateStr + 'T00:00:00');
                            return date.toLocaleDateString('es-ES', { 
                                weekday: 'long', 
                                day: 'numeric', 
                                month: 'long',
                                year: 'numeric'
                            });
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('es-ES');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'var(--color-text-muted, #94a3b8)',
                        font: {
                            size: 11,
                        },
                        maxRotation: 45,
                        minRotation: 0,
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'var(--color-text-muted, #94a3b8)',
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return value.toLocaleString('es-ES');
                        }
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart',
            },
            onResize: function(chart) {
                // Update gradient on resize
                updateGradient(chart);
            }
        }
    });
        // Update gradient after chart is drawn
        function updateGradient(chart) {
            const dataset = chart.data.datasets[0];
            if (dataset && chart.chartArea) {
                const gradient = createGradient(chart);
                if (gradient) {
                    dataset.backgroundColor = gradient;
                }
            }
        }
        
        // Set gradient after initial render
        chartInstance.update();
        setTimeout(() => {
            updateGradient(chartInstance);
            chartInstance.update('none');
        }, 100);
    }

// Initialize chart only if analytics tab is visible on page load
(function() {
    function checkAndInitChart() {
        const analyticsTab = document.getElementById('tab-analytics');
        if (analyticsTab && analyticsTab.classList.contains('active')) {
            // Analytics tab is visible, initialize chart
            setTimeout(() => {
                initializeChart();
            }, 200);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAndInitChart);
    } else {
        checkAndInitChart();
    }
})();
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

