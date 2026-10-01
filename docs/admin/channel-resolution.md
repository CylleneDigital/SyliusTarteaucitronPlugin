# Admin channel resolution

## Problem

Sylius exposes `ChannelContextInterface` (`sylius.context.channel`) which reflects the **shop visitor channel**, not the channel the administrator wants to configure.

In multi-channel back office, using shop ChannelContext would mix configurations.

## Solution: `AdminChannelResolver`

File: `src/Sylius/Admin/AdminChannelResolver.php`

### `fromRequest(Request $request): ChannelInterface`

1. Read query param `channelCode`
2. If non-empty → `ChannelRepository::findOneBy(['code' => $code])`
   - Not found → `404 NotFoundHttpException`
3. If empty → first channel from `allOrdered()` (sorted by `id ASC`)
   - No channels → `404`

### `allOrdered(): list<ChannelInterface>`

Lists all channels sorted by ID — used for UI selector.

## Usage

Only in `TarteaucitronConfigurationAction` (admin).

**Do not use** on shop: shop uses `ConsentConfigurationProvider` + `sylius.context.channel`.

## Typical URLs

```
/admin/tarteaucitron?channelCode=fashion_web
/admin/tarteaucitron?channelCode=food_web
```

After save, redirect keeps `channelCode`:

```php
$this->urlGenerator->generate('cyllene_digital_sylius_tarteaucitron_admin_configuration', [
    'channelCode' => (string) $channel->getCode(),
]);
```

## Shop vs admin contrast

| Context | Channel source | Service |
|---------|----------------|---------|
| Shop front | Visitor / hostname / Sylius routing | `ChannelContextInterface` |
| Admin plugin | Query `channelCode` | `AdminChannelResolver` |

## Tests

Covered by `tests/Unit/Sylius/Admin/AdminChannelResolverTest.php`.
