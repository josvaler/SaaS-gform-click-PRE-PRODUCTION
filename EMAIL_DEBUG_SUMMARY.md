# Email Sending Debug Summary

## Issues Found and Fixed

### 1. ✅ Missing Email Sending in Chrome API Endpoint
**Problem:** The Chrome API endpoint (`public/api/chrome/create.php`) was creating links but not sending confirmation emails.

**Fix:** Added email sending functionality after link creation, matching the web form implementation.

### 2. ✅ Improved Error Logging
**Problem:** Limited error logging made it difficult to diagnose email sending failures.

**Fix:** Added comprehensive logging throughout:
- Email attempt logging with recipient and SMTP details
- Success/failure logging
- Detailed error information including SMTP configuration
- Better exception handling with stack traces

### 3. ✅ Enhanced EmailService Debugging
**Problem:** EmailService had minimal error information when failures occurred.

**Fix:** Added detailed logging in EmailService:
- Validates SMTP credentials on construction
- Logs all email attempts with SMTP host and username
- Provides detailed error information when sends fail
- Logs configuration details for troubleshooting

## Current SMTP Configuration

Based on `.env` file:
- **Host:** `smtp.dreamhost.com`
- **Port:** `587` (STARTTLS)
- **Username:** `info@gforms.click`
- **From Email:** `noreply@gforms.click`
- **From Name:** `GForms`
- **Debug Mode:** `0` (Disabled - Production Mode)

## How to Verify Email Sending

### Step 1: Check Error Logs
After creating a link, check the error log:
```bash
tail -f /var/www/gforms.click/gforms_error.log | grep -i email
```

You should see logs like:
- `Link creation: Attempting to send email to: user@example.com for link: abc123`
- `EmailService: Attempting to send email to user@example.com via smtp.dreamhost.com as info@gforms.click`
- `EmailService: Email sent successfully to user@example.com` (on success)
- Or detailed error messages (on failure)

### Step 2: Test Email Configuration
Use the admin email test page:
1. Log in as admin
2. Navigate to Admin panel
3. Click "Send Email" button
4. This will show SMTP configuration and allow sending test emails

### Step 3: Enable Debug Mode (if needed)
To see detailed SMTP debugging, set in `.env`:
```
SMTP_DEBUG=2
```

**Warning:** Debug mode will output detailed SMTP conversation - disable after troubleshooting!

**Current Status:** Debug mode is **DISABLED** (SMTP_DEBUG=0) - Production mode is active.

## Common Issues to Check

1. **SMTP Credentials**: Verify `SMTP_USERNAME` and `SMTP_PASSWORD` are correct in `.env`
2. **SMTP Server**: DreamHost SMTP may require specific configuration
3. **Port/Encryption**: Currently using port 587 with STARTTLS
4. **Firewall**: Ensure outbound connections to port 587 are allowed
5. **Email Limit**: DreamHost may have sending limits

## Files Modified

1. `public/create-link.php` - Enhanced email logging
2. `public/api/chrome/create.php` - Added email sending (was missing) + comprehensive debugging
3. `Services/EmailService.php` - Added comprehensive logging and validation

## Chrome Extension Specific Issues

### Enhanced Debugging for Chrome Extension
The Chrome API endpoint now includes extensive logging to help diagnose email sending issues:

- Logs user data (ID, email, name) when email process starts
- Verifies helper function availability
- Logs each step of email generation and sending
- Provides detailed error traces if anything fails

### Testing Chrome Extension Email

1. **Create a link via Chrome extension**
2. **Immediately check logs:**
   ```bash
   tail -f /var/www/gforms.click/gforms_error.log | grep "Chrome API"
   ```

3. **You should see logs like:**
   - `Chrome API: Starting email sending process for link: xyz123`
   - `Chrome API: User data - ID: 123, Email: user@example.com, Name: John Doe`
   - `Chrome API: Helper function exists, generating email template...`
   - `Chrome API: Email template generated successfully`
   - `Chrome API: Attempting to send email to: user@example.com for link: xyz123`
   - `Chrome API: Email sent successfully to: user@example.com` (on success)

