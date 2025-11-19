<?php
declare(strict_types=1);

// Build redirect URI dynamically if not set in env
$redirectUri = env('GOOGLE_REDIRECT_URI');
if (empty($redirectUri)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectUri = $protocol . '://' . $host . '/login';
}

return [
    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'redirect_uri' => $redirectUri,
    'prompt' => 'select_account',
    'scopes' => [
        'email',
        'profile',
    ],
];

