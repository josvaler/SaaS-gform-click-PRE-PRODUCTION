# Product Roadmap

## Vision

GForms ShortLinks helps Google Forms publishers share memorable short URLs with analytics, QR codes, and subscription-based power features.

## Current phase

**Pre-production (extension v1.3.1)** — core features built; Stripe test mode; not production-ready.

## Milestones

| Milestone | Target | Status | Outcome |
|---|---|---|---|
| Core shortener + OAuth | 2025-Q4 | Done | Web app live |
| Stripe test integration | 2025-Q4 | Done | Checkout + webhooks |
| Chrome extension v1.3.1 | 2025-Q4 | Done | Create links from Forms |
| Production launch | 2026-Q3 | Planned | Live Stripe + QA |
| GeoIP + preview pages | 2026-H2 | Planned | Premium feature completion |

## Now (0–3 months)

- **P0:** Switch Stripe from test to live keys (when ready to launch)
- **P0:** Remove/protect debug endpoints in `public/`
- **P1:** SECURITY.md, BACKLOG.md, VERSION at project root
- **P1:** Real GeoIP (MaxMind GeoLite2 or equivalent)
- **P2:** Link preview page UI
- **P2:** Admin diagnostics polish

## Next (3–6 months)

- Bulk link import/export
- Public REST API (beyond extension internal endpoints)
- ENTERPRISE custom domains

## Later (6+ months)

- Mobile-optimized dashboard
- Advanced team workspaces

## Dependencies and blockers

- Stripe live account approval and webhook setup
- DNS and TLS already in place
- Chrome Web Store extension review for updates
- Sync stale docs (`08-DOC-README.md`, `09-DOC-FEATURES.md`)

## Success metrics

- Successful redirect latency < 100ms P95
- Stripe subscription sync accuracy 100%
- Extension daily active users

## Out of scope

- Generic URL shortener (non-Google-Forms URLs)
- Self-hosted deployment for third parties (SaaS only)

## Alignment with engineering backlog

Execution tasks in `BACKLOG.md`. Detailed status in [11-STATUS-CHANGELOG.md](./11-STATUS-CHANGELOG.md).

## Review cadence

Update monthly during pre-production; each release after launch.
