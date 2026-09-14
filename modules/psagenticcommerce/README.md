# PrestaShop Agentic Commerce

`psagenticcommerce` is a merchant-neutral canonical product and AI commerce module for **PrestaShop 8.2+**. Runtime code requires PHP 8+; use a PHP version supported by your PrestaShop installation.

The module contains no merchant name, brand, vertical taxonomy or product-specific suitability rule. Each merchant supplies its own metadata, technical specification keys, evidence, taxonomy and suitability vocabulary.

## Install

Build the production ZIP from the repository root:

```bash
bash scripts/build-psagenticcommerce.sh
```

This creates:

```text
dist/psagenticcommerce.zip
```

The ZIP root is `psagenticcommerce/`, so it can be uploaded directly from **Back Office > Modules > Module Manager > Upload a module**.

The GitHub `Package psagenticcommerce` workflow builds the same installable ZIP as a workflow artifact.

## Core responsibilities

- multistore-safe product and variant identity
- language, locale, currency and tax-country representation context
- product-level metadata inheritance with variant overrides
- verified / declared / derived specification tiers
- exact-value evidence provenance with `value_hash`
- deterministic anonymous/public catalog pricing
- publication-policy redaction and suitability provenance controls
- representation-aware AI JSON product/catalog/manifest delivery
- canonical UCP catalog search/lookup provider
- OpenAI Stable full-snapshot product feed generation and SFTP delivery

Marketing descriptions are non-authoritative content. Technical facts are published only from explicit specification fields that satisfy evidence policy.

## Back Office

Open **Module Manager > PrestaShop Agentic Commerce > Configure**. Settings are stored per shop.

The configuration screen contains:

- OpenAI seller/store identity and policy URLs
- target countries and store country
- search / checkout / ads eligibility
- optional default brand
- SFTP host, port, username and remote path
- secret or SSH public-key authentication
- SHA-256 / MD5 host-key pinning or `known_hosts` verification
- timeout
- cron token
- manual **Generate snapshot** and **Generate & send by SFTP** operations

SFTP authentication secrets and private-key passphrases are encrypted at rest using AES-256-GCM with a key derived from the PrestaShop `_COOKIE_KEY_`. They are never repopulated into the Back Office password fields. If `_COOKIE_KEY_` is rotated, re-enter the protected SFTP values.

SFTP remains disabled until explicitly enabled and cannot send unless host-key verification is configured.

## OpenAI product feed

The module builds a full `products.jsonl.gz` snapshot from public canonical variants. Each variant becomes one feed row. Invalid rows are reported and skipped; if the source catalog is non-empty but every row is invalid, the previous good snapshot is not overwritten.

The snapshot is written under the PrestaShop cache directory and may then be pushed to the SFTP destination provided during OpenAI merchant onboarding.

Scheduled generation uses the `openaisnapshot` module controller. Call it with **POST** and provide the Back Office cron token only through:

```text
X-Agentic-Cron-Token: <token>
```

The token is not accepted in the query string.

## AI JSON endpoints

Discovery uses the current host/shop and the shop's default public representation:

- `/.well-known/ai-catalog.json`

Representation-specific documents use:

- `/ai/v1/s1/tr-TR/TRY/TR/catalog.json`
- `/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json`

A representation is isolated by:

`shop + language/locale + currency + tax-country`

The requested shop ID must match the shop selected by the request host. Locale and currency must be active for that shop, and the country must match the shop's configured public tax country.

## UCP catalog provider

When `fdpsucp` is installed, `psagenticcommerce` registers a catalog provider through `actionUcpCollectCatalogProviders`. The canonical layer remains independent of UCP; only the adapter/provider layer maps public canonical products to UCP.

The provider currently targets UCP catalog semantics dated `2026-08-25`:

- canonical product groups become UCP products
- canonical variants become UCP variants
- prices are ISO-4217 minor-unit integers
- availability is structured
- variant descriptions, GTIN barcodes and selected options are mapped
- `sale_unit` maps to UCP `quantity_unit` where applicable
- merchant `category_type` is exposed as a merchant taxonomy category
- search supports category and currency-aware minor-unit price filters
- pagination uses opaque cursors
- lookup accepts canonical product IDs, canonical variant IDs and legacy numeric product IDs

This does **not** claim that the entire bundled `fdpsucp` transactional stack is UCP `2026-08-25` conformant. Its cart/checkout implementation still carries older protocol-version debt and is tracked separately. Without the canonical provider, the existing `fdpsucp` catalog fallback keeps its legacy response version.

## HTTP and cache behavior

Responses use JSON content type, ETag and conditional `If-None-Match` handling. Public responses are cacheable with revalidation.

AI JSON cache writes are atomic. Cache entries carry a shop generation token; product, combination, stock and specific-price changes advance the generation so stale entries are immediately non-servable.

The module installs Apache rewrite rules for the well-known discovery endpoint and representation routes. On nginx, equivalent routes must be forwarded to the module front controllers because nginx does not read `.htaccess`.

## Storage and uninstall

The module uses:

- `{prefix}agenticcommerce_product_meta`
- `{prefix}agenticcommerce_evidence`

Canonical merchant metadata/evidence is deliberately preserved on uninstall. The OpenAI cron token and protected SFTP authentication values are removed.

## Tests

The module contains PHPUnit tests plus a dependency-free smoke test:

```bash
php tests/smoke.php
```

Coverage includes canonical identity/value hashing, provenance filtering, suitability policy, public pricing state restoration, AI representation isolation, cache generation invalidation, ETag handling, UCP catalog mapping, OpenAI feed mapping, secret encryption and SFTP configuration safety.
