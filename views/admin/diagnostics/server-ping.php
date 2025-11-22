<?php
declare(strict_types=1);

/**
 * Server Ping Component
 * Displays visual server connectivity status and metrics information
 */

// List of servers to ping
$servers = [
    ['name' => 'Google Forms', 'host' => 'docs.google.com', 'port' => 443],
    ['name' => 'Stripe API', 'host' => 'api.stripe.com', 'port' => 443],
    ['name' => 'Stripe JS', 'host' => 'js.stripe.com', 'port' => 443],
    ['name' => 'Google OAuth', 'host' => 'accounts.google.com', 'port' => 443],
    ['name' => 'Google API', 'host' => 'www.googleapis.com', 'port' => 443],
    ['name' => 'QR Server API', 'host' => 'api.qrserver.com', 'port' => 443],
];

// Function to ping server
function pingServer(string $host, int $port, int $timeout = 5): array
{
    $startTime = microtime(true);
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($connection) {
        fclose($connection);
        return [
            'success' => true,
            'response_time' => $responseTime,
            'error' => null
        ];
    } else {
        return [
            'success' => false,
            'response_time' => null,
            'error' => $errstr ?: t('admin.diagnostics.connection_failed')
        ];
    }
}
?>

<!-- Server Ping Component -->
<div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Title -->
    <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="fas fa-server"></i>
        <span><?= t('admin.diagnostics.server_status') ?></span>
    </h3>
    
    <!-- Server Status List -->
                    <div style="display: grid; gap: 0.75rem; margin-bottom: 2rem;">
                        <?php foreach ($servers as $server): ?>
                            <?php
                            $result = pingServer($server['host'], $server['port']);
                            $emoji = $result['success'] ? '🟢' : '🔴';
                            $statusText = $result['success'] ? t('admin.diagnostics.server_online') : t('admin.diagnostics.server_offline');
                            $statusColor = $result['success'] ? '#10b981' : '#ef4444';
                            ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem 1rem; background: rgba(17, 24, 39, 0.4); border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                                <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                                    <span style="font-size: 1.25rem;"><?= htmlspecialchars($emoji) ?></span>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;"><?= htmlspecialchars($server['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;"><?= htmlspecialchars($server['host']) ?>:<?= $server['port'] ?></div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: <?= $statusColor ?>; margin-bottom: 0.25rem;"><?= htmlspecialchars($statusText) ?></div>
                                    <?php if ($result['success'] && $result['response_time'] !== null): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;"><?= htmlspecialchars((string)$result['response_time']) ?> ms</div>
                                    <?php elseif (!$result['success']): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;"><?= htmlspecialchars($result['error']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

