<?php
/**
 * Delete User by Email Script
 * 
 * This script deletes all data associated with a user by their email address.
 * 
 * Usage:
 *   php scripts/delete_user_by_email.php jose.luis.valerio@gmail.com
 * 
 * Or edit the $email variable below to hardcode the email.
 * 
 * WARNING: This operation is IRREVERSIBLE!
 */

declare(strict_types=1);

// Set the email to delete (or pass as command line argument)
$email = $argv[1] ?? 'jose.luis.valerio@gmail.com';

// Load bootstrap
require __DIR__ . '/../config/bootstrap.php';

// Color output for terminal
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
];

function colorize(string $text, string $color): string
{
    global $colors;
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

echo colorize("\n" . str_repeat("=", 70) . "\n", 'cyan');
echo colorize("  DELETE USER BY EMAIL SCRIPT\n", 'bold');
echo colorize(str_repeat("=", 70) . "\n\n", 'cyan');

try {
    $pdo = db();
    
    // Normalize email (lowercase, trim)
    $email = strtolower(trim($email));
    
    if (empty($email)) {
        throw new RuntimeException("Email cannot be empty");
    }
    
    echo colorize("Searching for user with email: ", 'blue') . colorize($email, 'bold') . "\n\n";
    
    // Find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo colorize("❌ User not found with email: $email\n", 'red');
        exit(1);
    }
    
    $userId = (int)$user['id'];
    $userEmail = $user['email'] ?? 'N/A';
    $userName = $user['name'] ?? 'N/A';
    $googleId = $user['google_id'] ?? null;
    
    echo colorize("✅ User found:\n", 'green');
    echo "   ID: $userId\n";
    echo "   Email: $userEmail\n";
    echo "   Name: $userName\n";
    echo "   Google ID: " . ($googleId ?: 'N/A') . "\n";
    echo "   Plan: " . ($user['plan'] ?? 'N/A') . "\n";
    echo "   Created: " . ($user['created_at'] ?? 'N/A') . "\n\n";
    
    // Count related data
    echo colorize("Counting related data to be deleted...\n", 'yellow');
    
    $counts = [];
    
    // Count operations
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM operations WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $counts['operations'] = (int)$stmt->fetch()['count'];
    
    // Count short links
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM short_links WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $counts['short_links'] = (int)$stmt->fetch()['count'];
    
    // Count clicks (via short links)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM clicks c
        INNER JOIN short_links sl ON c.short_link_id = sl.id
        WHERE sl.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $counts['clicks'] = (int)$stmt->fetch()['count'];
    
    // Count quota_daily
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quota_daily WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $counts['quota_daily'] = (int)$stmt->fetch()['count'];
    
    // Count quota_monthly
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quota_monthly WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $counts['quota_monthly'] = (int)$stmt->fetch()['count'];
    
    // Count login logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_login_logs WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $counts['login_logs'] = (int)$stmt->fetch()['count'];
    
    // Get QR code paths before deletion
    $stmt = $pdo->prepare("SELECT qr_code_path FROM short_links WHERE user_id = :user_id AND qr_code_path IS NOT NULL");
    $stmt->execute(['user_id' => $userId]);
    $qrCodePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n" . colorize("Data to be deleted:\n", 'yellow');
    echo "   - User record: 1\n";
    echo "   - Operations: {$counts['operations']}\n";
    echo "   - Short links: {$counts['short_links']}\n";
    echo "   - Clicks: {$counts['clicks']}\n";
    echo "   - Daily quotas: {$counts['quota_daily']}\n";
    echo "   - Monthly quotas: {$counts['quota_monthly']}\n";
    echo "   - Login logs: {$counts['login_logs']}\n";
    echo "   - QR code files: " . count($qrCodePaths) . "\n";
    
    $totalRecords = 1 + array_sum($counts);
    echo "\n" . colorize("Total records to delete: $totalRecords\n", 'bold');
    
    // Confirmation (skip if running non-interactively)
    if (posix_isatty(STDIN)) {
        echo "\n" . colorize("⚠️  WARNING: This operation is IRREVERSIBLE!\n", 'red');
        echo colorize("Type 'DELETE' to confirm: ", 'yellow');
        $confirmation = trim(fgets(STDIN));
        
        if ($confirmation !== 'DELETE') {
            echo colorize("\n❌ Deletion cancelled.\n", 'red');
            exit(0);
        }
    } else {
        echo colorize("\n⚠️  Non-interactive mode: Proceeding with deletion...\n", 'yellow');
    }
    
    echo "\n" . colorize("Deleting user and all related data...\n", 'yellow');
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete QR code files first (before database deletion)
        $deletedFiles = 0;
        $failedFiles = 0;
        foreach ($qrCodePaths as $qrPath) {
            if (!empty($qrPath)) {
                $fullPath = __DIR__ . '/../public' . $qrPath;
                if (file_exists($fullPath)) {
                    if (@unlink($fullPath)) {
                        $deletedFiles++;
                    } else {
                        $failedFiles++;
                        error_log("Failed to delete QR code file: $fullPath");
                    }
                }
            }
        }
        
        if ($deletedFiles > 0 || $failedFiles > 0) {
            echo "   QR code files: $deletedFiles deleted, $failedFiles failed\n";
        }
        
        // Delete user (CASCADE will delete all related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        
        $deleted = $stmt->rowCount();
        
        if ($deleted === 0) {
            throw new RuntimeException("User was not deleted. It may have been deleted already.");
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo colorize("\n✅ Successfully deleted user and all related data!\n", 'green');
        echo "\nSummary:\n";
        echo "   - User ID: $userId\n";
        echo "   - Email: $userEmail\n";
        echo "   - Total records deleted: $totalRecords\n";
        echo "   - QR code files deleted: $deletedFiles\n";
        if ($failedFiles > 0) {
            echo colorize("   - QR code files failed: $failedFiles (check error log)\n", 'yellow');
        }
        
        // Log the deletion
        error_log("User deleted by script: email=$userEmail, user_id=$userId, total_records=$totalRecords");
        
        echo "\n" . colorize("Deletion complete!\n", 'green');
        
    } catch (Throwable $e) {
        // Rollback on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo colorize("\n❌ Database error: " . $e->getMessage() . "\n", 'red');
    exit(1);
} catch (Throwable $e) {
    echo colorize("\n❌ Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}

echo "\n";

