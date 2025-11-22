<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$reportType = $_POST['report_type'] ?? $_GET['report_type'] ?? '';
$paramsJson = $_POST['params'] ?? '{}';
$params = json_decode($paramsJson, true) ?? [];

$reportsDir = __DIR__ . '/../../../reports';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}

try {
    $pdo = db();
    $stripeConfig = require __DIR__ . '/../../../config/stripe.php';
    
    $timestamp = date('Ymd_His');
    $date = date('Y-m-d');
    $filename = '';
    $data = [];
    $headers = [];
    
    switch ($reportType) {
        case 'user_subscription':
            $filename = "user_subscription_{$timestamp}_{$date}.csv";
            $data = generateUserSubscriptionReport($pdo, $stripeConfig);
            $headers = getUserSubscriptionHeaders();
            break;
            
        case 'stripe_sync':
            $filename = "stripe_sync_{$timestamp}_{$date}.csv";
            $data = generateStripeSyncReport($pdo, $stripeConfig);
            $headers = getStripeSyncHeaders();
            break;
            
        case 'general_users':
            $filename = "general_users_{$timestamp}_{$date}.csv";
            $data = generateGeneralUsersReport($pdo);
            $headers = getGeneralUsersHeaders();
            break;
            
        case 'custom_gmail':
            $inputType = $params['input_type'] ?? 'gmail_id';
            $values = $params['values'] ?? '';
            $filename = "custom_gmail_{$timestamp}_{$date}.csv";
            $data = generateCustomGmailReport($pdo, $stripeConfig, $inputType, $values);
            $headers = getCustomGmailHeaders();
            break;
            
        case 'stripe_sanity':
            $filename = "stripe_sanity_{$timestamp}_{$date}.csv";
            $data = generateStripeSanityReport($pdo, $stripeConfig);
            $headers = getStripeSanityHeaders();
            break;
            
        default:
            throw new Exception('Invalid report type');
    }
    
    // Save CSV file
    $filepath = $reportsDir . '/' . $filename;
    saveCSV($filepath, $headers, $data);
    
    $fileSize = filesize($filepath);
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'date' => date('Y-m-d H:i:s'),
        'rows' => count($data),
        'size' => $fileSize,
        'size_formatted' => formatBytes($fileSize)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function generateUserSubscriptionReport(PDO $pdo, array $stripeConfig): array
{
    $stmt = $pdo->query("
        SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id, 
               plan_expiration, created_at
        FROM users
        ORDER BY id DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    
    foreach ($users as $user) {
        $result[] = [
            'user_id' => $user['id'],
            'email' => $user['email'] ?? '',
            'google_id' => $user['google_id'] ?? '',
            'plan' => $user['plan'] ?? 'FREE',
            'stripe_customer_id' => $user['stripe_customer_id'] ?? '',
            'stripe_subscription_id' => $user['stripe_subscription_id'] ?? '',
            'plan_expiration' => $user['plan_expiration'] ?? '',
            'created_at' => $user['created_at'] ?? ''
        ];
    }
    
    return $result;
}

function generateStripeSyncReport(PDO $pdo, array $stripeConfig): array
{
    if (empty($stripeConfig['secret_key'])) {
        throw new Exception('STRIPE_SECRET_KEY not configured');
    }
    
    $stripe = new \Stripe\StripeClient($stripeConfig['secret_key']);
    
    $stmt = $pdo->query("
        SELECT id, email, google_id, plan, stripe_customer_id, stripe_subscription_id
        FROM users
        WHERE stripe_customer_id IS NOT NULL
        ORDER BY id DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    
    foreach ($users as $user) {
        $syncStatus = 'OK';
        $issues = [];
        
        try {
            $customer = $stripe->customers->retrieve($user['stripe_customer_id']);
            $subscriptions = $stripe->subscriptions->all([
                'customer' => $user['stripe_customer_id'],
                'limit' => 1
            ]);
            
            $stripePlan = 'NONE';
            if (count($subscriptions->data) > 0) {
                $sub = $subscriptions->data[0];
                $stripePlan = $sub->status === 'active' ? 'PREMIUM' : $sub->status;
                
                if ($user['plan'] !== 'PREMIUM' && $sub->status === 'active') {
                    $syncStatus = 'MISMATCH';
                    $issues[] = 'DB plan does not match Stripe active subscription';
                }
            } else {
                if ($user['plan'] === 'PREMIUM') {
                    $syncStatus = 'MISMATCH';
                    $issues[] = 'DB shows PREMIUM but no active Stripe subscription';
                }
            }
        } catch (Exception $e) {
            $syncStatus = 'ERROR';
            $issues[] = $e->getMessage();
        }
        
        $result[] = [
            'email' => $user['email'] ?? '',
            'customer_id' => $user['stripe_customer_id'] ?? '',
            'subscription_status' => $stripePlan,
            'database_plan' => $user['plan'] ?? 'FREE',
            'sync_status' => $syncStatus,
            'issues' => implode('; ', $issues)
        ];
    }
    
    return $result;
}

function generateGeneralUsersReport(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.name, u.google_id, u.plan, u.role, 
               u.stripe_customer_id, u.stripe_subscription_id, u.created_at,
               (SELECT COUNT(*) FROM short_links WHERE user_id = u.id) as total_links,
               (SELECT COUNT(*) FROM clicks WHERE short_link_id IN 
                   (SELECT id FROM short_links WHERE user_id = u.id)) as total_clicks,
               (SELECT MAX(created_at) FROM user_login_logs WHERE user_id = u.id) as last_login
        FROM users u
        ORDER BY u.id DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    
    foreach ($users as $user) {
        $result[] = [
            'user_id' => $user['id'],
            'email' => $user['email'] ?? '',
            'name' => $user['name'] ?? '',
            'google_id' => $user['google_id'] ?? '',
            'plan' => $user['plan'] ?? 'FREE',
            'role' => $user['role'] ?? 'USER',
            'stripe_customer_id' => $user['stripe_customer_id'] ?? '',
            'stripe_subscription_id' => $user['stripe_subscription_id'] ?? '',
            'total_links' => $user['total_links'] ?? 0,
            'total_clicks' => $user['total_clicks'] ?? 0,
            'last_login' => $user['last_login'] ?? '',
            'created_at' => $user['created_at'] ?? ''
        ];
    }
    
    return $result;
}

function generateCustomGmailReport(PDO $pdo, array $stripeConfig, string $inputType, string $values): array
{
    $values = array_map('trim', explode(',', $values));
    $values = array_filter($values);
    
    if (empty($values)) {
        throw new Exception('No values provided');
    }
    
    $result = [];
    
    foreach ($values as $value) {
        if ($inputType === 'gmail_id') {
            $stmt = $pdo->prepare("
                SELECT u.*,
                       (SELECT COUNT(*) FROM short_links WHERE user_id = u.id) as total_links,
                       (SELECT COUNT(*) FROM clicks WHERE short_link_id IN 
                           (SELECT id FROM short_links WHERE user_id = u.id)) as total_clicks,
                       (SELECT MAX(created_at) FROM user_login_logs WHERE user_id = u.id) as last_login
                FROM users u
                WHERE u.google_id = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT u.*,
                       (SELECT COUNT(*) FROM short_links WHERE user_id = u.id) as total_links,
                       (SELECT COUNT(*) FROM clicks WHERE short_link_id IN 
                           (SELECT id FROM short_links WHERE user_id = u.id)) as total_clicks,
                       (SELECT MAX(created_at) FROM user_login_logs WHERE user_id = u.id) as last_login
                FROM users u
                WHERE u.email = ?
            ");
        }
        
        $stmt->execute([$value]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $result[] = [
                'search_value' => $value,
                'user_id' => $user['id'],
                'email' => $user['email'] ?? '',
                'name' => $user['name'] ?? '',
                'google_id' => $user['google_id'] ?? '',
                'plan' => $user['plan'] ?? 'FREE',
                'role' => $user['role'] ?? 'USER',
                'stripe_customer_id' => $user['stripe_customer_id'] ?? '',
                'stripe_subscription_id' => $user['stripe_subscription_id'] ?? '',
                'plan_expiration' => $user['plan_expiration'] ?? '',
                'total_links' => $user['total_links'] ?? 0,
                'total_clicks' => $user['total_clicks'] ?? 0,
                'last_login' => $user['last_login'] ?? '',
                'created_at' => $user['created_at'] ?? ''
            ];
        } else {
            $result[] = [
                'search_value' => $value,
                'user_id' => 'NOT FOUND',
                'email' => '',
                'name' => '',
                'google_id' => '',
                'plan' => '',
                'role' => '',
                'stripe_customer_id' => '',
                'stripe_subscription_id' => '',
                'plan_expiration' => '',
                'total_links' => 0,
                'total_clicks' => 0,
                'last_login' => '',
                'created_at' => ''
            ];
        }
    }
    
    return $result;
}

function generateStripeSanityReport(PDO $pdo, array $stripeConfig): array
{
    if (empty($stripeConfig['secret_key'])) {
        throw new Exception('STRIPE_SECRET_KEY not configured');
    }
    
    $stripe = new \Stripe\StripeClient($stripeConfig['secret_key']);
    $result = [];
    
    // 1. API Validation
    try {
        $account = $stripe->accounts->retrieve();
        $result[] = [
            'section' => 'API Validation',
            'check' => 'Account Connection',
            'status' => 'OK',
            'details' => "Account: {$account->id}",
            'recommendation' => ''
        ];
    } catch (Exception $e) {
        $result[] = [
            'section' => 'API Validation',
            'check' => 'Account Connection',
            'status' => 'ERROR',
            'details' => $e->getMessage(),
            'recommendation' => 'Check STRIPE_SECRET_KEY'
        ];
    }
    
    // 2. Webhooks
    try {
        $webhooks = $stripe->webhookEndpoints->all(['limit' => 10]);
        foreach ($webhooks->data as $wh) {
            $result[] = [
                'section' => 'Webhooks',
                'check' => 'Webhook Endpoint',
                'status' => $wh->status,
                'details' => "ID: {$wh->id}, URL: {$wh->url}",
                'recommendation' => ''
            ];
        }
    } catch (Exception $e) {
        $result[] = [
            'section' => 'Webhooks',
            'check' => 'Webhook Check',
            'status' => 'ERROR',
            'details' => $e->getMessage(),
            'recommendation' => ''
        ];
    }
    
    // 3. Duplicate Customers
    $duplicatesFound = false;
    try {
        $customers = $stripe->customers->all(['limit' => 200]);
        $emailMap = [];
        foreach ($customers->data as $c) {
            $email = $c->email ?? 'NO_EMAIL';
            if (!isset($emailMap[$email])) {
                $emailMap[$email] = [];
            }
            $emailMap[$email][] = $c->id;
        }
        
        foreach ($emailMap as $email => $ids) {
            if (count($ids) > 1) {
                $duplicatesFound = true;
                $result[] = [
                    'section' => 'Duplicate Customers',
                    'check' => 'Duplicate Found',
                    'status' => 'WARNING',
                    'details' => "Email: {$email}, IDs: " . implode(', ', $ids),
                    'recommendation' => 'Merge duplicate customers'
                ];
            }
        }
        
        if (!$duplicatesFound) {
            $result[] = [
                'section' => 'Duplicate Customers',
                'check' => 'No Duplicates',
                'status' => 'OK',
                'details' => 'No duplicate customers found',
                'recommendation' => ''
            ];
        }
    } catch (Exception $e) {
        $result[] = [
            'section' => 'Duplicate Customers',
            'check' => 'Check Failed',
            'status' => 'ERROR',
            'details' => $e->getMessage(),
            'recommendation' => ''
        ];
    }
    
    // 4. Local vs Stripe Crosscheck
    $missingStripe = [];
    $missingGoogle = [];
    $stmt = $pdo->query("SELECT id, email, google_id, stripe_customer_id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        if (!$user['stripe_customer_id']) {
            $missingStripe[] = "{$user['email']} (google: {$user['google_id']})";
        }
        if (!$user['google_id']) {
            $missingGoogle[] = "{$user['email']} (stripe: {$user['stripe_customer_id']})";
        }
    }
    
    if (count($missingStripe) > 0) {
        foreach ($missingStripe as $m) {
            $result[] = [
                'section' => 'Crosscheck',
                'check' => 'Missing Stripe ID',
                'status' => 'WARNING',
                'details' => $m,
                'recommendation' => 'Create Stripe customer'
            ];
        }
    } else {
        $result[] = [
            'section' => 'Crosscheck',
            'check' => 'Stripe IDs',
            'status' => 'OK',
            'details' => 'All local users have stripe_customer_id',
            'recommendation' => ''
        ];
    }
    
    if (count($missingGoogle) > 0) {
        foreach ($missingGoogle as $m) {
            $result[] = [
                'section' => 'Crosscheck',
                'check' => 'Missing Google ID',
                'status' => 'WARNING',
                'details' => $m,
                'recommendation' => 'Validate Google Auth flow'
            ];
        }
    } else {
        $result[] = [
            'section' => 'Crosscheck',
            'check' => 'Google IDs',
            'status' => 'OK',
            'details' => 'All Stripe users have Google IDs',
            'recommendation' => ''
        ];
    }
    
    // 5. Subscriptions
    try {
        $subs = $stripe->subscriptions->all(['limit' => 50]);
        $active = 0;
        $incomplete = 0;
        $canceled = 0;
        
        foreach ($subs->data as $s) {
            if ($s->status === 'active') $active++;
            if ($s->status === 'incomplete') $incomplete++;
            if ($s->status === 'canceled') $canceled++;
            
            $result[] = [
                'section' => 'Subscriptions',
                'check' => 'Subscription',
                'status' => $s->status,
                'details' => "ID: {$s->id}, Customer: {$s->customer}",
                'recommendation' => ''
            ];
        }
        
        $result[] = [
            'section' => 'Subscriptions',
            'check' => 'Summary',
            'status' => 'OK',
            'details' => "Active: {$active}, Incomplete: {$incomplete}, Canceled: {$canceled}",
            'recommendation' => $incomplete > 0 ? 'Review payment failures' : ''
        ];
    } catch (Exception $e) {
        $result[] = [
            'section' => 'Subscriptions',
            'check' => 'Check Failed',
            'status' => 'ERROR',
            'details' => $e->getMessage(),
            'recommendation' => ''
        ];
    }
    
    // 6. Last 10 Events
    try {
        $events = $stripe->events->all(['limit' => 10]);
        foreach ($events->data as $ev) {
            $result[] = [
                'section' => 'Recent Events',
                'check' => 'Event',
                'status' => 'INFO',
                'details' => "ID: {$ev->id}, Type: {$ev->type}, Created: " . date("Y-m-d H:i:s", $ev->created),
                'recommendation' => ''
            ];
        }
    } catch (Exception $e) {
        // Error handling
    }
    
    // 7. Summary
    $fail = $duplicatesFound || count($missingStripe) > 0 || count($missingGoogle) > 0;
    $result[] = [
        'section' => 'Summary',
        'check' => 'System Status',
        'status' => $fail ? 'ISSUES DETECTED' : 'HEALTHY',
        'details' => $fail ? 'Issues detected - see recommendations' : 'All checks passed',
        'recommendation' => $fail ? 'Review warnings and recommendations above' : ''
    ];
    
    return $result;
}

function getUserSubscriptionHeaders(): array
{
    return [
        t('admin.reports.csv_headers.user_id'),
        t('admin.reports.csv_headers.email'),
        t('admin.reports.csv_headers.google_id'),
        t('admin.reports.csv_headers.plan'),
        t('admin.reports.csv_headers.stripe_customer_id'),
        t('admin.reports.csv_headers.stripe_subscription_id'),
        t('admin.reports.csv_headers.plan_expiration'),
        t('admin.reports.csv_headers.created_at')
    ];
}

function getStripeSyncHeaders(): array
{
    return ['Email', 'Customer ID', 'Subscription Status', 'Database Plan', 'Sync Status', 'Issues'];
}

function getGeneralUsersHeaders(): array
{
    return [
        t('admin.reports.csv_headers.user_id'),
        t('admin.reports.csv_headers.email'),
        t('admin.reports.csv_headers.name'),
        t('admin.reports.csv_headers.google_id'),
        t('admin.reports.csv_headers.plan'),
        t('admin.reports.csv_headers.role'),
        t('admin.reports.csv_headers.stripe_customer_id'),
        t('admin.reports.csv_headers.stripe_subscription_id'),
        t('admin.reports.csv_headers.total_links'),
        t('admin.reports.csv_headers.total_clicks'),
        t('admin.reports.csv_headers.last_login'),
        t('admin.reports.csv_headers.created_at')
    ];
}

function getCustomGmailHeaders(): array
{
    return [
        'Search Value',
        t('admin.reports.csv_headers.user_id'),
        t('admin.reports.csv_headers.email'),
        t('admin.reports.csv_headers.name'),
        t('admin.reports.csv_headers.google_id'),
        t('admin.reports.csv_headers.plan'),
        t('admin.reports.csv_headers.role'),
        t('admin.reports.csv_headers.stripe_customer_id'),
        t('admin.reports.csv_headers.stripe_subscription_id'),
        t('admin.reports.csv_headers.plan_expiration'),
        t('admin.reports.csv_headers.total_links'),
        t('admin.reports.csv_headers.total_clicks'),
        t('admin.reports.csv_headers.last_login'),
        t('admin.reports.csv_headers.created_at')
    ];
}

function getStripeSanityHeaders(): array
{
    return ['Section', 'Check', 'Status', 'Details', 'Recommendation'];
}

/**
 * Format large numbers as Excel text to prevent scientific notation
 * Uses Excel formula: ="value" to force text format
 */
function formatExcelText($value): string
{
    if ($value === null || $value === '' || $value === 'NOT FOUND') {
        return (string)$value;
    }
    
    // Convert to string and check if it's a numeric value
    $strValue = (string)$value;
    
    // Check if it's a numeric string (could be a large number)
    if (is_numeric($strValue) && strlen($strValue) > 10) {
        // Format as Excel text formula: ="value"
        return '="' . $strValue . '"';
    }
    
    return $strValue;
}

/**
 * Check if a field key should be formatted as Excel text
 */
function shouldFormatAsText(string $key): bool
{
    $textFields = [
        'user_id',
        'google_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'customer_id',
        'search_value' // Only if it's numeric
    ];
    
    return in_array($key, $textFields, true);
}

function saveCSV(string $filepath, array $headers, array $data): void
{
    $fp = fopen($filepath, 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($fp, $headers);
    
    // Create mapping from header index to data key
    // Assume headers are in the same order as data keys
    if (!empty($data)) {
        $dataKeys = array_keys($data[0]);
        $keyMapping = [];
        foreach ($headers as $index => $header) {
            $keyMapping[$index] = $dataKeys[$index] ?? '';
        }
    }
    
    // Write data
    foreach ($data as $row) {
        $csvRow = [];
        if (!empty($keyMapping)) {
            foreach ($keyMapping as $index => $key) {
                $value = $row[$key] ?? '';
                
                // Format large numbers as Excel text
                if (shouldFormatAsText($key)) {
                    $value = formatExcelText($value);
                }
                
                $csvRow[] = $value;
            }
        } else {
            // Fallback: use values in order (format any large numeric values)
            $csvRow = [];
            foreach (array_values($row) as $val) {
                // Check if value looks like a large number
                if (is_numeric($val) && strlen((string)$val) > 10) {
                    $csvRow[] = formatExcelText($val);
                } else {
                    $csvRow[] = $val;
                }
            }
        }
        fputcsv($fp, $csvRow);
    }
    
    fclose($fp);
}

