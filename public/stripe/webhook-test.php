<?php
/**
 * Simple test endpoint to verify webhook accessibility
 * Visit: https://gforms.click/stripe/webhook-test
 */
header('Content-Type: text/plain');
echo "Webhook Test Endpoint\n";
echo "====================\n\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN') . "\n";
echo "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n";
echo "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN') . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'YES' : 'NO') . "\n";
echo "\n";
echo "If you can see this, the endpoint is accessible.\n";
echo "The webhook endpoint should be: https://gforms.click/stripe/webhook\n";

