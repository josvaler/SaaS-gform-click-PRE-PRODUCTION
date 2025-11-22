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

/**
 * Generate email template for link creation confirmation
 * 
 * @param array $link Link data from database
 * @param string $baseUrl Base URL of the application
 * @param string $userName User's name or email
 * @return string HTML email template
 */
function generate_link_creation_email_template(array $link, string $baseUrl, string $userName = ''): string
{
    $shortUrl = rtrim($baseUrl, '/') . '/' . html($link['short_code'] ?? '');
    $originalUrl = html($link['original_url'] ?? '');
    $label = html($link['label'] ?? '');
    $shortCode = html($link['short_code'] ?? '');
    $createdAt = !empty($link['created_at']) ? date('F d, Y \a\t H:i', strtotime($link['created_at'])) : 'N/A';
    $expiresAt = !empty($link['expires_at']) ? date('F d, Y \a\t H:i', strtotime($link['expires_at'])) : null;
    $linkDetailsUrl = rtrim($baseUrl, '/') . '/link/' . html($link['short_code'] ?? '');
    $qrCodePath = $link['qr_code_path'] ?? null;
    $qrCodeUrl = null;
    
    // Generate QR code URL if path exists
    if ($qrCodePath) {
        // QR code path is stored as /qr/filename.png, so we need to check in public/qr/
        $qrFilePath = __DIR__ . '/../public' . $qrCodePath;
        if (file_exists($qrFilePath)) {
            $qrCodeUrl = rtrim($baseUrl, '/') . $qrCodePath;
        }
    }
    
    $greeting = !empty($userName) ? html($userName) : 'Hello';
    
    // Build label row if label exists
    $labelRow = '';
    if (!empty($label)) {
        $labelRow = '<tr>
            <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600;">Label:</td>
            <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">' . $label . '</td>
        </tr>';
    }
    
    // Build expiration row if expiration exists
    $expirationRow = '';
    if ($expiresAt) {
        $expirationRow = '<tr>
            <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600;">Expires:</td>
            <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">' . $expiresAt . '</td>
        </tr>';
    }
    
    // Build QR code section if QR code exists
    $qrCodeSection = '';
    if ($qrCodeUrl) {
        $qrCodeSection = '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; margin-bottom: 30px; text-align: center;">
            <h2 style="margin: 0 0 16px; color: #1e293b; font-size: 18px; font-weight: 600;">QR Code</h2>
            <img src="' . $qrCodeUrl . '" alt="QR Code" style="max-width: 200px; height: auto; border: 1px solid #e2e8f0; border-radius: 4px; background-color: #ffffff; padding: 8px;">
            <p style="margin: 12px 0 0; color: #64748b; font-size: 12px;">Scan this QR code to access your link</p>
        </div>';
    }
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Short Link Has Been Created</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">Link Created Successfully</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #334155; font-size: 16px; line-height: 1.6;">
                                {$greeting},
                            </p>
                            <p style="margin: 0 0 30px; color: #334155; font-size: 16px; line-height: 1.6;">
                                Your short link has been created successfully! Here are all the details:
                            </p>
                            
                            <!-- Link Details Box -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; margin-bottom: 30px;">
                                <h2 style="margin: 0 0 20px; color: #1e293b; font-size: 18px; font-weight: 600;">Link Information</h2>
                                
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600; width: 140px;">Short URL:</td>
                                        <td style="padding: 8px 0;">
                                            <a href="{$shortUrl}" style="color: #10b981; text-decoration: none; font-size: 14px; word-break: break-all; font-weight: 500;">{$shortUrl}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600;">Original URL:</td>
                                        <td style="padding: 8px 0;">
                                            <a href="{$originalUrl}" style="color: #3b82f6; text-decoration: none; font-size: 14px; word-break: break-all;">{$originalUrl}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600;">Short Code:</td>
                                        <td style="padding: 8px 0; color: #1e293b; font-size: 14px; font-family: monospace; background-color: #ffffff; padding: 4px 8px; border-radius: 4px; display: inline-block;">{$shortCode}</td>
                                    </tr>
                                    {$labelRow}
                                    <tr>
                                        <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 600;">Created:</td>
                                        <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">{$createdAt}</td>
                                    </tr>
                                    {$expirationRow}
                                </table>
                            </div>
                            
                            {$qrCodeSection}
                            
                            <!-- Action Button -->
                            <table role="presentation" style="width: 100%; margin: 30px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="{$linkDetailsUrl}" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">View Link Details</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                You can manage this link and view analytics from your dashboard.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 14px;">
                                Thank you for using GForms!
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                If you have any questions, please contact us at <a href="mailto:support@gforms.click" style="color: #10b981; text-decoration: none;">support@gforms.click</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

