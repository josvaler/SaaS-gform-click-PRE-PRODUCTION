# Application Template

Complete PHP application template with Google OAuth 2.0 authentication and Stripe subscription management.

## Features

- ✅ Google OAuth 2.0 authentication with state validation
- ✅ Stripe subscription checkout and management
- ✅ Customer billing portal integration
- ✅ Webhook handling for subscription events
- ✅ User repository pattern for data access
- ✅ Template system for views
- ✅ Session management
- ✅ Database schema with subscription tracking
- ✅ Security best practices (prepared statements, CSRF protection)

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Web server (Apache/Nginx)
- Google Cloud Console account (for OAuth)
- Stripe account (for payments)

## Installation

### 1. Copy Template to Your Project

```bash
cp -r /var/www/html/public/template /path/to/your/new-project
cd /path/to/your/new-project
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Database Setup

Create a MySQL database and import the schema:

```bash
mysql -u root -p your_database_name < database/schema.sql
```

Or manually:
```sql
CREATE DATABASE your_database_name;
USE your_database_name;
SOURCE database/schema.sql;
```

### 4. Environment Configuration

Create a `.env` file in the root directory or set environment variables:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_app_db
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_CHARSET=utf8mb4

# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/login.php

# Stripe Configuration
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_PRICE_ID=price_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SUCCESS_URL=https://yourdomain.com/billing.php?status=success
STRIPE_CANCEL_URL=https://yourdomain.com/billing.php?status=cancelled
STRIPE_PORTAL_CONFIGURATION_ID=your_portal_config_id

# Application Configuration
APP_URL=https://yourdomain.com
```

**Note:** The `env()` function reads from `$_ENV` or `getenv()`. You can use:
- `.env` file with a library like `vlucas/phpdotenv`
- Server environment variables
- Direct modification of config files (not recommended for production)

### 5. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Configure consent screen if needed
6. Set application type to "Web application"
7. Add authorized redirect URI: `https://yourdomain.com/login.php`
8. Copy Client ID and Client Secret to your `.env` file

### 6. Stripe Setup

