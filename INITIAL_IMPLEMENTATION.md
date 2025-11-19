# Gformus.link - Google Forms URL Shortener Implementation Plan

## Overview
Transform the existing SaaS template into a Google Forms-exclusive URL shortener with quota management, analytics, QR generation, three-tier subscription model (FREE/PREMIUM/ENTERPRISE), IP tracking, and admin panel functionality.

## Database Schema Changes

### 1. Update `users` table
- Add `ENTERPRISE` to plan ENUM: `plan ENUM('FREE','PREMIUM','ENTERPRISE')`
- Add profile fields: `avatar_url`, `country`, `city`, `address`, `postal_code`, `phone`, `company`, `website`, `bio`, `locale`
- Add `role ENUM('USER','ADMIN') DEFAULT 'USER'` - Admin assignment is manual via SQL only (no UI)
- Add `plan_expiration DATETIME NULL` for tracking subscription end dates
- Keep existing Stripe fields (customer_id, subscription_id, etc.)

### 2. Create `short_links` table
- `id`, `user_id`, `original_url`, `short_code` (UNIQUE), `label`, `created_at`, `expires_at`, `is_active`, `has_preview_page`, `qr_code_path`
- Foreign key to users, indexes on short_code, user_id, created_at

### 3. Create `clicks` table
- `id`, `short_link_id`, `clicked_at`, `ip_address`, `user_agent`, `device_type`, `country`, `referrer`
- Foreign key to short_links, indexes for analytics queries

### 4. Create `quota_daily` table
- `id`, `user_id`, `date`, `links_created`
- Unique index on (user_id, date) for efficient quota checks

### 5. Create `quota_monthly` table
- `id`, `user_id`, `year_month` (YYYYMM format), `links_created`
- Unique index on (user_id, year_month)

### 6. Create `user_login_logs` table
- `id`, `user_id`, `google_id`, `ip_address`, `user_agent`, `logged_in_at`, `country` (optional, from IP geolocation)
- Foreign key to users, indexes on user_id, google_id, ip_address, logged_in_at
- Purpose: Track every login with IP address for admin security monitoring

### 7. Remove/modify `operations` table
- Remove operations table (not needed for URL shortener)
- Keep structure for potential future use or remove entirely

## Models Layer

### 1. Update `UserRepository`
- Add methods: `updateProfile()`, `findByRole()`, `updateRole()`
- Extend `updatePlan()` to handle ENTERPRISE
- Add `getPlanLimits()` helper

### 2. Create `ShortLinkRepository`
- `create()`, `findByShortCode()`, `findByUserId()`, `update()`, `deactivate()`, `activate()`, `delete()`
- `getActiveLinks()`, `getExpiredLinks()`, `searchByUser()`

### 3. Create `ClickRepository`
- `recordClick()`, `getClickStats()`, `getClicksByLink()`, `getClicksByDateRange()`
- Analytics methods: `getDeviceStats()`, `getCountryStats()`, `getHourlyStats()`

### 4. Create `QuotaRepository`
- `checkDailyQuota()`, `checkMonthlyQuota()`, `incrementDailyQuota()`, `incrementMonthlyQuota()`
- `getQuotaStatus()` - returns current usage and limits based on plan

### 5. Create `LoginLogRepository`
- `recordLogin()` - record user login with IP, user_agent, timestamp
- `findByIp()` - get all logins from a specific IP address
- `findByGoogleId()` - get all logins for a specific Google ID
- `findByUserId()` - get login history for a user
- `searchByIpAndGoogleId()` - combined search for admin panel

## Services Layer

### 1. `UrlValidationService`
- Validate Google Forms URLs only (docs.google.com/forms/ and forms.gle/)
- Return validation errors in Spanish
- Reject non-Google-Forms URLs

### 2. `ShortCodeService`
- Generate random short codes (FREE users)
- Validate custom codes (PREMIUM/ENTERPRISE)
- Check uniqueness
- Sanitize and validate format

### 3. `QrCodeService`
- Generate QR codes using PHP library (e.g., endroid/qr-code)
- Store QR images in `/public/qr/` directory
- Return file path for storage in database

### 4. `AnalyticsService`
- Aggregate click data
- Generate charts data (daily clicks, device split, country split, hourly distribution)
- Calculate conversion metrics

