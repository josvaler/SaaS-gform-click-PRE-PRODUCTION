<?php
declare(strict_types=1);

/**
 * OS Health Check Component
 * Displays Ubuntu/Linux server health metrics with gauge charts
 */

// Helper function to execute shell command safely
function execCommand(string $command): ?string
{
    try {
        $output = @shell_exec($command . ' 2>&1');
        return $output ? trim($output) : null;
    } catch (Exception $e) {
        return null;
    }
}

// formatNumber is now in config/helpers.php

// Helper function to get percentage color
function getPercentageColor(float $percentage): string
{
    if ($percentage < 70) return '#10b981'; // green
    if ($percentage < 90) return '#f59e0b'; // yellow
    return '#ef4444'; // red
}

// Get current date/time
$currentDate = date('Y-m-d H:i:s');

// CPU Metrics
$loadAverage = execCommand('uptime | awk -F\'load average:\' \'{print $2}\'');
$cpuUsage = execCommand('mpstat 1 1 2>/dev/null | awk \'/Average/ {print "user="$3" system="$5" iowait="$6" steal="$7}\'');
if (!$cpuUsage) {
    // Fallback to /proc/stat
    $stat1 = file_get_contents('/proc/stat');
    sleep(1);
    $stat2 = file_get_contents('/proc/stat');
    // Simple CPU calculation fallback
    $cpuUsage = 'user=0 system=0 iowait=0 steal=0';
}

// Parse CPU usage
preg_match('/user=([\d.]+)/', $cpuUsage, $userMatch);
preg_match('/system=([\d.]+)/', $cpuUsage, $systemMatch);
preg_match('/iowait=([\d.]+)/', $cpuUsage, $iowaitMatch);
preg_match('/steal=([\d.]+)/', $cpuUsage, $stealMatch);

$cpuUser = isset($userMatch[1]) ? (float)$userMatch[1] : 0;
$cpuSystem = isset($systemMatch[1]) ? (float)$systemMatch[1] : 0;
$cpuIowait = isset($iowaitMatch[1]) ? (float)$iowaitMatch[1] : 0;
$cpuSteal = isset($stealMatch[1]) ? (float)$stealMatch[1] : 0;

// Memory Metrics
$freeOutput = execCommand('free -m');
$memAvailable = execCommand('awk \'/MemAvailable/ {print int($2)}\' /proc/meminfo');
$swapInfo = execCommand('swapon --show 2>/dev/null');

// Parse memory
preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $freeOutput ?? '', $memMatch);
$memTotal = isset($memMatch[1]) ? (int)$memMatch[1] : 0;
$memUsed = isset($memMatch[2]) ? (int)$memMatch[2] : 0;
$memUsagePercent = $memTotal > 0 ? ($memUsed / $memTotal) * 100 : 0;

preg_match('/Swap:\s+(\d+)\s+(\d+)\s+(\d+)/', $freeOutput ?? '', $swapMatch);
$swapTotal = isset($swapMatch[1]) ? (int)$swapMatch[1] : 0;
$swapUsed = isset($swapMatch[2]) ? (int)$swapMatch[2] : 0;
$swapUsagePercent = $swapTotal > 0 ? ($swapUsed / $swapTotal) * 100 : 0;

// Disk Metrics
$dfOutput = execCommand('df -h --output=source,pcent,size,used,avail | grep -v tmpfs');
$dfInodes = execCommand('df -i | grep -v tmpfs');
$diskIo = execCommand('iostat -dx 2>/dev/null | head -n 20');

