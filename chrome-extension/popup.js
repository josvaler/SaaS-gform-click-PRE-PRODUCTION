// API Base URL
const API_BASE_URL = 'https://gforms.click';

// DOM Elements
const loginSection = document.getElementById('loginSection');
const mainSection = document.getElementById('mainSection');
const loginBtn = document.getElementById('loginBtn');
const logoutBtn = document.getElementById('logoutBtn');
const loading = document.getElementById('loading');
const messages = document.getElementById('messages');
const urlInput = document.getElementById('urlInput');
const labelInput = document.getElementById('labelInput');
const createBtn = document.getElementById('createBtn');
const useCurrentBtn = document.getElementById('useCurrentBtn');
const result = document.getElementById('result');
const shortlinkUrl = document.getElementById('shortlinkUrl');
const copyBtn = document.getElementById('copyBtn');
const userName = document.getElementById('userName');
const userEmail = document.getElementById('userEmail');
const userPlan = document.getElementById('userPlan');
const userAvatar = document.getElementById('userAvatar');
const dailyQuota = document.getElementById('dailyQuota');
const monthlyQuota = document.getElementById('monthlyQuota');

// State
let currentToken = null;
let currentUser = null;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    setupEventListeners();
    await loadCurrentTabUrl();
});

// Setup event listeners
function setupEventListeners() {
    loginBtn.addEventListener('click', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    createBtn.addEventListener('click', handleCreateShortlink);
    useCurrentBtn.addEventListener('click', handleUseCurrentTab);
    copyBtn.addEventListener('click', handleCopyShortlink);
}

// Decode JWT token to check expiration
function decodeJWT(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    } catch (e) {
        console.error('Error decoding JWT:', e);
        return null;
    }
}

// Check if token is expired (with 5 minute buffer)
function isTokenExpired(token) {
    if (!token) return true;
    
    const payload = decodeJWT(token);
    if (!payload || !payload.exp) return true;
    
    // Check if token expires within 5 minutes (300 seconds)
    const expirationTime = payload.exp * 1000; // Convert to milliseconds
    const bufferTime = 5 * 60 * 1000; // 5 minutes in milliseconds
    const now = Date.now();
    
    return (expirationTime - now) < bufferTime;
}

// Check if user is authenticated and token is valid
async function checkAuth() {
    try {
        const data = await chrome.storage.local.get(['idToken', 'user']);
        
        if (data.idToken && data.user) {
            // Check if token is expired
            if (isTokenExpired(data.idToken)) {
                console.log('Token expired, clearing storage and showing login');
                await chrome.storage.local.clear();
                currentToken = null;
                currentUser = null;
                showLoginSection();
                showError('Your session has expired. Please log in again.');
                return;
            }
            
            currentToken = data.idToken;
            currentUser = data.user;
            showMainSection();
            await loadUserInfo();
        } else {
            showLoginSection();
        }
    } catch (error) {
        console.error('Auth check error:', error);
        showLoginSection();
    }
}

// Handle token expiration and re-authenticate if needed
async function handleTokenExpiration() {
    console.log('Token expired, clearing storage and prompting for re-login');
    await chrome.storage.local.clear();
    currentToken = null;
    currentUser = null;
    showLoginSection();
    showError('Your session has expired. Please log in again to continue.');
}

// Show login section
function showLoginSection() {
    loginSection.style.display = 'flex';
    mainSection.style.display = 'none';
    clearMessages();
}

// Show main section
function showMainSection() {
    loginSection.style.display = 'none';
    mainSection.style.display = 'flex';
    result.style.display = 'none';
}

