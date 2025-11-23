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
                <h1>Privacy Policy — GForms ShortLinks</h1>
                <p class="text-muted">Last updated: November 23, 2025</p>
            </div>
            <div style="padding: 2rem;">
                <div style="line-height: 1.8; color: var(--color-text);">
                    
                    <p>GForms ShortLinks ("the extension") allows users to generate short, shareable links for Google Forms directly from their browser. We are committed to protecting your privacy and providing full transparency about how data is used.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">1. Information We Collect</h2>
                    
                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">1.1 Google Identity (Email, Name, Profile Image)</h3>
                    <p>If you sign in using Google Login, we receive your basic Google profile information via Chrome's identity API:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Email</li>
                        <li>Name</li>
                        <li>Profile image</li>
                    </ul>
                    <p>This information is used only to:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Identify your user account</li>
                        <li>Associate shortlinks with your profile</li>
                        <li>Provide premium features (if applicable)</li>
                    </ul>
                    <p>No additional Google account data is collected.</p>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">1.2 activeTab</h3>
                    <p>Used only to read the URL of the active tab when the user opens the extension popup. No page content is accessed.</p>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">1.3 Extension Settings (storage permission)</h3>
                    <p>We use Chrome's storage API to store minimal internal data:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Authentication state</li>
                        <li>User preferences</li>
                        <li>Usage-related configuration</li>
                    </ul>
                    <p>No sensitive personal data is stored locally.</p>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.2rem;">1.4 Communication With External Domains (host_permissions)</h3>
                    <p>The extension communicates only with these domains:</p>
                    <p><strong>Google Forms</strong></p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><code>https://docs.google.com/forms/*</code></li>
                        <li>Used solely to detect when the user is viewing a Google Form.</li>
                    </ul>
                    <p><strong>GForms Shortener API</strong></p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li><code>https://gforms.click/*</code></li>
                        <li>Used to:</li>
                        <li style="margin-left: 1rem;">Create shortlinks via API</li>
                        <li style="margin-left: 1rem;">Retrieve authentication results</li>
                        <li style="margin-left: 1rem;">Link usage activity with your account</li>
                    </ul>
                    <p>The extension does not interact with any other external sites.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">2. How We Use Data</h2>
                    <p>We use the collected information exclusively to:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Generate shortlinks for Google Forms</li>
                        <li>Authenticate users securely using Google OAuth</li>
                        <li>Associate shortlinks with user accounts</li>
                        <li>Provide core extension functionality</li>
                    </ul>
                    <p>We do not, under any circumstance:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Sell data</li>
                        <li>Share data with advertisers</li>
                        <li>Track users across websites</li>
                        <li>Build advertising or behavioral profiles</li>
                        <li>Collect background activity or browsing history</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">3. No Remote Code Execution</h2>
                    <p>The extension does not load, fetch, or execute remote code.</p>
                    <p>All extension logic is packaged inside the Chrome extension bundle.</p>
                    <p>External communication is limited to HTTPS API requests for:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Google OAuth login</li>
                        <li>Shortlink creation using gforms.click</li>
                    </ul>
                    <p>No executable scripts, remote modules, or dynamic code are injected or executed.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">4. Data Sharing</h2>
                    <p>Your data may be shared only in these specific and limited cases:</p>
                    <p><strong>Google Authentication</strong></p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Your basic Google profile is transmitted securely to authenticate your identity via OAuth.</li>
                    </ul>
                    <p><strong>Shortlink Processing</strong></p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Your Google Form URL and user ID (if logged in) may be sent to the gforms.click API to generate a shortlink.</li>
                    </ul>
                    <p>We never share your data with:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Third-party advertisers</li>
                        <li>Marketing platforms</li>
                        <li>Analytics providers</li>
                        <li>External partners unrelated to the service</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">5. Data Retention</h2>
                    <p>We retain:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>Shortlinks generated by users</li>
                        <li>Metadata required to operate the service</li>
                        <li>Authentication identifiers</li>
                    </ul>
                    <p>You may request full deletion of your data by contacting:</p>
                    <p style="margin-top: 0.5rem;">
                        📧 <a href="mailto:support@gforms.click" style="color: var(--color-primary);">support@gforms.click</a>
                    </p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">6. Security</h2>
                    <p>We take privacy and security seriously:</p>
                    <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                        <li>All communication uses HTTPS encryption</li>
                        <li>Login is handled through Chrome's secure launchWebAuthFlow</li>
                        <li>No passwords are collected or stored</li>
                        <li>No tracking, fingerprinting, or background monitoring</li>
                    </ul>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">7. Children's Privacy</h2>
                    <p>This extension is not intended for children under 13, and we do not knowingly collect information from minors.</p>

                    <h2 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">8. Contact Information</h2>
                    <p>For privacy questions or data deletion requests, contact us at:</p>
                    <p style="margin-top: 0.5rem;">
                        📧 <a href="mailto:support@gforms.click" style="color: var(--color-primary);">support@gforms.click</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

