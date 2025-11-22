<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

/**
 * .env File Editor API
 * Handles reading and writing .env file with security measures:
 * - Backup creation with timestamp
 * - File locking (flock)
 * - Input sanitization
 * - Format validation
 */

$envFile = __DIR__ . '/../../../.env';
$action = $_POST['action'] ?? $_GET['action'] ?? 'read';

/**
 * Parse .env file into key-value pairs
 * Handles quoted values, spaces, and comments
 */
function parseEnvFile(string $content): array
{
    $lines = explode("\n", $content);
    $envData = [];
    $lineNumber = 0;
    
    foreach ($lines as $line) {
        $lineNumber++;
        $line = rtrim($line, "\r\n");
        $originalLine = $line;
        
        // Skip empty lines
        if (trim($line) === '') {
            $envData[] = [
                'key' => '',
                'value' => '',
                'original' => $originalLine,
                'line_number' => $lineNumber,
                'is_comment' => false,
                'is_empty' => true
            ];
            continue;
        }
        
        // Skip comments
        if (preg_match('/^\s*#/', $line)) {
            $envData[] = [
                'key' => '',
                'value' => '',
                'original' => $originalLine,
                'line_number' => $lineNumber,
                'is_comment' => true,
                'is_empty' => false
            ];
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') === false) {
            // Invalid line (no = sign)
            $envData[] = [
                'key' => '',
                'value' => '',
                'original' => $originalLine,
                'line_number' => $lineNumber,
                'is_comment' => false,
                'is_empty' => false,
                'is_invalid' => true
            ];
            continue;
        }
        
        // Split on first = sign
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1] ?? '');
        
        // Remove quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        $envData[] = [
            'key' => $key,
            'value' => $value,
            'original' => $originalLine,
            'line_number' => $lineNumber,
            'is_comment' => false,
            'is_empty' => false,
            'is_invalid' => false
        ];
    }
    
    return $envData;
}

/**
 * Reconstruct .env file content from parsed data
 */
function reconstructEnvFile(array $envData): string
{
    $lines = [];
    foreach ($envData as $item) {
        if ($item['is_empty'] || $item['is_comment'] || $item['is_invalid']) {
            $lines[] = $item['original'];
        } else {
            $key = $item['key'];
            $value = $item['value'];
            
            // Quote value if it contains spaces or special characters
            if (preg_match('/[\s=#"]/', $value)) {
                $value = '"' . str_replace('"', '\\"', $value) . '"';
            }
            
            $lines[] = $key . '=' . $value;
        }
    }
    
    return implode("\n", $lines);
}

/**
 * Read .env file safely
 */
function readEnvFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        // Return empty array if file doesn't exist (new installation)
        return [];
    }
    
    if (!is_readable($filePath)) {
        throw new Exception('File .env is not readable. Check file permissions.');
    }
    
    $content = @file_get_contents($filePath);
    if ($content === false) {
        throw new Exception('Failed to read .env file');
    }
    
    if (empty(trim($content))) {
        return [];
    }
    
    return parseEnvFile($content);
}

/**
 * Write .env file safely with backup and locking
 */
function writeEnvFile(string $filePath, string $content): array
{
    // Ensure directory exists
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            throw new Exception('Failed to create directory for .env file');
        }
    }
    
    // Validate content format (basic check)
    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        // Check for basic KEY=VALUE format
        if (strpos($line, '=') === false && !empty($line)) {
            throw new Exception("Invalid format at line " . ($lineNum + 1) . ": missing '=' sign");
        }
    }
    
    // Create backup only if file exists
    $backupPath = null;
    if (file_exists($filePath)) {
        $backupPath = $filePath . '.bak.' . date('Ymd_His');
        if (!@copy($filePath, $backupPath)) {
            throw new Exception('Failed to create backup file. Check file permissions.');
        }
    }
    
    // Write with file locking
    $fp = @fopen($filePath, 'c+');
    if ($fp === false) {
        throw new Exception('Failed to open .env file for writing. Check file permissions.');
    }
    
    // Acquire exclusive lock (wait up to 5 seconds)
    $lockAcquired = false;
    $attempts = 0;
    while ($attempts < 10) {
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $lockAcquired = true;
            break;
        }
        usleep(500000); // Wait 0.5 seconds
        $attempts++;
    }
    
    if (!$lockAcquired) {
        fclose($fp);
        throw new Exception('Failed to acquire file lock. Another process may be using the file.');
    }
    
    // Truncate file
    ftruncate($fp, 0);
    rewind($fp);
    
    // Write content
    $written = fwrite($fp, $content);
    if ($written === false) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new Exception('Failed to write to .env file');
    }
    
    // Ensure newline at end
    if (substr($content, -1) !== "\n") {
        fwrite($fp, "\n");
        $written++;
    }
    
    // Release lock and close
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return [
        'backup_path' => $backupPath,
        'bytes_written' => $written
    ];
}

/**
 * Sanitize input value
 */
function sanitizeValue(string $value): string
{
    // Remove null bytes
    $value = str_replace("\0", '', $value);
    // Trim whitespace
    $value = trim($value);
    return $value;
}

try {
    switch ($action) {
        case 'read':
            $envData = readEnvFile($envFile);
            echo json_encode([
                'success' => true,
                'data' => $envData
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'save_table':
            // Save from table mode (key-value pairs)
            $updates = json_decode($_POST['updates'] ?? '[]', true);
            if (!is_array($updates)) {
                throw new Exception('Invalid updates data');
            }
            
            // Read current file
            $envData = readEnvFile($envFile);
            
            // Apply updates
            foreach ($updates as $update) {
                $key = sanitizeValue($update['key'] ?? '');
                $newValue = sanitizeValue($update['value'] ?? '');
                
                if (empty($key)) {
                    continue;
                }
                
                // Find and update matching entry
                foreach ($envData as &$item) {
                    if ($item['key'] === $key && !$item['is_comment'] && !$item['is_empty']) {
                        $item['value'] = $newValue;
                        // Reconstruct original line
                        if (preg_match('/[\s=#"]/', $newValue)) {
                            $quotedValue = '"' . str_replace('"', '\\"', $newValue) . '"';
                        } else {
                            $quotedValue = $newValue;
                        }
                        $item['original'] = $key . '=' . $quotedValue;
                        break;
                    }
                }
                unset($item);
            }
            
            // Reconstruct file content
            $newContent = reconstructEnvFile($envData);
            
            // Write file
            $result = writeEnvFile($envFile, $newContent);
            
            echo json_encode([
                'success' => true,
                'message' => 'File saved successfully',
                'backup' => $result['backup_path']
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'save_editor':
            // Save from text editor mode
            $content = $_POST['content'] ?? '';
            if (empty($content)) {
                throw new Exception('Empty content provided');
            }
            
            // Sanitize content (remove null bytes)
            $content = str_replace("\0", '', $content);
            
            // Validate basic format
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') === false) {
                    throw new Exception("Invalid format at line " . ($lineNum + 1) . ": missing '=' sign");
                }
            }
            
            // Write file
            $result = writeEnvFile($envFile, $content);
            
            echo json_encode([
                'success' => true,
                'message' => 'File saved successfully',
                'backup' => $result['backup_path']
            ], JSON_PRETTY_PRINT);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

