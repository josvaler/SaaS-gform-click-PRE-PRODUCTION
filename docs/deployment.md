# Deployment Guide

## Environments

| Environment | URL | Notes |
|---|---|---|
| Pre-production | https://gforms.click | Stripe test mode |

## Server prerequisites

- PHP 8.4+, Apache with `mod_rewrite`
- MariaDB database
- Composer 2.x
- TLS certificate for gforms.click

## Repository layout

```
/var/www/gforms.click/
├── public/            # Webroot (Apache DocumentRoot)
├── config/            # App configuration
├── Services/          # EmailService, etc.
├── database/          # schema + migrations
├── scripts/           # maintenance scripts
├── chrome-extension/  # MV3 extension source
├── docs/              # setup, config, status docs
├── composer.json
└── .env               # secrets (not in git)
```

## Environment variables

`.env` file (see `.env.example` if present):

- Database credentials (MariaDB)
- Google OAuth client ID/secret
- Stripe test keys + webhook secret
- SMTP settings for EmailService

## Install and build

```bash
cd /var/www/gforms.click
composer install --no-dev --optimize-autoloader
# apply SQL migrations in database/ as needed
```

## Start / stop / restart

Apache-based (no PM2):

```bash
sudo systemctl reload apache2
```

## Reverse proxy / TLS

Apache vhost points DocumentRoot to `public/`. `.htaccess` handles rewrites.

## Deployment procedure

1. `git pull origin main`
2. `composer install --no-dev`
3. Run any new SQL migrations
4. `sudo systemctl reload apache2`
5. Test: login, create link, redirect, Stripe webhook test event

## Rollback procedure

1. `git checkout <previous-commit>`
2. `composer install`
3. Reload Apache
4. Restore MariaDB backup if schema migration was applied

## Post-deploy verification

- https://gforms.click loads
- Google OAuth login works
- Short link redirect + click logged
- Admin diagnostics: DB + Stripe connected
- Extension create-link API returns 200

## Logs and troubleshooting

- Apache error log
- PHP errors (check `public/` permissions)
- Stripe webhook log in dashboard
- Admin diagnostics cache in `cache/diagnostics/`

## Backup and restore

- MariaDB: include in backup strategy (nightly recommended)
- Code: git (`josvaler/SaaS-gform-click-PRE-PRODUCTION`)

## Security checklist

- Remove or protect debug PHP endpoints in `public/`
- `.env` permissions not world-readable
- Before production: switch to Stripe live keys + new webhook
- No test scripts exposed in webroot

## Related docs

- [01-SETUP-QUICKSTART.md](./01-SETUP-QUICKSTART.md)
- [02-SETUP-UBUNTU.md](./02-SETUP-UBUNTU.md)
- [07-CONFIG-STRIPE_CONFIGURATION.md](./07-CONFIG-STRIPE_CONFIGURATION.md)
- [architecture.md](./architecture.md)
