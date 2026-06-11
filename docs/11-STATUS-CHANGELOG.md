# Changelog

All notable changes to **GForms ShortLinks** (gforms.click) are documented here.

**Repository:** [SaaS-gform-click-PRE-PRODUCTION](https://github.com/josvaler/SaaS-gform-click-PRE-PRODUCTION)  
**Current branch:** `main` (42 commits)  
**Last commit:** `44170ae` — 2025-11-26 — Chrome extension v1.3.1 title sync  
**Overall status:** Pre-production — core features built; not production-ready

---

## Project Status (2025-06-10)

| Area | Status |
|------|--------|
| **Stripe** | **Test mode** (`sk_test_` / `pk_test_`) — checkout, portal, and webhooks implemented and tested in development; **not switched to live keys** |
| **Email (SMTP)** | Implemented — link creation + subscription lifecycle notifications |
| **Chrome Extension** | v1.3.1 — create shortlinks from Google Forms |
| **Admin diagnostics** | Partial — some tabs marked under construction |
| **GeoIP** | Placeholder — real country detection not integrated |
| **Preview pages** | Schema flag exists; UI not implemented |
| **Production launch** | Pending — live Stripe, final QA, docs sync |

### Stripe (development mode)

- Keys in `.env` use the **test** prefix (`sk_test_`, `pk_test_`).
- Webhook endpoint configured for `https://gforms.click/stripe/webhook`.
- Diagnostic cache (2025-11-22) shows API connected, 4 test customers, 5 active test subscriptions, 2 webhook endpoints.
- **Before production:** replace keys with `sk_live_` / `pk_live_`, create live webhook + price IDs, run end-to-end checkout test, reconcile DB vs Stripe.

### Known pending work

- Switch Stripe from test to live mode
- Real GeoIP service (e.g. MaxMind GeoLite2)
- Link preview page UI
- Bulk link import/export
- Public REST API (Chrome extension uses internal endpoints only)
- ENTERPRISE custom domains and billing flows
- Admin diagnostics polish (originally marked “under construction”)
- Update stale docs (`09-DOC-FEATURES.md`, `08-DOC-README.md` still say “Stripe pending”)

---

## [Chrome Extension 1.3.1] - 2025-11-26

### Changed
- Auto-update extension version in popup title from `manifest.json`
- Sync production manifest with v1.3.1

## [Chrome Extension 1.3.0] - 2025-11-25

### Changed
- Removed promotional tab; updated privacy policy and support page
- Promotional page animation and install redirect flow

### Fixed
- Email sending issues and Chrome extension token expiration
- JSON parsing and path issues in Chrome extension create endpoint

## [Chrome Extension 1.0.0] - 2025-11-24

### Added
- Chrome Extension (Manifest V3) for creating Google Forms shortlinks from the browser
- OAuth token verification API (`/api/chrome/auth/verify`)
- Create-link API endpoint for extension (`/api/chrome/create`)
- Purple-themed popup UI with centered user info

---

## [2.2.0] - 2025-11-22 — Admin, diagnostics & Stripe sync

### Added
- Mini-TOP system monitor with real-time CPU chart (8 cores, dynamic Y-axis)
- Admin `.env` and `.htaccess` file editors
- Report deletion (single + bulk) and CSV export fix for large numbers
- Lazy-loading and caching for admin diagnostics panels
- OS health check with visual gauge charts
- Comprehensive server diagnostics (database, OS, connectivity, Stripe)

### Fixed
- Stripe subscription sync mismatches (`scripts/cleanup_stripe_mismatches.php`)
- `login_logs` table name corrected to `user_login_logs` in reports

### Changed
- Admin panel restructured with tabs and accordion layout
- Favicon converted from ICO to SVG

---

## [2.1.0] - 2025-11-22 — Email integration

### Added
- `EmailService` (PHPMailer + SMTP)
- Email confirmation after link creation (HTML template, non-blocking)
- Subscription success and cancellation emails via Stripe webhook
- Admin email test interface (`public/send-email.php`)
- Email template helpers in `config/helpers.php`

### Security
- Added `.env.bak*` to `.gitignore` after GitHub push protection blocked secrets in backup file

**Merge commit:** `95db9d3` (`email-integration` → `main`)

---

## [2.0.0] - 2025-11-20 — Stripe integration

### Added
- Stripe Checkout for monthly/annual PREMIUM subscriptions
- Stripe Billing Portal for subscription management
- Webhook handler for subscription lifecycle (`checkout.session.completed`, subscription created/updated/deleted)
- Stripe diagnostic tools (`diagnose.php`, `check-subscriptions.php`, `webhook-test.php`)
- Stripe configuration guide (`07-CONFIG-STRIPE_CONFIGURATION.md`)
- Retry logic and validation on checkout/portal API calls

### Changed
- Pricing page wired to Stripe checkout
- Translations updated for billing flows

**Note:** Implemented and tested in **Stripe test mode** only.

---

## [1.1.0] - 2025-11-19 — UI, i18n & admin

### Added
- Internationalization (English / Spanish) with language switcher
- GDPR/CCPA cookie consent banner
- Explore Links page and admin badge display
- Link activation conflict prevention and default expiration date
- Plan and admin badges on dashboard and create-link pages
- Professional analytics chart and tabbed layout on link-details page
- Conditional debug system; ENTERPRISE pricing button fixes
- Admin panel rebuild with modern design and improved search

### Changed
- Landing page and dashboard optimized
- Links page search and filtering fixes

---

## [1.0.3] - 2025-11-19

### Fixed
- **Critical:** QR code not displaying — `$link` variable overwritten by `header.php` foreach loops; fixed by storing in `$linkData`

## [1.0.2] - 2025-11-19

### Fixed
- QR code validation logic rewrite (DB path + filesystem checks)
- Undefined array key warnings across link-details, links, admin
- QR directory path in `config/config.php`
- Enhanced `QrCodeService` error handling

## [1.0.1] - 2025-11-19

### Fixed
- QR code path configuration (`../public/qr`)
- `/regenerate-qr` routing in `.htaccess`
- Null-safe date handling in `html()` helper

---

## [1.0.0] - 2025-11-18 — Initial release

### Added
- Google OAuth2 authentication and session management
- Google Forms URL shortening with quotas (FREE / PREMIUM / ENTERPRISE)
- Click analytics (device, country placeholder, hourly, daily charts)
- Automatic QR code generation
- Dark-mode responsive UI, pricing page, billing views
- Admin panel (user search, ENTERPRISE assignment, login logs)
- PSR-4 architecture, CSRF protection, clean URLs, `.env` config
- MySQL schema: users, short_links, clicks, quota_daily/monthly, user_login_logs

---

## Git summary

| Item | Value |
|------|-------|
| **Remote** | `origin` → `https://github.com/josvaler/SaaS-gform-click-PRE-PRODUCTION.git` |
| **Branches** | `main` (active), `email-integration` (merged) |
| **Total commits** | 42 |
| **Author** | Jose Luis Valerio |
| **Uncommitted changes** | Docs reorganization — old flat names removed, numbered `docs/01-*` … `docs/16-*` added; `docs_backup_20251126_153903/` backup folder (not committed) |

### Recent commit history

```
44170ae feat: Auto-update extension version in title from manifest
a26337e chore: Update Chrome extension to v1.3.1 and sync with production manifest
72c44f1 Fix: Email sending issues and Chrome extension token expiration
913a884 Version 1.3.0: Remove promotional tab, update privacy policy, add support page
cddffe0 feat: Add Chrome Extension for Google Forms shortlink creation
95db9d3 Merge email-integration branch: Add email notifications
fa1b0fb v2.0.0 - Major Release: Complete Stripe Integration
115aaf4 Initial commit: GForms ShortLinks application
```

---

## Roadmap (not yet released)

### v3.0 — Production launch
- [ ] Stripe live mode (`sk_live_` keys, live webhook, live prices)
- [ ] Production QA checklist and monitoring
- [ ] Sync documentation with actual feature state

### v3.1 — Platform
- [ ] GeoIP integration
- [ ] Link preview pages
- [ ] Public API documentation

### v3.2 — Enterprise
- [ ] Custom domains
- [ ] Bulk import/export
- [ ] Advanced reporting

---

*Last updated: 2025-06-10*
