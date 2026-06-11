# GForms ShortLinks

URL shortener built exclusively for Google Forms. Short links, analytics, QR codes, and subscription tiers.

**Status:** Pre-production (Chrome extension v1.3.1) | **Site:** https://gforms.click

## Features

- Google Forms URL validation only (`docs.google.com/forms`, `forms.gle`)
- Memorable short codes and fast 302 redirects
- Google OAuth2 authentication
- Plans: FREE, PREMIUM, ENTERPRISE
- Click analytics (device, country, trends)
- Chrome extension v1.3.1 (create links from Forms)
- Stripe checkout and webhooks (**test mode**)
- SMTP email notifications (link created, subscription events)
- Admin diagnostics and user management

## Tech stack

- PHP 8.4, Apache, MariaDB
- Composer, PHPMailer
- Google OAuth2, Stripe (test keys)
- Chrome Extension (Manifest V3)

## Requirements

- PHP 8.0+
- MariaDB 5.7+
- Apache with `mod_rewrite`
- Composer
- Google OAuth credentials
- Stripe account (test mode configured)

## Quick start

```bash
git clone https://github.com/josvaler/SaaS-gform-click-PRE-PRODUCTION.git
cd SaaS-gform-click-PRE-PRODUCTION
composer install
```

1. Copy `.env.example` to `.env` and configure database, OAuth, Stripe, SMTP
2. Import database schema from `database/`
3. Point Apache DocumentRoot to `public/`
4. Open https://gforms.click

## Production deploy

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl reload apache2
```

## Documentation

| Doc | Description |
|-----|-------------|
| [docs/architecture.md](docs/architecture.md) | System design |
| [docs/deployment.md](docs/deployment.md) | Deploy runbook |
| [docs/roadmap.md](docs/roadmap.md) | Product roadmap |
| [docs/11-STATUS-CHANGELOG.md](docs/11-STATUS-CHANGELOG.md) | Status + full changelog |
| [docs/01-SETUP-QUICKSTART.md](docs/01-SETUP-QUICKSTART.md) | Setup guide |
| [docs/07-CONFIG-STRIPE_CONFIGURATION.md](docs/07-CONFIG-STRIPE_CONFIGURATION.md) | Stripe config |
| [CHANGELOG.md](CHANGELOG.md) | Release summary |

## Project structure

```
public/              # Webroot (Apache DocumentRoot)
config/              # Bootstrap, Stripe, helpers
Services/            # EmailService
database/            # Schema and migrations
chrome-extension/    # MV3 extension source
docs/                # Setup, config, status docs
```

## Server layout

| Component | Detail |
|-----------|--------|
| App path | `/var/www/gforms.click` |
| Webroot | `public/` |
| Database | MariaDB |

## Before production launch

- Switch Stripe from test to live keys
- Remove or protect debug PHP in `public/`
- Complete GeoIP and preview pages
- Final QA and docs sync

## License

Proprietary — VVAIStudio
