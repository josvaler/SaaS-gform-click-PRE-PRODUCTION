# Engineering Backlog

**Project:** GForms ShortLinks  
**Domain:** https://gforms.click  
**Repository:** `/var/www/gforms.click`  
**Stack:** PHP 8.4 + MariaDB + Stripe (test) + Chrome Extension  
**Version:** `1.3.2` (extension line)  
**Company:** VVAIStudio

## Project summary

URL shortener for Google Forms with analytics, QR codes, and subscription tiers. Chrome extension creates links from Forms. Pre-production: Stripe test mode, production launch pending.

## Status snapshot

| Area | Status | Notes |
| ---- | ------ | ----- |
| Core shortener | Pre-production | Redirects + analytics |
| Auth | Pre-production | Google OAuth2 |
| Stripe | Test mode | Checkout + webhooks; not live keys |
| Email | Done | PHPMailer + SMTP |
| Chrome extension | Done | v1.3.1 |
| Admin diagnostics | Partial | Some tabs under construction |
| GeoIP | Placeholder | Not real country detection |
| Preview pages | Missing | Schema only |
| Docs | Done | Numbered docs/ + architecture, deployment, roadmap |

## Now (in progress or next 1-2 weeks)

- [ ] **P0** Remove or protect debug/test PHP in `public/` webroot
- [ ] **P0** Verify `.env` file permissions (not world-readable)
- [ ] **P1** SECURITY.md, BACKLOG.md, VERSION at project root
- [ ] **P1** Sync stale docs (`08-DOC-README.md`, `09-DOC-FEATURES.md`) with Stripe status
- [ ] **P2** Real GeoIP integration (MaxMind GeoLite2)

## Next (queued, not started)

- [ ] **P0** Switch Stripe to live keys + live webhook (when ready to launch)
- [ ] **P1** Link preview page UI
- [ ] **P1** Admin diagnostics polish
- [ ] **P2** Bulk link import/export
- [ ] **P2** Public REST API (extension uses internal endpoints today)
- [ ] **P2** ENTERPRISE custom domains

## Later (backlog / ideas)

- Mobile-optimized dashboard
- Team workspaces
- Advanced fraud detection on redirects

## Blocked / waiting

- **Production launch**: blocked on Stripe live setup and security hardening
- **GeoIP vendor**: TBD

## Done recently (last 30 days)

- Docs reorganization into numbered `docs/` structure
- Root CHANGELOG.md and LICENSE added
- See [docs/11-STATUS-CHANGELOG.md](./docs/11-STATUS-CHANGELOG.md) for full history

## Technical debt

- Stripe test vs live documentation drift
- Debug endpoints in webroot (portfolio scanner HIGH findings)
- GeoIP placeholder
- `docs_backup_20251126_153903/` duplicate tree

## Security & compliance

- [ ] Remove debug PHP from `public/`
- [ ] `.env.example` present and `.env` not world-readable
- [ ] Stripe webhook signature verification audit
- [ ] CSRF review on admin forms
- [ ] Chrome extension token expiration handling (recent fixes in 1.3.x)

## How to update this file

Sync with [CHANGELOG.md](./CHANGELOG.md) and [docs/11-STATUS-CHANGELOG.md](./docs/11-STATUS-CHANGELOG.md). Update **Overall status:** when launching production.
