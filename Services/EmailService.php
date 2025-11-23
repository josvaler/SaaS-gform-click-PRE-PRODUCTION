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
        $this->mailer->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = env('SMTP_USERNAME');
        $this->mailer->Password = env('SMTP_PASSWORD');
        
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
        $this->mailer->setFrom(
            env('SMTP_FROM_EMAIL', 'noreply@gforms.click'),
            env('SMTP_FROM_NAME', 'GForms')
        );
        
        // Character encoding
        $this->mailer->CharSet = 'UTF-8';
        
        // Debug (set to 0 in production)
        $this->mailer->SMTPDebug = (int)env('SMTP_DEBUG', '0');
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
                error_log("Email send failed: {$this->mailer->ErrorInfo}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Email exception: {$e->getMessage()}");
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

