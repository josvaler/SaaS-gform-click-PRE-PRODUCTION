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

function html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

