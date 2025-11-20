<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pageTitle = 'Terms and Conditions';
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
                <h1>Terms and Conditions</h1>
                <p class="text-muted">Last updated: <?= date('F j, Y') ?></p>
            </div>
            <div style="padding: 2rem;">
                <div style="line-height: 1.8; color: var(--color-text);">
                    
                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">1. Acceptance of Terms</h2>
                    <p>By accessing and using GForms ShortLinks ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">2. Service Description</h2>
                    <p>GForms ShortLinks is a URL shortening service exclusively designed for Google Forms links. The Service allows users to create short, memorable links that redirect to Google Forms URLs (docs.google.com/forms/ or forms.gle/).</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">3. Eligibility</h2>
                    <p>You must be at least 18 years old to use this Service. By using the Service, you represent and warrant that you are at least 18 years of age and have the legal capacity to enter into this agreement.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">4. Account Registration</h2>
                    <p>To use the Service, you must register for an account using Google OAuth authentication. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">5. Acceptable Use</h2>
                    <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You agree NOT to:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Use the Service to shorten URLs that are not Google Forms links</li>
                        <li>Use the Service for any illegal, harmful, or fraudulent activity</li>
                        <li>Attempt to circumvent or disable any security features of the Service</li>
                        <li>Interfere with or disrupt the Service or servers connected to the Service</li>
                        <li>Use automated systems to access the Service without authorization</li>
                        <li>Create links that redirect to malicious, offensive, or inappropriate content</li>
                        <li>Violate any applicable laws or regulations</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">6. Subscription Plans</h2>
                    <p>The Service offers three subscription plans:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>FREE Plan:</strong> Limited to 10 links per day and 200 links per month. No custom short codes or expiration dates.</li>
                        <li><strong>PREMIUM Plan:</strong> Up to 600 links per month with no daily limit. Includes custom short codes, expiration dates, advanced analytics, and link management features.</li>
                        <li><strong>ENTERPRISE Plan:</strong> Unlimited links with all features. Pricing and terms are negotiated separately.</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">7. Payment Terms</h2>
                    <p>PREMIUM subscriptions are billed monthly through Stripe. By subscribing, you agree to pay the subscription fees as displayed. Subscriptions automatically renew unless cancelled. You may cancel your subscription at any time through your account settings or Stripe customer portal.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">8. Link Ownership and Content</h2>
                    <p>You retain ownership of the links you create. However, you acknowledge that:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>The Service only stores the URL and metadata, not the content of Google Forms</li>
                        <li>We are not responsible for the content of the Google Forms you link to</li>
                        <li>You are solely responsible for ensuring your links comply with these Terms</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">9. Service Availability</h2>
                    <p>We strive to maintain high availability but do not guarantee uninterrupted or error-free service. The Service may be temporarily unavailable due to maintenance, updates, or unforeseen circumstances.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">10. Termination</h2>
                    <p>We reserve the right to suspend or terminate your account and access to the Service at any time, with or without notice, for violation of these Terms or for any other reason we deem necessary.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">11. Limitation of Liability</h2>
                    <p>To the maximum extent permitted by law, GForms ShortLinks shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses resulting from your use of the Service.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">12. Indemnification</h2>
                    <p>You agree to indemnify and hold harmless GForms ShortLinks, its officers, directors, employees, and agents from any claims, damages, losses, liabilities, and expenses (including legal fees) arising out of your use of the Service or violation of these Terms.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">13. Changes to Terms</h2>
                    <p>We reserve the right to modify these Terms at any time. We will notify users of any material changes by posting the updated Terms on this page. Your continued use of the Service after such modifications constitutes acceptance of the updated Terms.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">14. Governing Law</h2>
                    <p>These Terms shall be governed by and construed in accordance with applicable laws, without regard to conflict of law provisions.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">15. Contact Information</h2>
                    <p>If you have any questions about these Terms, please contact us at: <a href="mailto:support@gformus.link" style="color: var(--color-primary);">support@gformus.link</a></p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">16. Trademark Disclaimer</h2>
                    <p style="font-size: 0.95rem; color: var(--color-text-muted); font-style: italic;">
                        <?= t('legal.disclaimer') ?>
                    </p>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

