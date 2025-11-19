<?php
declare(strict_types=1);

namespace App\Services;

class QrCodeService
{
    private string $qrDirectory;
    private string $baseUrl;

    public function __construct(string $qrDirectory, string $baseUrl)
    {
        $this->qrDirectory = rtrim($qrDirectory, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
        
        // Create directory if it doesn't exist
        if (!is_dir($this->qrDirectory)) {
            mkdir($this->qrDirectory, 0755, true);
        }
    }

    public function generateQrCode(string $shortCode, string $filename = null): ?string
    {
        $url = $this->baseUrl . '/' . $shortCode;
        
        if ($filename === null) {
            $filename = $shortCode . '.png';
        }
        
        $filePath = $this->qrDirectory . '/' . $filename;
        
        // Use simple QR code generation (requires endroid/qr-code package)
        // For now, we'll create a placeholder that can be replaced with actual QR library
        try {
            // Check if endroid/qr-code is available
            if (class_exists('\Endroid\QrCode\QrCode')) {
                $qrCode = new \Endroid\QrCode\QrCode($url);
                $qrCode->setSize(300);
                $qrCode->setMargin(10);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                file_put_contents($filePath, $result->getString());
            } else {
                // Fallback: Use QR Server API (simple but requires internet)
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
                
                // Use curl if available, otherwise file_get_contents
                if (function_exists('curl_init')) {
                    $ch = curl_init($qrUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    $qrImage = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($qrImage === false || $httpCode !== 200) {
                        error_log('QR Code API failed: HTTP ' . $httpCode);
                        return null;
                    }
                } else {
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'follow_location' => true,
                        ]
                    ]);
                    $qrImage = @file_get_contents($qrUrl, false, $context);
                    
                    if ($qrImage === false) {
                        error_log('QR Code API failed: file_get_contents returned false');
                        return null;
                    }
                }
                
                // Ensure directory exists and is writable
                if (!is_dir($this->qrDirectory)) {
                    if (!mkdir($this->qrDirectory, 0755, true)) {
                        error_log('QR Code directory creation failed: ' . $this->qrDirectory);
                        return null;
                    }
                }
                
                if (!is_writable($this->qrDirectory)) {
                    error_log('QR Code directory not writable: ' . $this->qrDirectory);
                    return null;
                }
                
                // Write the file
                $bytesWritten = @file_put_contents($filePath, $qrImage);
                if ($bytesWritten === false) {
                    error_log('QR Code file write failed: ' . $filePath);
                    return null;
                }
                
                // Verify file was created
                if (!file_exists($filePath)) {
                    error_log('QR Code file was not created: ' . $filePath);
                    return null;
                }
            }
            
            return '/qr/' . $filename;
        } catch (\Throwable $e) {
            error_log('QR Code generation error: ' . $e->getMessage());
            return null;
        }
    }

    public function getQrCodePath(string $shortCode): string
    {
        return '/qr/' . $shortCode . '.png';
    }

    public function deleteQrCode(string $filename): bool
    {
        $filePath = $this->qrDirectory . '/' . basename($filename);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}