### 5. `QuotaService`
- Check if user can create link (daily/monthly limits)
- Enforce plan-based limits:
  - FREE: 10/day, 200/month
  - PREMIUM: 600/month, no daily limit
  - ENTERPRISE: unlimited
- Return quota status with upgrade suggestions

### 6. `RedirectService`
- Handle public redirects (no auth required)
- Record click analytics
- Check expiration and active status
- Handle preview pages (if enabled)

### 7. `IpTrackingService`
- `getClientIp()` - extract real client IP (handle proxies, load balancers)
- `getUserAgent()` - get user agent string
- `recordLogin()` - wrapper to record login with IP tracking
- Optional: Basic IP geolocation (country detection) if needed

## Controllers/Public Pages

### 1. `public/index.php` - Landing Page
- Show form to paste Google Form URL (requires login)
- Display examples and explanation
- Show ads for FREE users (conditional rendering)
- Redirect logged-in users to dashboard

### 2. `public/login.php` - Google OAuth
- Keep existing Google OAuth flow
- Store avatar_url from Google profile
- Update locale if available
- Record login IP address: call LoginLogRepository->recordLogin() after successful authentication
- Capture IP address, user_agent, and timestamp on every login

### 3. `public/dashboard.php` - User Dashboard
- Show quota status (daily/monthly usage vs limits)
- Display current plan badge
- Quick stats: total links, active links, total clicks
- Upgrade button for FREE users
- Link to profile, link management, pricing

### 4. `public/profile.php` - User Profile Page
- Pre-fill with Google data (name, email, avatar)
- Editable fields: country (required dropdown), city, address, postal_code, phone, company, website, bio, preferred_language
- Show plan badge with avatar
- Update profile handler

### 5. `public/create-link.php` - Create Short Link
- Form: original_url, custom_code (PREMIUM/ENTERPRISE only), expiration_date (PREMIUM/ENTERPRISE only), label
- Validate Google Forms URL
- Check quota before creation
- Generate QR code and store
- Redirect to link details page

### 6. `public/links.php` - Link Management (PREMIUM/ENTERPRISE)
- List all user links with pagination
- Filters: date range, status (active/expired/deactivated), click count, search by label/URL
- Actions: activate/deactivate, view details, delete
- Search functionality

### 7. `public/link/{short_code}.php` - Link Details & Analytics
- Show link information
- Display analytics: total clicks, daily chart (30 days), device split, country split, hourly distribution
- QR code display/download
- Edit link (PREMIUM/ENTERPRISE only)
- Deactivate/reactivate controls

### 8. `public/{short_code}` - Public Redirect Handler
- Check if short_code exists
- Verify link is active and not expired
- Record click with analytics data
- Redirect with HTTP 302/307
- Handle preview pages if enabled

### 9. `public/pricing.php` - Pricing Page
- Three-column layout: FREE, PREMIUM, ENTERPRISE
- FREE: Show limits, $0
- PREMIUM: Show price, features, Stripe checkout button
- ENTERPRISE: "Contact us" / "A medida", no price
- Hide ads for PREMIUM/ENTERPRISE users

### 10. `public/admin.php` - Admin Panel (Basic)
- List all users
- Filter by plan, role
- Assign ENTERPRISE plan to users
- View user details and links
- Search functionality:
  - Search by IP address (show all users who logged in from that IP)
  - Search by Google ID (show login history for that Google ID)
  - Combined search (IP + Google ID)
- Display login history table with IP, timestamp, user_agent
- Access strictly restricted to ADMIN role only (check `role = 'ADMIN'` in database)
- Redirect non-admin users with 403 error
- Admin role assignment is manual via SQL only (no UI for role changes)

### 11. `public/billing.php` - Update Existing
- Extend to show ENTERPRISE status
- Show "Contact support" for ENTERPRISE instead of payment
- Keep Stripe integration for PREMIUM

### 12. `public/stripe/webhook.php` - Update Existing
- Handle PREMIUM subscription expiration → auto-downgrade to FREE
- Handle ENTERPRISE (manual, no webhook needed)

## Views Layer

### 1. `views/index.php` - Landing Page Template
- Hero section with form
- Examples section
- Conditional ad blocks (FREE users only)
- Call-to-action for login

### 2. `views/dashboard.php` - Dashboard Template
- Quota display cards
- Plan badge
- Quick stats grid
- Recent links list
- Upgrade prompts

