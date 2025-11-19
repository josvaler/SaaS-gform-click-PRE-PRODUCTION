<?php
declare(strict_types=1);

// Debug: Check if .env file exists and is readable
$envFile = __DIR__ . '/../.env';
echo "=== Pre-Bootstrap Debug ===\n";
echo "Looking for .env at: $envFile\n";
echo "File exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n";
echo "File readable: " . (is_readable($envFile) ? 'YES' : 'NO') . "\n";
if (file_exists($envFile)) {
    echo "File permissions: " . substr(sprintf('%o', fileperms($envFile)), -4) . "\n";
    echo "File owner: " . posix_getpwuid(fileowner($envFile))['name'] . "\n";
}
echo "\n";

require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Google OAuth Configuration Debug ===\n\n";

// Check .env loading
echo "1. Environment Variables:\n";
echo "   GOOGLE_CLIENT_ID: " . (env('GOOGLE_CLIENT_ID') ? substr(env('GOOGLE_CLIENT_ID'), 0, 30) . '...' : 'NOT SET') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (env('GOOGLE_CLIENT_SECRET') ? substr(env('GOOGLE_CLIENT_SECRET'), 0, 10) . '...' : 'NOT SET') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (env('GOOGLE_REDIRECT_URI') ?: 'NOT SET') . "\n\n";

// Check config loading
$googleConfig = require __DIR__ . '/../config/google.php';
echo "2. Google Config:\n";
echo "   client_id: " . ($googleConfig['client_id'] ? substr($googleConfig['client_id'], 0, 30) . '...' : 'EMPTY') . "\n";
echo "   client_secret: " . ($googleConfig['client_secret'] ? substr($googleConfig['client_secret'], 0, 10) . '...' : 'EMPTY') . "\n";
echo "   redirect_uri: " . $googleConfig['redirect_uri'] . "\n\n";

// Test Google Client
if (class_exists('\Google\Client')) {
    echo "3. Google Client Test:\n";
    try {
        $client = new Google\Client();
        $client->setClientId(trim($googleConfig['client_id']));
        $client->setClientSecret(trim($googleConfig['client_secret']));
        $client->setRedirectUri($googleConfig['redirect_uri']);
        $client->setAccessType('offline');
        $client->setPrompt('select_account');
        $client->setScopes(['email', 'profile']);
        
        echo "   Client ID set: " . ($client->getClientId() ? 'YES' : 'NO') . "\n";
        echo "   Redirect URI set: " . ($client->getRedirectUri() ?: 'NO') . "\n";
        
        $authUrl = $client->createAuthUrl();
        echo "   Auth URL generated: YES\n";
        echo "   Auth URL: " . $authUrl . "\n\n";
        
        // Parse the URL to check parameters
        $parsed = parse_url($authUrl);
        parse_str($parsed['query'] ?? '', $params);
        echo "4. Auth URL Parameters:\n";
        echo "   client_id: " . (isset($params['client_id']) ? substr($params['client_id'], 0, 30) . '...' : 'MISSING') . "\n";
        echo "   redirect_uri: " . ($params['redirect_uri'] ?? 'MISSING') . "\n";
        echo "   response_type: " . ($params['response_type'] ?? 'MISSING') . "\n";
        echo "   scope: " . ($params['scope'] ?? 'MISSING') . "\n";
        
    } catch (Throwable $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "3. Google Client: NOT AVAILABLE\n";
}

echo "\n=== Instructions ===\n";
echo "1. Make sure https://gforms.click/login is in Google Console\n";
echo "2. Check that client_id matches your Google Console OAuth client\n";
echo "3. Verify redirect_uri matches exactly (including https://)\n";

