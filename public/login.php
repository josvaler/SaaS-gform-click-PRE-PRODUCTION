<?php
declare(strict_types=1);

use App\Models\UserRepository;
use App\Models\LoginLogRepository;
use App\Services\IpTrackingService;

require __DIR__ . '/../config/bootstrap.php';

if (session_user()) {
    redirect('/dashboard');
}

$pageTitle = 'Login with Google';
$navLinksLeft = [
    ['label' => t('nav.home'), 'href' => '/'],
    ['label' => t('nav.pricing'), 'href' => '/pricing'],
];

$authError = null;
$authUrl = null;
$googleSdkAvailable = class_exists('\Google\Client');

// Initialize authUrl to prevent undefined variable errors
if (!$googleSdkAvailable) {
    $authError = 'Google OAuth SDK is not installed. Please install google/apiclient package.';
}

// Always set authUrl to go through action=start flow (never generate direct Google URL)
$authUrl = '/login?action=start';

if ($googleSdkAvailable) {
    // Validate Google OAuth configuration
    if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
        $authError = 'Google OAuth is not properly configured. Please check your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.';
        error_log('Google OAuth config error: client_id=' . (!empty($googleConfig['client_id']) ? 'SET' : 'EMPTY') . ', client_secret=' . (!empty($googleConfig['client_secret']) ? 'SET' : 'EMPTY'));
        $authUrl = null; // Disable button if config is missing
    } else {
        $client = new Google\Client();
        $client->setClientId(trim($googleConfig['client_id']));
        $client->setClientSecret(trim($googleConfig['client_secret']));
        $client->setRedirectUri($googleConfig['redirect_uri']);
        $client->setAccessType('offline');
        $client->setPrompt($googleConfig['prompt']);
        $client->setScopes($googleConfig['scopes']);

        if (isset($_GET['action']) && $_GET['action'] === 'start') {
            $state = bin2hex(random_bytes(16));
            
            // Store state in session BEFORE setting it on client
            $_SESSION['oauth_state'] = $state;
            $_SESSION['oauth_start_time'] = time();
            
            // Set state on Google Client
            $client->setState($state);
            
            // Log state for debugging with cookie info
            $cookieName = session_name();
            $cookieValue = $_COOKIE[$cookieName] ?? 'NOT SET';
            debug_log('OAuth start - State: ' . $state . ', Session ID: ' . session_id() . ', Cookie: ' . $cookieValue . ', Domain: ' . session_get_cookie_params()['domain']);
            
            // Verify state is in session before proceeding
            if (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
                $authError = 'Failed to store OAuth state in session. Please try again.';
                error_log('OAuth start failed - State not properly stored in session');
            } else {
                // CRITICAL: Save session and ensure cookie is sent before redirect
                session_write_close();
                
                try {
                    $authUrl = $client->createAuthUrl();
                    
                    // Verify state is in the URL
                    if (strpos($authUrl, 'state=') === false) {
                        // Restart session if we're not redirecting
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $authError = 'OAuth state not included in authorization URL. Please try again.';
                        debug_log('OAuth URL missing state parameter: ' . substr($authUrl, 0, 200));
                    } elseif (empty($authUrl)) {
                        // Restart session if we're not redirecting
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $authError = 'Failed to generate Google OAuth URL. Please check your configuration.';
                        debug_log('Google OAuth URL generation failed - client_id: ' . substr($client->getClientId(), 0, 30) . '...');
                    } else {
                        // Log the auth URL (without sensitive data) and session info
                        debug_log('OAuth redirect URL: ' . preg_replace('/(client_secret|code)=[^&]+/', '$1=***', $authUrl));
                        debug_log('OAuth redirect - Session ID: ' . session_id() . ', Cookie: ' . session_name() . '=' . session_id());
                        
                        // Parse URL to verify state is present
                        $urlParts = parse_url($authUrl);
                        parse_str($urlParts['query'] ?? '', $params);
                        debug_log('OAuth redirect - State in URL: ' . ($params['state'] ?? 'MISSING'));
                        
                        // Use direct header redirect to ensure clean session closure
                        header('Location: ' . $authUrl);
                        exit;
                    }
                } catch (Throwable $e) {
                    // Restart session if we're not redirecting
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $authError = 'Error generating OAuth URL: ' . $e->getMessage();
                    error_log('Google OAuth URL error: ' . $e->getMessage());
                }
            }
        }

        if (isset($_GET['code'])) {
            // Ensure session is started (bootstrap should handle this, but be safe)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Log all GET parameters for debugging
            debug_log('OAuth callback - GET params: ' . print_r($_GET, true));
            debug_log('OAuth callback - Session ID: ' . session_id());
            debug_log('OAuth callback - Session name: ' . session_name());
            debug_log('OAuth callback - Cookie: ' . (isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'NOT SET'));
            
            $state = $_GET['state'] ?? '';
            $sessionState = $_SESSION['oauth_state'] ?? null;
            
            // Debug logging
            debug_log('OAuth callback - Received state: ' . ($state ?: 'EMPTY') . ', Session state: ' . ($sessionState ?: 'EMPTY'));
            debug_log('OAuth callback - Session data: ' . print_r($_SESSION, true));
            
            // If state is empty from Google, check if it's a session persistence issue
            if (empty($state) && empty($sessionState)) {
                // Both are empty - likely session didn't persist
                $authError = 'Session not persisting between requests. Please ensure cookies are enabled and try again.';
                error_log('OAuth state validation failed: Both Google state and session state are empty - session persistence issue');
            } elseif (empty($sessionState)) {
                $authError = 'OAuth state not found in session. The session may have expired or cookies are blocked. Please try again.';
                error_log('OAuth state validation failed: Session state is empty. Session ID: ' . session_id() . ', Cookie present: ' . (isset($_COOKIE[session_name()]) ? 'YES' : 'NO'));
            } elseif (empty($state)) {
                $authError = 'OAuth state not received from Google. This may indicate the state parameter was not included in the authorization URL. Please try again.';
                error_log('OAuth state validation failed: State from Google is empty. This suggests the auth URL did not include state parameter.');
            } elseif ($state !== $sessionState) {
                $authError = 'Invalid OAuth state. The state received does not match the session. Please try again.';
                error_log('OAuth state validation failed: State mismatch. Received: ' . $state . ', Expected: ' . $sessionState);
            } else {
                // Re-initialize client to ensure it has all config
                $client = new Google\Client();
                $client->setClientId(trim($googleConfig['client_id']));
                $client->setClientSecret(trim($googleConfig['client_secret']));
                $client->setRedirectUri($googleConfig['redirect_uri']);
                $client->setAccessType('offline');
                
                try {
                    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                    if (isset($token['error'])) {
                        $authError = 'Authentication failed: ' . $token['error'];
                    } else {
                        $oauthService = new Google\Service\Oauth2($client);
                        $googleProfile = $oauthService->userinfo->get();

                        try {
                            $pdo = db();
                            $userRepo = new UserRepository($pdo);

                            $userRecord = $userRepo->upsertFromGoogle([
                                'google_id' => $googleProfile->id,
                                'email' => $googleProfile->email,
                                'name' => $googleProfile->name,
                                'avatar_url' => $googleProfile->picture ?? null,
                                'locale' => $googleProfile->locale ?? null,
                            ]);

                            $_SESSION['user'] = [
                                'id' => (int)$userRecord['id'],
                                'google_id' => trim((string)$userRecord['google_id']),
                                'email' => strtolower(trim((string)$userRecord['email'])),
                                'name' => $userRecord['name'],
                                'plan' => $userRecord['plan'] ?? 'FREE',
                                'lifetime_ops' => (int)$userRecord['lifetime_ops'],
                                'avatar' => $userRecord['avatar_url'] ?? $googleProfile->picture ?? null,
                                'stripe_customer_id' => $userRecord['stripe_customer_id'] ?? null,
                                'role' => $userRecord['role'] ?? 'USER',
                            ];

                            // Record login IP
                            try {
                                $ipService = new IpTrackingService();
                                $loginLogRepo = new LoginLogRepository($pdo);
                                $loginLogRepo->recordLogin([
                                    'user_id' => (int)$userRecord['id'],
                                    'google_id' => trim((string)$userRecord['google_id']),
                                    'ip_address' => $ipService->getClientIp(),
                                    'user_agent' => $ipService->getUserAgent(),
                                    'country' => $ipService->detectCountry($ipService->getClientIp()),
                                ]);
                            } catch (Throwable $logException) {
                                error_log('Login log error: ' . $logException->getMessage());
                            }

                            unset($_SESSION['oauth_state']);
                            
                            // Mark that user has logged in (for cookie banner check)
                            $_SESSION['first_login'] = true;
                            
                            session_write_close();
                            
                            redirect('/dashboard');
                        } catch (Throwable $exception) {
                            $authError = 'Unable to persist user: ' . $exception->getMessage();
                            error_log('Login error: ' . $exception->getMessage());
                        }
                    }
                } catch (Throwable $exception) {
                    $authError = 'Unable to authenticate: ' . $exception->getMessage();
                    error_log('OAuth error: ' . $exception->getMessage());
                }
            }
        }

        // authUrl is already set to /login?action=start at the top
        // No need to regenerate it here
    }
} else {
    // Google SDK not available
    $authError = 'Google OAuth SDK is not installed. Please install google/apiclient package.';
    $authUrl = null; // Disable button if SDK not available
}

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 520px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Login with Google</h2>
                    <p class="text-muted">Secure OAuth 2.0 sign-in</p>
                </div>
            </div>

            <?php if ($authError): ?>
                <div class="alert alert-error"><?= html($authError) ?></div>
            <?php endif; ?>

            <?php if ($googleSdkAvailable && !empty($authUrl) && empty($authError)): ?>
                <div style="padding: 1.5rem;">
                    <a href="<?= html($authUrl) ?>" class="btn btn-primary" style="width: 100%;">
                        Sign in with Google
                    </a>
                </div>
            <?php elseif (!$googleSdkAvailable): ?>
                <div class="alert alert-error">
                    Google OAuth SDK is not installed. Please install google/apiclient package.
                </div>
            <?php elseif (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])): ?>
                <div class="alert alert-error">
                    Google OAuth is not configured. Please check your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    Unable to generate OAuth URL. Please try again.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

