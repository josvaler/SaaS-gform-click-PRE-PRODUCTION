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
        redirect('/login');
    }
    
    // Check role in database
    try {
        $pdo = db();
        $statement = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $user['id']]);
        $dbUser = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$dbUser || $dbUser['role'] !== 'ADMIN') {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    } catch (\Throwable $e) {
        error_log('Admin check error: ' . $e->getMessage());
        http_response_code(403);
        die('Access denied.');
    }
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

