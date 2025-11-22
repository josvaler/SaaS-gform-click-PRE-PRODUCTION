<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

require_once __DIR__ . '/../../../config/cache.php';

header('Content-Type: application/json; charset=utf-8');

// Helper function to get global status value
function getGlobalStatus(PDO $pdo, string $variable): ?string
{
    try {
        $escapedVariable = str_replace(['\\', "'", '"', ';', '--'], '', $variable);
        $stmt = $pdo->query("SHOW GLOBAL STATUS LIKE " . $pdo->quote($escapedVariable));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Value'] ?? null;
    } catch (Exception $e) {
        return null;
    }
}

try {
    $cacheKey = 'diagnostics_database';
    $ttl = 30; // 30 seconds cache
    
    // Try to get from cache
    $cached = cache_get($cacheKey, $ttl);
    $isCached = $cached !== null;
    
    if ($isCached) {
        echo json_encode([
            'success' => true,
            'cached' => true,
            'timestamp' => time(),
            'data' => $cached
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $pdo = db();
    $currentDate = date('Y-m-d H:i:s');
    
    // Connections
    $threadsConnected = getGlobalStatus($pdo, 'Threads_connected');
    $threadsRunning = getGlobalStatus($pdo, 'Threads_running');
    $maxUsedConnections = getGlobalStatus($pdo, 'Max_used_connections');
    $abortedConnects = getGlobalStatus($pdo, 'Aborted_connects');
    
    // QPS
    $uptime = getGlobalStatus($pdo, 'Uptime');
    $queries = getGlobalStatus($pdo, 'Queries');
    $qps = null;
    if ($uptime && $queries && (float)$uptime > 0) {
        $qps = (float)$queries / (float)$uptime;
    }
    
    // Slow Queries
    $slowQueries = getGlobalStatus($pdo, 'Slow_queries');
    
    // Handler Latencies
    $handlerReadFirst = getGlobalStatus($pdo, 'Handler_read_first');
    $handlerReadKey = getGlobalStatus($pdo, 'Handler_read_key');
    $handlerReadNext = getGlobalStatus($pdo, 'Handler_read_next');
    $handlerReadPrev = getGlobalStatus($pdo, 'Handler_read_prev');
    $handlerReadRnd = getGlobalStatus($pdo, 'Handler_read_rnd');
    $handlerReadRndNext = getGlobalStatus($pdo, 'Handler_read_rnd_next');
    $handlerWrite = getGlobalStatus($pdo, 'Handler_write');
    
    // InnoDB Buffer Pool
    $bufferPoolReads = getGlobalStatus($pdo, 'Innodb_buffer_pool_reads');
    $bufferPoolReadRequests = getGlobalStatus($pdo, 'Innodb_buffer_pool_read_requests');
    $hitRate = null;
    if ($bufferPoolReads !== null && $bufferPoolReadRequests !== null && (float)$bufferPoolReadRequests > 0) {
        $hitRate = (1 - (float)$bufferPoolReads / (float)$bufferPoolReadRequests) * 100;
    }
    
    // IOPS
    $innodbDataReads = getGlobalStatus($pdo, 'Innodb_data_reads');
    $innodbDataWrites = getGlobalStatus($pdo, 'Innodb_data_writes');
    $innodbOsLogFsyncs = getGlobalStatus($pdo, 'Innodb_os_log_fsyncs');
    
    // Redo Log
    $innodbLogWaits = getGlobalStatus($pdo, 'Innodb_log_waits');
    $innodbLogWritten = getGlobalStatus($pdo, 'Innodb_log_written');
    
    // Locks
    $innodbRowLockTime = getGlobalStatus($pdo, 'Innodb_row_lock_time');
    $innodbRowLockWaits = getGlobalStatus($pdo, 'Innodb_row_lock_waits');
    $innodbDeadlocks = getGlobalStatus($pdo, 'Innodb_deadlocks');
    
    // Replication
    $replication = null;
    try {
        $replicationStmt = $pdo->query("SHOW SLAVE STATUS");
        $replication = $replicationStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Not a slave or replication not configured
    }
    
    $data = [
        'current_date' => $currentDate,
        'connections' => [
            'threads_connected' => $threadsConnected,
            'threads_running' => $threadsRunning,
            'max_used_connections' => $maxUsedConnections,
            'aborted_connects' => $abortedConnects
        ],
        'qps' => [
            'qps' => $qps,
            'uptime' => $uptime,
            'queries' => $queries
        ],
        'slow_queries' => [
            'slow_queries' => $slowQueries
        ],
        'handler_latencies' => [
            'handler_read_first' => $handlerReadFirst,
            'handler_read_key' => $handlerReadKey,
            'handler_read_next' => $handlerReadNext,
            'handler_read_prev' => $handlerReadPrev,
            'handler_read_rnd' => $handlerReadRnd,
            'handler_read_rnd_next' => $handlerReadRndNext,
            'handler_write' => $handlerWrite
        ],
        'buffer_pool' => [
            'innodb_buffer_pool_reads' => $bufferPoolReads,
            'innodb_buffer_pool_read_requests' => $bufferPoolReadRequests,
            'hit_rate' => $hitRate
        ],
        'iops' => [
            'innodb_data_reads' => $innodbDataReads,
            'innodb_data_writes' => $innodbDataWrites,
            'innodb_os_log_fsyncs' => $innodbOsLogFsyncs
        ],
        'redo_log' => [
            'innodb_log_waits' => $innodbLogWaits,
            'innodb_log_written' => $innodbLogWritten
        ],
        'locks' => [
            'innodb_row_lock_time' => $innodbRowLockTime,
            'innodb_row_lock_waits' => $innodbRowLockWaits,
            'innodb_deadlocks' => $innodbDeadlocks
        ],
        'replication' => $replication
    ];
    
    // Cache the results
    cache_set($cacheKey, $data, $ttl);
    
    echo json_encode([
        'success' => true,
        'cached' => false,
        'timestamp' => time(),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

