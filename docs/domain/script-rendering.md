# Tracker JavaScript rendering

## `TrackerScriptRenderer` class

File: `src/Consent/Tracker/TrackerScriptRenderer.php`

Responsibility: transform a list of `TrackerRuntimeState` (read from DB) into executable JavaScript snippets after `tarteaucitron.init()`.

## Input: `TrackerRuntimeState`

Readonly DTO (internal):

```php
final readonly class TrackerRuntimeState
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public string $type,
        public bool $enabled,
        public array $parameters = [],
    ) {
    }
}
```

Built by `ConsentConfigurationProvider::toRuntimeState()` from `TarteaucitronService`; non-string
values of the JSON `parameters` column are dropped on the way.

## `render(TrackerRuntimeState $service)` algorithm

1. Lookup definition via `TrackerRegistry::getOrNull($service->type)`
2. If definition missing → returns `null` (silent)
3. If `!areRequiredParametersSatisfied($enabled, $parameters)` → returns `null`
4. For each `TrackerParameter` with non-empty value:
   - Emits `tarteaucitron.user.{userKey} = {InlineJson::encode(value)};`
5. Emits `(tarteaucitron.job = tarteaucitron.job || []).push({InlineJson::encode(type)});`

## Output example

Configuration: gtag enabled, `gtag_ua = G-XXXXXXXXXX`

```javascript
tarteaucitron.user.gtagUa = "G-XXXXXXXXXX";
(tarteaucitron.job = tarteaucitron.job || []).push("gtag");
```

## XSS security

**Absolute rule:** database values always pass through `InlineJson::encode()`
(`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS`, plus `JSON_UNESCAPED_SLASHES |
JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`): `<`, `>`, `&`, `"` and `'` come out as `\u003C`-style
escapes, so a value can close neither the `<script>` element nor an HTML attribute. The shop template
uses the same flags via the `|tarteaucitron_json` filter.

## Parameterless embed trackers

An enabled embed (e.g. `youtube`) with zero required parameters produces only:

```javascript
(tarteaucitron.job = tarteaucitron.job || []).push("youtube");
```

HTML placeholders in the theme are still required (tarteaucitron CSS classes).

## `renderAll(iterable $services)`

Concatenates non-null snippets with `\n`. Called by `TarteaucitronRuntime::trackerScripts()`.

## Exclusion conditions

| Situation | Snippet emitted? |
|-----------|------------------|
| Service disabled | No |
| Required parameter empty | No |
| Type unknown to registry | No |
| Service enabled, embed without params | Yes (job.push only) |
| Service enabled, all required params filled | Yes |

## Full shop chain

```
TarteaucitronService (DB)
  → TrackerRuntimeState
  → TrackerScriptRenderer::renderAll()
  → tarteaucitron_tracker_scripts() (Twig)
  → templates/shop/tarteaucitron.html.twig (|raw)
```

The `|raw` filter is safe because the renderer only emits encoded JS.
