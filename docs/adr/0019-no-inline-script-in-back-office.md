# 0019. No inline script in the back office

- Status: accepted
- Date: 2026-10-20

## Context

The configuration page needs a little behaviour: remembering the active tab across a save, opening
the tab that holds an error, searching the services. An inline script would be blocked by a strict
back-office Content-Security-Policy. A Stimulus controller would have to go through the
application's admin front-end build, which the plugin otherwise does not need.

## Decision

The page holds no inline script and no inline event handler. Its behaviour and its styles live in a
plain script and a plain stylesheet of the plugin, published with its other public files: no
front-end build in the application, no Stimulus controller. The script is loaded with the CSP nonce
and a content fingerprint, and the page hands it its state through data attributes. The stylesheet
and the templates hard-code no colour, so the Sylius admin theme, dark mode included, applies.

## Consequences

- The back office works under a strict script policy, without a front-end build step, and follows
  the admin theme.
- The behaviour is plain JavaScript, outside the Stimulus conventions of the Sylius admin.
- A shop that overrides the page template has to keep the data attributes the script reads.

## Related

- [0018. The CSP nonce comes from a provider, fixed or per response](0018-csp-nonce-from-a-provider.md)
- [0022. Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md)
- [0028. The back office is organised by intention](0028-back-office-organised-by-intention.md)
