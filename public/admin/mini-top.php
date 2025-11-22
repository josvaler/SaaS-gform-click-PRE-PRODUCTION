<?php
declare(strict_types=1);

require __DIR__ . '/../../config/bootstrap.php';
require_admin();

/**
 * Mini-TOP System Monitoring Dashboard
 * Reads system metrics from /proc filesystem and displays in real-time
 * Supports both JSON API (for AJAX) and HTML display modes
 */

// Check if JSON API request (only if explicitly requested via ?json=1 parameter)
// Don't check Accept header as browsers may send it in iframes
$isJsonRequest = isset($_GET['json']) && $_GET['json'] == '1';

// Helper function to read file safely
function readProcFile(string $path): ?string {
    if (!file_exists($path) || !is_readable($path)) {
        return null;
    }
    return @file_get_contents($path);
}

// Helper function to parse CPU stats
function parseCpuStats(): array {
    $stat = readProcFile('/proc/stat');
    if (!$stat) {
        return ['total' => null, 'cores' => []];
    }
    
    $lines = explode("\n", trim($stat));
    $cpus = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^cpu(\d*)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
            $cpuId = $matches[1] === '' ? 'total' : (int)$matches[1];
            $user = (int)$matches[2];
            $nice = (int)$matches[3];
            $system = (int)$matches[4];
            $idle = (int)$matches[5];
            $iowait = (int)$matches[6];
            $irq = (int)$matches[7];
            $softirq = (int)$matches[8];
            
            $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq;
            
            $cpus[$cpuId] = [
                'user' => $user,
                'nice' => $nice,
                'system' => $system,
                'idle' => $idle,
                'iowait' => $iowait,
                'irq' => $irq,
                'softirq' => $softirq,
                'total' => $total,
            ];
        }
    }
    
    $result = ['total' => null, 'cores' => []];
    if (isset($cpus['total'])) {
        $result['total'] = $cpus['total'];
    }
    
    ksort($cpus);
    foreach ($cpus as $id => $data) {
        if ($id !== 'total') {
            $result['cores'][$id] = $data;
        }
    }
    
    return $result;
}

// Helper function to parse memory info
function parseMemoryInfo(): ?array {
    $meminfo = readProcFile('/proc/meminfo');
    if (!$meminfo) {
        return null;
    }
    
    $data = [];
    foreach (explode("\n", $meminfo) as $line) {
        if (preg_match('/^(\w+):\s+(\d+)\s+kB/', $line, $matches)) {
            $data[$matches[1]] = (int)$matches[2] * 1024; // Convert to bytes
        }
    }
    
    $total = $data['MemTotal'] ?? 0;
    $available = $data['MemAvailable'] ?? ($data['MemFree'] ?? 0);
    $used = $total - $available;
    $percent = $total > 0 ? ($used / $total) * 100 : 0;
    
    return [
        'total' => $total,
        'used' => $used,
        'available' => $available,
        'percent' => $percent,
    ];
}

// Helper function to get load average
function getLoadAverage(): ?array {
    $loadavg = readProcFile('/proc/loadavg');
    if (!$loadavg) {
        return null;
    }
    
    $parts = explode(' ', trim($loadavg));
    return [
        '1m' => (float)($parts[0] ?? 0),
        '5m' => (float)($parts[1] ?? 0),
        '15m' => (float)($parts[2] ?? 0),
    ];
}

// Helper function to get uptime
function getUptime(): ?array {
    $uptime = readProcFile('/proc/uptime');
    if (!$uptime) {
        return null;
    }
    
    $parts = explode(' ', trim($uptime));
    $seconds = (float)($parts[0] ?? 0);
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    return [
        'seconds' => $seconds,
        'days' => $days,
        'hours' => $hours,
        'minutes' => $minutes,
        'formatted' => sprintf('%dd %02dh %02dm', $days, $hours, $minutes),
    ];
}

// Helper function to get disk usage
function getDiskUsage(): ?array {
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    
    if ($total === false || $free === false) {
        return null;
    }
    
    $used = $total - $free;
    $percent = $total > 0 ? ($used / $total) * 100 : 0;
    
    return [
        'total' => $total,
        'used' => $used,
        'free' => $free,
        'percent' => $percent,
    ];
}

