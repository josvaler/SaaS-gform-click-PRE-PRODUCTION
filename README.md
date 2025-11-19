# GForms ShortLinks

A professional URL shortener exclusively designed for Google Forms. Create short, memorable links for your Google Forms with analytics, QR codes, and subscription-based features.

## 🚀 Features

### Core Functionality
- **Google Forms Only**: Validates and accepts only Google Forms URLs (`docs.google.com/forms` and `forms.gle`)
- **Clean URLs**: Generate memorable short links like `gformus.link/reina`
- **Fast Redirects**: HTTP 302 redirects for optimal performance
- **QR Codes**: Automatic QR code generation for each link

### Authentication
- **Google OAuth2**: Secure authentication using Google accounts
- **Session Management**: Secure session handling with CSRF protection
- **User Profiles**: Sync with Google profile data (name, email, avatar, locale)

### Subscription Plans

#### FREE Plan
- 10 links per day
- 200 links per month
- Auto-generated short codes only
- Basic analytics

#### PREMIUM Plan
- 600 links per month (no daily limit)
- Custom short codes
- Link expiration dates
- Advanced analytics
- Link management interface
- Preview pages (coming soon)

#### ENTERPRISE Plan
- Unlimited links
- All PREMIUM features
- Custom pricing (contact sales)
- Priority support

### Analytics & Tracking
- **Total Clicks**: Track total clicks per link
- **Daily Trends**: View clicks over the last 30 days
- **Device Statistics**: See mobile, tablet, and desktop usage
- **Country Statistics**: Geographic distribution of clicks
- **Hourly Patterns**: Peak usage times analysis
- **Click Details**: IP address, user agent, referrer tracking

### Admin Features
- User management
- Search by email, Google ID, or IP address
- ENTERPRISE plan assignment
- Login log viewing

### User Interface
- **Dark Mode**: Modern dark theme (default)
- **Responsive Design**: Works on all devices
- **User Badge**: Display avatar, name, email, and plan type
- **Plan Badges**: Visual indicators for FREE/PREMIUM/ENTERPRISE plans

## 📋 Requirements

- PHP 8.0 or higher
- MySQL/MariaDB 5.7 or higher
- Apache with mod_rewrite enabled
- Composer
- Google OAuth2 credentials
- Stripe account (for payment processing - pending implementation)

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/josvaler/SaaS-gform-click-PRE-PRODUCTION.git
   cd SaaS-gform-click-PRE-PRODUCTION
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your credentials
   ```

4. **Set up database**
   ```bash
   mysql -u your_user -p your_database < database/schema.sql
   ```

5. **Configure web server**
   - Point document root to `/public` directory
   - Ensure mod_rewrite is enabled
   - Set proper file permissions

6. **Set up Google OAuth**
   - Create OAuth 2.0 credentials in Google Cloud Console
   - Add authorized redirect URI: `https://yourdomain.com/login`
   - Update `.env` with client ID and secret

## ⚙️ Configuration

### Environment Variables (.env)

```bash
# Database
DB_HOST=localhost
DB_NAME=backend_gforms
DB_USER=gforms_user
DB_PASSWORD=your_password

# Google OAuth
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/login

# Stripe (pending implementation)
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_PRICE_ID=price_...

# Application
APP_URL=https://yourdomain.com
```

## 📁 Project Structure

```
gforms.click/
├── config/          # Configuration files
├── database/        # Database schema and migrations
├── Models/          # Data access layer (Repositories)
├── Services/        # Business logic layer
├── public/          # Web-accessible files
│   ├── assets/      # CSS and JavaScript
│   ├── qr/          # Generated QR codes
│   └── stripe/      # Stripe integration (pending)
├── views/           # PHP templates
└── vendor/          # Composer dependencies
```

## 🔐 Security Features

- CSRF protection on all forms
- Secure session management
- SQL injection prevention (PDO prepared statements)
- XSS protection (HTML escaping)
- Secure cookie settings (HttpOnly, Secure, SameSite)
- IP tracking for security monitoring

## 📊 Database Schema

- **users**: User accounts, profiles, plans
- **short_links**: Shortened URLs and metadata
- **clicks**: Click analytics data
- **quota_daily**: Daily quota tracking
- **quota_monthly**: Monthly quota tracking
- **user_login_logs**: IP tracking for admin search

## 🚧 Known Limitations

### Stripe Integration
⚠️ **Payment processing is not yet implemented**. The Stripe integration files exist but the payment flow is incomplete. Users cannot currently upgrade to PREMIUM or process ENTERPRISE payments through the application.

### Geolocation
⚠️ IP-to-country detection is currently using a placeholder. For production, integrate with a GeoIP service like MaxMind GeoLite2.

## 🧪 Testing

Test the application:
1. Visit the landing page
2. Login with Google OAuth
3. Create a short link
4. Test the redirect
5. View analytics

## 📝 License

[Your License Here]

## 👤 Author

Jose Luis Valerio - jose.luis.valerio@gmail.com

## 🙏 Acknowledgments

- Built on PHP 8.x
- Uses Google API Client for OAuth
- Stripe PHP SDK (pending integration)
