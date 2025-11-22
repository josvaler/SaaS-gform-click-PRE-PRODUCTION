<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

/**
 * .htaccess File Editor API
 * Handles reading and writing .htaccess file with security measures:
 * - Backup creation with timestamp
 * - File locking (flock)
 * - Input sanitization
 */

$htaccessFile = __DIR__ . '/../../../public/.htaccess';
$action = $_POST['action'] ?? $_GET['action'] ?? 'read';

/**
 * Parse .htaccess file into line-by-line data
 * Handles comments, empty lines, and directives
 */
function parseHtaccessFile(string $content): array
{
    $lines = explode("\n", $content);
    $htaccessData = [];
    $lineNumber = 0;
    
    foreach ($lines as $line) {
        $lineNumber++;
        $line = rtrim($line, "\r\n");
        $originalLine = $line;
        
        // Skip empty lines
        if (trim($line) === '') {
            $htaccessData[] = [
                'content' => '',
                'original' => $originalLine,
                'line_number' => $lineNumber,
                'is_comment' => false,
                'is_empty' => true
            ];
            continue;
        }
        
        // Check for comments
        if (preg_match('/^\s*#/', $line)) {
            $htaccessData[] = [
                'content' => $line,
                'original' => $originalLine,
                'line_number' => $lineNumber,
                'is_comment' => true,
                'is_empty' => false
            ];
            continue;
        }
        
        // Regular directive line
        $htaccessData[] = [
            'content' => $line,
            'original' => $originalLine,
            'line_number' => $lineNumber,
            'is_comment' => false,
            'is_empty' => false
        ];
    }
    
    return $htaccessData;
}

/**
 * Reconstruct .htaccess file content from parsed data
 */
function reconstructHtaccessFile(array $htaccessData): string
{
    $lines = [];
    foreach ($htaccessData as $item) {
        $lines[] = $item['original'];
    }
    
    return implode("\n", $lines);
}

/**
 * Read .htaccess file safely
 */
function readHtaccessFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        // Return empty array if file doesn't exist
        return [];
    }
    
    if (!is_readable($filePath)) {
        throw new Exception('File .htaccess is not readable. Check file permissions.');
    }
    
    $content = @file_get_contents($filePath);
    if ($content === false) {
        throw new Exception('Failed to read .htaccess file');
    }
    
    if (empty(trim($content))) {
        return [];
    }
    
    return parseHtaccessFile($content);
}

/**
 * Write .htaccess file safely with backup and locking
 */
function writeHtaccessFile(string $filePath, string $content): array
{
    // Ensure directory exists
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            throw new Exception('Failed to create directory for .htaccess file');
        }
    }
    
    // Basic validation - check for common .htaccess directives
    // We don't enforce strict format since .htaccess can have various Apache directives
    
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
        throw new Exception('Failed to open .htaccess file for writing. Check file permissions.');
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
        throw new Exception('Failed to write to .htaccess file');
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
            $htaccessData = readHtaccessFile($htaccessFile);
            echo json_encode([
                'success' => true,
                'data' => $htaccessData
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'save_table':
            // Save from table mode (line-by-line updates)
            $updates = json_decode($_POST['updates'] ?? '[]', true);
            if (!is_array($updates)) {
                throw new Exception('Invalid updates data');
            }
            
            // Read current file
            $htaccessData = readHtaccessFile($htaccessFile);
            
            // Apply updates
            foreach ($updates as $update) {
                $lineNumber = (int)($update['line_number'] ?? 0);
                $newContent = sanitizeValue($update['content'] ?? '');
                
                if ($lineNumber < 1 || $lineNumber > count($htaccessData)) {
                    continue;
                }
                
                // Update the line (0-indexed)
                $htaccessData[$lineNumber - 1]['content'] = $newContent;
                $htaccessData[$lineNumber - 1]['original'] = $newContent;
            }
            
            // Reconstruct file content
            $newContent = reconstructHtaccessFile($htaccessData);
            
            // Write file
            $result = writeHtaccessFile($htaccessFile, $newContent);
            
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
            
            // Write file
            $result = writeHtaccessFile($htaccessFile, $content);
            
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

