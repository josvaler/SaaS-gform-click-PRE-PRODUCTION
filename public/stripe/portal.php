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
    redirect('/billing?status=portal_error');
}

$secretKey = $stripeConfig['secret_key'];
if (empty($secretKey) || $secretKey === 'sk_test_xxx' || $secretKey === 'sk_live_xxx') {
    redirect('/billing?status=portal_error');
}

$stripe = new StripeClient($secretKey);
$user = session_user();
$customerId = $user['stripe_customer_id'] ?? null;
$userId = $user['id'] ?? null;

try {
    $pdo = db();
    $userRepo = new UserRepository($pdo);
    if (!$userId && !empty($user['google_id'])) {
        $dbUser = $userRepo->findByGoogleId($user['google_id']);
        if ($dbUser) {
            $userId = (int)$dbUser['id'];
            $customerId = $dbUser['stripe_customer_id'] ?? $customerId;
            $_SESSION['user']['id'] = $userId;
            $_SESSION['user']['stripe_customer_id'] = $customerId;
        }
    }

    if (!$customerId) {
        $customer = $stripe->customers->create([
            'email' => $user['email'] ?? null,
            'name' => $user['name'] ?? null,
            'metadata' => [
                'google_id' => $user['google_id'] ?? null,
            ],
        ]);
        $customerId = $customer->id;

        if ($userId) {
            $userRepo->updateStripeCustomerId($userId, $customerId);
        }

        $_SESSION['user']['stripe_customer_id'] = $customerId;
    }
} catch (Throwable $exception) {
    error_log('Stripe Portal Error (Customer): ' . $exception->getMessage());
    redirect('/billing?status=error');
}

try {
    $session = $stripe->billingPortal->sessions->create([
        'customer' => $customerId,
        'return_url' => $stripeConfig['success_url'],
    ]);

    redirect($session->url);
} catch (Throwable $exception) {
    error_log('Stripe Portal Error: ' . $exception->getMessage());
    redirect('/billing?status=portal_error');
}

