# PrestaShop Agentic Commerce

`psagenticcommerce` is a merchant-neutral canonical product and AI commerce module for PrestaShop 8+ / PHP 8+.

It contains no store name, brand, vertical taxonomy or product-specific suitability rule. Each merchant supplies its own metadata, technical specification keys, evidence, taxonomy and suitability vocabulary.

## Core responsibilities

- multistore-safe product and variant identity
- language, locale, currency and tax-country representation context
- product-level metadata inheritance with variant overrides
- verified / declared / derived specification tiers
- exact-value evidence provenance with `value_hash`
- deterministic anonymous/public catalog pricing
- publication-policy redaction and suitability provenance controls
- representation-aware AI JSON product/catalog/manifest delivery
- generic extension points for OpenAI and UCP adapters

Marketing descriptions remain non-authoritative content. Technical facts are published only from explicit specification fields that satisfy evidence policy.

## AI JSON endpoints

Discovery uses the current host/shop and the shop's default public representation:

- `/.well-known/ai-catalog.json`

Representation-specific documents use:

- `/ai/v1/s1/tr-TR/TRY/TR/catalog.json`
- `/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json`

A representation is isolated by:

`shop + language/locale + currency + tax-country`

The requested shop ID must match the shop selected by the request host. Locale and currency must be active for that shop, and the country must match the shop's configured public tax country.

## HTTP and cache behavior

Responses use JSON content type, ETag and conditional `If-None-Match` handling. Public responses are cacheable with revalidation.

AI JSON cache writes are atomic. Cache entries carry a shop generation token; product, combination, stock and specific-price changes advance the generation so old entries immediately become non-servable without relying on filesystem timestamp precision.

The module installs Apache rewrite rules for the well-known discovery endpoint and representation routes, including stores with Friendly URL disabled. On nginx, equivalent routes must be forwarded to the module front controllers because nginx does not read `.htaccess`.

## Storage

The module uses the current PrestaShop DB prefix:

- `{prefix}agenticcommerce_product_meta`
- `{prefix}agenticcommerce_evidence`

JSON payloads use LONGTEXT for MySQL/MariaDB portability and are validated at application level. Malformed metadata fails closed.

Uninstall preserves merchant metadata/evidence; destructive purge is explicit.

## Tests

The module contains PHPUnit tests plus a dependency-free smoke test:

```bash
php tests/smoke.php
```

Coverage includes canonical identity/value hashing, provenance filtering, suitability policy, public pricing state restoration, AI representation isolation, cache generation invalidation, rewrite idempotency and ETag matching.

`fdpsucp` contains the generic catalog-provider extension point. Canonical-to-UCP mapping remains a separate protocol adapter task.
