<?php
declare(strict_types=1);

/**
 * OS Health Check Component (Lazy-Loaded)
 * Displays Ubuntu/Linux server health metrics with gauge charts
 * Data is loaded via AJAX when accordion opens
 */
?>

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

<div id="os-content" style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Loading State -->
    <div id="os-loading" style="text-align: center; padding: 3rem 1rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
        <p style="color: var(--text-secondary); opacity: 0.8;"><?= t('admin.diagnostics.loading') ?></p>
    </div>
    
    <!-- Error State -->
    <div id="os-error" style="display: none; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 1rem;">
        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
            <?= t('admin.diagnostics.error_loading') ?>
        </div>
        <div id="os-error-message" style="color: var(--text-secondary); font-size: 0.875rem;"></div>
        <button onclick="loadOsData()" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
            <i class="fas fa-redo" style="margin-right: 0.5rem;"></i><?= t('admin.diagnostics.retry') ?>
        </button>
    </div>
    
    <!-- Content (hidden until loaded) -->
    <div id="os-data" style="display: none;">
        <!-- Header -->
        <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
            <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                <?= t('admin.diagnostics.os_health_check') ?>
            </h3>
            <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
                <span id="os-date"></span>
                <span id="os-cache-info" style="margin-left: 1rem; font-size: 0.75rem; opacity: 0.7;"></span>
            </p>
        </div>

        <div id="os-metrics" style="display: grid; gap: 1.5rem;">
            <!-- Metrics will be inserted here -->
        </div>
    </div>
</div>

<script>
// Gauge chart configuration function using Chart.js doughnut as gauge
function createGaugeChart(canvasId, value, label, maxValue = 100) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') {
        console.error('Chart.js not loaded or canvas not found:', canvasId);
        return null;
    }
    
    const percentage = Math.min((value / maxValue) * 100, 100);
    
    // Determine color based on percentage
    let color = '#10b981'; // green
    if (percentage >= 90) color = '#ef4444'; // red
    else if (percentage >= 70) color = '#f59e0b'; // yellow
    
    // Create semi-circle gauge using doughnut chart
    return new Chart(canvas, {
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

function loadOsData() {
    const loadingEl = document.getElementById('os-loading');
    const errorEl = document.getElementById('os-error');
    const dataEl = document.getElementById('os-data');
    const metricsEl = document.getElementById('os-metrics');
    const dateEl = document.getElementById('os-date');
    const cacheInfoEl = document.getElementById('os-cache-info');
    
    // Show loading, hide error and data
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    dataEl.style.display = 'none';
    
    fetch('/admin/diagnostics/os.php')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }
            
            const data = result.data;
            const age = result.cached ? Math.floor((Date.now() / 1000 - result.timestamp) / 1000) : 0;
            
            // Hide loading, show data
            loadingEl.style.display = 'none';
            dataEl.style.display = 'block';
            
            // Show date and cache info
            dateEl.textContent = data.current_date || '';
            if (result.cached && age > 0) {
                cacheInfoEl.textContent = `(Cached, ${age}s ago)`;
            } else {
                cacheInfoEl.textContent = '(Live data)';
            }
            
            // Render metrics HTML
            let html = '';
            let chartConfigs = []; // Store chart configurations to create after HTML is inserted
            
            // CPU Metrics
            if (data.cpu) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 CPU METRICS
                        </h4>
                `;
                
                if (data.cpu.load_average) {
                    html += `
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                <span style="color: var(--text-secondary);">Load Average:</span>
                                <span style="color: var(--text-primary); font-weight: 600;">${escapeHtml(data.cpu.load_average)}</span>
                            </div>
                        </div>
                    `;
                }
                
                html += `
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">CPU Usage:</div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                                <div class="gauge-container">
                                    <canvas id="cpuUserGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">user</div>
                                </div>
                                <div class="gauge-container">
                                    <canvas id="cpuSystemGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">system</div>
                                </div>
                                <div class="gauge-container">
                                    <canvas id="cpuIowaitGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">iowait</div>
                                </div>
                                <div class="gauge-container">
                                    <canvas id="cpuStealGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">steal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Store chart configs
                chartConfigs.push({id: 'cpuUserGauge', value: data.cpu.user || 0, label: 'user'});
                chartConfigs.push({id: 'cpuSystemGauge', value: data.cpu.system || 0, label: 'system'});
                chartConfigs.push({id: 'cpuIowaitGauge', value: data.cpu.iowait || 0, label: 'iowait'});
                chartConfigs.push({id: 'cpuStealGauge', value: data.cpu.steal || 0, label: 'steal'});
            }
            
            // Memory Metrics
            if (data.memory) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 MEMORY
                        </h4>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">Usage:</div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                                <div class="gauge-container">
                                    <canvas id="ramUsageGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">RAM</div>
                                </div>
                `;
                
                if (data.swap && data.swap.total > 0) {
                    html += `
                                <div class="gauge-container">
                                    <canvas id="swapUsageGauge"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">Swap</div>
                                </div>
                    `;
                    chartConfigs.push({id: 'swapUsageGauge', value: data.swap.usage_percent || 0, label: 'Swap'});
                }
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
                
                chartConfigs.push({id: 'ramUsageGauge', value: data.memory.usage_percent || 0, label: 'RAM'});
            }
            
            // Disk Metrics
            if (data.disk && data.disk.filesystems && data.disk.filesystems.length > 0) {
                html += `
                    <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                            🔶 DISK
                        </h4>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-secondary); margin-bottom: 0.75rem;">Usage:</div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; justify-items: center;">
                `;
                
                data.disk.filesystems.slice(0, 4).forEach((disk, index) => {
                    const diskName = disk.source.split('/').pop() || disk.source;
                    html += `
                                <div class="gauge-container">
                                    <canvas id="diskGauge${index}"></canvas>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.5rem;">${escapeHtml(diskName)}</div>
                                </div>
                    `;
                    chartConfigs.push({id: `diskGauge${index}`, value: disk.usage || 0, label: diskName});
                });
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Insert HTML
            metricsEl.innerHTML = html;
            
            // Create charts after a short delay to ensure canvas elements are rendered
            setTimeout(() => {
                if (typeof Chart !== 'undefined') {
                    chartConfigs.forEach(config => {
                        createGaugeChart(config.id, config.value, config.label);
                    });
                } else {
                    console.warn('Chart.js not available, retrying...');
                    setTimeout(() => {
                        chartConfigs.forEach(config => {
                            createGaugeChart(config.id, config.value, config.label);
                        });
                    }, 500);
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading OS data:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('os-error-message').textContent = error.message || 'Failed to load OS data';
        });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Auto-load when this component is visible (accordion opens)
if (typeof window !== 'undefined') {
    window.loadOsData = loadOsData;
}
</script>
