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

