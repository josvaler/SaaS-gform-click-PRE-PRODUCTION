# Security Policy

**VVAIStudio** takes the security of its software, services, and customer data seriously. This document describes how to report vulnerabilities in **GForms ShortLinks** (gforms.click).

> **Scope:** https://gforms.click and the Chrome extension (v1.3.1). Currently **pre-production** (Stripe test mode).

---

## Supported Versions

| Version | Supported | Notes |
| ------- | --------- | ----- |
| `1.3.x` | Yes | Current line (extension + web) |
| `1.0.x`–`1.2.x` | Limited | Critical fixes only |
| `< 1.0` | No | End of support |

See [CHANGELOG.md](./CHANGELOG.md) and [docs/11-STATUS-CHANGELOG.md](./docs/11-STATUS-CHANGELOG.md).

---

## Reporting a Vulnerability

**Do not report security vulnerabilities through public GitHub Issues.**

### Primary contact

**Email:** [security@vvaistudio.com](mailto:security@vvaistudio.com)

### Fallback contact

**Email:** [info@vvaistudio.com](mailto:info@vvaistudio.com)  
**Subject:** `SECURITY — GForms ShortLinks — [Brief summary]`

### What to include

1. Component (web app, redirect handler, admin, Chrome extension API, Stripe webhook)
2. Affected URL or endpoint
3. Steps to reproduce
4. Your contact information

---

## Response Timelines

| Stage | Target |
| ----- | ------ |
| Acknowledgment | 3 business days |
| Initial triage | 7 business days |
| Critical fix | 7–14 days |

---

## Responsible Disclosure

Avoid accessing other users' links or account data. Do not test on production Stripe live mode until launched.

---

## Safe Harbor

Good-faith research under this policy with prompt reporting to [security@vvaistudio.com](mailto:security@vvaistudio.com).

Does not cover Google OAuth, Stripe, or MariaDB vendor issues unless VVAIStudio code or configuration is the root cause.

---

## Out of Scope

- Stripe test-mode-only findings without production impact (note if live launch would change severity)
- Google Forms or Google OAuth platform bugs without app flaw
- DoS without prior approval
- Social engineering against users

---

## Product-Specific Notes (GForms ShortLinks)

- **Redirect endpoint** (`public/redirect.php`): report open redirect only if non-Google-Forms URLs can be shortened or redirected maliciously.
- **Debug/test PHP in `public/`**: any world-accessible debug script is high priority; include exact path.
- **`.env` file permissions**: world-readable `.env` is critical; do not exfiltrate secret values in reports.
- **Chrome extension** (`/api/chrome/*`): report token theft, CSRF on create-link, or OAuth scope abuse.
- **Stripe webhook** (`public/stripe/webhook.php`): report signature bypass or subscription state manipulation.
- **Admin panel**: report unauthorized access to user data, env editor, or htaccess editor.
- **Pre-production:** Stripe keys are **test mode** (`sk_test_`); live-key issues apply after launch.

---

## Contact

| Purpose | Contact |
| ------- | ------- |
| Security reports | [security@vvaistudio.com](mailto:security@vvaistudio.com) |
| General | [info@vvaistudio.com](mailto:info@vvaistudio.com) |

**Last updated:** 2026-06-10  
**Policy owner:** VVAIStudio Security / Engineering
