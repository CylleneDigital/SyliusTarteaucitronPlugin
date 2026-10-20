# 0018. The CSP nonce comes from a provider, fixed or per response

- Status: accepted
- Date: 2026-10-20

## Context

The shop snippet is made of script tags, some inline, and a shop with a nonce-based
Content-Security-Policy blocks them unless they carry the nonce of the current response. A nonce set
once in the configuration is the same on every response, which defeats the purpose of a nonce;
shops generate theirs with different libraries, or not at all.

## Decision

The plugin asks a nonce provider each time it renders its script tags. Two exclusive bundle keys
choose it: `script_nonce` for a fixed value, or `script_nonce_provider` for a per-response one, either
`nelmio` for NelmioSecurityBundle or the id of a service implementing the public
`ScriptNonceProviderInterface`. The nonce goes on the shop scripts, the script preloads and the
back-office script. NelmioSecurityBundle stays optional; asking for it without the bundle fails the
container build.

## Consequences

- The plugin fits whatever CSP library the shop already uses, without depending on one.
- Files the library injects itself, and the scripts of accepted services, carry no nonce: the
  shop's policy has to allow them.
- Behind a full-page cache, a per-response nonce is cached with the page and protects nothing.

## Related

- [0016. Every value written into JavaScript goes through InlineJson](0016-javascript-values-encoded-by-inline-json.md)
- [0019. No inline script in the back office](0019-no-inline-script-in-back-office.md)
