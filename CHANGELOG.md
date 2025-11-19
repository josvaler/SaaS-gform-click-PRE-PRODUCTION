# Changelog

All notable changes to GForms ShortLinks will be documented in this file.

## [1.0.0] - 2025-11-18

### Initial Release - GForms ShortLinks

#### Features Implemented

**Authentication & User Management**
- ✅ Google OAuth2 authentication with secure session handling
- ✅ User profile management with Google data synchronization
- ✅ User roles (USER, ADMIN) with admin panel access control
- ✅ IP tracking on login for security and admin search
- ✅ Session persistence across OAuth redirects

**URL Shortening**
- ✅ Google Forms URL validation (only accepts docs.google.com/forms and forms.gle URLs)
- ✅ Clean, memorable short URLs (e.g., gformus.link/reina)
- ✅ Automatic short code generation
- ✅ Custom short codes for PREMIUM and ENTERPRISE users
- ✅ Link expiration dates for PREMIUM and ENTERPRISE users
- ✅ Link activation/deactivation
- ✅ Fast HTTP 302 redirects

**Subscription Plans**
- ✅ FREE Plan: 10 links/day, 200 links/month
- ✅ PREMIUM Plan: 600 links/month, no daily limit
- ✅ ENTERPRISE Plan: Unlimited links
- ✅ Plan-based feature restrictions
- ✅ Quota tracking with automatic daily/monthly resets

**Analytics & Tracking**
- ✅ Total clicks per link
- ✅ Daily clicks chart (last 30 days)
- ✅ Device type statistics (Mobile, Tablet, Desktop)
- ✅ Country statistics
- ✅ Hourly usage patterns (last 24 hours)
- ✅ Click details: IP address, user agent, referrer, timestamp

**QR Codes**
- ✅ Automatic QR code generation for each short link
- ✅ QR codes stored in `/public/qr/` directory
- ✅ QR codes include link label

**User Interface**
- ✅ Dark mode theme (default)
- ✅ Fully responsive design
- ✅ User profile badge with avatar, name, email, and plan type
- ✅ Plan badges with distinct styling (FREE/PREMIUM/ENTERPRISE)
- ✅ Link management interface for PREMIUM/ENTERPRISE users
- ✅ Dashboard with quota status and quick stats
- ✅ Pricing page with plan comparison

**Admin Features**
- ✅ Admin panel (ADMIN role only)
- ✅ User search by email, Google ID, or IP address
- ✅ ENTERPRISE plan assignment
- ✅ Login log viewing

**Technical Features**
- ✅ Clean URLs without .php extensions
- ✅ PSR-4 autoloading
- ✅ Modular architecture (Models, Services, Controllers)
- ✅ CSRF protection on forms
- ✅ Environment variable configuration (.env file)
- ✅ Database migrations support
- ✅ Error logging and debugging

#### Known Issues / Pending

**Stripe Integration** ⚠️
- ❌ Stripe checkout flow not yet implemented
- ❌ Stripe webhook handling incomplete
- ❌ Payment processing pending
- ❌ Subscription management pending

**Geolocation**
- ⚠️ IP-to-country detection using placeholder
- ⚠️ Needs integration with GeoIP service (e.g., MaxMind GeoLite2)

**Additional Features Not Yet Implemented**
- Preview pages for links (has_preview_page flag exists but not implemented)
- Advanced analytics filters
- Bulk link operations
- Link import/export
- API endpoints

#### Database Schema

- `users` - User accounts with profiles, plans, Stripe customer IDs
- `short_links` - Shortened URLs with metadata
- `clicks` - Click analytics data
- `quota_daily` - Daily quota tracking
- `quota_monthly` - Monthly quota tracking
- `user_login_logs` - IP tracking for logins

#### Configuration

- Environment variables via `.env` file
- Database: `backend_gforms`
- Session management with secure cookies
- Google OAuth2 credentials required
- Stripe credentials (pending implementation)

---

## Future Versions

### Planned for v1.1.0
- Complete Stripe integration
- GeoIP service integration
- Preview pages for links
- Enhanced analytics filters
- API endpoints

### Planned for v1.2.0
- Bulk operations
- Link import/export
- Advanced reporting
- Email notifications

