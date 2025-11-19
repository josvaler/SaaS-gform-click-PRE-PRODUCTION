<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Session Test ===\n\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "\n";

// Test storing and retrieving
if (isset($_GET['set'])) {
    $_SESSION['test_value'] = $_GET['set'];
    echo "Set session value: " . $_GET['set'] . "\n";
    echo "Session saved.\n\n";
    echo "Visit: " . $_SERVER['REQUEST_URI'] . "?get=1\n";
} elseif (isset($_GET['get'])) {
    $value = $_SESSION['test_value'] ?? 'NOT SET';
    echo "Retrieved session value: " . $value . "\n";
    echo "\n";
    echo "Session data:\n";
    print_r($_SESSION);
} else {
    echo "Usage:\n";
    echo "  Set: ?set=test123\n";
    echo "  Get: ?get=1\n";
    echo "\n";
    echo "Current session data:\n";
    print_r($_SESSION);
}

