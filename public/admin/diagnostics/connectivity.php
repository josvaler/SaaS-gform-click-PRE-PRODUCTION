<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

require_once __DIR__ . '/../../../config/cache.php';

header('Content-Type: application/json; charset=utf-8');

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
            'error' => $errstr ?: 'Connection failed'
        ];
    }
}

try {
    $cacheKey = 'diagnostics_connectivity';
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
    
    // Generate fresh data
    $results = [];
    foreach ($servers as $server) {
        $result = pingServer($server['host'], $server['port']);
        $results[] = [
            'name' => $server['name'],
            'host' => $server['host'],
            'port' => $server['port'],
            'success' => $result['success'],
            'response_time' => $result['response_time'],
            'error' => $result['error']
        ];
    }
    
    $data = [
        'servers' => $results
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

