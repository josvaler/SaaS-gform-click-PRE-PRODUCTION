<?php
// Cookie Consent Banner Component
// This banner appears on first visit and allows users to manage cookie preferences
?>
<div id="cookie-consent-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-container">
        <div class="cookie-banner-content">
            <div class="cookie-banner-header">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;">🍪 Cookie Consent</h3>
                <p style="margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--color-text-muted);">
                    We use cookies to enhance your experience, analyze site usage, and assist in our marketing efforts. 
                    By clicking "Accept All", you consent to our use of cookies. You can manage your preferences below.
                </p>
            </div>
            
            <div class="cookie-banner-actions">
                <button id="cookie-accept-all" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Accept All
                </button>
                <button id="cookie-reject-all" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Reject All
                </button>
                <button id="cookie-customize" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Customize
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cookie Preferences Modal -->
<div id="cookie-preferences-modal" class="cookie-modal" style="display: none;">
    <div class="cookie-modal-overlay"></div>
    <div class="cookie-modal-content">
        <div class="cookie-modal-header">
            <h2 style="margin: 0; font-size: 1.5rem;">Cookie Preferences</h2>
            <button id="cookie-modal-close" class="cookie-modal-close" aria-label="Close">&times;</button>
        </div>
        
        <div class="cookie-modal-body">
            <p style="margin-bottom: 1.5rem; color: var(--color-text-muted); line-height: 1.6;">
                Manage your cookie preferences. You can enable or disable different types of cookies below. 
                Note that some cookies are essential for the website to function and cannot be disabled.
            </p>
            
            <!-- Essential Cookies -->
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem;">Essential Cookies</h3>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--color-text-muted);">
                            Required for the website to function properly
                        </p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-essential" checked disabled>
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-details">
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-top: 0.75rem;">
                        These cookies are strictly necessary for the website to function and cannot be disabled. They include:
                    </p>
                    <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.8;">
                        <li><strong>Session Cookie (PHPSESSID):</strong> Maintains your login state and authentication (expires when browser closes)</li>
                        <li><strong>CSRF Token:</strong> Protects against cross-site request forgery attacks (stored in session)</li>
                        <li><strong>OAuth State:</strong> Temporary cookie for secure Google OAuth authentication flow</li>
                    </ul>
                    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.75rem; font-style: italic;">
                        Duration: Session-based (deleted when browser closes) | Type: HTTP-only, Secure
                    </p>
                </div>
            </div>
            
            <!-- Functional Cookies -->
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem;">Functional Cookies</h3>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--color-text-muted);">
                            Enhance functionality and personalization
                        </p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-functional" checked>
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-details">
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-top: 0.75rem;">
                        These cookies allow the website to remember your preferences and provide enhanced features:
                    </p>
                    <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.8;">
                        <li><strong>Language Preference:</strong> Remembers your selected language (English/Spanish) for future visits</li>
                        <li><strong>User Preferences:</strong> Stores your interface preferences and settings</li>
                    </ul>
                    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.75rem; font-style: italic;">
                        Duration: 1 year | Type: HTTP-only, Secure
                    </p>
                </div>
            </div>
            
            <!-- Analytics Cookies -->
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem;">Analytics Cookies</h3>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--color-text-muted);">
                            Help us understand how visitors interact with our service
                        </p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-analytics">
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-details">
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-top: 0.75rem;">
                        These cookies collect anonymous information about how you use our service:
                    </p>
                    <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.8;">
                        <li><strong>Click Analytics:</strong> Tracks clicks on shortened links (IP address, device type, country, referrer) for link owners</li>
                        <li><strong>Usage Statistics:</strong> Aggregated data about link creation and usage patterns</li>
                    </ul>
                    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.75rem; font-style: italic;">
                        <strong>Note:</strong> We do NOT use third-party analytics services (Google Analytics, etc.). All analytics are first-party and stored on our servers.
                    </p>
                    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.5rem; font-style: italic;">
                        Duration: 2 years | Type: HTTP-only, Secure
                    </p>
                </div>
            </div>
            
            <!-- Marketing Cookies (Currently None) -->
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem;">Marketing Cookies</h3>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--color-text-muted);">
                            Used to deliver relevant advertisements
                        </p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-marketing" disabled>
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-details">
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-top: 0.75rem;">
                        We currently do not use marketing or advertising cookies. This category is reserved for future use.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="cookie-modal-footer">
            <button id="cookie-save-preferences" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                Save Preferences
            </button>
            <button id="cookie-accept-all-modal" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                Accept All
            </button>
        </div>
    </div>
</div>

