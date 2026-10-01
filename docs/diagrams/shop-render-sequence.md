# Shop sequence - page render

## Actors

- Browser
- Twig (`sylius_shop.base.head`)
- `TarteaucitronRuntime`
- `ConsentConfigurationProvider`
- `InitOptionsMapper`
- `TrackerScriptRenderer`
- `TarteaucitronLanguageResolver`
- tarteaucitron.js (browser)

## Diagram

```mermaid
sequenceDiagram
    participant B as Browser
    participant T as tarteaucitron.html.twig
    participant RT as TarteaucitronRuntime
    participant P as ConsentConfigurationProvider
    participant CC as ChannelContext Sylius
    participant Repo as ConfigurationRepository
    participant M as InitOptionsMapper
    participant SR as TrackerScriptRenderer
    participant L as TarteaucitronLanguageResolver
    participant JS as tarteaucitron.min.js

    B->>T: Render shop page (head hook)
    T->>RT: tarteaucitron_enabled()
    RT->>P: resolve()
    P->>CC: getChannel()
    CC-->>P: ChannelInterface
    P->>Repo: findShopConsentByChannel(channel)

    alt No config or disabled
        P-->>RT: ResolvedConsent(enabled: false)
        RT-->>T: false
        T-->>B: (nothing injected)
    else Active config
        P->>M: toTarteaucitronInit(options)
        P->>P: merge the integration options (YAML)
        P-->>RT: ResolvedConsent(enabled, init, services, lifetime, localizedOptions)
        RT-->>T: true

        T->>RT: tarteaucitron_script_nonce()
        RT-->>T: nonce of the provider or null
        T->>RT: tarteaucitron_custom_text()
        RT->>L: currentLocaleCode()
        RT-->>T: texts of the current locale (may be empty)
        T->>RT: tarteaucitron_init()
        RT-->>T: array jsKeys (current locale links applied)

        T->>RT: tarteaucitron_language(), tarteaucitron_library_version()
        RT->>L: resolve()
        RT-->>T: language code (e.g. es), library version (compiled)
        Note over T: preloads: lang + services (unless useExternalJs), library CSS (unless useExternalCss)
        T->>RT: tarteaucitron_asset_version('css/sylius-fix.css')
        RT-->>T: ?v= fingerprint (compiled)
        T->>RT: tarteaucitron_consent_lifetime_days()
        RT-->>T: days (tarteaucitronForceExpire)

        T->>RT: tarteaucitron_asset_version('tarteaucitron.min.js')
        RT-->>T: ?v= fingerprint (compiled)

        T->>RT: tarteaucitron_tracker_scripts()
        RT->>SR: renderAll(services)
        SR-->>RT: JS snippets
        RT-->>T: string

        T-->>B: preloads + sylius-fix.css + globals + min.js + inline init
        B->>JS: tarteaucitron.init({...})
        B->>JS: user.* assignments + job.push
        JS-->>B: Consent banner + services
    end
```

## Key points

1. **ChannelContext** determines which configuration to load - not admin query param
2. **Provider cache** - one expensive `resolve()` per channel/request
3. **`tarteaucitron_json`** filter in Twig for the init payload and the three globals
   (`tarteaucitronForceLanguage`, `tarteaucitronForceExpire`, `tarteaucitronCustomText`); **PHP
   `InlineJson`** in the script renderer for `user.*` - same flags
4. If enabled false → template emits no tarteaucitron script tags

## Source files

- `templates/shop/tarteaucitron.html.twig`
- `src/Twig/TarteaucitronRuntime.php`
- `src/Sylius/Provider/ConsentConfigurationProvider.php`
- `src/Consent/Tracker/TrackerScriptRenderer.php`
- `config/twig_hooks/shop.yaml`