// Handle login
async function handleLogin() {
    try {
        loading.style.display = 'block';
        clearMessages();
        
        // Use launchWebAuthFlow via background script
        chrome.runtime.sendMessage({ action: 'login' }, async (response) => {
            try {
                if (!response || !response.success) {
                    const errorMsg = response?.error || 'Login failed';
                    showError('Login failed: ' + errorMsg);
                    loading.style.display = 'none';
                    return;
                }
                
                const idToken = response.idToken;
                const accessToken = response.accessToken;
                
                if (!idToken) {
                    throw new Error('No id_token received from OAuth flow');
                }
                
                console.log('ID Token obtained successfully');
                
                // Verify id_token (JWT) with our API
                console.log('Verifying id_token with API...');
                const verifyResponse = await fetch(`${API_BASE_URL}/api/chrome/auth/verify`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${idToken}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                console.log('Verification response status:', verifyResponse.status);
                
                // Get response as text first
                const responseText = await verifyResponse.text();
                console.log('Verification response text length:', responseText.length);
                console.log('Verification response preview:', responseText.substring(0, 200));
                
                // Check if response is empty
                if (!responseText || responseText.trim().length === 0) {
                    throw new Error('Empty response from server. Status: ' + verifyResponse.status);
                }
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from server. Status: ' + verifyResponse.status + '. Response: ' + responseText.substring(0, 200));
                }
                
                if (!verifyResponse.ok) {
                    throw new Error(data.error || 'Token verification failed (Status: ' + verifyResponse.status + ')');
                }
                
                if (!data.success) {
                    throw new Error(data.error || 'Authentication failed');
                }
                
                // Store tokens and user info
                await chrome.storage.local.set({
                    idToken: idToken,
                    accessToken: accessToken,
                    user: data.user
                });
                
                currentToken = idToken;
                currentUser = data.user;
                
                showMainSection();
                await loadUserInfo();
                
            } catch (error) {
                console.error('Login error:', error);
                showError('Login failed: ' + (error.message || 'Unknown error'));
            } finally {
                loading.style.display = 'none';
            }
        });
    } catch (error) {
        console.error('Login error:', error);
        showError('Login failed: ' + (error.message || 'Unknown error'));
        loading.style.display = 'none';
    }
}

// Handle logout
async function handleLogout() {
    await chrome.storage.local.clear();
    currentToken = null;
    currentUser = null;
    showLoginSection();
    clearMessages();
}

