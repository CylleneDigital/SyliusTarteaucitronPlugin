# Embeds (YouTube, Maps, widgets)

## Concept

Some tarteaucitron services do not load a script via ID but **replace HTML placeholders** in content (deferred iframe, click-to-load).

In the plugin, they are marked `isEmbed(): true` in their tracker definition.

## Embed trackers in catalog

| type | Category |
|------|----------|
| `adsense` | ads |
| `amazon` | ads |
| `kwanko` | ads |
| `trustpilot` | other |
| `calendly` | other |
| `vimeo` | video |
| `dailymotion` | video |
| `recaptcha` | api |
| `googlemapsembed` | api |
| `youtube` | video |
| `youtubeplaylist` | video |
| `tiktokvideo` | video |
| `facebook` | social |
| `facebookpost` | social |
| `instagram` | social |
| `twitter` | social |
| `twitterembed` | social |
| `linkedin` | social |
| `pinterest` | social |

## Activation

1. Back office: switch the service on in the services column
2. Save channel configuration
3. Theme: place HTML placeholders with tarteaucitron classes

Without back-office activation, `job.push` is omitted — placeholders won't be managed.

## Placeholders in theme

Follow the [official tarteaucitron documentation](https://tarteaucitron.io/en/free-installation-open-source/).

YouTube example (illustrative — verify vendor doc):

```html
<div class="youtube_player" videoID="VIDEO_ID" width="560" height="315"></div>
```

CSS classes (`youtube_player`, etc.) are defined by tarteaucitron.js, not the PHP plugin.

## Twig helper

```twig
{% if tarteaucitron_is_embed('youtube') %}
    {# product / CMS template with placeholder #}
{% endif %}
```

Useful to render embed markup only when tracker is **known** to registry (built-in or custom with `isEmbed(): true`).

**Current limitation:** `tarteaucitron_is_embed()` does not check enabled state in DB. In practice, enable in back office and condition display on the same business flag if needed.

## Plugin JS rendering

An enabled embed without parameters produces:

```javascript
(tarteaucitron.job = tarteaucitron.job || []).push("youtube");
```

No `tarteaucitron.user.*` assignment if no parameters.

## reCAPTCHA (special case)

Embed with optional parameters:

- `recaptcha_api` → `tarteaucitron.user.recaptchaapi`
- `recaptcha_hl` → `tarteaucitron.user.recaptcha_hl`

## Google Fonts

`googlefonts` is **not** an embed (`isEmbed: false`) — requires `google_fonts` parameter.

## Integration testing

The `@javascript` Behat scenarios enable `youtube` (tarteaucitron.js only opens the banner when an
enabled service needs consent) and check the banner in a real browser; they do not check the
placeholder replacement itself. Validate embeds manually on the shop with tarteaucitron enabled and
the service switched on.
