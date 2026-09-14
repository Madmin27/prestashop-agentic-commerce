# PrestaShop Agentic Commerce

PrestaShop Agentic Commerce is a merchant-neutral module stack for exposing a PrestaShop catalog to AI systems through a canonical product model, AI JSON endpoints, UCP catalog services, and OpenAI product-feed delivery.

The current `psagenticcommerce` module targets **PrestaShop 8.2+** and **PHP 8+**.

## What this repository provides

The architecture is intentionally layered:

```text
PrestaShop
   |
   v
Canonical Product Model
   |
   +--> AI JSON discovery / catalog / product endpoints
   +--> UCP catalog search and lookup
   +--> OpenAI product feed (products.jsonl.gz)
            |
            +--> optional SFTP delivery
```

PrestaShop remains the operational source of truth. Exporters consume the same canonical product representation; one feed is never parsed to produce another.

## Main module

The primary module is:

```text
modules/psagenticcommerce/
```

It currently includes:

- multistore-safe product and variant identity
- language, locale, currency and tax-country representation context
- verified, declared and derived technical-property tiers
- evidence bound to exact values by `value_hash`
- deterministic anonymous/public catalog pricing
- AI JSON discovery, catalog and product endpoints
- generation-token cache invalidation and ETag support
- UCP catalog provider integration
- OpenAI Stable product-feed mapping
- full `products.jsonl.gz` snapshot generation
- Back Office OpenAI feed configuration
- SFTP delivery with host-key verification
- encrypted SFTP secrets at rest
- token-protected scheduled snapshot endpoint
- English and Turkish Back Office translations

## Requirements

- PrestaShop **8.2 or newer**
- PHP **8.0 or newer**, using a PHP version supported by the installed PrestaShop release
- PHP cURL extension for SFTP delivery
- libcurl built with `sftp` protocol support when SFTP is enabled
- Apache rewrite support for automatic `.htaccess` exposure, or equivalent nginx routing configured manually

## Installation

### Direct folder installation

Copy only the module directory into your PrestaShop installation:

```text
<prestashop>/modules/psagenticcommerce/
```

The directory name must remain exactly:

```text
psagenticcommerce
```

Then open:

**Back Office > Modules > Module Manager**

Find **PrestaShop Agentic Commerce** and click **Install**.

### ZIP installation

A production package can be built from the repository root:

```bash
bash scripts/build-psagenticcommerce.sh
```

The resulting archive is expected at:

```text
dist/psagenticcommerce.zip
```

The ZIP root is `psagenticcommerce/`, so it can be uploaded from **Module Manager > Upload a module**.

## Back Office configuration

Open:

**Modules > Module Manager > PrestaShop Agentic Commerce > Configure**

Settings are stored per shop in multistore installations.

### OpenAI merchant feed

The configuration screen contains fields for:

- seller name and seller URL
- return-policy URL
- target countries
- store country
- optional default brand
- search eligibility
- checkout eligibility
- ads eligibility
- privacy-policy URL
- terms URL

### OpenAI SFTP delivery

The SFTP section supports:

- host and port
- username
- account-secret authentication
- SSH public-key authentication
- private/public key file paths
- encrypted private-key passphrase
- remote file path
- SHA-256 host-key pinning
- MD5 host-key fallback
- `known_hosts` verification
- transfer timeout

SFTP delivery is disabled by default and cannot run unless host-key verification is configured.

Protected credentials are encrypted using AES-256-GCM with key material derived from PrestaShop's `_COOKIE_KEY_`. Password fields are never repopulated in Back Office.

## OpenAI product feed

The module converts public canonical variants into OpenAI Stable feed rows and writes a full snapshot:

```text
products.jsonl.gz
```

Each sellable variant becomes one row. The exporter maps fields such as:

- item and group IDs
- title and description
- canonical product URL
- primary image
- price and ISO currency
- availability
- brand
- GTIN / MPN when available
- variant dimensions
- material when backed by canonical technical data
- target countries
- merchant policy information

Invalid rows are skipped and reported. If the source catalog is non-empty but every row is invalid, the module refuses to overwrite the previous valid snapshot with an empty file.

`pre_order` rows are intentionally fail-closed until canonical availability-date support exists.

## Scheduled snapshot generation

The module exposes an `openaisnapshot` front controller for scheduled jobs.

Use **POST** and pass the cron token only through this HTTP header:

```text
X-Agentic-Cron-Token: <token>
```

The token is not accepted through the query string.

When SFTP is enabled and configured, the scheduled job generates the snapshot and sends it to the configured remote path.

## AI JSON

Discovery endpoint:

```text
/.well-known/ai-catalog.json
```

Representation-specific catalog example:

```text
/ai/v1/s1/tr-TR/TRY/TR/catalog.json
```

Representation-specific product example:

```text
/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json
```

A public representation is isolated by:

```text
shop + language/locale + currency + tax-country
```

The requested shop must match the shop selected by the request host. Locale and currency must be active for that shop.

## UCP catalog provider

When `fdpsucp` is installed, `psagenticcommerce` registers its canonical catalog provider through:

```text
actionUcpCollectCatalogProviders
```

The provider currently maps canonical catalog data to UCP catalog semantics dated `2026-08-25`, including:

- products and variants
- ISO-4217 minor-unit prices
- structured availability
- variant descriptions and options
- GTIN barcodes
- quantity units
- merchant taxonomy category
- category filters
- currency-aware price filters
- opaque cursor pagination
- canonical product/variant lookup

The bundled transactional cart/checkout implementation is tracked separately and should not yet be described as full-stack UCP `2026-08-25` conformance.

## Canonical data and evidence

Canonical product metadata is stored separately from volatile commerce state.

Core tables:

```text
{prefix}agenticcommerce_product_meta
{prefix}agenticcommerce_evidence
```

Technical values are separated into:

- `verified_specs`
- `declared_specs`
- `derived_properties`

Public technical facts must satisfy publication-policy and provenance requirements. Marketing descriptions are not treated as authoritative technical evidence.

## Multistore behavior

Product and variant identity include `id_shop`. Product-shop and combination-shop membership is explicitly checked before canonical publication.

Configuration such as OpenAI merchant settings and SFTP delivery settings is stored per shop.

## Translations

Packaged Back Office translations are stored under:

```text
modules/psagenticcommerce/translations/en-US/
modules/psagenticcommerce/translations/tr-TR/
```

The module uses the domain:

```text
Modules.Psagenticcommerce.Admin
```

## Security notes

- public catalog output never exposes stored SFTP secrets
- protected SFTP values are encrypted at rest
- SFTP requires host-key verification
- cron authentication uses a dedicated random token
- the cron token is accepted only through an HTTP header
- runtime/personalized prices are not published as public feed facts
- exact internal stock quantities are not published by default
- unsupported technical claims fail closed

## Development status

The module is under active development. The next validation target is a real **PrestaShop 8.2** installation, where installation, Back Office configuration, AI JSON, UCP integration, snapshot generation and SFTP behavior should be tested end-to-end.

Do not treat the repository's GitHub Actions status as proof of production readiness; live PrestaShop integration testing remains authoritative for this development branch.

## License

See [LICENSE](LICENSE).