// Helper function to get top processes
// Note: formatBytes() is already available from helpers.php
function getTopProcesses(int $limit = 5): array {
    $output = @shell_exec("ps -eo pid,user,comm,%cpu,%mem --sort=-%cpu --no-headers | head -{$limit} 2>/dev/null");
    if (!$output) {
        return [];
    }
    
    $processes = [];
    foreach (explode("\n", trim($output)) as $line) {
        $line = preg_replace('/\s+/', ' ', trim($line));
        if (empty($line)) continue;
        
        $parts = explode(' ', $line);
        if (count($parts) >= 5) {
            $processes[] = [
                'pid' => $parts[0] ?? '',
                'user' => $parts[1] ?? '',
                'command' => $parts[2] ?? '',
                'cpu' => (float)($parts[3] ?? 0),
                'mem' => (float)($parts[4] ?? 0),
            ];
        }
    }
    
    return $processes;
}

// Get current metrics
$cpuStats = parseCpuStats();
$memory = parseMemoryInfo();
$loadAvg = getLoadAverage();
$uptime = getUptime();
$disk = getDiskUsage();
$processes = getTopProcesses(5);
$cpuCount = count($cpuStats['cores']) ?: 1;

// If JSON request, return data only
if ($isJsonRequest) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'data' => [
            'cpu' => $cpuStats,
            'memory' => $memory,
            'loadavg' => $loadAvg,
            'uptime' => $uptime,
            'disk' => $disk,
            'processes' => $processes,
            'cpu_count' => $cpuCount,
        ],
    ], JSON_PRETTY_PRINT);
    exit;
}

