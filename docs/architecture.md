# Architecture

## System overview

GForms ShortLinks (gforms.click) is a URL shortener for Google Forms. Users authenticate with Google OAuth, create short links with analytics, and manage subscriptions via Stripe (currently test mode). A Chrome extension (v1.3.1) creates links from the browser.

## Goals and constraints

- Accept only Google Forms URLs
- Fast 302 redirects with click analytics
- Subscription tiers: FREE, PREMIUM, ENTERPRISE
- Pre-production: Stripe test keys, not live launch yet

## High-level diagram

```
Browser / Chrome Extension
        |
        v
Apache + PHP 8.4 (document root: public/)
        |
        +--> MariaDB (users, links, clicks, subscriptions)
        +--> Google OAuth2
        +--> Stripe (test mode) webhooks
        +--> SMTP (PHPMailer) notifications
```

## Runtime components

| Component | Responsibility | Tech |
|---|---|---|
| `public/` PHP app | Web UI, redirects, APIs | PHP 8.4, Apache |
| `config/` | Bootstrap, helpers, Stripe, translations | PHP |
| `Services/EmailService.php` | Transactional email | PHPMailer |
| `chrome-extension/` | Manifest V3 extension | JS |
| `public/api/chrome/*` | Extension auth + create link | PHP JSON APIs |
| MariaDB | Persistent data | MySQL-compatible |

## Request and data flows

1. **Redirect** — `gforms.click/{code}` → validate → 302 to Google Form → log click.
2. **OAuth login** — Google OAuth → session → user profile sync.
3. **Create link** — Web or extension → validate Form URL → insert row → optional email.
4. **Stripe webhook** — `public/stripe/webhook.php` → subscription lifecycle → DB update + email.

## Data stores

- **MariaDB:** users, short links, click analytics, subscriptions, login logs
- **File cache:** `cache/diagnostics/*.json` for admin panels

## External integrations

- Google OAuth2
- Stripe **test** mode (`sk_test_`, `pk_test_`)
- SMTP (link creation + subscription emails)
- Chrome Web Store extension distribution

## Authentication and authorization

- Google OAuth sessions with CSRF protection
- Plan-based limits (FREE/PREMIUM/ENTERPRISE)
- Admin panel (`public/admin.php`) for operators
- Extension token verification at `/api/chrome/auth/verify`

## Deployment topology

- Path: `/var/www/gforms.click`
- Apache serves `public/` as webroot
- Composer dependencies in `vendor/`
- Env in `.env` (never commit)

## Security boundaries

| Risk | Mitigation |
|---|---|
| Debug PHP in `public/` | Remove or block in production |
| World-readable `.env` | chmod 640, owned by web user |
| Stripe secrets | `.env` only, live keys not yet deployed |

## Observability

- Admin diagnostics tabs (DB, OS, Stripe, connectivity)
- `logs/` and PHP error log
- Stripe dashboard (test mode)

## Known risks / technical debt

- Stripe still in test mode
- GeoIP placeholder (not real country detection)
- Preview pages schema exists, UI not built
- Some admin diagnostics marked under construction

## Related docs

- [11-STATUS-CHANGELOG.md](./11-STATUS-CHANGELOG.md)
- [08-DOC-README.md](./08-DOC-README.md)
- [deployment.md](./deployment.md)
- [roadmap.md](./roadmap.md)
