<?php
declare(strict_types=1);

require __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

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
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing Authorization header']);
    exit;
}

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid Authorization header format']);
    exit;
}

    $idToken = trim($matches[1]);

    if (empty($idToken)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Missing id_token']);
        exit;
    }

// Verify id_token directly with Google Client (bootstrap already loaded at top)
try {
    $client = new Google\Client([
        'client_id' => '837476462692-rqfbcflt7tgm3i60a4vqe18a6sjajgpu.apps.googleusercontent.com'
    ]);
    
    $payload = $client->verifyIdToken($idToken);
    
    if (!$payload || !isset($payload['sub'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        exit;
    }
    
    // Get user from database
    $googleId = $payload['sub'];
    $pdo = db();
    $userRepo = new \App\Models\UserRepository($pdo);
    $user = $userRepo->findByGoogleId($googleId);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not found. Please log in via the web app first.']);
        exit;
    }
    
    // Format user data
    $user = [
        'id' => (int)$user['id'],
        'google_id' => trim((string)$user['google_id']),
        'email' => strtolower(trim((string)$user['email'])),
        'name' => $user['name'],
        'plan' => $user['plan'] ?? 'FREE',
        'role' => $user['role'] ?? 'USER',
        'avatar_url' => $user['avatar_url'] ?? null,
    ];
    
} catch (\Throwable $e) {
    error_log('Chrome create auth error: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token verification failed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$originalUrl = trim($input['original_url'] ?? '');
$label = trim($input['label'] ?? '');
$customCode = trim($input['custom_code'] ?? '');
$expirationDate = trim($input['expiration_date'] ?? '');

// Validate required fields
if (empty($originalUrl)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'original_url is required']);
    exit;
}

// Initialize services
$pdo = db();
$shortLinkRepo = new \App\Models\ShortLinkRepository($pdo);
$quotaRepo = new \App\Models\QuotaRepository($pdo);
$quotaService = new \App\Services\QuotaService($quotaRepo);
$urlValidator = new \App\Services\UrlValidationService();
$shortCodeService = new \App\Services\ShortCodeService($shortLinkRepo);

$appConfig = require __DIR__ . '/../../config/config.php';
$qrService = new \App\Services\QrCodeService($appConfig['qr_dir'], $appConfig['base_url']);

$currentPlan = $user['plan'] ?? 'FREE';
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

// Validate Google Forms URL
$urlValidation = $urlValidator->validateGoogleFormsUrl($originalUrl);
if (!$urlValidation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $urlValidation['error']]);
    exit;
}

// Check quota
$quotaCheck = $quotaService->canCreateLink((int)$user['id'], $currentPlan);
if (!$quotaCheck['can_create']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => $quotaCheck['message']]);
    exit;
}

// Generate or validate short code
$shortCode = null;
if (!empty($customCode) && ($isPremium || $isEnterprise)) {
    $codeValidation = $shortCodeService->validateCustomCode($customCode);
    if (!$codeValidation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $codeValidation['error']]);
        exit;
    }
    $shortCode = $codeValidation['sanitized'];
} else {
    if (!empty($customCode)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Custom codes are only available for PREMIUM and ENTERPRISE plans']);
        exit;
    }
    $shortCode = $shortCodeService->generateRandomCode();
}

// Validate expiration date (if provided)
$expiresAt = null;
if (!empty($expirationDate) && ($isPremium || $isEnterprise)) {
    // Parse MM/DD/YYYY HH:MM format
    $parsedDate = null;
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $expirationDate, $matches)) {
        $month = (int)$matches[1];
        $day = (int)$matches[2];
        $year = (int)$matches[3];
        $hour = (int)$matches[4];
        $minute = (int)$matches[5];
        
        if (checkdate($month, $day, $year) && $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
            $parsedDate = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
        }
    }
    
    if ($parsedDate === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid expiration date format. Use MM/DD/YYYY HH:MM']);
        exit;
    }
    
    if (strtotime($parsedDate) < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Expiration date must be in the future']);
        exit;
    }
    
    $expiresAt = $parsedDate;
} elseif (!empty($expirationDate)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Expiration dates are only available for PREMIUM and ENTERPRISE plans']);
    exit;
}

// Generate QR code
$qrPath = $qrService->generateQrCode($shortCode);

// Create short link
try {
    $link = $shortLinkRepo->create([
        'user_id' => (int)$user['id'],
        'original_url' => $urlValidation['normalized_url'],
        'short_code' => $shortCode,
        'label' => $label ?: null,
        'expires_at' => $expiresAt,
        'is_active' => 1,
        'has_preview_page' => 0,
        'qr_code_path' => $qrPath,
    ]);
    
    // Record quota usage
    $quotaService->recordLinkCreation((int)$user['id']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'short_code' => $shortCode,
        'short_url' => $appConfig['base_url'] . '/' . $shortCode,
        'base_url' => $appConfig['base_url'],
        'qr_code_url' => $qrPath ? ($appConfig['base_url'] . '/' . $qrPath) : null,
        'link' => [
            'id' => $link['id'],
            'original_url' => $link['original_url'],
            'short_code' => $link['short_code'],
            'label' => $link['label'],
            'created_at' => $link['created_at'],
        ]
    ]);
    
} catch (\Throwable $e) {
    error_log('Chrome API create link error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create shortlink. Please try again.']);
}

