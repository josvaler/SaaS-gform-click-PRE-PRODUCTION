# Stripe Webhook Configuration Guide

Complete guide to configure Stripe webhooks for this application. Follow these steps to avoid common pitfalls.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Stripe Dashboard Setup](#stripe-dashboard-setup)
3. [Server Configuration](#server-configuration)
4. [Webhook Code Requirements](#webhook-code-requirements)
5. [Apache/.htaccess Configuration](#apachehtaccess-configuration)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Security Considerations](#security-considerations)

---

## Prerequisites

- Stripe account (test or live mode)
- Apache web server with mod_rewrite enabled
- PHP 8.0+ with Stripe PHP SDK installed
- HTTPS enabled (Stripe requires HTTPS for webhooks)

---

## Stripe Dashboard Setup

### Step 1: Create Webhook Endpoint

1. Go to **Stripe Dashboard** → **Developers** → **Webhooks**
2. Click **"Add endpoint"**
3. Enter endpoint URL:
   ```
   https://yourdomain.com/stripe/webhook
   ```
   **Important:** Use clean URL without `.php` extension
4. Select events to listen to:
   - `checkout.session.completed` (required)
   - `customer.subscription.created` (recommended)
   - `customer.subscription.updated` (recommended)
   - `customer.subscription.deleted` (recommended)
5. Click **"Add endpoint"**

### Step 2: Get Webhook Signing Secret

1. After creating the endpoint, click on it
2. Click **"Reveal"** next to **"Signing secret"**
3. Copy the secret (starts with `whsec_`)
4. **Save this secret** - you'll need it for server configuration

**Important:** 
- Test mode and Live mode have different secrets
- If you recreate the webhook endpoint, you'll get a new secret
- Always use the secret that matches your current Stripe mode

---

## Server Configuration

### Step 1: Configure Environment Variables

Add Stripe configuration to your Apache virtual host file:

```bash
sudo nano /etc/apache2/sites-available/your-site-le-ssl.conf
```

Add these lines inside the `<VirtualHost>` block:

```apache
SetEnv STRIPE_SECRET_KEY sk_test_...your_secret_key...
SetEnv STRIPE_PUBLISHABLE_KEY pk_test_...your_publishable_key...
SetEnv STRIPE_PRICE_ID price_...your_monthly_price_id...
SetEnv STRIPE_PRICE_ID_YEAR price_...your_annual_price_id...
SetEnv STRIPE_WEBHOOK_SECRET whsec_...your_webhook_secret...
```

**Critical:** 
- Replace `...` with your actual Stripe keys
- Use test keys for testing, live keys for production
- The webhook secret must match the one from Stripe Dashboard

### Step 2: Restart Apache

```bash
sudo systemctl restart apache2
```

### Step 3: Verify Configuration

Test that environment variables are loaded:

```bash
php -r "require '/var/www/your-app/config/bootstrap.php'; \$config = require '/var/www/your-app/config/stripe.php'; echo 'Webhook secret: ' . (!empty(\$config['webhook_secret']) ? 'CONFIGURED (length: ' . strlen(\$config['webhook_secret']) . ')' : 'NOT CONFIGURED') . PHP_EOL;"
```

Expected output: `Webhook secret: CONFIGURED (length: 107)`

---

## Webhook Code Requirements

### File Location

The webhook file must be located at:
```
public/stripe/webhook.php
```

### Required Code Structure

The webhook must:

1. **Accept only POST requests**
2. **Validate Stripe signature** using the webhook secret
3. **Extract metadata** from the event (especially `google_id`)
4. **Update database** with subscription information
5. **Always return 200 OK** to acknowledge receipt (even on errors)

### Key Code Patterns

#### 1. Early Validation

```php
// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check signature header
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
if (empty($signature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing Stripe signature header']);
    exit;
}
```

#### 2. Signature Validation

```php
try {
    $event = Webhook::constructEvent(
        $payload,
        $signature,
        $stripeConfig['webhook_secret']
    );
} catch (\Stripe\Exception\SignatureVerificationException $exception) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}
```

#### 3. Metadata Extraction

For `checkout.session.completed` events, metadata is on the session object:

```php
if ($type === 'checkout.session.completed') {
    $googleId = $data->metadata->google_id ?? null;
    $customerId = $data->customer ?? null;
    $subscriptionId = $data->subscription ?? null;
    
    // Fallback: try to get from subscription if not on session
    if (empty($googleId) && $subscriptionId) {
        $stripe = new StripeClient($stripeConfig['secret_key']);
        $subscription = $stripe->subscriptions->retrieve($subscriptionId);
        $googleId = $subscription->metadata->google_id ?? null;
    }
}
```

#### 4. Always Return 200

```php
// Always return 200 OK to acknowledge receipt
http_response_code(200);
echo json_encode(['received' => true, 'event_type' => $type]);
exit;
```

**Critical:** Never return error codes (400, 500) for processing errors. Log errors internally but always return 200 to Stripe, otherwise Stripe will retry the webhook.

---

## Apache/.htaccess Configuration

### Required .htaccess Rules

Your `.htaccess` file must route the clean URL to the PHP file:

```apache
# Stripe webhook route - allow direct .php access
RewriteRule ^stripe/webhook\.php$ stripe/webhook.php [L,QSA]
# Also allow clean URL (recommended)
RewriteRule ^stripe/webhook$ stripe/webhook.php [L,QSA]
```

**Important:** 
- The webhook route must come **before** the general `.php` hiding rule
- Both URLs should work: `/stripe/webhook` and `/stripe/webhook.php`
- Use the clean URL (`/stripe/webhook`) in Stripe Dashboard

### Full .htaccess Example

```apache
RewriteEngine On
RewriteBase /

# Stripe webhook route (must come before general .php rule)
RewriteRule ^stripe/webhook$ stripe/webhook.php [L,QSA]

# Hide .php extension for other files
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Testing

### Step 1: Test Webhook Endpoint Accessibility

```bash
curl -X POST https://yourdomain.com/stripe/webhook \
  -H "Content-Type: application/json" \
  -d '{"test":true}'
```

Expected response: `{"error":"Missing Stripe signature header..."}`

If you get a 404 or 500, check:
- File exists at `public/stripe/webhook.php`
- `.htaccess` routing is correct
- Apache mod_rewrite is enabled

### Step 2: Send Test Webhook from Stripe

1. Go to **Stripe Dashboard** → **Webhooks** → Your endpoint
2. Click **"Send test webhook"**
3. Select event: `checkout.session.completed`
4. Click **"Send test webhook"**
5. Check the response:
   - **200 OK** = Success
   - **400/500** = Check error message

### Step 3: Verify Database Update

After a successful webhook, check your database:

```sql
SELECT id, email, plan, plan_expiration, stripe_customer_id, stripe_subscription_id 
FROM users 
WHERE email = 'test@example.com';
```

Expected:
- `plan` = `PREMIUM`
- `plan_expiration` = future date
- `stripe_customer_id` = `cus_...`
- `stripe_subscription_id` = `sub_...`

### Step 4: Test Real Subscription

1. Complete a test subscription checkout
2. Check Stripe Dashboard → Webhooks → Recent deliveries
3. Verify event shows **200 OK** status
4. Check database was updated

### Step 5: Example Event Payload and Response

Here's a complete example of a `checkout.session.completed` event that returns **200 OK**:

**Event Payload (from Stripe):**
```json
{
  "id": "evt_1SVlloAsZsvo7FHidtWOpmCI",
  "object": "event",
  "api_version": "2023-08-16",
  "created": 1763698588,
  "data": {
    "object": {
      "id": "cs_test_a1TZaEVMpRvxCgZYSYNHg4VIgUfYe05m8fLAsmzEF8Scsb5FbqJL2iT2Q2",
      "object": "checkout.session",
      "adaptive_pricing": {
        "enabled": false
      },
      "after_expiration": null,
      "allow_promotion_codes": null,
      "amount_subtotal": 199,
      "amount_total": 199,
      "automatic_tax": {
        "enabled": false,
        "liability": null,
        "provider": null,
        "status": null
      },
      "billing_address_collection": null,
      "branding_settings": {
        "background_color": "#ffffff",
        "border_style": "rounded",
        "button_color": "#0074d4",
        "display_name": "Micro SaaS",
        "font_family": "default",
        "icon": null,
        "logo": null
      },
      "cancel_url": "https://gforms.click/billing?status=cancelled",
      "client_reference_id": null,
      "client_secret": null,
      "collected_information": {
        "business_name": null,
        "individual_name": null,
        "shipping_details": null
      },
      "consent": null,
      "consent_collection": null,
      "created": 1763698568,
      "currency": "usd",
      "currency_conversion": null,
      "custom_fields": [],
      "custom_text": {
        "after_submit": null,
        "shipping_address": null,
        "submit": null,
        "terms_of_service_acceptance": null
      },
      "customer": "cus_TShA12zyXnS5sV",
      "customer_creation": "always",
      "customer_details": {
        "address": {
          "city": null,
          "country": "MX",
          "line1": null,
          "line2": null,
          "postal_code": null,
          "state": null
        },
        "business_name": null,
        "email": "jose.luis.valerio@gmail.com",
        "individual_name": null,
        "name": "JOSE LUIS VALERIO",
        "phone": null,
        "tax_exempt": "none",
        "tax_ids": []
      },
      "customer_email": "jose.luis.valerio@gmail.com",
      "discounts": [],
      "expires_at": 1763784967,
      "invoice": "in_1SVlllAsZsvo7FHik9weF6X1",
      "invoice_creation": null,
      "livemode": false,
      "locale": null,
      "metadata": {
        "google_id": "114138085669238320940",
        "billing_period": "monthly"
      },
      "mode": "subscription",
      "origin_context": null,
      "payment_intent": null,
      "payment_link": null,
      "payment_method_collection": "always",
      "payment_method_configuration_details": {
        "id": "pmc_1SPY8NAsZsvo7FHi3Yqwu4jz",
        "parent": null
      },
      "payment_method_options": {
        "card": {
          "request_three_d_secure": "automatic"
        }
      },
      "payment_method_types": [
        "card"
      ],
      "payment_status": "paid",
      "permissions": null,
      "phone_number_collection": {
        "enabled": false
      },
      "recovered_from": null,
      "saved_payment_method_options": {
        "allow_redisplay_filters": [
          "always"
        ],
        "payment_method_remove": "disabled",
        "payment_method_save": null
      },
      "setup_intent": null,
      "shipping_address_collection": null,
      "shipping_cost": null,
      "shipping_details": null,
      "shipping_options": [],
      "status": "complete",
      "submit_type": null,
      "subscription": "sub_1SVllmAsZsvo7FHiW0uYj8d5",
      "success_url": "https://gforms.click/billing?status=success",
      "total_details": {
        "amount_discount": 0,
        "amount_shipping": 0,
        "amount_tax": 0
      },
      "ui_mode": "hosted",
      "url": null,
      "wallet_options": null
    }
  },
  "livemode": false,
  "pending_webhooks": 1,
  "request": {
    "id": null,
    "idempotency_key": null
  },
  "type": "checkout.session.completed"
}
```

**Expected Webhook Response (200 OK):**
```json
{
  "received": true,
  "event_type": "checkout.session.completed"
}
```

**Key Fields to Extract:**

From the event payload above, your webhook should extract:

- **`google_id`**: `"114138085669238320940"` (from `data.object.metadata.google_id`)
- **`customer`**: `"cus_TShA12zyXnS5sV"` (from `data.object.customer`)
- **`subscription`**: `"sub_1SVllmAsZsvo7FHiW0uYj8d5"` (from `data.object.subscription`)
- **`billing_period`**: `"monthly"` (from `data.object.metadata.billing_period`)
- **`payment_status`**: `"paid"` (from `data.object.payment_status`)
- **`status`**: `"complete"` (from `data.object.status`)

**What the Webhook Should Do:**

1. Validate the signature using `STRIPE_WEBHOOK_SECRET`
2. Extract `google_id` from `data.object.metadata.google_id`
3. Find the user in database using `google_id`
4. Update user plan to `PREMIUM`
5. Store `customer` ID as `stripe_customer_id`
6. Store `subscription` ID as `stripe_subscription_id`
7. Calculate `plan_expiration` based on `billing_period` or subscription `current_period_end`
8. Return `200 OK` with the response above

---

## Troubleshooting

### Issue: "Failed to connect to remote host"

**Symptoms:** Stripe shows "Failed to connect" in webhook deliveries

**Causes:**
1. Webhook URL is incorrect
2. Server firewall blocking Stripe IPs
3. CrowdSec or security software blocking Stripe

**Solutions:**

1. **Verify webhook URL:**
   - Must be exactly: `https://yourdomain.com/stripe/webhook`
   - No `.php` extension
   - Must be `https://` (not `http://`)
   - No trailing slash

2. **Check firewall:**
   ```bash
   sudo ufw status
   # Ensure port 443 is open
   ```

3. **Whitelist Stripe IPs in CrowdSec:**
   If using CrowdSec, create whitelist:
   ```bash
   sudo nano /etc/crowdsec/postoverflows/s01-whitelist/stripe-whitelist.yaml
   ```
   Add Stripe IP ranges (see Security section below)

4. **Test endpoint accessibility:**
   ```bash
   curl -I https://yourdomain.com/stripe/webhook
   ```
   Should return 405 (Method Not Allowed) for GET, not 404

---

### Issue: "Invalid signature"

**Symptoms:** Webhook returns 400 with "Invalid signature" error

**Causes:**
1. Webhook secret mismatch
2. Wrong secret for test/live mode
3. Webhook endpoint was recreated (new secret)

**Solutions:**

1. **Get current webhook secret:**
   - Stripe Dashboard → Webhooks → Your endpoint
   - Click "Reveal" next to "Signing secret"
   - Copy the secret

2. **Update Apache config:**
   ```bash
   sudo nano /etc/apache2/sites-available/your-site-le-ssl.conf
   ```
   Update `STRIPE_WEBHOOK_SECRET` with the new secret

3. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2
   ```

4. **Verify secret is loaded:**
   ```bash
   php -r "require 'config/bootstrap.php'; \$c = require 'config/stripe.php'; echo strlen(\$c['webhook_secret']);"
   ```
   Should output `107` (length of webhook secret)

5. **Check test vs live mode:**
   - If testing, use test mode webhook secret
   - If production, use live mode webhook secret
   - They are different!

---

### Issue: Database not updating

**Symptoms:** Webhook returns 200 OK but database shows no changes

**Causes:**
1. `google_id` not found in metadata
2. User not found in database
3. Silent error in processing

**Solutions:**

1. **Verify metadata is set in checkout:**
   ```php
   // In checkout.php, ensure metadata includes google_id
   'metadata' => [
       'google_id' => $googleId,
       'billing_period' => $billingPeriod,
   ],
   ```

2. **Check webhook logs:**
   Temporarily add logging to see what's happening:
   ```php
   error_log('Google ID: ' . ($googleId ?: 'NOT FOUND'));
   error_log('User found: ' . ($user ? 'YES' : 'NO'));
   ```

3. **Test with Stripe test webhook:**
   - Use the event payload you shared earlier
   - Verify `google_id` is in `metadata` field

4. **Check database directly:**
   ```sql
   SELECT * FROM users WHERE google_id = 'your_google_id';
   ```

---

### Issue: Webhook not being called

**Symptoms:** No webhook calls in access logs, Stripe shows "Failed"

**Causes:**
1. Webhook URL incorrect in Stripe Dashboard
2. Server not accessible from internet
3. DNS issues

**Solutions:**

1. **Verify webhook URL in Stripe Dashboard:**
   - Must be exactly: `https://yourdomain.com/stripe/webhook`
   - Check for typos
   - No `.php` extension

2. **Test server accessibility:**
   ```bash
   curl -I https://yourdomain.com/stripe/webhook
   ```

3. **Check DNS:**
   ```bash
   dig yourdomain.com
   ```

4. **Check Apache access logs:**
   ```bash
   tail -f /var/log/apache2/access.log | grep webhook
   ```

---

## Security Considerations

### 1. CrowdSec Whitelisting

If using CrowdSec, whitelist Stripe IPs to prevent blocking:

**Create whitelist file:**
```bash
sudo nano /etc/crowdsec/postoverflows/s01-whitelist/stripe-whitelist.yaml
```

**Content:**
```yaml
name: crowdsecurity/stripe-whitelist
description: "Whitelist Stripe webhook IPs"
whitelist:
  reason: "Stripe webhook service - trusted payment processor"
  expression:
    - "IpInRange(evt.Overflow.Alert.Source.IP, '3.18.12.63')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '3.130.192.231')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '13.235.14.237')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '13.235.122.149')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '18.211.135.69')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '35.154.171.200')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '52.15.183.38')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.187.174.169')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.187.205.235')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.187.216.72')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.241.31.99')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.241.31.102')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.241.34.107')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '52.84.0.0/15')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.187.0.0/16')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.230.0.0/16')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.239.0.0/16')"
    - "IpInRange(evt.Overflow.Alert.Source.IP, '54.241.0.0/16')"
```

**Enable and restart:**
```bash
sudo cscli postoverflows install /etc/crowdsec/postoverflows/s01-whitelist/stripe-whitelist.yaml
sudo systemctl restart crowdsec
```

### 2. Webhook Secret Security

- **Never commit webhook secrets to git**
- Store in Apache environment variables or secure config
- Use different secrets for test and production
- Rotate secrets if compromised

### 3. HTTPS Requirement

Stripe requires HTTPS for webhooks. Ensure:
- Valid SSL certificate installed
- HTTPS redirect configured
- No mixed content issues

---

## Quick Checklist

Use this checklist when setting up Stripe webhooks:

- [ ] Stripe webhook endpoint created in Dashboard
- [ ] Webhook URL: `https://yourdomain.com/stripe/webhook` (no `.php`)
- [ ] Webhook signing secret copied from Stripe Dashboard
- [ ] `STRIPE_WEBHOOK_SECRET` set in Apache config
- [ ] Apache restarted after config changes
- [ ] Webhook secret verified (length should be 107)
- [ ] `.htaccess` routes `/stripe/webhook` to `webhook.php`
- [ ] Webhook file exists at `public/stripe/webhook.php`
- [ ] Webhook code validates signature
- [ ] Webhook code extracts `google_id` from metadata
- [ ] Webhook code updates database
- [ ] Webhook always returns 200 OK
- [ ] Test webhook sent from Stripe Dashboard
- [ ] Database verified after test webhook
- [ ] CrowdSec whitelist configured (if using)
- [ ] Firewall allows port 443

---

## Common Mistakes to Avoid

1. ❌ **Using `.php` in webhook URL** - Use clean URL: `/stripe/webhook`
2. ❌ **Wrong webhook secret** - Must match the one in Stripe Dashboard
3. ❌ **Test/live mode mismatch** - Use test secret for test mode
4. ❌ **Returning error codes** - Always return 200, log errors internally
5. ❌ **Missing metadata** - Ensure `google_id` is in checkout metadata
6. ❌ **Webhook route after general .php rule** - Must come before in `.htaccess`
7. ❌ **Not restarting Apache** - Always restart after config changes
8. ❌ **HTTP instead of HTTPS** - Stripe requires HTTPS

---

## Additional Resources

- [Stripe Webhook Documentation](https://stripe.com/docs/webhooks)
- [Stripe Webhook Best Practices](https://stripe.com/docs/webhooks/best-practices)
- [Stripe Testing Guide](https://stripe.com/docs/testing)

---

## Support

If you encounter issues not covered here:

1. Check Stripe Dashboard → Webhooks → Recent deliveries for error details
2. Check Apache error logs: `/var/log/apache2/error.log`
3. Check application error logs: `gforms_error.log` (if configured)
4. Verify webhook secret matches Stripe Dashboard
5. Test webhook endpoint accessibility with curl

---

## .htaccess Example

Complete working `.htaccess` configuration with Stripe webhook support:

```apache
# Activar mod_rewrite
RewriteEngine On
RewriteBase /

# --- Regenerate QR route (must come before general .php rule) ---
RewriteRule ^regenerate-qr$ regenerate-qr.php [L,QSA]

# --- Link details route ---
RewriteRule ^link/([a-zA-Z0-9_-]+)$ link-details.php?code=$1 [L,QSA]

# --- Stripe webhook route - allow direct .php access ---
# Allow direct access to webhook.php (Stripe uses .php extension)
RewriteRule ^stripe/webhook\.php$ stripe/webhook.php [L,QSA]
# Also allow clean URL for backward compatibility
RewriteRule ^stripe/webhook$ stripe/webhook.php [L,QSA]

# --- Quitar .php de las URLs ---
# Si el archivo existe con .php, servirlo sin mostrar la extensión
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]

# --- Short code redirects ---
# If not a file, directory, or known route, try redirect handler
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/(login|dashboard|profile|create-link|links|link|pricing|billing|logout|admin|stripe|qr|assets|regenerate-qr|set-language|terms|privacy|explore|api) [NC]
RewriteRule ^([a-zA-Z0-9_-]+)$ redirect.php [L,QSA]

# --- Evitar acceso directo a PHP internos ---
RewriteRule ^(.*/)?\.ht.* - [F]

# --- Seguridad básica ---
<FilesMatch "\.(env|ini|log|cache|bak|sql)$">
    Require all denied
</FilesMatch>

# Evitar listar directorios
Options -Indexes

# Forzar UTF-8
AddDefaultCharset UTF-8

# Bloquear acceso al .htaccess
<Files ".htaccess">
    Require all granted
</Files>

# --- Forzar HTTPS si lo necesitas ---
# Descomenta si deseas forzarlo
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Key Points for Stripe Webhook:

1. **Webhook route comes first** - Before the general `.php` hiding rule
2. **Both URLs supported** - `/stripe/webhook` and `/stripe/webhook.php` work
3. **Clean URL recommended** - Use `/stripe/webhook` in Stripe Dashboard
4. **HTTPS enforced** - Required for Stripe webhooks

### Minimal Stripe-Only .htaccess:

If you only need the webhook route, here's a minimal version:

```apache
RewriteEngine On
RewriteBase /

# Stripe webhook route (must come before general .php rule)
RewriteRule ^stripe/webhook$ stripe/webhook.php [L,QSA]

# Hide .php extension for other files
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

**Last Updated:** 2025-11-20  
**Tested With:** Stripe API 2023-08-16, PHP 8.0+, Apache 2.4

