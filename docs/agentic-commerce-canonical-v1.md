# Canonical Product Architecture v1

`PrestaShop -> Canonical Product Model -> Multi-Protocol Exporters`

## Frozen principles

1. PrestaShop remains the operational source of truth.
2. Canonical data is merchant-neutral and multistore-aware.
3. Product identity is `id_shop + id_product`; variant identity adds `id_product_attribute`.
4. Language/currency are representation context, not product identity.
5. Product-level metadata (`id_product_attribute = 0`) is inherited by variants; variant rows override it.
6. Technical values are separated into `verified_specs`, `declared_specs`, and `derived_properties`.
7. Evidence is bound to the exact supported value by SHA-256 `value_hash`; property name alone is insufficient.
8. Public publication fails closed per property: unsupported specs are omitted; strict mode is a quality gate.
9. Runtime/potentially personalized prices and exact stock quantities are not published as public facts by default.
10. Merchant taxonomies, product classes, sale units and suitability terms are data/configuration, not core code.
11. `fdpsucp` remains generic; catalog overrides connect through `CatalogProviderRegistry`.

## Canonical IDs

- product group: `ps-{shop}-{product}`
- variant: `ps-{shop}-{product}-{combination}`

The same variant can have multiple language/currency representations. Static exporters must therefore include representation context in their output path/key rather than changing canonical identity.

## Evidence lifecycle

Evidence records carry class (`verified`, `declared`, `derived`), source, confidence, date, public/private visibility, lifecycle status and `value_hash`.

Product-level evidence is inherited by variants. It may validate an inherited variant property only when the exact value hash matches. A changed variant value therefore cannot accidentally reuse evidence for the old/base value.

## Storage

- `{prefix}agenticcommerce_product_meta`
- `{prefix}agenticcommerce_evidence`

LONGTEXT is used for extensible JSON metadata. Application validation rejects malformed/non-object metadata instead of silently treating it as empty.

## Runtime baseline

The generic module targets PrestaShop 8+ / PHP 8+. PrestaShop 1.7 is not declared compatible because the implementation uses PHP 8 syntax.

## Next integration boundary

The canonical model does not know UCP/OpenAI/Google wire formats. Exporters/adapters consume canonical DTOs. The `fdpsucp` provider registry is generic infrastructure; the merchant-neutral canonical-to-UCP provider is the next task.