4. **If email fails, you'll see:**
   - Specific error messages indicating what went wrong
   - Stack traces for debugging
   - SMTP configuration details

## ✅ Email Sending is Working!

**VERIFIED:** The logs show emails are being sent successfully from the Chrome extension API endpoint.

**Example from logs (Nov 26, 2025 11:39:20):**
- ✅ Email sent to: `jose.luis.valerio@gmail.com`
- ✅ Link code: `iqwajh`
- ✅ SMTP server: `smtp.dreamhost.com`
- ✅ Status: "Email sent successfully"

## If Emails Aren't Being Received

Since the logs confirm emails are being sent, if they're not arriving, check:

### 1. Check Spam/Junk Folder
- **Most common issue** - emails often end up in spam
- Check Gmail's "Spam" folder
- Check "Promotions" or "Updates" tabs in Gmail

### 2. Gmail Filtering
- Gmail may filter emails from `noreply@gforms.click`
- Add `noreply@gforms.click` to contacts/whitelist
- Check "All Mail" folder in Gmail

### 3. Deliverability Issues
- DreamHost SMTP may have rate limits
- Emails from `noreply@` addresses are often filtered
- Consider using a "From" address that users can reply to

### 4. Email Content Issues
- HTML emails with links/images may trigger spam filters
- QR code images may cause issues
- Consider simplifying email template for better deliverability

## Next Steps for Troubleshooting

1. ✅ **Email sending is confirmed working** (check logs)
2. **Check spam/junk folders** in email client
3. **Test with a different email address** to rule out Gmail filtering
4. **Consider using a verified sender domain** (SPF/DKIM records)
5. **Monitor DreamHost email logs** for delivery status

## Debugging Commands

Check recent email-related logs:
```bash
tail -100 /var/www/gforms.click/gforms_error.log | grep -i "EmailService\|Link creation.*email\|Chrome API.*email"
```

Check SMTP configuration:
```bash
grep "^SMTP_" /var/www/gforms.click/.env | grep -v "PASSWORD"
```

---

## Chrome Extension Token Expiration Fix

### Problem: "Invalid or expired token" Error

**Issue:** Google ID tokens expire after approximately 1 hour. The Chrome extension was storing tokens and reusing them without checking expiration, causing authentication failures.

**Symptoms:**
- Error message: "Failed to create shortlink: Invalid or expired token"
- Occurs when trying to create links after being logged in for >1 hour
- No automatic re-authentication

### Solution Implemented

Added comprehensive token expiration handling to the Chrome extension:

1. **JWT Token Decoding Function** - Decodes JWT tokens to check expiration time
2. **Token Expiration Check** - Checks if token is expired (with 5-minute buffer for safety)
3. **Automatic Re-authentication** - Automatically prompts for re-login when token expires
4. **Proactive Token Validation** - Checks token validity before making API calls
5. **Better Error Handling** - Detects token expiration errors and handles them gracefully

### Files Modified

- `chrome-extension/popup.js`:
  - Added `decodeJWT()` function
  - Added `isTokenExpired()` function  
  - Added `handleTokenExpiration()` function
  - Updated `checkAuth()` to verify token expiration
  - Updated `handleCreateShortlink()` to check token before API calls
  - Updated `loadUserInfo()` to handle token expiration

### How It Works

1. **On Extension Load**: Checks if stored token is expired, clears if needed
2. **Before API Calls**: Validates token expiration before making requests
3. **On 401 Errors**: Detects authentication failures and prompts for re-login
4. **User Experience**: Shows clear message "Your session has expired. Please log in again."

### Testing

To test the fix:
1. Log in to the Chrome extension
2. Wait 1+ hour (or manually expire the token)
3. Try to create a link
4. Extension should automatically detect expiration and prompt for re-login

### Future Improvements

- Implement token refresh mechanism using refresh tokens
- Store token expiration time separately for faster checks
- Add background token refresh before expiration

