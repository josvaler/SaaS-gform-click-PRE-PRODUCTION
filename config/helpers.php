<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false ? $default : $value;
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function render(string $view, array $data = []): void
{
    extract($data);
    $viewPath = __DIR__ . '/../views/' . ltrim($view, '/');
    if (!str_ends_with($viewPath, '.php')) {
        $viewPath .= '.php';
    }

    if (!file_exists($viewPath)) {
        throw new RuntimeException('View not found: ' . $viewPath);
    }

    require $viewPath;
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function session_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): void
{
    if (!session_user()) {
        redirect('/login');
    }
}

function require_admin(): void
{
    require_auth();
    
    $user = session_user();
    if (empty($user['id'])) {
        error_log('Admin check failed: User ID not in session. Session data: ' . json_encode($user));
        redirect('/login');
    }
    
    // First check session role (faster, but verify with database)
    $sessionRole = $user['role'] ?? null;
    if ($sessionRole === 'ADMIN') {
        // Double-check with database for security
        try {
            $pdo = db();
            $statement = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $user['id']]);
            $dbUser = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($dbUser && $dbUser['role'] === 'ADMIN') {
                return; // Access granted
            } else {
                error_log('Admin check failed: Session says ADMIN but database says: ' . ($dbUser['role'] ?? 'NULL') . ' for user ID: ' . $user['id']);
            }
        } catch (\Throwable $e) {
            error_log('Admin check database error: ' . $e->getMessage() . ' for user ID: ' . $user['id']);
            // If database check fails but session says ADMIN, allow access (session was set from DB during login)
            if ($sessionRole === 'ADMIN') {
                return;
            }
        }
    }
    
    // If we get here, user is not admin
    error_log('Admin check failed: User ID ' . $user['id'] . ' - Session role: ' . ($sessionRole ?? 'NULL'));
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    die('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access Denied</title></head><body style="font-family: Arial, sans-serif; text-align: center; padding: 50px;"><h1>403 Forbidden</h1><p>Access denied. Admin privileges required.</p><p>User ID: ' . htmlspecialchars((string)($user['id'] ?? 'N/A')) . '</p><p>Session Role: ' . htmlspecialchars($sessionRole ?? 'N/A') . '</p><p><a href="/login">Login</a> | <a href="/dashboard">Dashboard</a></p></body></html>');
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function formatNumber($value, int $decimals = 0): string
{
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return number_format((float)$value, $decimals);
}

function html(?string $text): string
{
    if ($text === null) {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Translation function - returns translated string based on current language
 * @param string $key Translation key (e.g., 'dashboard.title')
 * @param array $params Parameters to replace in string (e.g., ['name' => 'John'])
 * @return string Translated string
 */
function t(string $key, array $params = []): string
{
    static $translations = null;
    static $currentLang = null;
    
    // Get current language from session or user preference
    if ($currentLang === null) {
        $user = session_user();
        $currentLang = $_SESSION['lang'] ?? null;
        
        // If not in session, try user's locale from database
        if ($currentLang === null && $user) {
            $currentLang = $user['locale'] ?? null;
        }
        
        // Normalize locale: 'es_ES' -> 'es', 'en_US' -> 'en'
        if ($currentLang) {
            $currentLang = strtolower(substr($currentLang, 0, 2));
        }
        
        // Default to Spanish if not set or invalid
        if (!in_array($currentLang, ['en', 'es'])) {
            $currentLang = 'es'; // Spanish is default
        }
        
        // Store in session for performance
        $_SESSION['lang'] = $currentLang;
    }
    
    // Load translations if not loaded
    if ($translations === null) {
        $transFile = __DIR__ . "/translations/{$currentLang}.php";
        if (file_exists($transFile)) {
            $translations = require $transFile;
        } else {
            // Fallback to Spanish if translation file doesn't exist
            $transFile = __DIR__ . "/translations/es.php";
            $translations = file_exists($transFile) ? require $transFile : [];
        }
    }
    
    // Get translation or return key if not found
    $text = $translations[$key] ?? $key;
    
    // Replace parameters: t('welcome', ['name' => 'John']) -> "Welcome, John!"
    foreach ($params as $param => $value) {
        $text = str_replace(":{$param}", html($value), $text);
    }
    
    return $text;
}

/**
 * Get current language code
 * @return string Current language ('es' or 'en')
 */
function current_lang(): string
{
    $user = session_user();
    $lang = $_SESSION['lang'] ?? $user['locale'] ?? 'es';
    $lang = strtolower(substr($lang, 0, 2));
    return in_array($lang, ['en', 'es']) ? $lang : 'es';
}

/**
 * Debug logging function - only logs if SYSTEM_CODE_DEBUG is enabled
 * Use this for debug/informational logs that should be conditionally enabled
 * Critical errors should still use error_log() directly
 * 
 * @param string $message The debug message to log
 * @return void
 */
function debug_log(string $message): void
{
    $debugEnabled = env('SYSTEM_CODE_DEBUG', 'false');
    if ($debugEnabled === 'true' || $debugEnabled === '1') {
        error_log('[DEBUG] ' . $message);
    }
}

/**
 * Get Early Bird promotion count and availability
 * Returns count of PREMIUM users and remaining slots (max 1000)
 * 
 * @return array{count: int, remaining: int, is_available: bool}
 */
function get_early_bird_count(): array
{
    // Check if promotions are enabled
    $promotionsEnabled = env('GFORMS_PROMOTIONS', 'false') === 'true';
    if (!$promotionsEnabled) {
        return ['count' => 0, 'remaining' => 0, 'is_available' => false];
    }
    
    try {
        $pdo = db();
        // Count PREMIUM users (first 1000 by subscription date)
        // Use updated_at when plan changed to PREMIUM, or created_at if already PREMIUM
        $statement = $pdo->query(
            "SELECT COUNT(*) as count 
             FROM (
                 SELECT id 
                 FROM users 
                 WHERE plan = 'PREMIUM' 
                 ORDER BY 
                     CASE 
                         WHEN updated_at > created_at THEN updated_at 
                         ELSE created_at 
                     END ASC
                 LIMIT 1000
             ) as premium_users"
        );
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $count = (int)($result['count'] ?? 0);
        $remaining = max(0, 1000 - $count);
        $isAvailable = $remaining > 0;
        
        return [
            'count' => $count,
            'remaining' => $remaining,
            'is_available' => $isAvailable
        ];
    } catch (Throwable $e) {
        error_log('Error getting early bird count: ' . $e->getMessage());
        return ['count' => 0, 'remaining' => 0, 'is_available' => false];
    }
}

