<?php
declare(strict_types=1);

// Start output buffering to prevent any output before JSON
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');

// Handle preflight requests
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'OPTIONS') {
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
    // Load bootstrap (inside try-catch to catch any errors)
    // Path: /public/api/chrome/ -> up 3 levels to root -> config/bootstrap.php
    require dirname(__DIR__, 3) . "/config/bootstrap.php";
    
    // Only allow POST
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($requestMethod !== 'POST') {
        sendJsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
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
    sendJsonResponse(401, ['success' => false, 'error' => 'Missing Authorization header']);
}

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    sendJsonResponse(401, ['success' => false, 'error' => 'Invalid Authorization header format']);
}

$idToken = trim($matches[1]);

if (empty($idToken)) {
    sendJsonResponse(401, ['success' => false, 'error' => 'Missing id_token']);
}

// Verify id_token directly with Google Client (bootstrap already loaded at top)
try {
    $client = new Google\Client([
        'client_id' => '837476462692-rqfbcflt7tgm3i60a4vqe18a6sjajgpu.apps.googleusercontent.com'
    ]);
    
    $payload = $client->verifyIdToken($idToken);
    
    if (!$payload || !isset($payload['sub'])) {
        sendJsonResponse(401, ['success' => false, 'error' => 'Invalid or expired token']);
    }
    
    // Get user from database
    $googleId = $payload['sub'];
    $pdo = db();
    $userRepo = new \App\Models\UserRepository($pdo);
    $user = $userRepo->findByGoogleId($googleId);
    
    if (!$user) {
        sendJsonResponse(401, ['success' => false, 'error' => 'User not found. Please log in via the web app first.']);
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
    sendJsonResponse(401, ['success' => false, 'error' => 'Token verification failed']);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$originalUrl = trim($input['original_url'] ?? '');
$label = trim($input['label'] ?? '');
$customCode = trim($input['custom_code'] ?? '');
$expirationDate = trim($input['expiration_date'] ?? '');

// Validate required fields
if (empty($originalUrl)) {
    sendJsonResponse(400, ['success' => false, 'error' => 'original_url is required']);
}

// Initialize services
$pdo = db();
$shortLinkRepo = new \App\Models\ShortLinkRepository($pdo);
$quotaRepo = new \App\Models\QuotaRepository($pdo);
$quotaService = new \App\Services\QuotaService($quotaRepo);
$urlValidator = new \App\Services\UrlValidationService();
$shortCodeService = new \App\Services\ShortCodeService($shortLinkRepo);

$appConfig = require dirname(__DIR__, 3) . "/config/config.php";
$qrService = new \App\Services\QrCodeService($appConfig['qr_dir'], $appConfig['base_url']);

$currentPlan = $user['plan'] ?? 'FREE';
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

// Validate Google Forms URL
$urlValidation = $urlValidator->validateGoogleFormsUrl($originalUrl);
if (!$urlValidation['valid']) {
    sendJsonResponse(400, ['success' => false, 'error' => $urlValidation['error']]);
}

// Check quota
$quotaCheck = $quotaService->canCreateLink((int)$user['id'], $currentPlan);
if (!$quotaCheck['can_create']) {
    sendJsonResponse(403, ['success' => false, 'error' => $quotaCheck['message']]);
}

// Generate or validate short code
$shortCode = null;
if (!empty($customCode) && ($isPremium || $isEnterprise)) {
    $codeValidation = $shortCodeService->validateCustomCode($customCode);
    if (!$codeValidation['valid']) {
        sendJsonResponse(400, ['success' => false, 'error' => $codeValidation['error']]);
    }
    $shortCode = $codeValidation['sanitized'];
} else {
    if (!empty($customCode)) {
        sendJsonResponse(403, ['success' => false, 'error' => 'Custom codes are only available for PREMIUM and ENTERPRISE plans']);
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
        sendJsonResponse(400, ['success' => false, 'error' => 'Invalid expiration date format. Use MM/DD/YYYY HH:MM']);
    }
    
    if (strtotime($parsedDate) < time()) {
        sendJsonResponse(400, ['success' => false, 'error' => 'Expiration date must be in the future']);
    }
    
    $expiresAt = $parsedDate;
} elseif (!empty($expirationDate)) {
    sendJsonResponse(403, ['success' => false, 'error' => 'Expiration dates are only available for PREMIUM and ENTERPRISE plans']);
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
    
    // Send confirmation email to user
    try {
        error_log('Chrome API: Starting email sending process for link: ' . $shortCode);
        error_log('Chrome API: User data - ID: ' . (int)$user['id'] . ', Email: ' . ($user['email'] ?? 'NOT SET') . ', Name: ' . ($user['name'] ?? 'NOT SET'));
        
        $userEmail = $user['email'] ?? null;
        if (empty($userEmail)) {
            error_log('Chrome API: User email is empty or null, skipping email send. User ID: ' . (int)$user['id']);
        } else {
            error_log('Chrome API: Attempting to send email to: ' . $userEmail . ' for link: ' . $shortCode);
            
            // Check if helper function exists
            if (!function_exists('generate_link_creation_email_template')) {
                error_log('Chrome API: ERROR - generate_link_creation_email_template function not found!');
            } else {
                error_log('Chrome API: Helper function exists, generating email template...');
            }
            
            $emailService = new \App\Services\EmailService();
            $userName = $user['name'] ?? $user['email'] ?? '';
            $emailSubject = 'Your Short Link Has Been Created - GForms';
            
            try {
                $emailBody = generate_link_creation_email_template($link, $appConfig['base_url'], $userName);
                error_log('Chrome API: Email template generated successfully');
            } catch (\Throwable $templateError) {
                error_log('Chrome API: Error generating email template - ' . $templateError->getMessage());
                throw $templateError;
            }
            
            $emailSent = $emailService->send($userEmail, $emailSubject, $emailBody);
            if ($emailSent) {
                error_log('Chrome API: Email sent successfully to: ' . $userEmail);
            } else {
                error_log('Chrome API: Failed to send email to: ' . $userEmail . '. Check SMTP configuration and error logs.');
            }
        }
    } catch (\Throwable $emailError) {
        // Log email error but don't fail the link creation
        error_log('Chrome API: Exception in email sending - ' . $emailError->getMessage());
        error_log('Chrome API: Email error trace: ' . $emailError->getTraceAsString());
    }
    
    // Return success response
    sendJsonResponse(200, [
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
    error_log('Stack trace: ' . $e->getTraceAsString());
    sendJsonResponse(500, ['success' => false, 'error' => 'Failed to create shortlink. Please try again.']);
}

} catch (\Throwable $e) {
    error_log('Chrome create endpoint error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    sendJsonResponse(500, ['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
