<?php
declare(strict_types=1);

use App\Models\UserRepository;
use Stripe\Webhook;

require __DIR__ . '/../../config/bootstrap.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!class_exists(Webhook::class)) {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

try {
    $event = Webhook::constructEvent(
        $payload,
        $signature,
        $stripeConfig['webhook_secret']
    );
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
}

$type = $event->type;
$data = $event->data->object;
$googleId = $data->metadata->google_id ?? null;
$stripeCustomerId = $data->customer ?? null;

if ($googleId) {
    try {
        $pdo = db();
        $userRepo = new UserRepository($pdo);
        $user = $userRepo->findByGoogleId($googleId);

        if ($user) {
            if ($type === 'checkout.session.completed') {
                // Set plan expiration to 30 days from now
                $planExpiration = date('Y-m-d H:i:s', strtotime('+30 days'));
                $userRepo->updatePlan((int)$user['id'], 'PREMIUM', $planExpiration);
                if ($stripeCustomerId) {
                    $userRepo->updateStripeCustomerId((int)$user['id'], $stripeCustomerId);
                    if (isset($_SESSION['user']['google_id']) && $_SESSION['user']['google_id'] === $googleId) {
                        $_SESSION['user']['stripe_customer_id'] = $stripeCustomerId;
                        $_SESSION['user']['plan'] = 'PREMIUM';
                    }
                }
            }

            if ($type === 'customer.subscription.deleted') {
                // Auto-downgrade to FREE when subscription is deleted
                $userRepo->updatePlan((int)$user['id'], 'FREE', null);
                if (isset($_SESSION['user']['google_id']) && $_SESSION['user']['google_id'] === $googleId) {
                    $_SESSION['user']['plan'] = 'FREE';
                }
            }
            
            // Handle subscription expiration (when period ends)
            if ($type === 'customer.subscription.updated') {
                $subscriptionId = $data->id ?? null;
                $status = $data->status ?? null;
                $cancelAtPeriodEnd = $data->cancel_at_period_end ?? false;
                $cancelAt = isset($data->cancel_at) && $data->cancel_at 
                    ? gmdate('Y-m-d H:i:s', (int)$data->cancel_at) 
                    : null;
                $currentPeriodEnd = isset($data->current_period_end) && $data->current_period_end
                    ? gmdate('Y-m-d H:i:s', (int)$data->current_period_end)
                    : null;

                // If subscription is past_due, unpaid, or canceled, downgrade to FREE
                if (in_array($status, ['past_due', 'unpaid', 'canceled', 'incomplete_expired'])) {
                    $userRepo->updatePlan((int)$user['id'], 'FREE', null);
                    if (isset($_SESSION['user']['google_id']) && $_SESSION['user']['google_id'] === $googleId) {
                        $_SESSION['user']['plan'] = 'FREE';
                    }
                }

                $updates = [];
                if ($subscriptionId) $updates['stripe_subscription_id'] = $subscriptionId;
                if ($cancelAtPeriodEnd !== null) $updates['cancel_at_period_end'] = $cancelAtPeriodEnd ? 1 : 0;
                if ($cancelAt) $updates['cancel_at'] = $cancelAt;
                if ($currentPeriodEnd) {
                    $updates['current_period_end'] = $currentPeriodEnd;
                    // Update plan_expiration to match subscription period end
                    $userRepo->updatePlan((int)$user['id'], 'PREMIUM', $currentPeriodEnd);
                }

                if (!empty($updates)) {
                    $userRepo->updateSubscriptionMetadata((int)$user['id'], $updates);
                }
            }

        }
    } catch (Throwable $exception) {
        error_log('Webhook processing error: ' . $exception->getMessage());
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);

