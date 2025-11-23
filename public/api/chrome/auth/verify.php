<?php
declare(strict_types=1);

// Start output buffering to prevent any output before JSON
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Function to send JSON response and exit
function sendJsonResponse(int $code, array $data): void {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Get Authorization header from multiple sources (Apache sometimes doesn't pass it directly)
    $authHeader = null;
    
    // Try HTTP_AUTHORIZATION first (standard)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    // Try REDIRECT_HTTP_AUTHORIZATION (Apache mod_rewrite sometimes adds REDIRECT_ prefix)
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    // Try getallheaders() as fallback
    elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }
    
    if (empty($authHeader)) {
        // Debug: log all headers for troubleshooting
        error_log('Chrome auth: No Authorization header found. Available headers: ' . json_encode(array_keys($_SERVER)));
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'Missing Authorization header'
        ]);
    }

    // Extract Bearer token
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'Invalid Authorization header format'
        ]);
    }

    $idToken = trim($matches[1]);

    if (empty($idToken)) {
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'Missing id_token'
        ]);
    }

    // Load vendor autoload first (using dirname to go up 4 levels: auth -> chrome -> api -> public -> root)
    require dirname(__DIR__, 4) . "/vendor/autoload.php";
    
    // Load bootstrap for database and helpers
    require dirname(__DIR__, 4) . "/config/bootstrap.php";
    
    // Google Client validation
    $client = new Google\Client([
        'client_id' => '837476462692-rqfbcflt7tgm3i60a4vqe18a6sjajgpu.apps.googleusercontent.com'
    ]);

    $payload = $client->verifyIdToken($idToken);

    if (!$payload) {
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'Invalid ID token'
        ]);
    }

    // Validate expected fields
    if (!isset($payload['email']) || !isset($payload['sub'])) {
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'Missing required fields in token payload'
        ]);
    }

    // Look up user in database
    $googleId = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'] ?? null;
    $picture = $payload['picture'] ?? null;

    $pdo = db();
    $userRepo = new \App\Models\UserRepository($pdo);
    $user = $userRepo->findByGoogleId($googleId);

    if (!$user) {
        sendJsonResponse(401, [
            'success' => false,
            'error' => 'User not found. Please log in via the web app first to create your account.'
        ]);
    }

    // Get quota status
    $quotaRepo = new \App\Models\QuotaRepository($pdo);
    $quotaService = new \App\Services\QuotaService($quotaRepo);
    $quotaStatus = $quotaService->getQuotaStatus((int)$user['id'], $user['plan'] ?? 'FREE');

    $appConfig = require dirname(__DIR__, 4) . "/config/config.php";

    // Return success response
    sendJsonResponse(200, [
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'plan' => $user['plan'] ?? 'FREE',
            'avatar_url' => $user['avatar_url'] ?? null,
        ],
        'quota' => [
            'daily_used' => $quotaStatus['daily_used'],
            'daily_limit' => $quotaStatus['daily_limit'],
            'monthly_used' => $quotaStatus['monthly_used'],
            'monthly_limit' => $quotaStatus['monthly_limit'],
            'can_create' => $quotaStatus['can_create_daily'] && $quotaStatus['can_create_monthly'],
        ],
        'base_url' => $appConfig['base_url'],
    ]);

} catch (\Throwable $e) {
    error_log('Chrome auth verify error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    sendJsonResponse(500, [
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