// Parse disk usage for main filesystems
$diskData = [];
if ($dfOutput) {
    $lines = explode("\n", $dfOutput);
    foreach ($lines as $line) {
        if (preg_match('/(\S+)\s+(\d+)%\s+(\S+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
            if ($matches[1] !== 'Filesystem' && strpos($matches[1], '/dev/') === 0) {
                $diskData[] = [
                    'source' => $matches[1],
                    'usage' => (int)$matches[2],
                    'size' => $matches[3],
                    'used' => $matches[4],
                    'avail' => $matches[5]
                ];
            }
        }
    }
}

// Network Metrics
$gateway = execCommand('ip route | grep default | awk \'{print $3}\' | head -1');
$pingResult = $gateway ? execCommand("ping -c 4 {$gateway} 2>/dev/null | tail -1") : null;
$interfaceStats = execCommand('ip -s link 2>/dev/null');

// Process Metrics
$totalProcesses = execCommand('ps ax | wc -l');
$zombies = execCommand('ps aux | awk \'{ if ($8=="Z") print $0 }\' | wc -l');
$topCpu = execCommand('ps -eo pid,ppid,cmd,%cpu --sort=-%cpu | head -6');
$topRam = execCommand('ps -eo pid,ppid,cmd,%mem --sort=-%mem | head -6');

// Services Status
$services = ['apache2', 'mysql', 'mariadb', 'ssh', 'netdata'];
$serviceStatus = [];
foreach ($services as $svc) {
    $isInstalled = execCommand("systemctl list-unit-files 2>/dev/null | grep -q '^{$svc}' && echo 'yes' || echo 'no'");
    if ($isInstalled === 'yes') {
        $status = execCommand("systemctl --no-pager is-active {$svc} 2>/dev/null");
        $serviceStatus[$svc] = [
            'installed' => true,
            'active' => $status === 'active',
            'status' => execCommand("systemctl --no-pager status {$svc} 2>/dev/null | head -8")
        ];
    } else {
        $serviceStatus[$svc] = ['installed' => false];
    }
}
?>

<div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Header -->
    <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
        <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
            <?= t('admin.diagnostics.os_health_check') ?>
        </h3>
        <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
            <?= htmlspecialchars($currentDate) ?>
        </p>
    </div>

    <div style="display: grid; gap: 1.5rem;">
        <!-- CPU Metrics Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 CPU METRICS
            </h4>
            
            <?php if ($loadAverage): ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Load Average:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($loadAverage) ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">CPU Usage:</div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                    <!-- CPU User Gauge -->
                    <div class="gauge-container">
                        <canvas id="cpuUserGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">user</div>
                    </div>
                    <!-- CPU System Gauge -->
                    <div class="gauge-container">
                        <canvas id="cpuSystemGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">system</div>
                    </div>
                    <!-- CPU Iowait Gauge -->
                    <div class="gauge-container">
                        <canvas id="cpuIowaitGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">iowait</div>
                    </div>
                    <!-- CPU Steal Gauge -->
                    <div class="gauge-container">
                        <canvas id="cpuStealGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">steal</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 MEMORY
            </h4>
            
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">RAM:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($freeOutput ?? 'N/A') ?></pre>
            </div>
            
            <?php if ($memAvailable): ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">RAM Available (MB):</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($memAvailable) ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">Usage:</div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                    <!-- RAM Usage Gauge -->
                    <div class="gauge-container">
                        <canvas id="ramUsageGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">RAM</div>
                    </div>
                    <!-- Swap Usage Gauge -->
                    <?php if ($swapTotal > 0): ?>
                    <div class="gauge-container">
                        <canvas id="swapUsageGauge"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">Swap</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($swapInfo): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Swap usage:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($swapInfo) ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Disk Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 DISK
            </h4>
            
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">Disk usage:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($dfOutput ?? 'N/A') ?></pre>
            </div>
            
            <?php if (!empty($diskData)): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">Usage:</div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                    <?php foreach (array_slice($diskData, 0, 4) as $index => $disk): ?>
                    <div class="gauge-container">
                        <canvas id="diskGauge<?= $index ?>"></canvas>
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;"><?= htmlspecialchars(basename($disk['source'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($dfInodes): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Inodes:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($dfInodes) ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if ($diskIo): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Disk IO (top 10):</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($diskIo) ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Network Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 NETWORK
            </h4>
            
            <?php if ($gateway && $pingResult): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Default Gateway Ping:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($pingResult) ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if ($interfaceStats): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Interface Statistics:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary); max-height: 300px; overflow-y: auto;"><?= htmlspecialchars($interfaceStats) ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Processes Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 PROCESSES
            </h4>
            
            <div style="display: grid; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Total processes:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($totalProcesses) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Zombies:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($zombies) ?></span>
                </div>
            </div>
            
            <?php if ($topCpu): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Top CPU consumers:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($topCpu) ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if ($topRam): ?>
            <div style="margin-bottom: 1rem;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Top RAM consumers:</div>
                <pre style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($topRam) ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Services Status Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 SERVICES STATUS
            </h4>
            
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($serviceStatus as $svc => $status): ?>
                <div style="padding: 0.75rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                        >>> <?= htmlspecialchars($svc) ?>:
                    </div>
                    <?php if ($status['installed']): ?>
                        <div style="color: <?= $status['active'] ? '#10b981' : '#ef4444' ?>; margin-bottom: 0.5rem;">
                            Status: <?= $status['active'] ? '🟢 Active' : '🔴 Inactive' ?>
                        </div>
                        <?php if ($status['status']): ?>
                        <pre style="background: rgba(0, 0, 0, 0.3); padding: 0.5rem; border-radius: 0.25rem; overflow-x: auto; font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.5rem;"><?= htmlspecialchars($status['status']) ?></pre>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color: var(--text-secondary); opacity: 0.8;">
                            <?= t('admin.diagnostics.os_service_not_installed') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Gauge chart container styling for mobile responsiveness */