1. Create a [Stripe account](https://stripe.com/)
2. Get API keys from Dashboard → Developers → API keys
3. Create a Product and Price:
   - Go to Products → Add Product
   - Set up recurring subscription
   - Copy the Price ID (starts with `price_`)
4. Set up Webhook:
   - Go to Developers → Webhooks → Add endpoint
   - Endpoint URL: `https://yourdomain.com/stripe/webhook.php`
   - Select events:
     - `checkout.session.completed`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
   - Copy webhook signing secret (starts with `whsec_`)
5. Configure Billing Portal (optional but recommended):
   - Go to Settings → Billing → Customer portal
   - Configure portal settings
   - Copy Portal Configuration ID if needed

### 7. Web Server Configuration

#### Apache (.htaccess)

Point document root to `/public` directory:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/your/project/public
    
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 8. Update Application Name

Edit `config/config.php` and change `'name' => 'YourAppName'` to your application name.

## File Structure

```
template/
├── config/
│   ├── bootstrap.php          # Application initialization, autoloader
│   ├── database.php           # Database connection functions
│   ├── helpers.php            # Utility functions (env, redirect, etc.)
│   ├── google.php             # Google OAuth configuration
│   ├── stripe.php             # Stripe configuration
│   └── config.php             # Application configuration
├── Models/
│   └── UserRepository.php     # User data access layer
├── public/
│   ├── index.php              # Landing page
│   ├── login.php              # Google OAuth handler
│   ├── dashboard.php          # User dashboard
│   ├── billing.php            # Subscription management page
│   ├── logout.php             # Session cleanup
│   └── stripe/
│       ├── checkout.php       # Create Stripe checkout session
│       ├── portal.php         # Customer billing portal
│       └── webhook.php        # Stripe webhook handler
├── views/
│   ├── partials/
│   │   ├── header.php         # Shared header template
│   │   └── footer.php         # Shared footer template
│   └── billing.php            # Billing page template
├── database/
│   └── schema.sql             # Database schema
├── composer.json               # PHP dependencies
└── README.md                   # This file
```

## Usage

### Authentication Flow

1. User visits `/login.php`
2. Clicks "Sign in with Google"
3. Google OAuth redirects back with authorization code
4. Application exchanges code for access token
5. Fetches user profile from Google
6. Creates/updates user in database
7. Sets session and redirects to dashboard

### Subscription Flow

1. User visits `/billing.php`
2. Clicks "Upgrade with Stripe Checkout"
3. Redirected to Stripe checkout page
4. Completes payment
5. Stripe webhook notifies application
6. Application updates user plan to PREMIUM
7. User redirected back with success status

### Webhook Events Handled

- `checkout.session.completed` - Upgrades user to PREMIUM
- `customer.subscription.updated` - Syncs subscription metadata
- `customer.subscription.deleted` - Downgrades user to FREE

## Customization

### Adding New Features

1. **New Pages**: Create PHP files in `/public/` directory
2. **New Models**: Add classes in `/Models/` directory (follow PSR-4)
3. **New Views**: Create templates in `/views/` directory
4. **Database Changes**: Update schema and add migration scripts

### Template Variables

The billing template expects these variables:
- `$user` - User session data array
- `$currentPlan` - Current plan ('FREE' or 'PREMIUM')
- `$isPremium` - Boolean premium status
- `$status` - Status message (null, 'success', 'cancelled', 'error', etc.)
- `$hasScheduledCancellation` - Boolean cancellation status
- `$cancelDateFormatted` - Formatted cancellation date string

### Styling

The template uses CSS classes that should be defined in `/assets/css/style.css`:
- `.card`, `.card-header`
- `.btn`, `.btn-primary`, `.btn-outline`
- `.alert`, `.alert-success`, `.alert-error`, `.alert-warning`, `.alert-info`
- `.badge`, `.premium-badge`, `.free-badge`
- `.navbar`, `.nav-links`
- `.user-profile`, `.user-avatar`

## Security Considerations

1. **Environment Variables**: Never commit `.env` files to version control
2. **HTTPS**: Always use HTTPS in production
3. **Session Security**: Configure secure session cookies
4. **Input Validation**: All user input is sanitized with `htmlspecialchars()`
5. **SQL Injection**: All queries use prepared statements
6. **CSRF Protection**: OAuth state validation prevents CSRF attacks
7. **Webhook Verification**: Stripe webhooks are verified with signature

## Troubleshooting

### Google OAuth Not Working

- Check redirect URI matches exactly in Google Console
- Verify Client ID and Secret are correct
- Ensure Google+ API is enabled
- Check error logs for detailed messages

### Stripe Checkout Not Working

- Verify Stripe API keys are correct
- Check Price ID exists and is active
- Ensure webhook endpoint is accessible
- Verify webhook secret matches Stripe dashboard

### Database Connection Errors

- Verify database credentials
- Check database exists
- Ensure MySQL user has proper permissions
- Check PHP PDO MySQL extension is installed

### Session Issues

- Verify `session_start()` is called before any output
- Check session directory is writable
- Ensure cookies are enabled in browser
- Check PHP session configuration

## Testing

### Local Development

1. Use Stripe test mode keys
2. Use Google OAuth test credentials
3. Test webhooks with Stripe CLI:
   ```bash
   stripe listen --forward-to localhost:8000/stripe/webhook.php
   ```

### Production Checklist

- [ ] Use production Stripe keys
- [ ] Use production Google OAuth credentials
- [ ] Set up HTTPS certificate
- [ ] Configure webhook endpoint in Stripe
- [ ] Set secure session cookies
- [ ] Configure proper error logging
- [ ] Set up database backups
- [ ] Test all user flows

## Support

For issues or questions:
1. Check error logs (`error_log()` output)
2. Review Stripe dashboard for webhook events
3. Check Google Cloud Console for OAuth errors
4. Verify database connectivity and schema

## License

This template is provided as-is for use in your projects.

## Credits

Built with:
- PHP 8.0+
- Google API Client Library
- Stripe PHP SDK
- MySQL/MariaDB

---

**Last Updated:** 2024
**Version:** 1.0.0