### 3. `views/profile.php` - Profile Template
- Avatar display (Google image)
- Form with all profile fields
- Plan badge with styling
- Save button

### 4. `views/links.php` - Link Management Template
- Filters sidebar
- Links table with pagination
- Search bar
- Bulk actions (activate/deactivate)

### 5. `views/link-details.php` - Link Details Template
- Link info card
- Analytics charts (using Chart.js or similar)
- QR code display
- Edit form (PREMIUM/ENTERPRISE)

### 6. `views/pricing.php` - Pricing Template
- Three-column comparison table
- Feature lists
- Stripe checkout buttons
- Contact form for ENTERPRISE

### 7. `views/admin.php` - Admin Panel Template
- User list table
- Filters (by plan, role)
- Search section:
  - Search by IP address input field
  - Search by Google ID input field
  - Combined search option
  - Results table showing matching logins with IP, timestamp, user info
- ENTERPRISE assignment form
- User details modal
- Login history display for selected user

### 8. Update `views/partials/header.php`
- Show user badge with avatar, name, email, plan
- Conditional ad display (FREE users only)
- Navigation links

## Frontend Assets

### 1. `public/assets/css/style.css`
- Dark mode theme (default)
- Responsive mobile-first design
- Plan badge styles (FREE/PREMIUM/ENTERPRISE with distinct colors)
- Ad container styles
- Chart container styles
- Form validation styles

### 2. `public/assets/js/app.js`
- Form validation (Google Forms URL pattern)
- AJAX for link creation
- Search/filter functionality
- Chart initialization (if using Chart.js)
- Ad loading logic (conditional)

## Configuration Updates

### 1. `config/config.php`
- Add app name: 'Gformus.link'
- Add quota limits configuration
- Add ad configuration (enable/disable, ad provider settings)

### 2. `config/stripe.php`
- Keep existing PREMIUM price configuration
- Add ENTERPRISE handling (no automatic pricing)

### 3. `.htaccess` Updates
- Add rewrite rule for short code redirects: `/{short_code}` → redirect handler
- Keep existing .php removal rules

## Security Implementation

### 1. CSRF Protection
- Add CSRF tokens to all forms (create link, profile update, admin actions)
- Validate tokens on POST requests

### 2. Input Validation
- Sanitize all user input
- Validate Google Forms URLs strictly
- Validate short codes (alphanumeric, hyphens, underscores)
- SQL injection prevention (PDO prepared statements)

### 3. Authorization
- Require auth for link creation
- Restrict link management to PREMIUM/ENTERPRISE
- Restrict admin panel to ADMIN role only (strict database check, redirect non-admins)
- Admin role assignment: manual SQL only (`UPDATE users SET role = 'ADMIN' WHERE id = X`)
- Validate ownership before link operations

### 4. Rate Limiting
- Basic rate limiting on redirect handler (prevent abuse)
- IP-based throttling for click recording

### 5. Helper Functions
- Add `require_admin()` helper function (similar to `require_auth()`)
- Checks if user role is 'ADMIN' in database
- Redirects non-admins with 403

## Migration Strategy

### 1. Database Migration
- Create migration script to:
  - Update users table (add ENTERPRISE, profile fields, role)
  - Create new tables (short_links, clicks, quota_daily, quota_monthly, user_login_logs)
  - Remove operations table (or keep for reference)
  - Set default role to 'USER' for all existing users
  - Note: Admin assignment must be done manually via SQL: `UPDATE users SET role = 'ADMIN' WHERE id = X`

### 2. Data Migration
- Migrate existing user data
- Set default plan to FREE if not set
- Initialize quota tables

## Testing Checklist

### 1. Authentication
- Google OAuth login flow
- Session management
- Logout functionality
- IP tracking on login

### 2. Link Creation
- FREE user quota enforcement (10/day, 200/month)
- PREMIUM user limits (600/month)
- ENTERPRISE unlimited
- Custom code validation
- Expiration date handling
- QR code generation

### 3. Redirects
- Short code redirects work
- Analytics recording
- Expiration handling
- Active/inactive status

### 4. Analytics
- Click tracking accuracy
- Device/country detection
- Chart data generation
- Date range filtering

### 5. Stripe Integration
- PREMIUM checkout flow
- Webhook handling (subscription updates, cancellations)
- Auto-downgrade on expiration
- ENTERPRISE manual assignment

