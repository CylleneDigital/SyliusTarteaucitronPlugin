# Security policy

This plugin injects a consent banner and third-party tracker snippets into the shop front.
A flaw can bypass consent or execute stored script in visitors' browsers. Please report
it privately.

## Reporting a vulnerability

**Do not open a public issue.** Report privately:

- GitHub private vulnerability reporting:
  https://github.com/CylleneDigital/SyliusTarteaucitronPlugin/security/advisories/new
- email to sylius@groupe-cyllene.com

Please include:

- the plugin version
- the vendored tarteaucitron.js version (`public/tarteaucitron/VERSION`)
- the shop locale
- Sylius and PHP versions
- steps to reproduce

Do **not** include customer pre-production URLs.

## Response time

First response within **5 working days**. We keep you posted on the analysis, then on the
fix and its release date.

## Supported versions

| Version | Support |
|---|---|
| `1.x` | Bug and security fixes |

## Disclosure

Coordinated disclosure: the fix is released first, then the advisory. We are happy to
credit the reporter, unless they ask otherwise.
