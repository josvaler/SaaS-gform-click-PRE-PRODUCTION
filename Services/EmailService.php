<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        
        // SMTP Configuration
        $this->mailer->isSMTP();
        $smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
        $smtpUsername = env('SMTP_USERNAME', '');
        $smtpPassword = env('SMTP_PASSWORD', '');
        
        // Validate SMTP configuration
        if (empty($smtpUsername) || empty($smtpPassword)) {
            error_log("EmailService: Warning - SMTP credentials not configured. SMTP_USERNAME or SMTP_PASSWORD is empty.");
        }
        
        $this->mailer->Host = $smtpHost;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $smtpUsername;
        $this->mailer->Password = $smtpPassword;
        
        // Determine encryption based on port
        $port = (int)env('SMTP_PORT', '587');
        $this->mailer->Port = $port;
        
        // DreamHost typically uses port 465 with SSL or 587 with STARTTLS
        if ($port === 465) {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        } else {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        }
        
        // Additional SMTP options for better compatibility
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Sender
        $fromEmail = env('SMTP_FROM_EMAIL', 'noreply@gforms.click');
        $fromName = env('SMTP_FROM_NAME', 'GForms');
        $this->mailer->setFrom($fromEmail, $fromName);
        
        // Character encoding
        $this->mailer->CharSet = 'UTF-8';
        
        // Debug (set to 0 in production)
        $debugLevel = (int)env('SMTP_DEBUG', '0');
        $this->mailer->SMTPDebug = $debugLevel;
        
        if ($debugLevel > 0) {
            error_log("EmailService: SMTP Debug enabled (level {$debugLevel})");
            error_log("EmailService: Configuration - Host: {$smtpHost}, Port: {$port}, From: {$fromEmail}");
        }
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param bool $isHTML Whether body is HTML (default: true)
     * @param string|null $replyTo Reply-to email address
     * @return bool True on success, false on failure
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        bool $isHTML = true,
        ?string $replyTo = null
    ): bool {
        try {
            // Log email attempt
            $smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
            $smtpUser = env('SMTP_USERNAME', '');
            error_log("EmailService: Attempting to send email to {$to} via {$smtpHost} as {$smtpUser}");
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo);
            }
            
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $result = $this->mailer->send();
            
            if (!$result) {
                $errorInfo = $this->mailer->ErrorInfo ?? 'No error info available';
                error_log("EmailService: Send failed to {$to}");
                error_log("EmailService: Error details - {$errorInfo}");
                error_log("EmailService: SMTP Host: {$smtpHost}, Port: " . $this->mailer->Port);
                error_log("EmailService: SMTP Username set: " . (!empty($smtpUser) ? 'Yes' : 'No'));
                error_log("EmailService: SMTP Password set: " . (!empty(env('SMTP_PASSWORD', '')) ? 'Yes' : 'No'));
            } else {
                error_log("EmailService: Email sent successfully to {$to}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("EmailService: Exception sending email to {$to}");
            error_log("EmailService: Exception message - {$e->getMessage()}");
            error_log("EmailService: Exception trace - {$e->getTraceAsString()}");
            return false;
        }
    }
    
    /**
     * Send email with attachments
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $attachments Array of file paths to attach
     * @param bool $isHTML Whether body is HTML
     * @return bool True on success, false on failure
     */
    public function sendWithAttachments(
        string $to,
        string $subject,
        string $body,
        array $attachments = [],
        bool $isHTML = true
    ): bool {
        try {
            $this->mailer->clearAttachments();
            
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    $this->mailer->addAttachment($file);
                }
            }
            
            return $this->send($to, $subject, $body, $isHTML);
        } catch (Exception $e) {
            error_log("Email with attachments exception: {$e->getMessage()}");
            return false;
        }
    }
}

