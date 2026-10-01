# Back-office guide

For shop administrators. The screen is in **Configuration → Tarteaucitron**. Every setting is
stored **per channel**; each field has a help text under it.

## Which channel am I configuring?

The channel name is shown under the page title. When the shop has several channels, it is a
dropdown: pick the channel to configure. Saving never touches the other channels.

A channel starts with tarteaucitron **off** and every service switched off: visitors see nothing
until you enable tarteaucitron, switch on at least one service and save.

## Saving

**Save changes** (top right) saves every tab and the services column at once. You come back to the
tab you were on. When a field is invalid, its tab opens and shows the number of errors next to its
title; nothing is saved until it is fixed.

## CNIL warnings

Some settings move away from the CNIL cookie guidance. The screen then shows a yellow summary
above the tabs, a counter on the tabs concerned and a warning under each setting. Saving is
**never** blocked: these are reminders, not legal advice.

| Setting | Warned when |
|---|---|
| High privacy mode | off: browsing on counts as acceptance |
| Show "Deny all" button | off while "Accept all" is on the banner: refusing must be as easy as accepting |
| Default service state | "Accepted": services load before the visitor chooses |
| Consent lifetime | more than 180 days: the CNIL recommends asking again after 6 months |

## Tabs

### Essentials

- **Enable Tarteaucitron** - the main switch for this channel.
- **Privacy policy URL** and **Read more URL** - links shown in the banner and the panel, for every
  language (per-language links go in **Texts and languages**). An absolute `https://` URL or a
  shop path starting with `/`.
- **Consent lifetime (days)** - how long the visitor's choice is kept before asking again
  (180 by default, at most 364).

### Compliance

**Consent collection**: high privacy mode, the "Accept all" / "Deny all" buttons, the default
state of services, the close button, the mandatory cookies line and the browser "Do Not Track"
request. The defaults follow the CNIL guidance.

**Consent Mode (platform signals)**: Google, Bing, Piano and Piwik PRO consent modes send the
visitor's choice to these platforms. They are not services: switch on the matching services in the
column on the right (the linked services and their state are listed under each option), and
remove the same tags from your theme or Google Tag Manager to avoid loading them twice.

### Texts and languages

One block per language of the channel. For each language:

- the privacy policy and read-more links, when they differ from the Essentials tab;
- the banner title, message and buttons, the preferences panel introduction and the mandatory
  cookies text.

An empty field keeps the Essentials link or the tarteaucitron text, shown greyed out in the field.
The characters `<`, `>` and `"` are refused. A language added to the channel later appears here
automatically.

### Appearance

Banner position (middle, popup, top or bottom), the compact banner, the floating icon that lets
visitors reopen their choices, how services are listed in the panel, and the tarteaucitron
credit.

Keep a way for visitors to change their choice at any time: the floating icon, the compact banner,
or a "Manage cookies" link in your theme. The compact banner ("Manage services" button) only shows
once the visitor has chosen, and does nothing while the floating icon is on too.

A blue note under an option means tarteaucitron.js ignores it with the current settings (for
instance grouping services while the ad-blocker detection is on): it is not a CNIL warning.

### Advanced

Technical settings: the defaults suit most shops.

- **Cookie name** - changing it shows the banner again to every visitor.
- **Cookie domain** - to share the choice across subdomains (e.g. `.example.com`).
- **Send status events to dataLayer** - for Google Tag Manager setups.

## Services

The column on the right lists the services the shop can load, by category. Each category header
shows the number of services switched on, out of the services available.

- Use **Search a service** to find one by name or category.
- Switch a service on to show its settings: its identifier (e.g. the GA4 measurement ID) and, for
  some, a hint. A service switched on without its required identifier shows a warning and is
  **not** loaded on the shop until the field is filled.
- The top of the column lists the services switched on without their required identifier.
- Video, map and social widgets (YouTube, Google Maps…) only need to be switched on: the theme
  places them in pages.

A service your developer added in the configuration appears here like the others.

## What visitors see

On the first visit, the banner asks for consent. **Personalize** opens the preferences panel,
where each service can be allowed or denied; the choice is kept for the consent lifetime, then
asked again. Services only load once allowed (or, with a Consent Mode, in the restricted mode that
platform defines).
