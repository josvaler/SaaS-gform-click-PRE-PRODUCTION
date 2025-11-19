<?php
declare(strict_types=1);

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false && is_array($lines)) {
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove quotes if present (both single and double)
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                $value = trim($value);
                // Set from .env file (system env vars take precedence if already set)
                // But if empty, load from .env
                if (!empty($key)) {
                    // Only set if not already set OR if current value is empty
                    if (!isset($_ENV[$key]) || empty($_ENV[$key])) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    } else {
        error_log("Warning: Could not read .env file at $envFile");
    }
} elseif (file_exists($envFile) && !is_readable($envFile)) {
    // Log warning if file exists but isn't readable
    error_log("Warning: .env file exists at $envFile but is not readable. Check file permissions.");
}

// Prevent browser caching for dynamic PHP pages
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie settings for better security and cross-domain compatibility
    $cookieParams = session_get_cookie_params();
    
    // Determine if we're using HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
                || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    // Normalize domain - remove www to avoid cookie mismatch
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $domain = preg_replace('/^www\./', '', $host);
    // For localhost, use empty domain
    if ($domain === 'localhost' || strpos($domain, '127.0.0.1') !== false) {
        $domain = '';
    } else {
        // For production domains, use the domain without www
        $domain = '.' . $domain; // Leading dot allows subdomains
    }
    
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'] ?: 0, // 0 = until browser closes
        'path' => '/',
        'domain' => $domain, // Normalized domain (without www)
        'secure' => $isSecure, // Set to true for HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Allows OAuth redirects (cross-site GET requests)
    ]);
    
    session_start();
    
    // Regenerate session ID periodically for security (but not on every request to preserve state)
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Prevent browser caching for dynamic PHP pages (after session start to avoid issues)
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass);
    
    // Try exact path first
    $file = $baseDir . $relativePath . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // If not found, try with lowercase directory names (for case-sensitive filesystems)
    $parts = explode('/', $relativePath);
    if (count($parts) > 0) {
        // Convert first directory to lowercase (e.g., Models -> models)
        $parts[0] = strtolower($parts[0]);
        $file = $baseDir . implode('/', $parts) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$googleConfig = require __DIR__ . '/google.php';
$appConfig = require __DIR__ . '/config.php';
$stripeConfig = require __DIR__ . '/stripe.php';

