# Security policy

This plugin prints the consent banner and the tracker snippets into every shop page, from values an
administrator stores in the back office. A flaw here can load trackers before consent, or run stored
script in visitors' browsers, so please report it privately.

## Reporting a vulnerability

**Do not open a public issue.** Use either of the two private channels:

- [GitHub security advisory](https://github.com/CylleneDigital/SyliusTarteaucitronPlugin/security/advisories/new)
  ("Report a vulnerability")
- email to sylius@groupe-cyllene.com

Please include the plugin version, the vendored tarteaucitron.js version
(`public/tarteaucitron/VERSION`), the Sylius and PHP versions, the shop locale, and the steps to
reproduce. **Leave out real tracker identifiers and customer pre-production URLs.**

## Response time

First response within **5 working days**. We keep you posted on the analysis, then on the fix and
its release date.

## Supported versions

| Version | Support |
|---|---|
| `1.x` | Bug and security fixes |

The full policy, including what happens to a major version once the next one is out, is in
[`docs/architecture/public-contract.md`](docs/architecture/public-contract.md#support-policy).

## Disclosure

Coordinated disclosure: the fix is released first, then the advisory. We are happy to credit the
reporter, unless they ask otherwise.
