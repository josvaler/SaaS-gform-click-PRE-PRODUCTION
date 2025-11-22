<?php
declare(strict_types=1);

/**
 * Server Ping Component (Lazy-Loaded)
 * Displays visual server connectivity status and metrics information
 * Data is loaded via AJAX when accordion opens
 */
?>

<!-- Server Ping Component -->
<div id="connectivity-content" style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Loading State -->
    <div id="connectivity-loading" style="text-align: center; padding: 3rem 1rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
        <p style="color: var(--text-secondary); opacity: 0.8;"><?= t('admin.diagnostics.loading') ?></p>
    </div>
    
    <!-- Error State -->
    <div id="connectivity-error" style="display: none; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 1rem;">
        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
            <?= t('admin.diagnostics.error_loading') ?>
        </div>
        <div id="connectivity-error-message" style="color: var(--text-secondary); font-size: 0.875rem;"></div>
        <button onclick="loadConnectivityData()" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
            <i class="fas fa-redo" style="margin-right: 0.5rem;"></i><?= t('admin.diagnostics.retry') ?>
        </button>
    </div>
    
    <!-- Content (hidden until loaded) -->
    <div id="connectivity-data" style="display: none;">
        <!-- Title -->
        <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-server"></i>
            <span><?= t('admin.diagnostics.server_status') ?></span>
            <span id="connectivity-cache-info" style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.7; margin-left: auto;"></span>
        </h3>
        
        <!-- Server Status List -->
        <div id="connectivity-servers" style="display: grid; gap: 0.75rem; margin-bottom: 2rem;">
            <!-- Servers will be inserted here -->
        </div>

        <!-- Metrics Information Table -->
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(148, 163, 184, 0.2);">
            <h3 style="color: var(--text-primary); font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem;">
                <?= t('admin.diagnostics.metrics_title') ?>
            </h3>

            <div style="display: grid; gap: 1.5rem;">
                <!-- Latency Section -->
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                        <?= t('admin.diagnostics.latency_title') ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">
                        <strong><?= t('admin.diagnostics.latency_measures') ?></strong>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem; opacity: 0.8;">
                        <?= t('admin.diagnostics.latency_importance') ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟢</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.latency_excellent') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟡</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.latency_normal') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟡</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.latency_problem') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🔴</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.latency_bad') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Packet Loss Section -->
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                        <?= t('admin.diagnostics.packet_loss_title') ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">
                        <strong><?= t('admin.diagnostics.packet_loss_measures') ?></strong>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem; opacity: 0.8;">
                        <?= t('admin.diagnostics.packet_loss_importance') ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟢</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">0%: siempre debe ser la meta</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟡</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">1–5%: ya afectan apps web</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🔴</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">10%: emergencia</span>
                        </div>
                    </div>
                </div>

                <!-- Jitter Section -->
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                        <?= t('admin.diagnostics.jitter_title') ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">
                        <strong><?= t('admin.diagnostics.jitter_measures') ?></strong>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem; opacity: 0.8;">
                        <?= t('admin.diagnostics.jitter_importance') ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟢</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.jitter_perfect') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🟡</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.jitter_unstable') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1rem;">🔴</span>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;"><?= t('admin.diagnostics.jitter_bad') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Duplication Section -->
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                        <?= t('admin.diagnostics.duplication_title') ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; opacity: 0.8;">
                        <?= t('admin.diagnostics.duplication_info') ?>
                    </p>
                </div>

                <!-- ICMP Section -->
                <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                        <?= t('admin.diagnostics.icmp_title') ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; opacity: 0.8;">
                        <?= t('admin.diagnostics.icmp_info') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadConnectivityData() {
    const loadingEl = document.getElementById('connectivity-loading');
    const errorEl = document.getElementById('connectivity-error');
    const dataEl = document.getElementById('connectivity-data');
    const serversEl = document.getElementById('connectivity-servers');
    const cacheInfoEl = document.getElementById('connectivity-cache-info');
    
    // Show loading, hide error and data
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    dataEl.style.display = 'none';
    
    fetch('/admin/diagnostics/connectivity.php')
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
            
            // Show cache info
            if (result.cached && age > 0) {
                cacheInfoEl.textContent = `(Cached, ${age}s ago)`;
            } else {
                cacheInfoEl.textContent = '(Live data)';
            }
            
            // Render servers
            serversEl.innerHTML = '';
            if (data.servers && Array.isArray(data.servers)) {
                data.servers.forEach(server => {
                    const emoji = server.success ? '🟢' : '🔴';
                    const statusText = server.success ? '<?= t('admin.diagnostics.server_online') ?>' : '<?= t('admin.diagnostics.server_offline') ?>';
                    const statusColor = server.success ? '#10b981' : '#ef4444';
                    
                    const serverDiv = document.createElement('div');
                    serverDiv.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.875rem 1rem; background: rgba(17, 24, 39, 0.4); border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);';
                    serverDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                            <span style="font-size: 1.25rem;">${emoji}</span>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${escapeHtml(server.name)}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">${escapeHtml(server.host)}:${server.port}</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 600; color: ${statusColor}; margin-bottom: 0.25rem;">${statusText}</div>
                            ${server.success && server.response_time !== null ? 
                                `<div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">${server.response_time} ms</div>` :
                                `<div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">${escapeHtml(server.error || 'Connection failed')}</div>`
                            }
                        </div>
                    `;
                    serversEl.appendChild(serverDiv);
                });
            }
        })
        .catch(error => {
            console.error('Error loading connectivity data:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('connectivity-error-message').textContent = error.message || 'Failed to load connectivity data';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-load when this component is visible (accordion opens)
if (typeof window !== 'undefined') {
    window.loadConnectivityData = loadConnectivityData;
}
</script>
