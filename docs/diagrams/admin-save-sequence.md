# Admin sequence — configuration save

## Actors

- Administrator
- `TarteaucitronConfigurationAction`
- `AdminChannelResolver`
- `TarteaucitronConfigurationFactory`
- `ServiceCatalogSynchronizer`
- `TarteaucitronConfigurationType` + DataMappers
- `EntityManager`

## Diagram

```mermaid
sequenceDiagram
    participant U as Administrator
    participant C as ConfigurationAction
    participant R as AdminChannelResolver
    participant Repo as ConfigurationRepository
    participant F as ConfigurationFactory
    participant S as ServiceCatalogSynchronizer
    participant Form as Symfony Form
    participant EM as EntityManager

    U->>C: GET /admin/tarteaucitron?channelCode=WEB
    C->>R: fromRequest(request)
    R-->>C: ChannelInterface
    C->>Repo: findOneByChannel(channel)

    alt No configuration in DB
        C->>F: createForChannel(channel)
        F->>F: setInitOptions(defaults)
        F->>S: ensureSeeded(configuration)
        S-->>F: +N TarteaucitronService (disabled)
    else Existing configuration
        C->>F: ensureCatalog(configuration)
        F->>S: ensureSeeded(configuration)
    end

    C->>Form: create + handleRequest (GET)
    C-->>U: HTML form (Twig hooks)

    U->>C: POST (enabled, lifetime, init fields, localized options, services)
    C->>Form: handleRequest + isValid()

    alt Form valid
        C->>EM: persist(configuration)
        C->>EM: flush()
        C-->>U: 302 redirect + flash success
    else Validation errors
        C-->>U: HTML form + errors
    end
```

## Key points

1. **First GET** may create in-memory entity without `flush` — shop tarteaucitron stays off
2. **ensureSeeded** is idempotent — safe on every page open
3. **Init options** and **localized options** are unmapped fields, written to JSON by the DataMapper
4. **Validation errors** re-render the page; the first tab holding an error opens. CNIL warnings never block
5. **Redirect** keeps `channelCode` for multi-channel (and the `#tab-<id>` fragment, carried by the form action)

## Source files

- `src/Controller/Admin/TarteaucitronConfigurationAction.php`
- `src/Sylius/Factory/TarteaucitronConfigurationFactory.php`
- `src/Sylius/Synchronizer/ServiceCatalogSynchronizer.php`
