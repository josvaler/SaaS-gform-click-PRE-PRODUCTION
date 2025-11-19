<?php
declare(strict_types=1);

use Stripe\StripeClient;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();

// Sync user data from database
try {
    $pdo = db();
    if (!empty($user['google_id'])) {
        $userRepo = new UserRepository($pdo);
        $dbUser = $userRepo->findByGoogleId($user['google_id']);

        if ($dbUser) {
            $user['id'] = (int)$dbUser['id'];
            $user['plan'] = $dbUser['plan'];
            $user['stripe_customer_id'] = $dbUser['stripe_customer_id'] ?? ($user['stripe_customer_id'] ?? null);
            $user['stripe_subscription_id'] = $dbUser['stripe_subscription_id'] ?? null;
            $user['cancel_at_period_end'] = isset($dbUser['cancel_at_period_end']) ? (bool)$dbUser['cancel_at_period_end'] : null;
            $user['cancel_at'] = $dbUser['cancel_at'] ?? null;
            $user['current_period_end'] = $dbUser['current_period_end'] ?? null;
            $_SESSION['user'] = $user;
        }
    }
} catch (Throwable $exception) {
    // Ignore sync failures
}

$user = session_user();
$pageTitle = 'Billing';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Precios', 'href' => '/pricing'],
    ['label' => 'Mi Plan', 'href' => '/billing'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

// Prepare template variables
$status = $_GET['status'] ?? null;
$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');
$hasScheduledCancellation = !empty($user['cancel_at_period_end']) || !empty($user['cancel_at']);
$cancelDate = $user['cancel_at'] ?? $user['current_period_end'] ?? null;
$cancelDateFormatted = $cancelDate ? date('M j, Y', strtotime($cancelDate)) : 'period end';

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/billing.php';
require __DIR__ . '/../views/partials/footer.php';

