<?php
declare(strict_types=1);

use App\Services\RedirectService;
use App\Models\ShortLinkRepository;
use App\Models\ClickRepository;

require __DIR__ . '/../config/bootstrap.php';

// Get short code from URL path
// The .htaccess rewrite rule passes the short code as the matched pattern
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);
$shortCode = ltrim($path, '/');

// Remove query string if present
if (($pos = strpos($shortCode, '?')) !== false) {
    $shortCode = substr($shortCode, 0, $pos);
}

// Skip if it's a known route or file
$knownRoutes = ['login', 'dashboard', 'profile', 'create-link', 'links', 'link', 'pricing', 'billing', 'logout', 'admin', 'stripe', 'qr', 'assets', 'redirect'];
if (in_array($shortCode, $knownRoutes) || strpos($shortCode, '.') !== false || empty($shortCode)) {
    http_response_code(404);
    die('Not found');
}

$pdo = db();
$shortLinkRepo = new ShortLinkRepository($pdo);
$clickRepo = new ClickRepository($pdo);
$redirectService = new RedirectService($shortLinkRepo, $clickRepo);

$result = $redirectService->handleRedirect($shortCode);

if (!$result['success']) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Enlace no encontrado</title>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                background: #0f172a;
                color: #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }
            .container {
                text-align: center;
                padding: 2rem;
            }
            h1 { font-size: 2rem; margin-bottom: 1rem; }
            p { color: #94a3b8; margin-bottom: 2rem; }
            a { color: #60a5fa; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Enlace no encontrado</h1>
            <p><?= html($result['error'] === 'Link not found' ? 'Este enlace no existe.' : ($result['error'] === 'Link is deactivated' ? 'Este enlace ha sido desactivado.' : 'Este enlace ha expirado.')) ?></p>
            <a href="/">Volver al inicio</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Redirect to original URL
header('Location: ' . $result['url'], true, 302);
exit;