// Otherwise, render HTML dashboard
// Enable error display for debugging (remove in production if needed)
error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini-TOP - System Monitor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            padding: 1.5rem;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(148, 163, 184, 0.2);
        }
        
        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .refresh-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .refresh-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        .refresh-indicator.active i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .interval-selector {
            display: flex;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.6);
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        
        .interval-btn {
            padding: 0.5rem 1rem;
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.375rem;
            color: #e2e8f0;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .interval-btn:hover {
            background: rgba(96, 165, 250, 0.1);
            border-color: #60a5fa;
        }
        
        .interval-btn.active {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            border-color: transparent;
            color: #0f172a;
            font-weight: 600;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .metric-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .metric-icon {
            font-size: 1.25rem;
            color: #60a5fa;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }
        
        .metric-subvalue {
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #60a5fa 0%, #a78bfa 100%);
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .progress-fill.warning {
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
        }
        
        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }
        
        .cpu-cores-accordion {
            margin-top: 1rem;
        }
        
        .cpu-core-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            cursor: pointer;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        
        .cpu-core-header:hover {
            background: rgba(30, 41, 59, 0.7);
        }
        
        .cpu-core-header.expanded {
            background: rgba(96, 165, 250, 0.1);
        }
        
        .cpu-core-content {
            display: none;
            padding: 0.75rem;
            background: rgba(30, 41, 59, 0.3);
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .cpu-core-content.show {
            display: block;
        }
        
        .process-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .process-table th,
        .process-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .process-table th {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .process-table td {
            font-size: 0.875rem;
            color: #e2e8f0;
        }
        
        .process-table tr:hover {
            background: rgba(30, 41, 59, 0.3);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.5rem;
            padding: 1rem;
            color: #ef4444;
            margin: 1rem 0;
        }
        
        .cpu-chart-container {
            margin-top: 1.5rem;
            height: 250px;
            position: relative;
        }
        
        .cpu-chart-container canvas {
            max-height: 250px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .interval-selector {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Debug: Remove this after confirming it works -->
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 0.5rem; border-radius: 0.5rem; margin-bottom: 1rem; color: #10b981; font-size: 0.875rem;">
            <i class="fas fa-check-circle"></i> Mini-TOP Dashboard Loaded Successfully
        </div>
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i> Mini-TOP System Monitor
            </h1>
            <div class="refresh-controls">
                <div class="refresh-indicator" id="refreshIndicator">
                    <i class="fas fa-sync-alt"></i>
                    <span id="refreshStatus">Ready</span>
                </div>
                <div class="interval-selector">
                    <button class="interval-btn" data-interval="1">1s</button>
                    <button class="interval-btn active" data-interval="5">5s</button>
                    <button class="interval-btn" data-interval="10">10s</button>
                    <button class="interval-btn" data-interval="15">15s</button>
                </div>
            </div>
        </div>
        
        <div id="errorContainer"></div>
        
        <div class="metrics-grid" id="metricsGrid">
            <!-- CPU Card -->
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">CPU Usage</span>
                    <i class="fas fa-microchip metric-icon"></i>
                </div>
                <div class="metric-value" id="cpuTotal">--</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="cpuProgress" style="width: 0%"></div>
                </div>
                <div class="cpu-cores-accordion" id="cpuCoresAccordion">
                    <!-- CPU cores will be populated here -->
                </div>
            </div>
            
            <!-- Memory Card -->
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Memory</span>
                    <i class="fas fa-memory metric-icon"></i>
                </div>
                <div class="metric-value" id="memoryUsed">--</div>
                <div class="metric-subvalue" id="memoryDetails">--</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="memoryProgress" style="width: 0%"></div>
                </div>
                <div class="cpu-chart-container">
                    <canvas id="cpuUsageChart"></canvas>
                </div>
            </div>
            
            <!-- Load Average Card -->
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Load Average</span>
                    <i class="fas fa-chart-line metric-icon"></i>
                </div>
                <div class="metric-value" id="loadAvg1m">--</div>
                <div class="metric-subvalue" id="loadAvgDetails">--</div>
            </div>
            
            <!-- Uptime Card -->
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">System Uptime</span>
                    <i class="fas fa-clock metric-icon"></i>
                </div>
                <div class="metric-value" id="uptimeFormatted">--</div>
            </div>
            
            <!-- Disk Usage Card -->
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Disk Usage</span>
                    <i class="fas fa-hdd metric-icon"></i>
                </div>
                <div class="metric-value" id="diskUsed">--</div>
                <div class="metric-subvalue" id="diskDetails">--</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="diskProgress" style="width: 0%"></div>
                </div>
            </div>
        </div>
        
        <!-- Top Processes Card -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Top 5 Processes (by CPU)</span>
                <i class="fas fa-list metric-icon"></i>
            </div>
            <table class="process-table" id="processTable">
                <thead>
                    <tr>
                        <th>PID</th>
                        <th>User</th>
                        <th>Command</th>
                        <th>CPU %</th>
                        <th>Mem %</th>
                    </tr>
                </thead>
                <tbody id="processTableBody">
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Store previous CPU stats for delta calculation
        let previousCpuStats = null;
        let refreshInterval = null;
        let currentInterval = 5; // Default 5 seconds
        
        // CPU usage chart data (rolling window of 50 points)
        let cpuUsageHistory = []; // Total CPU
        let cpuCoreHistory = {}; // Individual cores: {0: [], 1: [], ...}
        let refreshCount = 0;
        let cpuUsageChart = null;
        
        // Color palette for CPU cores (8 different colors)
        const cpuCoreColors = [
            { border: 'rgb(96, 165, 250)', fill: 'rgba(96, 165, 250, 0.1)' },    // CPU0 - Blue
            { border: 'rgb(16, 185, 129)', fill: 'rgba(16, 185, 129, 0.1)' },    // CPU1 - Green
            { border: 'rgb(251, 191, 36)', fill: 'rgba(251, 191, 36, 0.1)' },   // CPU2 - Yellow
            { border: 'rgb(239, 68, 68)', fill: 'rgba(239, 68, 68, 0.1)' },     // CPU3 - Red
            { border: 'rgb(168, 85, 247)', fill: 'rgba(168, 85, 247, 0.1)' },   // CPU4 - Purple
            { border: 'rgb(236, 72, 153)', fill: 'rgba(236, 72, 153, 0.1)' },   // CPU5 - Pink
            { border: 'rgb(34, 197, 94)', fill: 'rgba(34, 197, 94, 0.1)' },     // CPU6 - Emerald
            { border: 'rgb(249, 115, 22)', fill: 'rgba(249, 115, 22, 0.1)' }     // CPU7 - Orange
        ];
        
        // Initialize CPU usage chart
        function initCpuUsageChart() {
            const ctx = document.getElementById('cpuUsageChart');
            if (!ctx) return;
            
            // Initialize datasets for total CPU and 8 cores
            const datasets = [
                {
                    label: 'CPU Total',
                    data: [],
                    borderColor: 'rgb(148, 163, 184)',
                    backgroundColor: 'rgb(148, 163, 184)', // Same as borderColor for legend
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderDash: [5, 5] // Dashed line for total
                }
            ];
            
            // Add datasets for CPU0-CPU7
            for (let i = 0; i < 8; i++) {
                const color = cpuCoreColors[i];
                datasets.push({
                    label: `CPU${i}`,
                    data: [],
                    borderColor: color.border,
                    backgroundColor: color.border, // Use borderColor for legend box
                    borderWidth: 1.5,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    pointBackgroundColor: color.border,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1
                });
                // Initialize history for this core
                cpuCoreHistory[i] = [];
            }
            
            cpuUsageChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'start',
                            labels: {
                                color: '#94a3b8',
                                font: {
                                    size: 10
                                },
                                usePointStyle: false,
                                boxWidth: 15,
                                boxHeight: 15,
                                padding: 6
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#e2e8f0',
                            bodyColor: '#e2e8f0',
                            borderColor: 'rgba(148, 163, 184, 0.2)',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 10
                                },
                                maxTicksLimit: 10
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        },
                        y: {
                            min: 0,
                            max: 25, // Will be dynamically adjusted (0-25%, 0-50%, or 0-100%)
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 10
                                },
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    },
                    animation: {
                        duration: 300
                    }
                }
            });
        }
        
        // Update CPU usage chart with new data point
        function updateCpuUsageChart(cpuUsage, cpuCoresData) {
            if (!cpuUsageChart || cpuUsage === null || isNaN(cpuUsage)) return;
            
            refreshCount++;
            
            // Add new data point for total CPU
            cpuUsageHistory.push(cpuUsage);
            if (cpuUsageHistory.length > 50) {
                cpuUsageHistory.shift();
            }
            
            // Add new data points for each CPU core
            const allCpuValues = [cpuUsage]; // Include total CPU for max calculation
            if (cpuCoresData) {
                Object.keys(cpuCoresData).forEach(coreId => {
                    const coreUsage = cpuCoresData[coreId];
                    if (coreUsage !== null && !isNaN(coreUsage)) {
                        const coreNum = parseInt(coreId);
                        if (coreNum >= 0 && coreNum < 8) {
                            if (!cpuCoreHistory[coreNum]) {
                                cpuCoreHistory[coreNum] = [];
                            }
                            cpuCoreHistory[coreNum].push(coreUsage);
                            if (cpuCoreHistory[coreNum].length > 50) {
                                cpuCoreHistory[coreNum].shift();
                            }
                            allCpuValues.push(coreUsage);
                        }
                    }
                });
            }
            
            // Update chart labels
            const coreLengths = Object.values(cpuCoreHistory).map(h => h ? h.length : 0);
            const maxLength = Math.max(cpuUsageHistory.length, ...(coreLengths.length > 0 ? coreLengths : [0]), 1);
            cpuUsageChart.data.labels = Array.from({ length: maxLength }, (_, index) => {
                return (refreshCount - maxLength + index + 1).toString();
            });
            
            // Update total CPU dataset (index 0)
            const totalCpuData = Array(maxLength).fill(null);
            cpuUsageHistory.forEach((value, idx) => {
                const position = maxLength - cpuUsageHistory.length + idx;
                if (position >= 0) {
                    totalCpuData[position] = value;
                }
            });
            cpuUsageChart.data.datasets[0].data = totalCpuData;
            
            // Update each CPU core dataset (indices 1-8)
            for (let i = 0; i < 8; i++) {
                const datasetIndex = i + 1;
                const coreData = Array(maxLength).fill(null);
                if (cpuCoreHistory[i] && cpuCoreHistory[i].length > 0) {
                    cpuCoreHistory[i].forEach((value, idx) => {
                        const position = maxLength - cpuCoreHistory[i].length + idx;
                        if (position >= 0) {
                            coreData[position] = value;
                        }
                    });
                }
                cpuUsageChart.data.datasets[datasetIndex].data = coreData;
            }
            
            // Dynamic Y-axis scaling: 
            // - 0-25% if all <= 25%
            // - 0-50% if all <= 50% (but > 25%)
            // - 0-100% if any > 50%
            const maxValue = Math.max(...allCpuValues);
            let yMax = 100;
            if (maxValue <= 25) {
                yMax = 25;
            } else if (maxValue <= 50) {
                yMax = 50;
            } else {
                yMax = 100;
            }
            cpuUsageChart.options.scales.y.max = yMax;
            
            cpuUsageChart.update('none'); // 'none' for instant update without animation
        }
        
        // Load saved interval from localStorage
        const savedInterval = localStorage.getItem('minitop_refresh_interval');
        if (savedInterval) {
            currentInterval = parseInt(savedInterval, 10);
            document.querySelectorAll('.interval-btn').forEach(btn => {
                btn.classList.remove('active');
                if (parseInt(btn.dataset.interval, 10) === currentInterval) {
                    btn.classList.add('active');
                }
            });
        }
        
        // Interval selector buttons
        document.querySelectorAll('.interval-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentInterval = parseInt(btn.dataset.interval, 10);
                localStorage.setItem('minitop_refresh_interval', currentInterval.toString());
                startRefresh();
            });
        });
        
        // CPU cores accordion toggle
        function toggleCpuCore(coreId) {
            const header = document.getElementById(`cpu-core-header-${coreId}`);
            const content = document.getElementById(`cpu-core-content-${coreId}`);
            
            if (content && header) {
                if (content.classList.contains('show')) {
                    content.classList.remove('show');
                    header.classList.remove('expanded');
                } else {
                    content.classList.add('show');
                    header.classList.add('expanded');
                }
            }
        }
        
        // Calculate CPU usage from delta
        function calculateCpuUsage(current, previous) {
            if (!current || !previous) return null;
            const totalDelta = current.total - previous.total;
            const idleDelta = current.idle - previous.idle;
            if (totalDelta <= 0) return null;
            const usage = 100 * (1 - (idleDelta / totalDelta));
            return Math.max(0, Math.min(100, usage));
        }
        
        // Format bytes
        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let i = 0;
            let size = bytes;
            while (size >= 1024 && i < units.length - 1) {
                size /= 1024;
                i++;
            }
            return Math.round(size * 100) / 100 + ' ' + units[i];
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Fetch and update metrics
        async function updateMetrics() {
            const indicator = document.getElementById('refreshIndicator');
            const status = document.getElementById('refreshStatus');
            const errorContainer = document.getElementById('errorContainer');
            
            indicator.classList.add('active');
            status.textContent = 'Refreshing...';
            errorContainer.innerHTML = '';
            
            try {
                const response = await fetch('?json=1&t=' + Date.now());
                if (!response.ok) {
                    throw new Error('Failed to fetch metrics');
                }
                const data = await response.json();
                
                if (!data.success || !data.data) {
                    throw new Error('Invalid response format');
                }
                
                updateDisplay(data.data);
                
            } catch (error) {
                errorContainer.innerHTML = `<div class="error-message">Error refreshing data: ${escapeHtml(error.message)}</div>`;
            } finally {
                indicator.classList.remove('active');
                status.textContent = 'Ready';
            }
        }
        
        // Update display with data
        function updateDisplay(data) {
            const cpuStats = data.cpu;
            const memory = data.memory;
            const loadAvg = data.loadavg;
            const uptime = data.uptime;
            const disk = data.disk;
            const processes = data.processes;
            const cpuCount = data.cpu_count || 1;
            
            // Update CPU
            const cpuCoresUsage = {}; // Store CPU core usage for chart
            if (cpuStats && cpuStats.total) {
                const cpuUsage = calculateCpuUsage(cpuStats.total, previousCpuStats?.total);
                if (cpuUsage !== null && !isNaN(cpuUsage)) {
                    document.getElementById('cpuTotal').textContent = cpuUsage.toFixed(1) + '%';
                    const progress = document.getElementById('cpuProgress');
                    progress.style.width = cpuUsage + '%';
                    progress.className = 'progress-fill' + (cpuUsage > 90 ? ' danger' : (cpuUsage > 70 ? ' warning' : ''));
                } else {
                    // First load - show "Calculating..." or wait for next refresh
                    document.getElementById('cpuTotal').textContent = 'Calculating...';
                }
                
                // Update CPU cores and collect usage data for chart
                const coresContainer = document.getElementById('cpuCoresAccordion');
                if (cpuStats.cores && Object.keys(cpuStats.cores).length > 0) {
                    coresContainer.innerHTML = '';
                    Object.keys(cpuStats.cores).forEach((coreId) => {
                        const core = cpuStats.cores[coreId];
                        const prevCore = previousCpuStats?.cores?.[coreId];
                        const coreUsage = calculateCpuUsage(core, prevCore);
                        
                        // Store core usage for chart (only for CPU0-CPU7)
                        const coreNum = parseInt(coreId);
                        if (coreNum >= 0 && coreNum < 8 && coreUsage !== null && !isNaN(coreUsage)) {
                            cpuCoresUsage[coreId] = coreUsage;
                        }
                        
                        if (coreUsage !== null) {
                            const coreHtml = `
                                <div class="cpu-core-header" id="cpu-core-header-${coreId}" onclick="toggleCpuCore(${coreId})">
                                    <span>CPU ${coreId}</span>
                                    <span>${coreUsage.toFixed(1)}% <i class="fas fa-chevron-down"></i></span>
                                </div>
                                <div class="cpu-core-content" id="cpu-core-content-${coreId}">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: ${coreUsage}%"></div>
                                    </div>
                                </div>
                            `;
                            coresContainer.innerHTML += coreHtml;
                        }
                    });
                }
                
                // Update CPU usage chart with total and core data
                if (cpuUsage !== null && !isNaN(cpuUsage)) {
                    updateCpuUsageChart(cpuUsage, cpuCoresUsage);
                }
            }
            
            // Update Memory
            if (memory) {
                document.getElementById('memoryUsed').textContent = formatBytes(memory.used);
                document.getElementById('memoryDetails').textContent = 
                    `${formatBytes(memory.used)} / ${formatBytes(memory.total)} (${memory.percent.toFixed(1)}%)`;
                const progress = document.getElementById('memoryProgress');
                progress.style.width = memory.percent + '%';
                progress.className = 'progress-fill' + (memory.percent > 90 ? ' danger' : (memory.percent > 70 ? ' warning' : ''));
            }
            
            // Update Load Average
            if (loadAvg) {
                document.getElementById('loadAvg1m').textContent = loadAvg['1m'].toFixed(2);
                document.getElementById('loadAvgDetails').textContent = 
                    `1m: ${loadAvg['1m'].toFixed(2)} | 5m: ${loadAvg['5m'].toFixed(2)} | 15m: ${loadAvg['15m'].toFixed(2)}`;
            }
            
            // Update Uptime
            if (uptime) {
                document.getElementById('uptimeFormatted').textContent = uptime.formatted;
            }
            
            // Update Disk
            if (disk) {
                document.getElementById('diskUsed').textContent = formatBytes(disk.used);
                document.getElementById('diskDetails').textContent = 
                    `${formatBytes(disk.used)} / ${formatBytes(disk.total)} (${disk.percent.toFixed(1)}%)`;
                const progress = document.getElementById('diskProgress');
                progress.style.width = disk.percent + '%';
                progress.className = 'progress-fill' + (disk.percent > 90 ? ' danger' : (disk.percent > 70 ? ' warning' : ''));
            }
            
            // Update Processes
            const tbody = document.getElementById('processTableBody');
            if (processes && processes.length > 0) {
                tbody.innerHTML = processes.map(p => `
                    <tr>
                        <td>${escapeHtml(p.pid)}</td>
                        <td>${escapeHtml(p.user)}</td>
                        <td>${escapeHtml(p.command)}</td>
                        <td>${p.cpu.toFixed(1)}%</td>
                        <td>${p.mem.toFixed(1)}%</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #94a3b8;">No data available</td></tr>';
            }
            
            // Store current stats for next calculation
            previousCpuStats = JSON.parse(JSON.stringify(cpuStats));
        }
        
        // Start auto-refresh
        function startRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            updateMetrics();
            refreshInterval = setInterval(updateMetrics, currentInterval * 1000);
        }
        
        // Initialize chart on page load
        if (typeof Chart !== 'undefined') {
            initCpuUsageChart();
        } else {
            console.warn('Chart.js not loaded, CPU usage chart will not be available');
        }
        
        // Initial display with server data
        try {
            const initialData = {
                cpu: <?= json_encode($cpuStats) ?>,
                memory: <?= json_encode($memory) ?>,
                loadavg: <?= json_encode($loadAvg) ?>,
                uptime: <?= json_encode($uptime) ?>,
                disk: <?= json_encode($disk) ?>,
                processes: <?= json_encode($processes) ?>,
                cpu_count: <?= $cpuCount ?>,
            };
            
            updateDisplay(initialData);
        } catch (error) {
            console.error('Error initializing display:', error);
            document.getElementById('errorContainer').innerHTML = 
                '<div class="error-message">Error loading initial data: ' + error.message + '</div>';
        }
        
        // Start auto-refresh
        startRefresh();
        
        // Make toggleCpuCore available globally
        window.toggleCpuCore = toggleCpuCore;
    </script>
</body>
</html>
