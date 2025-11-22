<?php
declare(strict_types=1);

/**
 * Database Health Check Component
 * Displays MariaDB/MySQL health metrics
 */

try {
    $pdo = db();
    
    // Helper function to get global status value
    function getGlobalStatus(PDO $pdo, string $variable): ?string
    {
        try {
            // Escape the variable name to prevent SQL injection (though all calls are with hardcoded strings)
            $escapedVariable = str_replace(['\\', "'", '"', ';', '--'], '', $variable);
            $stmt = $pdo->query("SHOW GLOBAL STATUS LIKE " . $pdo->quote($escapedVariable));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['Value'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Helper function to format number
    if (!function_exists('formatNumber')) {
        function formatNumber($value, int $decimals = 0): string
        {
            if ($value === null || $value === '') {
                return 'N/A';
            }
            return number_format((float)$value, $decimals);
        }
    }
    
    // Get current date/time
    $currentDate = date('Y-m-d H:i:s');
    
} catch (Exception $e) {
    echo '<div style="color: #ef4444; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3);">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    return;
}
?>

<div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Header -->
    <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
        <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
            <?= t('admin.diagnostics.db_health_check') ?>
        </h3>
        <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
            <?= htmlspecialchars($currentDate) ?>
        </p>
    </div>

    <div style="display: grid; gap: 1.5rem;">
        <!-- Connections Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_connections') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $threadsConnected = getGlobalStatus($pdo, 'Threads_connected');
                $threadsRunning = getGlobalStatus($pdo, 'Threads_running');
                $maxUsedConnections = getGlobalStatus($pdo, 'Max_used_connections');
                $abortedConnects = getGlobalStatus($pdo, 'Aborted_connects');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Threads_connected:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($threadsConnected) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Threads_running:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($threadsRunning) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Max_used_connections:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($maxUsedConnections) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Aborted_connects:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($abortedConnects) ?></span>
                </div>
            </div>
        </div>

        <!-- QPS Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_qps') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $uptime = getGlobalStatus($pdo, 'Uptime');
                $queries = getGlobalStatus($pdo, 'Queries');
                $qps = null;
                if ($uptime && $queries && (float)$uptime > 0) {
                    $qps = (float)$queries / (float)$uptime;
                }
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">QPS:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= $qps !== null ? formatNumber($qps, 2) : 'N/A' ?></span>
                </div>
            </div>
        </div>

        <!-- Slow Queries Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_slow_queries') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $slowQueries = getGlobalStatus($pdo, 'Slow_queries');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Slow_queries:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($slowQueries) ?></span>
                </div>
            </div>
        </div>

        <!-- Handler Latencies Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_handler_latencies') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $handlerReadFirst = getGlobalStatus($pdo, 'Handler_read_first');
                $handlerReadKey = getGlobalStatus($pdo, 'Handler_read_key');
                $handlerReadNext = getGlobalStatus($pdo, 'Handler_read_next');
                $handlerReadPrev = getGlobalStatus($pdo, 'Handler_read_prev');
                $handlerReadRnd = getGlobalStatus($pdo, 'Handler_read_rnd');
                $handlerReadRndNext = getGlobalStatus($pdo, 'Handler_read_rnd_next');
                $handlerWrite = getGlobalStatus($pdo, 'Handler_write');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_first:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadFirst) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_key:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadKey) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_next:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadNext) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_prev:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadPrev) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_rnd:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadRnd) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_read_rnd_next:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerReadRndNext) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Handler_write:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($handlerWrite) ?></span>
                </div>
            </div>
        </div>

        <!-- InnoDB Buffer Pool Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_buffer_pool') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $bufferPoolReads = getGlobalStatus($pdo, 'Innodb_buffer_pool_reads');
                $bufferPoolReadRequests = getGlobalStatus($pdo, 'Innodb_buffer_pool_read_requests');
                $hitRate = null;
                if ($bufferPoolReads !== null && $bufferPoolReadRequests !== null && (float)$bufferPoolReadRequests > 0) {
                    $hitRate = (1 - (float)$bufferPoolReads / (float)$bufferPoolReadRequests) * 100;
                }
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_buffer_pool_reads:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($bufferPoolReads) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_buffer_pool_read_requests:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($bufferPoolReadRequests) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);"><?= t('admin.diagnostics.db_hit_rate') ?>:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= $hitRate !== null ? formatNumber($hitRate, 2) : 'N/A' ?></span>
                </div>
            </div>
        </div>

        <!-- IOPS Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_iops') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $innodbDataReads = getGlobalStatus($pdo, 'Innodb_data_reads');
                $innodbDataWrites = getGlobalStatus($pdo, 'Innodb_data_writes');
                $innodbOsLogFsyncs = getGlobalStatus($pdo, 'Innodb_os_log_fsyncs');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_data_reads:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbDataReads) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_data_writes:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbDataWrites) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_os_log_fsyncs:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbOsLogFsyncs) ?></span>
                </div>
            </div>
        </div>

        <!-- Redo Log Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_redo_log') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $innodbLogWaits = getGlobalStatus($pdo, 'Innodb_log_waits');
                $innodbLogWritten = getGlobalStatus($pdo, 'Innodb_log_written');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_log_waits:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbLogWaits) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_log_written:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbLogWritten) ?></span>
                </div>
            </div>
        </div>

        <!-- Locks Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_locks') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                $innodbRowLockTime = getGlobalStatus($pdo, 'Innodb_row_lock_time');
                $innodbRowLockWaits = getGlobalStatus($pdo, 'Innodb_row_lock_waits');
                $innodbDeadlocks = getGlobalStatus($pdo, 'Innodb_deadlocks');
                ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_row_lock_time:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbRowLockTime) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_row_lock_waits:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbRowLockWaits) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="color: var(--text-secondary);">Innodb_deadlocks:</span>
                    <span style="color: var(--text-primary); font-weight: 600;"><?= formatNumber($innodbDeadlocks) ?></span>
                </div>
            </div>
        </div>

        <!-- Replication Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_replication') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                try {
                    $replicationStmt = $pdo->query("SHOW SLAVE STATUS");
                    $replication = $replicationStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($replication) {
                        $secondsBehindMaster = $replication['Seconds_Behind_Master'] ?? 'N/A';
                        $slaveIoRunning = $replication['Slave_IO_Running'] ?? 'N/A';
                        $slaveSqlRunning = $replication['Slave_SQL_Running'] ?? 'N/A';
                        $relayLogSpace = $replication['Relay_Log_Space'] ?? 'N/A';
                        ?>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                            <span style="color: var(--text-secondary);">Seconds_Behind_Master:</span>
                            <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars((string)$secondsBehindMaster) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                            <span style="color: var(--text-secondary);">Slave_IO_Running:</span>
                            <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars((string)$slaveIoRunning) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                            <span style="color: var(--text-secondary);">Slave_SQL_Running:</span>
                            <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars((string)$slaveSqlRunning) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                            <span style="color: var(--text-secondary);">Relay_Log_Space:</span>
                            <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars((string)$relayLogSpace) ?></span>
                        </div>
                        <?php
                    } else {
                        echo '<div style="color: var(--text-secondary); opacity: 0.8; padding: 0.5rem 0;">' . t('admin.diagnostics.db_replication_not_configured') . '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div style="color: var(--text-secondary); opacity: 0.8; padding: 0.5rem 0;">' . t('admin.diagnostics.db_replication_not_configured') . '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Table Sizes Section -->
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                🔶 <?= t('admin.diagnostics.db_table_sizes') ?>
            </h4>
            <div style="display: grid; gap: 0.5rem;">
                <?php
                try {
                    $tableSizeStmt = $pdo->query("
                        SELECT table_schema, table_name,
                               ROUND((data_length + index_length) / 1024 / 1024, 2) AS MB
                        FROM information_schema.tables
                        ORDER BY MB DESC
                        LIMIT 10
                    ");
                    $tableSizes = $tableSizeStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($tableSizes)) {
                        echo '<div style="color: var(--text-secondary); opacity: 0.8; padding: 0.5rem 0;">' . t('admin.diagnostics.db_no_tables') . '</div>';
                    } else {
                        foreach ($tableSizes as $table) {
                            $schema = htmlspecialchars($table['table_schema']);
                            $name = htmlspecialchars($table['table_name']);
                            $mb = formatNumber($table['MB'], 2);
                            ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                                <span style="color: var(--text-secondary);">
                                    <?= htmlspecialchars($schema) ?>.<?= htmlspecialchars($name) ?>
                                </span>
                                <span style="color: var(--text-primary); font-weight: 600;"><?= $mb ?> MB</span>
                            </div>
                            <?php
                        }
                    }
                } catch (Exception $e) {
                    echo '<div style="color: #ef4444; padding: 0.5rem 0;">' . t('admin.diagnostics.db_table_size_error') . ' ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

