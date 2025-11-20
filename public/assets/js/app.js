// Form Validation
document.addEventListener('DOMContentLoaded', function() {
    // Google Forms URL validation
    const urlInputs = document.querySelectorAll('input[type="url"][name="original_url"], input[type="url"][name="url"]');
    urlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateGoogleFormsUrl(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateGoogleFormsUrl(this);
            }
        });
    });
    
    // Custom code validation
    const customCodeInputs = document.querySelectorAll('input[name="custom_code"]');
    customCodeInputs.forEach(input => {
        input.addEventListener('input', function() {
            validateCustomCode(this);
        });
    });
});

function validateGoogleFormsUrl(input) {
    const url = input.value.trim();
    const errorMsg = input.parentElement.querySelector('.error-message');
    
    if (!url) {
        removeError(input, errorMsg);
        return true;
    }
    
    // Check if it's a Google Forms URL
    const googleFormsPattern = /^https:\/\/(docs\.google\.com\/forms\/|forms\.gle\/)/i;
    
    if (!googleFormsPattern.test(url)) {
        showError(input, errorMsg, 'Solo se permiten URLs de Google Forms (docs.google.com/forms/ o forms.gle/)');
        return false;
    }
    
    // Check HTTPS
    if (!url.startsWith('https://')) {
        showError(input, errorMsg, 'La URL debe usar HTTPS');
        return false;
    }
    
    removeError(input, errorMsg);
    return true;
}

function validateCustomCode(input) {
    const code = input.value.trim();
    const errorMsg = input.parentElement.querySelector('.error-message');
    
    if (!code) {
        removeError(input, errorMsg);
        return true;
    }
    
    // Check length
    if (code.length < 3) {
        showError(input, errorMsg, 'El código debe tener al menos 3 caracteres');
        return false;
    }
    
    if (code.length > 50) {
        showError(input, errorMsg, 'El código no puede tener más de 50 caracteres');
        return false;
    }
    
    // Check format
    const codePattern = /^[a-zA-Z0-9_-]+$/;
    if (!codePattern.test(code)) {
        showError(input, errorMsg, 'Solo letras, números, guiones y guiones bajos');
        return false;
    }
    
    removeError(input, errorMsg);
    return true;
}

function showError(input, errorMsg, message) {
    input.classList.add('error');
    input.style.borderColor = 'var(--color-error)';
    
    if (!errorMsg) {
        const msg = document.createElement('small');
        msg.className = 'error-message';
        msg.style.color = 'var(--color-error)';
        msg.style.display = 'block';
        msg.style.marginTop = '0.5rem';
        input.parentElement.appendChild(msg);
        msg.textContent = message;
    } else {
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
    }
}

function removeError(input, errorMsg) {
    input.classList.remove('error');
    input.style.borderColor = '';
    if (errorMsg) {
        errorMsg.style.display = 'none';
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copiado al portapapeles');
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Copiado al portapapeles');
    }
}

// Chart.js integration (if available)
if (typeof Chart !== 'undefined') {
    // Chart initialization will be done inline in link-details.php
}

