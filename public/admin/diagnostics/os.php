<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

require_once __DIR__ . '/../../../config/cache.php';

header('Content-Type: application/json; charset=utf-8');

// Helper function to execute shell command safely
function execCommand(string $command): ?string
{
    try {
        $output = @shell_exec($command . ' 2>&1');
        return $output ? trim($output) : null;
    } catch (Exception $e) {
        return null;
    }
}

// Helper function to get percentage color
function getPercentageColor(float $percentage): string
{
    if ($percentage < 70) return '#10b981'; // green
    if ($percentage < 90) return '#f59e0b'; // yellow
    return '#ef4444'; // red
}

try {
    $cacheKey = 'diagnostics_os';
    $ttl = 60; // 60 seconds cache
    
    // Try to get from cache
    $cached = cache_get($cacheKey, $ttl);
    $isCached = $cached !== null;
    
    if ($isCached) {
        echo json_encode([
            'success' => true,
            'cached' => true,
            'timestamp' => time(),
            'data' => $cached
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Generate fresh data
    $currentDate = date('Y-m-d H:i:s');
    
    // CPU Metrics
    $loadAverage = execCommand('uptime | awk -F\'load average:\' \'{print $2}\'');
    $cpuUsage = execCommand('mpstat 1 1 2>/dev/null | awk \'/Average/ {print "user="$3" system="$5" iowait="$6" steal="$7}\'');
    if (!$cpuUsage) {
        // Fallback to /proc/stat
        $stat1 = file_get_contents('/proc/stat');
        sleep(1);
        $stat2 = file_get_contents('/proc/stat');
        // Simple CPU calculation fallback
        $cpuUsage = 'user=0 system=0 iowait=0 steal=0';
    }
    
    // Parse CPU usage
    preg_match('/user=([\d.]+)/', $cpuUsage, $userMatch);
    preg_match('/system=([\d.]+)/', $cpuUsage, $systemMatch);
    preg_match('/iowait=([\d.]+)/', $cpuUsage, $iowaitMatch);
    preg_match('/steal=([\d.]+)/', $cpuUsage, $stealMatch);
    
    $cpuUser = isset($userMatch[1]) ? (float)$userMatch[1] : 0;
    $cpuSystem = isset($systemMatch[1]) ? (float)$systemMatch[1] : 0;
    $cpuIowait = isset($iowaitMatch[1]) ? (float)$iowaitMatch[1] : 0;
    $cpuSteal = isset($stealMatch[1]) ? (float)$stealMatch[1] : 0;
    $cpuTotal = $cpuUser + $cpuSystem + $cpuIowait + $cpuSteal;
    
    // Memory Metrics
    $freeOutput = execCommand('free -m');
    $memAvailable = execCommand('awk \'/MemAvailable/ {print int($2)}\' /proc/meminfo');
    $swapInfo = execCommand('swapon --show 2>/dev/null');
    
    // Parse memory
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $freeOutput ?? '', $memMatch);
    $memTotal = isset($memMatch[1]) ? (int)$memMatch[1] : 0;
    $memUsed = isset($memMatch[2]) ? (int)$memMatch[2] : 0;
    $memFree = isset($memMatch[3]) ? (int)$memMatch[3] : 0;
    $memUsagePercent = $memTotal > 0 ? ($memUsed / $memTotal) * 100 : 0;
    
    preg_match('/Swap:\s+(\d+)\s+(\d+)\s+(\d+)/', $freeOutput ?? '', $swapMatch);
    $swapTotal = isset($swapMatch[1]) ? (int)$swapMatch[1] : 0;
    $swapUsed = isset($swapMatch[2]) ? (int)$swapMatch[2] : 0;
    $swapFree = isset($swapMatch[3]) ? (int)$swapMatch[3] : 0;
    $swapUsagePercent = $swapTotal > 0 ? ($swapUsed / $swapTotal) * 100 : 0;
    
    // Disk Metrics
    $dfOutput = execCommand('df -h --output=source,pcent,size,used,avail | grep -v tmpfs');
    $dfInodes = execCommand('df -i | grep -v tmpfs');
    $diskIo = execCommand('iostat -dx 2>/dev/null | head -n 20');
    
    // Parse disk usage for main filesystems
    $diskData = [];
    if ($dfOutput) {
        $lines = explode("\n", $dfOutput);
        foreach ($lines as $line) {
            if (preg_match('/(\S+)\s+(\d+)%\s+(\S+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
                if ($matches[1] !== 'Filesystem' && strpos($matches[1], '/dev/') === 0) {
                    $diskData[] = [
                        'source' => $matches[1],
                        'usage' => (int)$matches[2],
                        'size' => $matches[3],
                        'used' => $matches[4],
                        'avail' => $matches[5]
                    ];
                }
            }
        }
    }
    
    // Network Metrics
    $gateway = execCommand('ip route | grep default | awk \'{print $3}\' | head -1');
    $pingResult = $gateway ? execCommand("ping -c 4 {$gateway} 2>/dev/null | tail -1") : null;
    $interfaceStats = execCommand('ip -s link 2>/dev/null');
    
    // Process Metrics
    $totalProcesses = execCommand('ps ax | wc -l');
    $zombies = execCommand('ps aux | awk \'{ if ($8=="Z") print $0 }\' | wc -l');
    $topCpu = execCommand('ps -eo pid,ppid,cmd,%cpu --sort=-%cpu | head -6');
    $topRam = execCommand('ps -eo pid,ppid,cmd,%mem --sort=-%mem | head -6');
    
    // Services Status
    $services = ['apache2', 'mysql', 'mariadb', 'ssh', 'netdata'];
    $serviceStatus = [];
    foreach ($services as $svc) {
        $isInstalled = execCommand("systemctl list-unit-files 2>/dev/null | grep -q '^{$svc}' && echo 'yes' || echo 'no'");
        if ($isInstalled === 'yes') {
            $status = execCommand("systemctl --no-pager is-active {$svc} 2>/dev/null");
            $serviceStatus[$svc] = [
                'installed' => true,
                'active' => $status === 'active',
                'status' => execCommand("systemctl --no-pager status {$svc} 2>/dev/null | head -8")
            ];
        } else {
            $serviceStatus[$svc] = ['installed' => false];
        }
    }
    
    // Uptime
    $uptime = execCommand('uptime -p');
    
    $data = [
        'current_date' => $currentDate,
        'cpu' => [
            'load_average' => $loadAverage,
            'user' => $cpuUser,
            'system' => $cpuSystem,
            'iowait' => $cpuIowait,
            'steal' => $cpuSteal,
            'total' => $cpuTotal,
            'total_percent' => min($cpuTotal, 100)
        ],
        'memory' => [
            'total' => $memTotal,
            'used' => $memUsed,
            'free' => $memFree,
            'available' => (int)$memAvailable,
            'usage_percent' => $memUsagePercent
        ],
        'swap' => [
            'total' => $swapTotal,
            'used' => $swapUsed,
            'free' => $swapFree,
            'usage_percent' => $swapUsagePercent,
            'info' => $swapInfo
        ],
        'disk' => [
            'filesystems' => $diskData,
            'inodes' => $dfInodes,
            'io' => $diskIo
        ],
        'network' => [
            'gateway' => $gateway,
            'ping_result' => $pingResult,
            'interface_stats' => $interfaceStats
        ],
        'processes' => [
            'total' => (int)$totalProcesses,
            'zombies' => (int)$zombies,
            'top_cpu' => $topCpu,
            'top_ram' => $topRam
        ],
        'services' => $serviceStatus,
        'uptime' => $uptime
    ];
    
    // Cache the results
    cache_set($cacheKey, $data, $ttl);
    
    echo json_encode([
        'success' => true,
        'cached' => false,
        'timestamp' => time(),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