// Load user info and quota
async function loadUserInfo() {
    if (!currentUser) return;
    
    // Check if token is expired before making request
    if (!currentToken || isTokenExpired(currentToken)) {
        await handleTokenExpiration();
        return;
    }
    
    try {
        userName.textContent = currentUser.name || 'User';
        userEmail.textContent = currentUser.email || '';
        userPlan.textContent = currentUser.plan || 'FREE';
        
        if (currentUser.avatar_url) {
            userAvatar.src = currentUser.avatar_url;
            userAvatar.style.display = 'block';
        }
        
        // Load quota info
        const response = await fetch(`${API_BASE_URL}/api/chrome/auth/verify`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${currentToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        // Check for token expiration
        if (response.status === 401) {
            await handleTokenExpiration();
            return;
        }
        
        if (response.ok) {
            const data = await response.json();
            if (data.quota) {
                // Calculate remaining quota
                const dailyRemaining = data.quota.daily_limit === null 
                    ? '∞' 
                    : Math.max(0, data.quota.daily_limit - data.quota.daily_used);
                const monthlyRemaining = data.quota.monthly_limit === null 
                    ? '∞' 
                    : Math.max(0, data.quota.monthly_limit - data.quota.monthly_used);
                
                // Display format: used/limit (remaining available)
                if (data.quota.daily_limit === null) {
                    dailyQuota.textContent = `${data.quota.daily_used}/∞`;
                } else {
                    dailyQuota.textContent = `${data.quota.daily_used}/${data.quota.daily_limit}`;
                    if (dailyRemaining > 0) {
                        dailyQuota.textContent += ` (${dailyRemaining} left)`;
                    } else {
                        dailyQuota.textContent += ' (limit reached)';
                    }
                }
                    
                if (data.quota.monthly_limit === null) {
                    monthlyQuota.textContent = `${data.quota.monthly_used}/∞`;
                } else {
                    monthlyQuota.textContent = `${data.quota.monthly_used}/${data.quota.monthly_limit}`;
                    if (monthlyRemaining > 0) {
                        monthlyQuota.textContent += ` (${monthlyRemaining} left)`;
                    } else {
                        monthlyQuota.textContent += ' (limit reached)';
                    }
                }
                
                // Log quota for debugging
                console.log('Quota Status:', {
                    daily: `${data.quota.daily_used}/${data.quota.daily_limit} (${dailyRemaining} remaining)`,
                    monthly: `${data.quota.monthly_used}/${data.quota.monthly_limit} (${monthlyRemaining} remaining)`,
                    can_create: data.quota.can_create
                });
            }
        }
    } catch (error) {
        console.error('Error loading user info:', error);
        // Don't show error for quota loading failures, just log them
    }
}

// Load current tab URL
async function loadCurrentTabUrl() {
    try {
        chrome.runtime.sendMessage({ action: 'getCurrentTab' }, (response) => {
            if (response && response.success && response.url) {
                const url = response.url;
                // Check if it's a Google Forms URL
                if (isGoogleFormsUrl(url)) {
                    urlInput.value = url;
                }
            }
        });
    } catch (error) {
        console.error('Error loading current tab:', error);
    }
}

// Check if URL is a Google Forms URL
function isGoogleFormsUrl(url) {
    try {
        const urlObj = new URL(url);
        const host = urlObj.hostname.toLowerCase();
        const path = urlObj.pathname.toLowerCase();
        
        return (host === 'docs.google.com' && path.includes('/forms/')) ||
               host === 'forms.gle';
    } catch (e) {
        return false;
    }
}

// Handle use current tab
async function handleUseCurrentTab() {
    try {
        chrome.runtime.sendMessage({ action: 'getCurrentTab' }, (response) => {
            if (response && response.success && response.url) {
                const url = response.url;
                if (isGoogleFormsUrl(url)) {
                    urlInput.value = url;
                    showSuccess('Current tab URL loaded');
                } else {
                    showError('Current tab is not a Google Forms URL');
                }
            } else {
                showError('Could not get current tab URL');
            }
        });
    } catch (error) {
        console.error('Error getting current tab:', error);
        showError('Error getting current tab URL');
    }
}

// Handle create shortlink
async function handleCreateShortlink() {
    const url = urlInput.value.trim();
    const label = labelInput.value.trim();
    
    if (!url) {
        showError('Please enter a Google Forms URL');
        return;
    }
    
    if (!isGoogleFormsUrl(url)) {
        showError('Please enter a valid Google Forms URL (docs.google.com/forms/ or forms.gle)');
        return;
    }
    
    // Check if token is expired before making request
    if (!currentToken || isTokenExpired(currentToken)) {
        await handleTokenExpiration();
        return;
    }
    
    try {
        loading.style.display = 'block';
        clearMessages();
        
        const response = await fetch(`${API_BASE_URL}/api/chrome/create`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${currentToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                original_url: url,
                label: label || null
            })
        });
        
        // Get response as text first
        const responseText = await response.text();
        console.log('Create response status:', response.status);
        console.log('Create response text length:', responseText.length);
        console.log('Create response preview:', responseText.substring(0, 200));
        
        // Check if response is empty
        if (!responseText || responseText.trim().length === 0) {
            throw new Error('Empty response from server. Status: ' + response.status);
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse response:', e);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server. Status: ' + response.status + '. Response: ' + responseText.substring(0, 200));
        }
        
        // Check for token expiration errors
        if (!response.ok) {
            const errorMsg = data.error || 'Failed to create shortlink';
            
            // Check if error is related to token expiration
            if (response.status === 401 || 
                errorMsg.toLowerCase().includes('invalid') && errorMsg.toLowerCase().includes('token') ||
                errorMsg.toLowerCase().includes('expired') ||
                errorMsg.toLowerCase().includes('authentication')) {
                console.log('Token expired or invalid, re-authenticating...');
                await handleTokenExpiration();
                return;
            }
            
            throw new Error(errorMsg);
        }
        
        if (!data.success) {
            const errorMsg = data.error || 'Failed to create shortlink';
            
            // Check if error is related to token expiration
            if (errorMsg.toLowerCase().includes('invalid') && errorMsg.toLowerCase().includes('token') ||
                errorMsg.toLowerCase().includes('expired') ||
                errorMsg.toLowerCase().includes('authentication')) {
                console.log('Token expired or invalid, re-authenticating...');
                await handleTokenExpiration();
                return;
            }
            
            throw new Error(errorMsg);
        }
        
        // Show success
        const shortUrl = `${data.base_url}/${data.short_code}`;
        shortlinkUrl.value = shortUrl;
        result.style.display = 'block';
        showSuccess('Shortlink created successfully!');
        
        // Update quota
        await loadUserInfo();
        
        // Clear inputs
        urlInput.value = '';
        labelInput.value = '';
        
    } catch (error) {
        console.error('Create shortlink error:', error);
        showError('Failed to create shortlink: ' + (error.message || 'Unknown error'));
    } finally {
        loading.style.display = 'none';
    }
}

// Handle copy shortlink
async function handleCopyShortlink() {
    try {
        await navigator.clipboard.writeText(shortlinkUrl.value);
        showSuccess('Shortlink copied to clipboard!');
    } catch (error) {
        console.error('Copy error:', error);
        showError('Failed to copy to clipboard');
    }
}

// Show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'message error';
    errorDiv.textContent = message;
    messages.appendChild(errorDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Show success message
function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'message success';
    successDiv.textContent = message;
    messages.appendChild(successDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Clear messages
function clearMessages() {
    messages.innerHTML = '';
}

