// OAuth Client ID from Google Cloud Console
const CLIENT_ID = '837476462692-rqfbcflt7tgm3i60a4vqe18a6sjajgpu.apps.googleusercontent.com';

// API Base URL
const API_BASE_URL = 'https://gforms.click';

// Google Login using launchWebAuthFlow (Manifest V3 compatible)
async function googleLogin() {
    const extensionId = chrome.runtime.id;
    const redirectUri = `https://${extensionId}.chromiumapp.org/`;
    const scopes = ["openid", "email", "profile"].join(" ");
    
    // Use response_type=id_token token to get both access_token and id_token (JWT)
    const authUrl =
        "https://accounts.google.com/o/oauth2/v2/auth" +
        `?client_id=${CLIENT_ID}` +
        `&redirect_uri=${encodeURIComponent(redirectUri)}` +
        `&response_type=id_token%20token` +
        `&scope=${encodeURIComponent(scopes)}` +
        `&include_granted_scopes=true` +
        `&nonce=${Date.now()}`;
    
    console.log('OAuth Debug:', {
        extensionId,
        redirectUri,
        clientId: CLIENT_ID,
        authUrl
    });

    return new Promise((resolve, reject) => {
        chrome.identity.launchWebAuthFlow(
            { url: authUrl, interactive: true },
            (redirectUrl) => {
                if (chrome.runtime.lastError) {
                    const errorMsg = chrome.runtime.lastError.message;
                    console.error('launchWebAuthFlow error:', errorMsg);
                    
                    if (errorMsg.includes('No such renderer') || errorMsg.includes('OAuth2')) {
                        reject(new Error('OAuth configuration error. Please verify the redirect URI is correctly set in Google Cloud Console: ' + redirectUri));
                    } else if (errorMsg.includes('User interaction required')) {
                        reject(new Error('User interaction required for login. Please try again.'));
                    } else {
                        reject(new Error(errorMsg));
                    }
                    return;
                }

                if (!redirectUrl) {
                    reject(new Error('No redirect URL received'));
                    return;
                }

                // Parse tokens from redirect URL
                const url = new URL(redirectUrl);
                const hash = url.hash.substring(1); // Remove #
                const params = new URLSearchParams(hash);
                
                const idToken = params.get('id_token');
                const accessToken = params.get('access_token');
                
                if (!idToken) {
                    const error = params.get('error') || 'No id_token in response';
                    console.error('OAuth error:', error);
                    reject(new Error(error));
                    return;
                }

                console.log('ID Token received (first 50 chars):', idToken.substring(0, 50) + '...');
                console.log('Access Token received:', accessToken ? 'Yes' : 'No');
                
                resolve({ idToken, accessToken });
            }
        );
    });
}

// Message handler for popup communication
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'login') {
        googleLogin()
            .then((tokens) => {
                sendResponse({ success: true, idToken: tokens.idToken, accessToken: tokens.accessToken });
            })
            .catch((error) => {
                console.error('Login error:', error);
                sendResponse({ success: false, error: error.message });
            });
        return true; // Will respond asynchronously
    }
    
    if (request.action === 'getCurrentTab') {
        chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
            if (tabs[0]) {
                sendResponse({ success: true, url: tabs[0].url, title: tabs[0].title });
            } else {
                sendResponse({ success: false, error: 'No active tab found' });
            }
        });
        return true; // Will respond asynchronously
    }
});