// Cookie Consent Management
const CookieConsent = {
    COOKIE_NAME: 'gforms_cookie_consent',
    CONSENT_EXPIRY_DAYS: 365,
    
    init: function() {
        const consent = this.getConsent();
        
        // Show banner if consent hasn't been given
        if (!consent) {
            this.showBanner();
        } else {
            // Apply saved preferences
            this.applyPreferences(consent);
        }
        
        this.setupEventListeners();
    },
    
    getConsent: function() {
        const cookie = this.getCookie(this.COOKIE_NAME);
        if (cookie) {
            try {
                return JSON.parse(decodeURIComponent(cookie));
            } catch (e) {
                return null;
            }
        }
        return null;
    },
    
    setConsent: function(preferences) {
        const expiryDate = new Date();
        expiryDate.setTime(expiryDate.getTime() + (this.CONSENT_EXPIRY_DAYS * 24 * 60 * 60 * 1000));
        
        const consentData = {
            timestamp: Date.now(),
            preferences: preferences,
            version: '1.0'
        };
        
        document.cookie = `${this.COOKIE_NAME}=${encodeURIComponent(JSON.stringify(consentData))}; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax; Secure=${location.protocol === 'https:'}`;
        
        this.applyPreferences(preferences);
        this.hideBanner();
        this.hideModal();
    },
    
    getCookie: function(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    },
    
    showBanner: function() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.style.display = 'block';
        }
    },
    
    hideBanner: function() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.style.display = 'none';
        }
    },
    
    showModal: function() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Load current preferences into modal
            const consent = this.getConsent();
            if (consent && consent.preferences) {
                document.getElementById('cookie-functional').checked = consent.preferences.functional !== false;
                document.getElementById('cookie-analytics').checked = consent.preferences.analytics === true;
            }
        }
    },
    
    hideModal: function() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },
    
    acceptAll: function() {
        this.setConsent({
            essential: true,
            functional: true,
            analytics: true,
            marketing: false
        });
    },
    
    rejectAll: function() {
        this.setConsent({
            essential: true, // Cannot be disabled
            functional: false,
            analytics: false,
            marketing: false
        });
    },
    
    savePreferences: function() {
        const preferences = {
            essential: true, // Always true, cannot be disabled
            functional: document.getElementById('cookie-functional').checked,
            analytics: document.getElementById('cookie-analytics').checked,
            marketing: false // Currently not used
        };
        
        this.setConsent(preferences);
    },
    
    applyPreferences: function(preferences) {
        // Store preferences in localStorage for quick access
        if (typeof Storage !== 'undefined') {
            localStorage.setItem('cookie_preferences', JSON.stringify(preferences));
        }
        
        // Note: Essential cookies are always enabled (handled server-side)
        // Functional and Analytics preferences are stored for future use
        // Currently, the system uses essential cookies only, but this allows
        // for future implementation of preference-based cookie usage
    },
    
    setupEventListeners: function() {
        // Accept All button
        const acceptAllBtn = document.getElementById('cookie-accept-all');
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => this.acceptAll());
        }
        
        // Reject All button
        const rejectAllBtn = document.getElementById('cookie-reject-all');
        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', () => this.rejectAll());
        }
        
        // Customize button
        const customizeBtn = document.getElementById('cookie-customize');
        if (customizeBtn) {
            customizeBtn.addEventListener('click', () => this.showModal());
        }
        
        // Modal close button
        const closeBtn = document.getElementById('cookie-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideModal());
        }
        
        // Modal overlay click
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            const overlay = modal.querySelector('.cookie-modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', () => this.hideModal());
            }
        }
        
        // Save preferences button
        const saveBtn = document.getElementById('cookie-save-preferences');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.savePreferences());
        }
        
        // Accept All in modal
        const acceptAllModalBtn = document.getElementById('cookie-accept-all-modal');
        if (acceptAllModalBtn) {
            acceptAllModalBtn.addEventListener('click', () => this.acceptAll());
        }
        
        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
                this.hideModal();
            }
        });
        
        // Manage cookies link in footer
        const manageCookiesLink = document.getElementById('manage-cookies-link');
        if (manageCookiesLink) {
            manageCookiesLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.showModal();
            });
        }
    }
};

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cookie consent
    CookieConsent.init();
    
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMobile = document.querySelector('.nav-mobile');
    
    if (mobileMenuToggle && navMobile) {
        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            navMobile.classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !navMobile.contains(event.target)) {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                navMobile.classList.remove('active');
            }
        });
        
        // Close mobile menu when clicking a link
        const mobileLinks = navMobile.querySelectorAll('.mobile-nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                navMobile.classList.remove('active');
            });
        });
    }
    
    // User Dropdown Toggle
    const userProfileTrigger = document.querySelector('.user-profile-trigger');
    const userProfileCompact = document.querySelector('.user-profile-compact');
    
    if (userProfileTrigger && userProfileCompact) {
        userProfileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            userProfileCompact.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userProfileCompact.contains(event.target)) {
                userProfileTrigger.setAttribute('aria-expanded', 'false');
                userProfileCompact.classList.remove('active');
            }
        });
        
        // Close dropdown when clicking a link
        const dropdownItems = userProfileCompact.querySelectorAll('.user-dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function() {
                userProfileTrigger.setAttribute('aria-expanded', 'false');
                userProfileCompact.classList.remove('active');
            });
        });
    }
});

// Hero Animation Loop
(function() {
    const animationContainer = document.getElementById('linkAnimation');
    if (!animationContainer) return;
    
    const step1 = animationContainer.querySelector('.step-1');
    const step2 = animationContainer.querySelector('.step-2');
    const step3 = animationContainer.querySelector('.step-3');
    
    if (!step1 || !step2 || !step3) return;
    
    // Reset animation classes
    function resetAnimation() {
        step1.style.animation = 'none';
        step2.style.animation = 'none';
        step3.style.animation = 'none';
        step1.style.opacity = '0';
        step2.style.opacity = '0';
        step3.style.opacity = '0';
        
        // Force reflow
        void step1.offsetWidth;
        
        // Restart animations
        step1.style.animation = '';
        step2.style.animation = '';
        step3.style.animation = '';
        step1.style.opacity = '1';
    }
    
    // Loop animation every 5.5 seconds (animation duration + pause)
    setInterval(resetAnimation, 5500);
})();

