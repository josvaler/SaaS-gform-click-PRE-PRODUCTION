<?php
declare(strict_types=1);

use App\Models\UserRepository;
use Stripe\StripeClient;

require __DIR__ . '/../../config/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/billing');
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    redirect('/billing?status=error');
}

if (!class_exists(StripeClient::class)) {
    error_log("WARNING: Stripe SDK not available");
    redirect('/billing?status=error');
}

$stripe = new StripeClient($stripeConfig['secret_key']);
$user = session_user();
$customerId = $user['stripe_customer_id'] ?? null;

try {
    $pdo = db();
    $userRepo = new UserRepository($pdo);
    if (!$customerId && !empty($user['google_id'])) {
        $dbUser = $userRepo->findByGoogleId($user['google_id']);
        if ($dbUser) {
            $customerId = $dbUser['stripe_customer_id'] ?? null;
            $_SESSION['user']['id'] = (int)$dbUser['id'];
            $_SESSION['user']['stripe_customer_id'] = $customerId;
        }
    }
} catch (Throwable $exception) {
    error_log("Database lookup error: " . $exception->getMessage());
}

$googleId = $user['google_id'] ?? null;

try {
    $payload = [
        'mode' => 'subscription',
        'line_items' => [[
            'price' => $stripeConfig['price_id'],
            'quantity' => 1,
        ]],
        'success_url' => $stripeConfig['success_url'],
        'cancel_url' => $stripeConfig['cancel_url'],
        'customer_email' => $user['email'] ?? null,
        'metadata' => [
            'google_id' => $googleId,
        ],
        'subscription_data' => [
            'metadata' => [
                'google_id' => $googleId,
            ],
        ],
    ];

    if ($customerId) {
        $payload['customer'] = $customerId;
        unset($payload['customer_email']);
    }

    $session = $stripe->checkout->sessions->create($payload);
    redirect($session->url);
} catch (Throwable $exception) {
    error_log('Stripe checkout error: ' . $exception->getMessage());
    redirect('/billing?status=error');
}

