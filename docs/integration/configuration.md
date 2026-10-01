# Bundle configuration (integrators)

The back office holds what a **shop admin** tunes, per channel: activation, links, consent
behaviour, texts, appearance, services. The bundle configuration holds what belongs to the
**integration** — the theme, the CSP, the hosting — and applies to **every channel**. It is
versioned with the project and can differ per environment.

Nothing is required: without a file, the plugin runs the official tarteaucitron.js install.

## Where

```yaml
# config/packages/cyllene_digital_sylius_tarteaucitron.yaml
cyllene_digital_sylius_tarteaucitron:
    script_nonce: ~
    script_nonce_provider: ~
    locale_aliases: { nb: no }
    integration:
        use_external_css: false
        mandatory_cta: false
        use_external_js: false
        server_side: false
        adblocker: false
        hashtag: '#tarteaucitron'
        custom_closer_id: ~
    trackers: {}
```

These are the defaults. Write only the keys you change. Per environment, override in
`config/packages/<env>/cyllene_digital_sylius_tarteaucitron.yaml`.

Check what the application actually uses:

```bash
bin/console debug:config cyllene_digital_sylius_tarteaucitron
bin/console config:dump-reference cyllene_digital_sylius_tarteaucitron
```

An unknown key, a wrong type or an invalid value stops the container build with an explicit
message — nothing is silently ignored.

## Back office or configuration?

| Setting | Where | Why |
|---|---|---|
| Enable, privacy / read-more links, consent lifetime | Back office, "Essentials" | Per channel, owned by the shop |
| High privacy, accept / deny buttons, default state, Consent Mode | Back office, "Compliance" | Compliance choices, warned against the CNIL guidance |
| Links and banner texts per language | Back office, "Texts and languages" | Per channel locale |
| Banner position, icon, lists, credit | Back office, "Appearance" | Look, per channel |
| Cookie name / domain, dataLayer event | Back office, "Advanced" | Can differ per channel (one domain per channel) |
| Services and their identifiers | Back office, services column | Per channel |
| CSP nonce, locale mapping | Configuration | Infrastructure |
| External CSS / JS, buttons on the mandatory cookies line, server-side tagging, ad-blocker message, panel anchor, focus target | Configuration, `integration` | Depend on the theme; a wrong value breaks the banner |
| Vendor services without a PHP class | Configuration, `trackers` | Declared by a developer, then enabled per channel in the back office |

## `script_nonce` / `script_nonce_provider`

CSP nonce added to the `<script>` tags the plugin emits (the three of
`templates/shop/tarteaucitron.html.twig`). Default: none. Set **one** of the two keys.

