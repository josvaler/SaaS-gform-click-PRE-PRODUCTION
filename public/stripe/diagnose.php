<?php
/**
 * Stripe Configuration Diagnostic Tool
 * 
 * Visit: https://gforms.click/stripe/diagnose
 * Or run: php public/stripe/diagnose.php
 * 
 * This script helps diagnose Stripe connection issues
 */

require __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stripe Configuration Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .check { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Stripe Configuration Diagnostic</h1>
    
    <?php
    $issues = [];
    $warnings = [];
    
    // Check 1: Stripe SDK
    echo "<h2>1. Stripe PHP SDK</h2>";
    if (class_exists('Stripe\StripeClient')) {
        echo "<div class='check success'>✅ Stripe SDK is installed</div>";
        try {
            $reflection = new ReflectionClass('Stripe\StripeClient');
            echo "<div class='check info'>SDK Version: " . ($reflection->getConstant('VERSION') ?? 'Unknown') . "</div>";
        } catch (Exception $e) {
            echo "<div class='check warning'>Could not determine SDK version</div>";
        }
    } else {
        echo "<div class='check error'>❌ Stripe SDK is NOT installed</div>";
        echo "<div class='check info'>Run: <code>composer require stripe/stripe-php</code></div>";
        $issues[] = "Stripe SDK not installed";
    }
    
    // Check 2: Environment Variables
    echo "<h2>2. Environment Variables</h2>";
    
    $requiredVars = [
        'STRIPE_SECRET_KEY' => 'Secret API Key',
        'STRIPE_PUBLISHABLE_KEY' => 'Publishable Key',
        'STRIPE_PRICE_ID' => 'Monthly Price ID',
        'STRIPE_WEBHOOK_SECRET' => 'Webhook Secret',
    ];
    
    foreach ($requiredVars as $var => $label) {
        $value = env($var, '');
        if (empty($value)) {
            echo "<div class='check error'>❌ $label ($var) is NOT set</div>";
            $issues[] = "$var not configured";
        } else {
            // Mask sensitive values
            $displayValue = $var === 'STRIPE_SECRET_KEY' || $var === 'STRIPE_WEBHOOK_SECRET' 
                ? substr($value, 0, 7) . '...' . substr($value, -4)
                : $value;
            
            // Check for placeholder values
            if (strpos($value, 'xxx') !== false || strpos($value, 'test_xxx') !== false || strpos($value, 'live_xxx') !== false) {
                echo "<div class='check warning'>⚠️ $label ($var) appears to be a placeholder: <code>$displayValue</code></div>";
                $warnings[] = "$var appears to be placeholder";
            } else {
                echo "<div class='check success'>✅ $label ($var) is set: <code>$displayValue</code></div>";
            }
        }
    }
    
    // Check 3: Stripe Configuration
    echo "<h2>3. Stripe Configuration</h2>";
    
    $stripeConfig = require __DIR__ . '/../../config/stripe.php';
    
    if (empty($stripeConfig['secret_key'])) {
        echo "<div class='check error'>❌ Secret key is empty in configuration</div>";
        $issues[] = "Secret key empty in config";
    } else {
        $secretKey = $stripeConfig['secret_key'];
        if (strpos($secretKey, 'sk_') !== 0) {
            echo "<div class='check error'>❌ Secret key format is invalid (should start with 'sk_')</div>";
            $issues[] = "Invalid secret key format";
        } else {
            echo "<div class='check success'>✅ Secret key format is valid</div>";
        }
    }
    
    if (empty($stripeConfig['price_id'])) {
        echo "<div class='check error'>❌ Monthly price ID is not set</div>";
        $issues[] = "Monthly price ID not set";
    } else {
        echo "<div class='check success'>✅ Monthly price ID: <code>" . htmlspecialchars($stripeConfig['price_id']) . "</code></div>";
    }
    
    // Check 4: API Connection Test
    echo "<h2>4. API Connection Test</h2>";
    
    if (class_exists('Stripe\StripeClient') && !empty($stripeConfig['secret_key'])) {
        try {
            $stripe = new \Stripe\StripeClient($stripeConfig['secret_key']);
            
            // Try a simple API call
            $account = $stripe->accounts->retrieve();
            
            echo "<div class='check success'>✅ Successfully connected to Stripe API</div>";
            echo "<div class='check info'>Account ID: <code>" . htmlspecialchars($account->id ?? 'N/A') . "</code></div>";
            echo "<div class='check info'>Account Type: <code>" . htmlspecialchars($account->type ?? 'N/A') . "</code></div>";
            
        } catch (\Stripe\Exception\AuthenticationException $e) {
            echo "<div class='check error'>❌ Authentication failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='check info'>Your STRIPE_SECRET_KEY may be invalid or expired</div>";
            $issues[] = "Stripe authentication failed";
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            echo "<div class='check error'>❌ Could not connect to Stripe API: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='check info'>Check your network connection and firewall settings</div>";
            $issues[] = "Cannot connect to Stripe API";
        } catch (\Stripe\Exception\RateLimitException $e) {
            echo "<div class='check warning'>⚠️ Rate limit exceeded: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='check info'>Wait a moment and try again</div>";
            $warnings[] = "Rate limit exceeded";
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            echo "<div class='check error'>❌ Invalid request: " . htmlspecialchars($e->getMessage()) . "</div>";
            $issues[] = "Invalid Stripe request";
        } catch (Throwable $e) {
            echo "<div class='check error'>❌ Unexpected error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='check info'>Error type: " . get_class($e) . "</div>";
            $issues[] = "Unexpected error: " . get_class($e);
        }
    } else {
        echo "<div class='check warning'>⚠️ Cannot test API connection (SDK or secret key missing)</div>";
    }
    
    // Check 5: Network Connectivity
    echo "<h2>5. Network Connectivity</h2>";
    
    $stripeHosts = [
        'api.stripe.com' => 'Stripe API',
        'js.stripe.com' => 'Stripe.js',
    ];
    
    foreach ($stripeHosts as $host => $label) {
        $connection = @fsockopen($host, 443, $errno, $errstr, 5);
        if ($connection) {
            echo "<div class='check success'>✅ Can reach $label ($host:443)</div>";
            fclose($connection);
        } else {
            echo "<div class='check error'>❌ Cannot reach $label ($host:443) - Error: $errstr ($errno)</div>";
            $issues[] = "Cannot reach $host";
        }
    }
    
    // Summary
    echo "<h2>📊 Summary</h2>";
    
    if (empty($issues) && empty($warnings)) {
        echo "<div class='check success'><strong>✅ All checks passed! Stripe should be working correctly.</strong></div>";
    } else {
        if (!empty($issues)) {
            echo "<div class='check error'><strong>❌ Found " . count($issues) . " issue(s):</strong></div>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>" . htmlspecialchars($issue) . "</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($warnings)) {
            echo "<div class='check warning'><strong>⚠️ Found " . count($warnings) . " warning(s):</strong></div>";
            echo "<ul>";
            foreach ($warnings as $warning) {
                echo "<li>" . htmlspecialchars($warning) . "</li>";
            }
            echo "</ul>";
        }
        
        echo "<div class='check info'><strong>💡 Next Steps:</strong></div>";
        echo "<ol>";
        if (in_array("Stripe SDK not installed", $issues)) {
            echo "<li>Install Stripe SDK: <code>composer require stripe/stripe-php</code></li>";
        }
        if (in_array("STRIPE_SECRET_KEY not configured", $issues) || in_array("Secret key empty in config", $issues)) {
            echo "<li>Set STRIPE_SECRET_KEY in your environment variables or .env file</li>";
        }
        if (in_array("Cannot connect to Stripe API", $issues)) {
            echo "<li>Check firewall settings - ensure outbound connections to api.stripe.com:443 are allowed</li>";
            echo "<li>Check if CrowdSec or other security software is blocking Stripe IPs</li>";
        }
        if (in_array("Stripe authentication failed", $issues)) {
            echo "<li>Verify your STRIPE_SECRET_KEY is correct in Stripe Dashboard</li>";
            echo "<li>Ensure you're using the correct key for test/live mode</li>";
        }
        echo "</ol>";
    }
    
    echo "<hr>";
    echo "<div class='check info'>Check the error logs at: <code>gforms_error.log</code> for detailed error messages</div>";
    ?>
</body>
</html>

