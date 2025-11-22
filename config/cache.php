<?php
declare(strict_types=1);

/**
 * Simple file-based cache system for diagnostics
 * Cache directory: /var/www/gforms.click/cache/
 */

/**
 * Get cached data if it exists and is not expired
 * 
 * @param string $key Cache key
 * @param int $ttl Time to live in seconds
 * @return array|null Cached data or null if not found/expired
 */
function cache_get(string $key, int $ttl = 30): ?array
{
    $cacheDir = __DIR__ . '/../cache/diagnostics';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . sanitize_cache_key($key) . '.json';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $content = @file_get_contents($cacheFile);
    if ($content === false) {
        return null;
    }
    
    $cacheData = @json_decode($content, true);
    if (!is_array($cacheData) || !isset($cacheData['timestamp']) || !isset($cacheData['data'])) {
        return null;
    }
    
    // Check if cache is expired
    $age = time() - $cacheData['timestamp'];
    if ($age > $ttl) {
        @unlink($cacheFile);
        return null;
    }
    
    return $cacheData['data'];
}

/**
 * Store data in cache
 * 
 * @param string $key Cache key
 * @param array $data Data to cache
 * @param int $ttl Time to live in seconds (for metadata)
 * @return bool Success
 */
function cache_set(string $key, array $data, int $ttl = 30): bool
{
    $cacheDir = __DIR__ . '/../cache/diagnostics';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . sanitize_cache_key($key) . '.json';
    
    $cacheData = [
        'timestamp' => time(),
        'ttl' => $ttl,
        'data' => $data
    ];
    
    $json = json_encode($cacheData, JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    
    return @file_put_contents($cacheFile, $json) !== false;
}

/**
 * Sanitize cache key to prevent directory traversal
 * 
 * @param string $key Cache key
 * @return string Sanitized key
 */
function sanitize_cache_key(string $key): string
{
    // Remove any path separators and dangerous characters
    $key = str_replace(['/', '\\', '..', "\0"], '', $key);
    // Only allow alphanumeric, dash, underscore
    $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    return $key;
}

/**
 * Clear expired cache files
 * 
 * @return int Number of files cleared
 */
function cache_clear_expired(): int
{
    $cacheDir = __DIR__ . '/../cache/diagnostics';
    if (!is_dir($cacheDir)) {
        return 0;
    }
    
    $cleared = 0;
    $files = glob($cacheDir . '/*.json');
    
    foreach ($files as $file) {
        $content = @file_get_contents($file);
        if ($content === false) {
            continue;
        }
        
        $cacheData = @json_decode($content, true);
        if (!is_array($cacheData) || !isset($cacheData['timestamp']) || !isset($cacheData['ttl'])) {
            @unlink($file);
            $cleared++;
            continue;
        }
        
        $age = time() - $cacheData['timestamp'];
        if ($age > $cacheData['ttl']) {
            @unlink($file);
            $cleared++;
        }
    }
    
    return $cleared;
}