**Per response** — what a nonce-based Content-Security-Policy needs. With
[NelmioSecurityBundle](https://github.com/nelmio/NelmioSecurityBundle):

```yaml
cyllene_digital_sylius_tarteaucitron:
    script_nonce_provider: nelmio
```

The plugin then asks NelmioSecurityBundle for the script nonce of the current response, which also
adds it to the `script-src` directive of the CSP header. The container build fails if the bundle
is not installed.

With another CSP library, write a service implementing
`CylleneDigital\SyliusTarteaucitronPlugin\Csp\ScriptNonceProviderInterface` and give its id:

```yaml
cyllene_digital_sylius_tarteaucitron:
    script_nonce_provider: App\Csp\TarteaucitronNonceProvider
```

`getScriptNonce()` is called on every render of the banner snippet; return `null` for no nonce.

**Fixed** — the same nonce on every response, e.g. a value set by the hosting:

```yaml
cyllene_digital_sylius_tarteaucitron:
    script_nonce: '%env(default::CSP_NONCE)%'
```

Files tarteaucitron.js injects itself (`lang/`, `tarteaucitron.services.min.js`,
`advertising.min.js`) and the third-party scripts of accepted services are **not** covered: your
CSP must allow them (`script-src 'self'` plus each service domain).

## `locale_aliases`

tarteaucitron.js picks its language among `availableLanguages` (see
`public/tarteaucitron/tarteaucitron.min.js`). The plugin maps the Sylius locale onto that list —
`es_ES` → `es`, unknown → `en`. Aliases cover locales whose code differs from the library one.

```yaml
cyllene_digital_sylius_tarteaucitron:
    locale_aliases:
        nb: no        # Norwegian Bokmål → library "no" (default)
        nn: no        # Norwegian Nynorsk
```

A key is either the primary subtag (`nb`) or the full locale, written `nb-no` or `nb_NO`: keys are
normalized to lower case with a dash, so `nb_NO`, `nb-NO` and `nb-no` are the same alias. A locale whose primary subtag already is a library language (`pt_BR` → `pt`)
needs no alias. Writing the key replaces the default map: keep `nb: no` if you need it.

## `integration`

`tarteaucitron.init()` options that depend on the theme. They are merged into the init payload of
every channel, after the back-office options.

### `use_external_css` (default `false`)

`true`: the library stops injecting `css/tarteaucitron.min.css`. Your theme must then ship a
**complete** banner stylesheet — without one the banner renders unstyled over the page. The plugin
still loads `css/sylius-fix.css`.

```yaml
cyllene_digital_sylius_tarteaucitron:
    integration:
        use_external_css: true
```

Start from `public/tarteaucitron/css/tarteaucitron.css` (vendored version), and re-check it after
each library upgrade.

### `mandatory_cta` (default `false`)

`true`: the "Mandatory cookies" line of the panel gets a disabled "Allow" button and a "Deny" button,
to show those cookies cannot be turned off. The library stylesheet hides these buttons
(`display: none !important`), so the key only has an effect with your own stylesheet
(`use_external_css: true`) that shows them.

### `use_external_js` (default `false`)

`true`: the library stops loading its language file and `tarteaucitron.services.min.js`. Your theme
must load both **before** `tarteaucitron.init()`, or no banner appears. Only for a theme that bundles
the library differently.

### `server_side` (default `false`)

`true`: accepted services are not loaded in the browser. Only when every tag goes through a
server-side tagging container (e.g. GTM server-side). The consent choice is still collected and
stored in the cookie.

### `adblocker` (default `false`)

`true`: when an ad blocker prevents tarteaucitron from working, the banner is replaced by a message
asking to disable it. The library then loads `advertising.min.js` (vendored) to detect the blocker.

### `hashtag` (default `'#tarteaucitron'`)

URL fragment that opens the preferences panel. It must start with `#`.

Visitors must be able to change their choice at any time. Besides the floating icon ("Appearance"
tab), put a link in the footer, either through the anchor:

```twig
<a href="#tarteaucitron">{{ 'app.footer.manage_cookies'|trans }}</a>
```

or through the class the library wires itself — no inline JavaScript, so it works under a strict CSP:

```twig
<button type="button" class="tarteaucitronOpenPanel">{{ 'app.footer.manage_cookies'|trans }}</button>
```

Changing `hashtag` means updating those links.

### `custom_closer_id` (default none)

HTML `id` of the element that receives the keyboard focus when the panel closes, when neither the
banner nor the compact banner is on screen. Point it at the footer link above so keyboard users land
back where they were:

```yaml
cyllene_digital_sylius_tarteaucitron:
    integration:
        custom_closer_id: manage-cookies
```

```twig
<button type="button" id="manage-cookies" class="tarteaucitronOpenPanel">…</button>
```

It must be a valid HTML id (a letter, then letters, digits, `-`, `_`, `:` or `.`).

## `trackers`

Offers a service of the vendored `tarteaucitron.services.js` in the back office without writing a
PHP class. It then behaves like a built-in one: on/off switch per channel, parameters, "incomplete"
warning.

```yaml
cyllene_digital_sylius_tarteaucitron:
    trackers:
        smartsupp:                      # tarteaucitron job key
            category: support           # analytic, ads, api, video, social, support, comment, other
            label: Smartsupp            # back-office name (translation key or text), default: the job key
            parameters:
                smartsuppKey:           # exact tarteaucitron.user.* key the service reads
                    placeholder: 'xxxxxxxx'
                    # required: true    # default; false for an optional key
                    # label: 'Smartsupp key'
            # embed: true               # for services replacing HTML placeholders (videos, widgets)
```

To find the job key and its `user.*` keys, search `public/tarteaucitron/tarteaucitron.services.js`
for `tarteaucitron.services.<key> = {` and read the `tarteaucitron.user.*` it uses.

The container build fails when the job key does not exist in the vendored library, when a parameter
is not a `user.*` key that service reads, or when the job key is already used by a built-in tracker
(`UniqueTrackerTypePass`). Services this
cannot express (admin hint, Consent Mode link) need a PHP class: see
[Custom tracker](custom-tracker.md).

## Upgrading from a version with these settings in the back office

`use_external_css`, `mandatory_cta`, `use_external_js`, `server_side`, `adblocker`, `hashtag` and
`custom_closer_id` used to be back-office options. Values saved then are **ignored**: set them in `integration` if you
had changed them. See [UPGRADE.md](../../UPGRADE.md).
