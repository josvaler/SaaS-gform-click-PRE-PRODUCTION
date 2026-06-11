# GForms Email Setup Guide

Complete guide to enable email functionality on your server for the GForms application. This is essential for the Forms System and other notification features.

## Table of Contents

1. [Overview](#overview)
2. [Option 1: Postfix (Local Mail Server)](#option-1-postfix-local-mail-server)
3. [Option 2: SMTP with PHPMailer (Recommended)](#option-2-smtp-with-phpmailer-recommended)
4. [Option 3: SendGrid (Production)](#option-3-sendgrid-production)
5. [Implementation Steps](#implementation-steps)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Security Considerations](#security-considerations)

---

## Overview

The GForms application needs email functionality for:
- **Forms System**: Send form submission notifications
- **User Notifications**: Welcome emails, password resets, etc.
- **Admin Alerts**: System notifications, error reports
- **Subscription Emails**: Plan upgrades, billing reminders

### Current Status
- ❌ No email functionality currently implemented
- ❌ No email service configured
- ❌ PHPMailer not installed

---

## Option 1: Postfix (Local Mail Server)

**Best for:** Development, testing, small deployments

### Step 1: Install Postfix

```bash
sudo apt update
sudo apt install postfix mailutils
```

During installation, you'll be prompted:
- **General type of mail configuration**: Select **"Internet Site"**
- **System mail name**: Enter your domain (e.g., `gforms.click`)

### Step 2: Configure Postfix

Edit the main configuration file:
```bash
sudo nano /etc/postfix/main.cf
```

Ensure these settings are present:
```conf
myhostname = gforms.click
mydomain = gforms.click
myorigin = $mydomain
inet_interfaces = all
inet_protocols = ipv4
```

### Step 3: Restart Postfix

```bash
sudo systemctl restart postfix
sudo systemctl enable postfix
```

### Step 4: Test Basic Mail

```bash
echo "Test email from GForms" | mail -s "Test Subject" your-email@gmail.com
```

Check mail logs:
```bash
sudo tail -f /var/log/mail.log
```

### Limitations
- Emails may go to spam folders
- Requires proper DNS/SPF records for production
- Not recommended for production without proper configuration

---

## Option 2: SMTP with PHPMailer (Recommended)

**Best for:** Production, reliable delivery, better deliverability

### Step 1: Install PHPMailer

```bash
cd /var/www/gforms.click
composer require phpmailer/phpmailer
```

### Step 2: Configure Gmail SMTP

#### 2.1 Enable 2-Step Verification
1. Go to https://myaccount.google.com/security
2. Enable **2-Step Verification** if not already enabled

#### 2.2 Generate App Password
1. Go to https://myaccount.google.com/apppasswords
2. Select **"Mail"** and **"Other (Custom name)"**
3. Enter name: "GForms Application"
4. Click **"Generate"**
5. **Copy the 16-character password** (you'll need this)

### Step 3: Create Email Service

Create the file: `Services/EmailService.php`

```php
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
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = (int)env('SMTP_PORT', '587');
        
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
```

### Step 4: Add Environment Variables

Add to your `.env` file:

```bash
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-character-app-password
SMTP_FROM_EMAIL=noreply@gforms.click
SMTP_FROM_NAME=GForms
SMTP_DEBUG=0
```

**Important:** 
- Use the **App Password** (16 characters), not your regular Gmail password
- Set `SMTP_DEBUG=0` in production
- Never commit `.env` with real credentials

### Step 5: Alternative SMTP Providers

#### Outlook/Office 365
```bash
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
```

#### Yahoo Mail
```bash
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_USERNAME=your-email@yahoo.com
SMTP_PASSWORD=your-app-password
```

---

## Option 3: SendGrid (Production)

**Best for:** High-volume production, best deliverability

### Step 1: Sign Up for SendGrid

1. Go to https://sendgrid.com
2. Create a free account (100 emails/day free)
3. Verify your email address

### Step 2: Create API Key

1. Go to **Settings** → **API Keys**
2. Click **"Create API Key"**
3. Name: "GForms Application"
4. Permissions: **"Full Access"** (or restrict to Mail Send)
5. Click **"Create & View"**
6. **Copy the API key** (shown only once!)

### Step 3: Configure EmailService

Modify `EmailService.php` constructor for SendGrid:

```php
$this->mailer->Host = 'smtp.sendgrid.net';
$this->mailer->Username = 'apikey';  // Literally "apikey"
$this->mailer->Password = env('SENDGRID_API_KEY');  // Your API key
$this->mailer->Port = 587;
```

### Step 4: Environment Variables

```bash
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=your-sendgrid-api-key-here
SMTP_FROM_EMAIL=noreply@gforms.click
SMTP_FROM_NAME=GForms
```

### Step 5: Verify Sender Domain (Recommended)

1. Go to **Settings** → **Sender Authentication**
2. Click **"Authenticate Your Domain"**
3. Follow DNS configuration steps
4. This improves deliverability significantly

---

## Implementation Steps

### Step 1: Choose Your Option

- **Development/Testing**: Option 1 (Postfix)
- **Production (Low Volume)**: Option 2 (Gmail SMTP)
- **Production (High Volume)**: Option 3 (SendGrid)

### Step 2: Install Dependencies

```bash
cd /var/www/gforms.click
composer require phpmailer/phpmailer
```

### Step 3: Create Email Service

Create `Services/EmailService.php` (see code above)

### Step 4: Update Configuration

Add SMTP settings to `.env` file

### Step 5: Create Email Config File (Optional)

Create `config/email.php`:

```php
<?php
declare(strict_types=1);

return [
    'smtp' => [
        'host' => env('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int)env('SMTP_PORT', '587'),
        'username' => env('SMTP_USERNAME'),
        'password' => env('SMTP_PASSWORD'),
        'encryption' => env('SMTP_ENCRYPTION', 'tls'),
        'debug' => (int)env('SMTP_DEBUG', '0'),
    ],
    'from' => [
        'email' => env('SMTP_FROM_EMAIL', 'noreply@gforms.click'),
        'name' => env('SMTP_FROM_NAME', 'GForms'),
    ],
];
```

### Step 6: Test Email Functionality

Create `public/test-email.php`:

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require_admin(); // Only admins can test

use App\Services\EmailService;

$emailService = new EmailService();
$user = session_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['email'] ?? $user['email'];
    $subject = $_POST['subject'] ?? 'Test Email from GForms';
    $body = $_POST['body'] ?? '<h1>Test Email</h1><p>This is a test email from your GForms application.</p>';
    
    $result = $emailService->send($to, $subject, $body);
    
    if ($result) {
        $message = "✅ Email sent successfully to {$to}!";
        $messageType = 'success';
    } else {
        $message = "❌ Email failed to send. Check error logs.";
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; }
        form { margin-top: 20px; }
        label { display: block; margin: 10px 0 5px; }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Email Test Tool</h1>
    
    <?php if (isset($message)): ?>
        <div class="<?= $messageType ?>"><?= html($message) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <label>To:</label>
        <input type="email" name="email" value="<?= html($user['email'] ?? '') ?>" required>
        
        <label>Subject:</label>
        <input type="text" name="subject" value="Test Email from GForms" required>
        
        <label>Body (HTML):</label>
        <textarea name="body" rows="10" required><h1>Test Email</h1><p>This is a test email from your GForms application.</p></textarea>
        
        <button type="submit">Send Test Email</button>
    </form>
</body>
</html>
```

**Access:** `https://yourdomain.com/test-email` (admin only)

---

## Testing

### Test 1: Basic Email Send

```php
use App\Services\EmailService;

$emailService = new EmailService();
$result = $emailService->send(
    to: 'test@example.com',
    subject: 'Test Email',
    body: '<h1>Hello</h1><p>This is a test.</p>'
);

var_dump($result); // Should return true
```

### Test 2: Check Error Logs

```bash
tail -f /var/www/gforms.click/error.log
# or
tail -f /var/log/mail.log
```

### Test 3: Verify Email Delivery

- Check recipient's inbox
- Check spam folder
- Verify email content renders correctly

---

## Troubleshooting

### Issue: "SMTP connect() failed"

**Solutions:**
1. Check firewall allows outbound port 587
2. Verify SMTP credentials are correct
3. For Gmail: Ensure App Password is used (not regular password)
4. Check if 2-Step Verification is enabled

### Issue: "Authentication failed"

**Solutions:**
1. Verify username/password are correct
2. For Gmail: Use App Password, not account password
3. Check if "Less secure app access" is enabled (older Gmail accounts)
4. Verify SMTP host and port are correct

### Issue: "Emails going to spam"

**Solutions:**
1. Set up SPF records in DNS
2. Set up DKIM signing
3. Use verified sender domain (SendGrid)
4. Avoid spam trigger words in subject/body
5. Include unsubscribe link

### Issue: "Connection timeout"

**Solutions:**
1. Check server can reach SMTP server: `telnet smtp.gmail.com 587`
2. Verify firewall rules
3. Check if port 587 is blocked
4. Try alternative port (465 for SSL)

### Issue: "PHPMailer class not found"

**Solutions:**
1. Run `composer install` or `composer require phpmailer/phpmailer`
2. Check autoloader is included: `require __DIR__ . '/../vendor/autoload.php'`
3. Verify namespace: `use PHPMailer\PHPMailer\PHPMailer;`

---

## Security Considerations

### 1. Protect Credentials

- ✅ Never commit `.env` file to git
- ✅ Use App Passwords, not regular passwords
- ✅ Rotate credentials periodically
- ✅ Use environment variables, not hardcoded values

### 2. Rate Limiting

Implement rate limiting for email sending:

```php
// Example: Limit to 10 emails per hour per user
$rateLimit = checkEmailRateLimit($userId);
if ($rateLimit['exceeded']) {
    return false;
}
```

### 3. Input Validation

Always validate email addresses:

```php
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email address');
}
```

### 4. Sanitize Content

Sanitize email content to prevent injection:

```php
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
```

### 5. Error Handling

Don't expose sensitive information in error messages:

```php
// Bad
echo "SMTP Error: Password incorrect";

// Good
error_log("SMTP authentication failed");
echo "Email service temporarily unavailable";
```

---

## Next Steps

After email is configured:

1. ✅ Test email functionality
2. ✅ Integrate into Forms System
3. ✅ Add email notifications for form submissions
4. ✅ Set up welcome emails for new users
5. ✅ Configure admin alert emails
6. ✅ Add email templates system

---

## Additional Resources

- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
- [Gmail App Passwords](https://support.google.com/accounts/answer/185833)
- [SendGrid Documentation](https://docs.sendgrid.com/)
- [Postfix Configuration](http://www.postfix.org/documentation.html)

---

## Checklist

- [ ] Choose email solution (Postfix/SMTP/SendGrid)
- [ ] Install PHPMailer: `composer require phpmailer/phpmailer`
- [ ] Create `Services/EmailService.php`
- [ ] Add SMTP configuration to `.env`
- [ ] Create test email script
- [ ] Test email sending
- [ ] Verify email delivery
- [ ] Check error logs
- [ ] Set up SPF/DKIM records (production)
- [ ] Implement rate limiting
- [ ] Add email templates
- [ ] Integrate with Forms System

---

**Last Updated:** 2025-01-XX  
**Status:** Ready for Implementation