### 6. Admin Panel
- ENTERPRISE assignment
- User management
- Access control (strict ADMIN role check)
- IP search functionality
- Google ID search functionality
- Login history display

### 7. Ads
- Ads show for FREE users
- Ads hidden for PREMIUM/ENTERPRISE
- Ad placement on landing page

### 8. IP Tracking
- IP recorded on every login
- Admin can search by IP
- Admin can search by Google ID
- Login history displays correctly

## File Structure Summary

```
/var/www/gforms.click/
├── config/
│   ├── bootstrap.php (update)
│   ├── config.php (update)
│   ├── stripe.php (update)
│   └── helpers.php (add quota helpers, require_admin())
├── Models/
│   ├── UserRepository.php (update)
│   ├── ShortLinkRepository.php (new)
│   ├── ClickRepository.php (new)
│   ├── QuotaRepository.php (new)
│   └── LoginLogRepository.php (new)
├── Services/ (new)
│   ├── UrlValidationService.php
│   ├── ShortCodeService.php
│   ├── QrCodeService.php
│   ├── AnalyticsService.php
│   ├── QuotaService.php
│   ├── RedirectService.php
│   └── IpTrackingService.php
├── public/
│   ├── index.php (update)
│   ├── login.php (update - add IP tracking)
│   ├── dashboard.php (update)
│   ├── profile.php (new)
│   ├── create-link.php (new)
│   ├── links.php (new)
│   ├── link-details.php (new)
│   ├── {short_code} redirect handler (new)
│   ├── pricing.php (new)
│   ├── admin.php (new - with IP/Google ID search)
│   ├── billing.php (update)
│   ├── .htaccess (update)
│   └── assets/
│       ├── css/style.css (update)
│       └── js/app.js (update)
├── views/
│   ├── index.php (update)
│   ├── dashboard.php (update)
│   ├── profile.php (new)
│   ├── links.php (new)
│   ├── link-details.php (new)
│   ├── pricing.php (new)
│   ├── admin.php (new - with search UI)
│   └── partials/
│       ├── header.php (update)
│       └── footer.php (keep)
└── database/
    ├── schema.sql (update)
    └── migrations/ (new)
        └── 001_add_url_shortener_tables.sql
```

## Implementation Order

1. Database schema updates (users table, new tables including user_login_logs)
2. Models layer (repositories including LoginLogRepository)
3. Services layer (validation, quota, QR, analytics, IP tracking)
4. Core pages (dashboard, create-link, redirect handler)
5. Link management (links.php, link-details.php)
6. Profile page
7. Pricing page and Stripe updates
8. Admin panel with IP/Google ID search functionality
9. Frontend styling and JS
10. Ad integration
11. Testing and refinement

## Key Requirements Summary

### User Requirements
- **Login**: Google OAuth only (no email/password)
- **Link Creation**: Requires login (no anonymous links)
- **Plans**: FREE (10/day, 200/month), PREMIUM (600/month), ENTERPRISE (unlimited)
- **Custom URLs**: PREMIUM/ENTERPRISE only
- **Expiration**: PREMIUM/ENTERPRISE only
- **Link Management**: PREMIUM/ENTERPRISE only

### Admin Requirements
- **Access**: ADMIN role only (manual SQL assignment)
- **Features**: ENTERPRISE assignment, user management, IP search, Google ID search
- **Security**: Strict role checking, 403 redirect for non-admins

### Technical Requirements
- **URLs**: Clean URLs without .php extensions
- **Language**: UI in Spanish, code comments in English
- **Theme**: Dark mode by default
- **Ads**: Show for FREE users only
- **IP Tracking**: Record on every login for admin monitoring

## Notes

- Admin role assignment: `UPDATE users SET role = 'ADMIN' WHERE id = X` (SQL only)
- All URLs use clean format: `/login`, `/dashboard`, `/billing` (no .php)
- Google Forms URL validation: Only accept `docs.google.com/forms/` and `forms.gle/`
- Quota resets: Daily (24h), Monthly (30 days)
- PREMIUM auto-downgrades to FREE on subscription expiration (via Stripe webhook)
- ENTERPRISE is manually assigned by admin (no automatic payment flow)

---

**Last Updated**: Initial Implementation Plan
**Status**: Ready for Implementation

