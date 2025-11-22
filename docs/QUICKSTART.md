# Quick Start Guide

## Copy Template to New Project

```bash
# Copy the template
cp -r /var/www/html/public/template /path/to/your-new-project
cd /path/to/your-new-project

# Install dependencies
composer install

# Set up database
mysql -u root -p -e "CREATE DATABASE your_app_db;"
mysql -u root -p your_app_db < database/schema.sql
```

## Configure Environment

Create a `.env` file or set environment variables:

```bash
# Database
export DB_HOST=localhost
export DB_NAME=your_app_db
export DB_USER=root
export DB_PASSWORD=your_password

# Google OAuth
export GOOGLE_CLIENT_ID=your_client_id
export GOOGLE_CLIENT_SECRET=your_client_secret
export GOOGLE_REDIRECT_URI=http://localhost/login

# Stripe
export STRIPE_SECRET_KEY=sk_test_...
export STRIPE_PUBLISHABLE_KEY=pk_test_...
export STRIPE_PRICE_ID=price_...
export STRIPE_WEBHOOK_SECRET=whsec_...

# App
export APP_URL=http://localhost
```

## Update Application Name

Edit `config/config.php`:
```php
'name' => 'YourAppName',  // Change this
```

## Point Web Server to `/public` Directory

Your web server document root should point to the `/public` directory.

## Test

1. Visit `http://localhost/` or `http://localhost`
2. Click "Get Started" → Login with Google
3. After login, visit `http://localhost/billing`
4. Test Stripe checkout (use test card: 4242 4242 4242 4242)

**Note:** This template uses clean URLs without `.php` extensions. The `.htaccess` file automatically handles URL rewriting, so `/login` serves `login.php`, `/dashboard` serves `dashboard.php`, etc.

## Next Steps

- Customize views in `/views/` directory
- Add your application logic
- Configure production environment variables
- Set up HTTPS
- Configure Stripe webhooks for production

See `README.md` for detailed documentation.

