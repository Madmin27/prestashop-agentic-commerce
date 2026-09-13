# Canonical Product Architecture v1

The project follows:

`PrestaShop -> Canonical Product Model -> Multi-Protocol Exporters`

## Principles

1. PrestaShop remains the operational source of truth.
2. Canonical data is merchant-neutral and multistore-aware.
3. Variants are first-class objects scoped by `id_shop + id_product + id_product_attribute`.
4. Technical data is separated into `verified_specs`, `declared_specs`, and `derived_properties`.
5. Public exports require publication policy checks so private evidence and non-public price contexts do not leak.
6. Merchant-specific taxonomies, product classes and suitability terms are data/configuration, not core code.
7. `fdpsucp` remains generic; catalog overrides connect through `CatalogProviderRegistry`.

## Canonical IDs

- product group: `ps-{shop}-{product}`
- variant: `ps-{shop}-{product}-{combination}`

## Evidence

Evidence records carry source type, optional source id/url, confidence, evidence date, public/private visibility and lifecycle status.

## Extensibility

A rope seller may add `diameter_mm`; a textile seller may add `gsm`; a packaging merchant may add `thickness_microns`. The core module does not need code changes for these keys.
