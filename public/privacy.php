<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pageTitle = 'Privacy Policy';
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
                <h1>Privacy Policy</h1>
                <p class="text-muted">Last updated: <?= date('F j, Y') ?></p>
            </div>
            <div style="padding: 2rem;">
                <div style="line-height: 1.8; color: var(--color-text);">
                    
                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">1. Introduction</h2>
                    <p>GForms ShortLinks ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our URL shortening service.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">2. Information We Collect</h2>
                    
                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.1 Account Information</h3>
                    <p>When you register using Google OAuth, we collect:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Google ID (unique identifier)</li>
                        <li>Email address</li>
                        <li>Full name</li>
                        <li>Profile picture URL (avatar)</li>
                        <li>Locale/language preference</li>
                    </ul>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.2 Optional Profile Information</h3>
                    <p>You may optionally provide:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Country</li>
                        <li>City</li>
                        <li>Address</li>
                        <li>Postal code</li>
                        <li>Phone number</li>
                        <li>Company name</li>
                        <li>Website URL</li>
                        <li>Bio/description</li>
                    </ul>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.3 Link Data</h3>
                    <p>For each shortened link you create, we store:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Original Google Forms URL</li>
                        <li>Short code</li>
                        <li>Custom label (if provided)</li>
                        <li>Creation timestamp</li>
                        <li>Expiration date (if set)</li>
                        <li>Active/inactive status</li>
                        <li>QR code file path</li>
                    </ul>
                    <p><strong>Important:</strong> We do NOT store, access, or process any content from your Google Forms. We only store the URL and metadata necessary for the shortening service.</p>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.4 Analytics and Tracking Data</h3>
                    <p>When someone clicks on your shortened link, we automatically collect:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>IP address</li>
                        <li>User agent (browser and device information)</li>
                        <li>Device type (Desktop, Mobile, Tablet)</li>
                        <li>Country (derived from IP address)</li>
                        <li>Referrer URL (if available)</li>
                        <li>Click timestamp</li>
                    </ul>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.5 Authentication Logs</h3>
                    <p>Each time you log in, we record:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>IP address</li>
                        <li>User agent</li>
                        <li>Login timestamp</li>
                        <li>Country (derived from IP address)</li>
                        <li>Google ID</li>
                    </ul>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.6 Payment Information</h3>
                    <p>For PREMIUM and ENTERPRISE subscribers:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Stripe customer ID</li>
                        <li>Stripe subscription ID</li>
                        <li>Plan type and expiration dates</li>
                        <li>Subscription status</li>
                    </ul>
                    <p><strong>Note:</strong> All payment processing is handled by Stripe. We do not store credit card numbers or payment details. Please refer to <a href="https://stripe.com/privacy" target="_blank" style="color: var(--color-primary);">Stripe's Privacy Policy</a> for information about their data handling.</p>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">2.7 Usage Data</h3>
                    <p>We track your usage to enforce plan limits:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Daily link creation count</li>
                        <li>Monthly link creation count</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">3. How We Use Your Information</h2>
                    <p>We use the collected information for the following purposes:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>To provide and maintain the Service</li>
                        <li>To create and manage your account</li>
                        <li>To process your subscription payments</li>
                        <li>To enforce plan limits and quotas</li>
                        <li>To provide analytics and statistics for your links</li>
                        <li>To detect and prevent fraud or abuse</li>
                        <li>To comply with legal obligations</li>
                        <li>To improve and optimize the Service</li>
                        <li>To communicate with you about your account or the Service</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">4. Data Sharing and Disclosure</h2>
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Service Providers:</strong> We use Stripe for payment processing. They have access to payment-related information necessary to process transactions.</li>
                        <li><strong>Legal Requirements:</strong> We may disclose your information if required by law or in response to valid legal requests.</li>
                        <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred.</li>
                        <li><strong>With Your Consent:</strong> We may share your information with your explicit consent.</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">5. Data Security</h2>
                    <p>We implement appropriate technical and organizational security measures to protect your information:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Encrypted data transmission (HTTPS)</li>
                        <li>Secure database storage</li>
                        <li>Access controls and authentication</li>
                        <li>Regular security assessments</li>
                    </ul>
                    <p>However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">6. Data Retention</h2>
                    <p>We retain your information for as long as necessary to provide the Service and fulfill the purposes outlined in this Privacy Policy:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Account Data:</strong> Retained while your account is active and for a reasonable period after account deletion for legal compliance.</li>
                        <li><strong>Link Data:</strong> Retained until you delete the link or your account is deleted.</li>
                        <li><strong>Analytics Data:</strong> Retained for analytical purposes and may be aggregated for statistical analysis.</li>
                        <li><strong>Login Logs:</strong> Retained for security and administrative purposes.</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">7. Your Rights and Choices</h2>
                    <p>You have the following rights regarding your personal information:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Access:</strong> You can access and update your profile information through your account settings.</li>
                        <li><strong>Deletion:</strong> You can delete individual links or request account deletion.</li>
                        <li><strong>Correction:</strong> You can update or correct your profile information at any time.</li>
                        <li><strong>Data Portability:</strong> You can export your link data through the Service interface.</li>
                        <li><strong>Opt-out:</strong> You can opt out of certain data collection, though this may limit Service functionality.</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">8. Cookies and Tracking Technologies</h2>
                    <p>We use cookies to enhance your experience and provide our services. We have implemented a cookie consent banner that complies with international standards (GDPR for EU users and CCPA for California residents).</p>
                    
                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">8.1 Cookie Categories</h3>
                    <p>We categorize cookies as follows:</p>
                    
                    <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 1rem;">Essential Cookies (Strictly Necessary)</h4>
                    <p>These cookies are required for the website to function and cannot be disabled:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Session Cookie (PHPSESSID):</strong> Maintains your login state and authentication. Expires when browser closes.</li>
                        <li><strong>CSRF Token:</strong> Stored in session to protect against cross-site request forgery attacks.</li>
                        <li><strong>OAuth State:</strong> Temporary cookie for secure Google OAuth authentication flow.</li>
                    </ul>
                    
                    <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 1rem;">Functional Cookies</h4>
                    <p>These cookies enhance functionality and personalization:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Language Preference:</strong> Remembers your selected language (English/Spanish) for future visits. Stored for 1 year.</li>
                        <li><strong>Cookie Consent Preferences:</strong> Stores your cookie consent choices. Stored for 1 year.</li>
                    </ul>
                    
                    <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 1rem;">Analytics Cookies</h4>
                    <p>These cookies help us understand how visitors use our service:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><strong>Click Analytics:</strong> First-party analytics stored on our servers (not third-party). Tracks clicks on shortened links for link owners. Data includes IP address, device type, country, and referrer.</li>
                    </ul>
                    <p><strong>Important:</strong> We do NOT use third-party analytics services (Google Analytics, Facebook Pixel, etc.). All analytics are first-party and stored on our servers.</p>
                    
                    <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 1rem;">Marketing Cookies</h4>
                    <p>We currently do not use marketing or advertising cookies.</p>
                    
                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">8.2 Cookie Consent</h3>
                    <p>When you first visit our website or log in for the first time, you will see a cookie consent banner. You can:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Accept all cookies</li>
                        <li>Reject all non-essential cookies</li>
                        <li>Customize your cookie preferences by category</li>
                    </ul>
                    <p>Your cookie preferences are stored for 1 year. You can change your preferences at any time by clearing your browser cookies or contacting us.</p>
                    
                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">8.3 Managing Cookies</h3>
                    <p>You can manage cookies through:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Our cookie consent banner (appears on first visit)</li>
                        <li>Your browser settings (most browsers allow you to refuse or delete cookies)</li>
                        <li>Contacting us to update your preferences</li>
                    </ul>
                    <p><strong>Note:</strong> Disabling essential cookies will prevent you from using the Service, as they are required for authentication and security.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">9. Children's Privacy</h2>
                    <p>Our Service is not intended for users under the age of 18. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">10. International Data Transfers</h2>
                    <p>Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that differ from those in your country. By using the Service, you consent to such transfers.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">11. California Privacy Rights</h2>
                    <p>If you are a California resident, you have additional rights under the California Consumer Privacy Act (CCPA), including the right to know what personal information we collect, the right to delete your personal information, and the right to opt-out of the sale of personal information (we do not sell personal information).</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">12. GDPR Rights (EU Users)</h2>
                    <p>If you are located in the European Union, you have additional rights under the General Data Protection Regulation (GDPR), including:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Right to access your personal data</li>
                        <li>Right to rectification</li>
                        <li>Right to erasure ("right to be forgotten")</li>
                        <li>Right to restrict processing</li>
                        <li>Right to data portability</li>
                        <li>Right to object to processing</li>
                        <li>Right to withdraw consent</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">13. Changes to This Privacy Policy</h2>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the updated Privacy Policy on this page and updating the "Last updated" date. Your continued use of the Service after such changes constitutes acceptance of the updated Privacy Policy.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">14. Contact Us</h2>
                    <p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us at:</p>
                    <p style="margin-top: 0.5rem;">
                        <strong>Email:</strong> <a href="mailto:support@gformus.link" style="color: var(--color-primary);">support@gformus.link</a><br>
                        <strong>Subject:</strong> Privacy Policy Inquiry
                    </p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">15. Trademark Disclaimer</h2>
                    <p style="font-size: 0.95rem; color: var(--color-text-muted); font-style: italic;">
                        <?= t('legal.disclaimer') ?>
                    </p>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

