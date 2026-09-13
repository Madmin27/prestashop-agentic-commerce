# PrestaShop Agentic Commerce

`psagenticcommerce` is a merchant-neutral canonical product and AI commerce module for PrestaShop 8+ / PHP 8+.

It intentionally contains no store name, brand, vertical taxonomy or product-specific suitability rule. Each merchant installs the module and supplies its own metadata, technical specification keys, evidence, taxonomy and suitability vocabulary.

## Core responsibilities

- multistore-safe product and variant identity
- explicit language, locale, currency and tax-country representation context
- product-level metadata inheritance with variant overrides
- verified / declared / derived specification tiers
- evidence provenance bound to exact values with `value_hash`
- public/private evidence and publication controls
- deterministic anonymous/public catalog pricing
- merchant-defined sale units and category types
- representation-aware AI JSON product/catalog/manifest exports
- generic extension points for future OpenAI and UCP adapters

## AI JSON representation

AI JSON exports are isolated by the dimensions that can change their content or price:

`shop + language/locale + currency + tax-country`

Example paths:

- `/ai/v1/s1/tr-TR/TRY/TR/catalog.json`
- `/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json`

`RepresentationKey` also produces deterministic cache keys. `AiJsonExportCoordinator` enumerates active shop products/combinations, builds public canonical DTOs, applies publication policy, and returns a complete manifest/catalog/product-document bundle.

The exporter core does **not** write a single global `/.well-known/ai-catalog.json` file. In PrestaShop multistore, multiple domains may share one document root, so the well-known discovery endpoint must be served by a shop-aware HTTP/rewrite layer. Delivery and atomic publishing remain separate from canonical/export transformation.

## Storage

The module uses the current PrestaShop DB prefix:

- `{prefix}agenticcommerce_product_meta`
- `{prefix}agenticcommerce_evidence`

JSON payloads use LONGTEXT for MySQL/MariaDB portability and are validated at application level. Malformed metadata fails closed.

Public exporters use `CanonicalPublicationPolicy`: unproven technical values are omitted, direct recommendations require verified/declared provenance, derived evidence can support conditional suitability, runtime/personalized price contexts are redacted, exact stock quantity is hidden by default, and private evidence/notes are not exposed.

`fdpsucp` contains the generic catalog-provider extension point, but the canonical-to-UCP adapter/provider remains a separate implementation task.

Uninstall preserves merchant metadata/evidence; destructive purge must be explicit. Install/upgrade migrates earlier development table names when present.

## Tests

The module contains PHPUnit tests plus a dependency-free smoke test:

```bash
php tests/smoke.php
```

The smoke test exercises canonical validation, provenance filtering, representation isolation, AI catalog generation and JSON encoding without requiring a running PrestaShop database.
