<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pageTitle = 'Support';
$navLinksLeft = [
    ['label' => t('nav.home'), 'href' => '/'],
];
$navLinksRight = [];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 900px;">
        <div class="card">
            <div class="card-header">
                <h1>Support</h1>
                <p class="text-muted">We're here to help you</p>
            </div>
            <div style="padding: 2rem;">
                <div style="line-height: 1.8; color: var(--color-text);">
                    
                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">Contact Us</h2>
                    <p>If you need assistance with GForms ShortLinks, have questions, or encounter any issues, please don't hesitate to reach out to our support team.</p>
                    
                    <div style="background: rgba(17, 24, 39, 0.4); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin: 1.5rem 0;">
                        <h3 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1.2rem; color: var(--accent-primary);">Email Support</h3>
                        <p style="margin-bottom: 0.5rem;">
                            <strong>Support Email:</strong> 
                            <a href="mailto:support@gforms.click" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">support@gforms.click</a>
                        </p>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--color-text-muted);">
                            We typically respond within 24-48 hours during business days.
                        </p>
                    </div>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">Common Questions</h2>
                    
                    <div style="margin-top: 1.5rem;">
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">How do I create a shortlink?</h3>
                        <p>You can create shortlinks in two ways:</p>
                        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                            <li>Use our web interface at <a href="/create-link" style="color: var(--accent-primary);">gforms.click/create-link</a></li>
                            <li>Install our Chrome extension for quick access from your browser</li>
                        </ul>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">What types of URLs can I shorten?</h3>
                        <p>GForms ShortLinks is designed exclusively for Google Forms URLs. We support:</p>
                        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                            <li>docs.google.com/forms/ URLs</li>
                            <li>forms.gle/ URLs</li>
                        </ul>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">How do I view analytics for my shortlinks?</h3>
                        <p>After logging in, navigate to your dashboard or links page to view detailed analytics including click counts, geographic data, and more.</p>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">I'm having trouble with the Chrome extension</h3>
                        <p>If you're experiencing issues with the Chrome extension:</p>
                        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                            <li>Make sure you're logged in through the web interface first</li>
                            <li>Check that you've granted the necessary permissions</li>
                            <li>Try reloading the extension or restarting your browser</li>
                            <li>Contact support with details about the issue</li>
                        </ul>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">What are the quota limits?</h3>
                        <p>Quota limits vary by plan:</p>
                        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                            <li><strong>FREE:</strong> 10 links per day, 200 per month</li>
                            <li><strong>PREMIUM:</strong> Unlimited daily, 600 links per month</li>
                            <li><strong>ENTERPRISE:</strong> Unlimited links</li>
                        </ul>
                        <p style="margin-top: 0.5rem;">Visit our <a href="/pricing" style="color: var(--accent-primary);">pricing page</a> for more details.</p>
                    </div>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">Before Contacting Support</h2>
                    <p>To help us assist you more quickly, please include the following information in your email:</p>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>Your account email address</li>
                        <li>A detailed description of the issue or question</li>
                        <li>Steps to reproduce the problem (if applicable)</li>
                        <li>Screenshots or error messages (if available)</li>
                        <li>Your browser and operating system information</li>
                    </ul>

                    <div style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(45, 212, 191, 0.1)); border: 1px solid rgba(14, 165, 233, 0.3); border-radius: 0.75rem; padding: 1.5rem; margin: 2rem 0;">
                        <h3 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1.2rem;">Need Immediate Help?</h3>
                        <p style="margin-bottom: 0.5rem;">
                            Send us an email at <a href="mailto:support@gforms.click" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">support@gforms.click</a>
                        </p>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--color-text-muted);">
                            We're committed to providing you with the best support experience possible.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>


