# Hepsiantep AI Commerce v1

Status: architecture frozen for implementation.

## Core principle

PrestaShop remains the system of record. `hepsiantepai` builds a canonical, evidence-aware product domain model and exports it to multiple protocols without modifying `fdpsucp` with Hepsiantep-specific code.

## Non-negotiable invariants

1. Single source of truth: exporters never parse other exporter outputs.
2. Zero hallucination: missing technical facts remain absent/null; derived facts are never promoted to verified facts.
3. Variant-first: sellable identity is `(id_shop, id_product, id_product_attribute)`; attribute 0 means simple product.
4. Multistore isolation: all AI metadata/evidence rows are scoped by `id_shop`.
5. Transport isolation: Canonical DTOs know nothing about UCP, HTTP, OpenAI or Google formats.
6. Upstream isolation: Hepsiantep-specific code never goes into `fdpsucp`.
7. Static vs live truth: static AI files are snapshots; transaction-time price/stock must be re-read live.
8. No implicit inference: derived properties come only from explicit, versioned deterministic rules.

## Identity

Use a deterministic canonical variant key such as `ps:{shop_id}:{product_id}:{product_attribute_id}`. SKU/GTIN/MPN are attributes, not database identity.

## Evidence classes

- `verified`: measured/tested or independently verified
- `declared`: supplier/manufacturer/merchant declaration
- `derived`: deterministic calculation or inference

Evidence is internal by default. Public exporters must not expose private `source_url`, `notes` or documents unless explicitly allowed later.

## Price and stock semantics

PrestaShop pricing can vary by shop, currency, tax context, quantity, customer group, country and specific-price rules. Static exports therefore represent a named public/default context only and must carry freshness metadata. Checkout/UCP must re-price through PrestaShop at execution time. Stock must be resolved in the active shop context.

## Locale

Evidence and normalized suitability keys are locale-neutral. Human-facing text is generated in an explicit language context. Exporters must accept language/currency context even if v1 initially publishes only the shop default locale.

## fdpsucp extension strategy

Future upstream PR mirrors the existing payment-provider pattern:

```text
CatalogProviderInterface
        ^
CatalogProviderRegistry
        ^
actionUcpCollectCatalogProviders
        ^
hepsiantepai provider
```

The registry must not silently use last-write-wins. Provider conflicts are explicit. If no external provider is registered, current native PrestaShop catalog behavior remains the fallback. CatalogService owns the UCP/HTTP envelope; providers return normalized results.

## Failure scenarios

- `hepsiantepai` disabled: `fdpsucp` continues with native catalog fallback.
- Two providers: fail/log explicitly; never depend on hook order.
- Stale static catalog: timestamps are exposed; live endpoints remain authoritative for commercial data.
- Combination deleted/recreated: new attribute ID means new canonical variant identity; stale artifact is removed on next export.
- Missing evidence URL: do not invent replacement evidence.
- Private evidence: never leak internal notes/URLs through public AI JSON by default.
- Upstream sync: generic `fdpsucp` edits stay isolated in dedicated commits/PRs.

## Discovery target

```text
/robots.txt
/llms.txt
/agents.md
/.well-known/ai-catalog.json
/.well-known/ucp
/ai/catalog.json -> alias/redirect to primary AI catalog
/ai/products/{canonical_variant_id}.json
```

## Implementation sequence

1. TASK-001: DB + validation
2. TASK-002: Canonical DTO + Builder
3. TASK-003: generic fdpsucp CatalogProvider extension
4. TASK-004: Hepsiantep provider + AI/OpenAI exporters
5. TASK-005: discovery surfaces + integration/conformance tests