.gauge-container {
    position: relative;
    width: 100%;
    max-width: 150px;
    margin: 0 auto;
}

.gauge-container canvas {
    width: 100% !important;
    height: auto !important;
    max-width: 150px;
    max-height: 150px;
}

@media (max-width: 768px) {
    .gauge-container {
        max-width: 140px;
    }
    
    .gauge-container canvas {
        max-width: 140px;
        max-height: 140px;
    }
}
</style>

<script>
// Gauge chart configuration function using Chart.js doughnut as gauge
function createGaugeChart(canvasId, value, label, maxValue = 100) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') {
        console.error('Chart.js not loaded or canvas not found:', canvasId);
        return;
    }
    
    const percentage = Math.min((value / maxValue) * 100, 100);
    
    // Determine color based on percentage
    let color = '#10b981'; // green
    if (percentage >= 90) color = '#ef4444'; // red
    else if (percentage >= 70) color = '#f59e0b'; // yellow
    
    // Create semi-circle gauge using doughnut chart
    const chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [percentage, 100 - percentage],
                backgroundColor: [color, 'rgba(148, 163, 184, 0.15)'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1,
            rotation: -90,
            circumference: 180,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            return label + ': ' + percentage.toFixed(1) + '%';
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: false
            }
        },
        plugins: [{
            id: 'gaugeText',
            beforeDraw: function(chart) {
                const ctx = chart.ctx;
                const centerX = chart.chartArea.left + (chart.chartArea.right - chart.chartArea.left) / 2;
                const centerY = chart.chartArea.top + (chart.chartArea.bottom - chart.chartArea.top) / 2 + 15;
                
                ctx.save();
                const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary') || '#ffffff';
                ctx.fillStyle = textColor;
                ctx.font = 'bold 16px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(percentage.toFixed(1) + '%', centerX, centerY);
                ctx.restore();
            }
        }]
    });
}

// Initialize all gauge charts when DOM is ready
(function() {
    function initGauges() {
        // Wait for Chart.js to be available
        if (typeof Chart === 'undefined') {
            setTimeout(initGauges, 100);
            return;
        }
        
        // Wait a bit more to ensure canvas elements are rendered
        setTimeout(function() {
            // CPU Gauges
            createGaugeChart('cpuUserGauge', <?= $cpuUser ?>, 'user');
            createGaugeChart('cpuSystemGauge', <?= $cpuSystem ?>, 'system');
            createGaugeChart('cpuIowaitGauge', <?= $cpuIowait ?>, 'iowait');
            createGaugeChart('cpuStealGauge', <?= $cpuSteal ?>, 'steal');
            
            // Memory Gauges
            createGaugeChart('ramUsageGauge', <?= $memUsagePercent ?>, 'RAM');
            <?php if ($swapTotal > 0): ?>
            createGaugeChart('swapUsageGauge', <?= $swapUsagePercent ?>, 'Swap');
            <?php endif; ?>
            
            // Disk Gauges
            <?php foreach (array_slice($diskData, 0, 4) as $index => $disk): ?>
            createGaugeChart('diskGauge<?= $index ?>', <?= $disk['usage'] ?>, '<?= htmlspecialchars(basename($disk['source'])) ?>');
            <?php endforeach; ?>
        }, 200);
    }
    
    // Initialize when accordion is opened or immediately if already open
    const osAccordion = document.getElementById('accordion-os');
    if (osAccordion) {
        const observer = new MutationObserver(function(mutations) {
            if (osAccordion.getAttribute('aria-hidden') === 'false') {
                initGauges();
                observer.disconnect();
            }
        });
        
        if (osAccordion.getAttribute('aria-hidden') === 'false') {
            initGauges();
        } else {
            observer.observe(osAccordion, { attributes: true, attributeFilter: ['aria-hidden'] });
        }
    } else {
        // Fallback if accordion not found
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGauges);
        } else {
            initGauges();
        }
    }
})();
</script>

