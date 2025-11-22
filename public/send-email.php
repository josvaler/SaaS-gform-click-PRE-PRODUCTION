<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require_admin(); // Only admins can test

use App\Services\EmailService;

$emailService = new EmailService();
$user = session_user();

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['email'] ?? $user['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Test Email from GForms');
    $body = trim($_POST['body'] ?? '<h1>Gforms Support</h1><p>Please, replace this text with yours</p>');
    
    if (empty($to)) {
        $message = "❌ Please provide a recipient email address.";
        $messageType = 'error';
    } else {
        // Enable debug output for testing
        $debugOutput = '';
        if (env('SMTP_DEBUG', '0') !== '0') {
            ob_start();
        }
        
        $result = $emailService->send($to, $subject, $body);
        
        if (env('SMTP_DEBUG', '0') !== '0') {
            $debugOutput = ob_get_clean();
        }
        
        if ($result) {
            $message = "✅ Email sent successfully to {$to}!";
            $messageType = 'success';
        } else {
            $errorDetails = "❌ Email failed to send. Check error logs for details.";
            if (!empty($debugOutput)) {
                $errorDetails .= "\n\n<pre style='background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem;'>" . html($debugOutput) . "</pre>";
            }
            $message = $errorDetails;
            $messageType = 'error';
        }
    }
}
?>

<style>
.send-email-content {
    padding: 1.5rem;
    max-width: 100%;
    overflow-y: auto;
    height: 100%;
}

.send-email-content .alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.send-email-content .alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
}

.send-email-content .alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.send-email-content input,
.send-email-content textarea {
    width: 100%;
    padding: 0.875rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.5);
    color: #e2e8f0;
    font-size: 0.875rem;
    font-family: inherit;
}

.send-email-content input:focus,
.send-email-content textarea:focus {
    outline: none;
    border-color: #10b981;
}

.send-email-content label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: #cbd5e1;
}

.send-email-content .btn {
    padding: 0.875rem 2rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.send-email-content .btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
}

.send-email-content .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
}

.send-email-content .info-box {
    margin-top: 2rem;
    padding: 1rem;
    background: rgba(14, 165, 233, 0.1);
    border-radius: 0.5rem;
    border: 1px solid rgba(14, 165, 233, 0.3);
}

.send-email-content .info-box h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #60a5fa;
}

.send-email-content .info-box p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #94a3b8;
}
</style>

<div class="send-email-content">
    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div>
            <label>To:</label>
            <input 
                type="email" 
                name="email" 
                value="<?= html($user['email'] ?? '') ?>" 
                required
            >
        </div>
        
        <div>
            <label>Subject:</label>
            <input 
                type="text" 
                name="subject" 
                value="Test Email from GForms" 
                required
            >
        </div>
        
        <div>
            <label>Body (HTML):</label>
            <textarea 
                name="body" 
                rows="10" 
                required
            ><h1>Test Email</h1>
<p>GForms Support.</p>
<p>This is an emergency email you can contact us by mail to <a href="mailto:support@gforms.click">support@gforms.click</a></p></textarea>
        </div>
        
        <div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>Send Test Email
            </button>
        </div>
    </form>
    
    <div class="info-box">
        <h3>SMTP Configuration</h3>
        <div>
            <p><strong>Host:</strong> <?= html(env('SMTP_HOST', 'Not configured')) ?></p>
            <p><strong>Port:</strong> <?= html(env('SMTP_PORT', 'Not configured')) ?></p>
            <p><strong>Username:</strong> <?= html(env('SMTP_USERNAME', 'Not configured')) ?></p>
            <p><strong>From Email:</strong> <?= html(env('SMTP_FROM_EMAIL', 'Not configured')) ?></p>
            <p><strong>Debug Mode:</strong> <?= html(env('SMTP_DEBUG', '0')) ?></p>
        </div>
    </div>
</div>
