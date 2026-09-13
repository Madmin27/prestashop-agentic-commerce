# PrestaShop Agentic Commerce

`psagenticcommerce` is a merchant-neutral canonical product and AI commerce module for PrestaShop 8+ / PHP 8+.

It intentionally contains no store name, brand, vertical taxonomy or product-specific suitability rule. Each merchant installs the module and supplies its own metadata, technical specification keys, evidence, taxonomy and suitability vocabulary.

## Core responsibilities

- multistore-safe product and variant identity
- explicit language and currency representation context
- product-level metadata inheritance with variant overrides
- verified / declared / derived specification tiers
- evidence provenance bound to exact values with `value_hash`
- public/private evidence and publication controls
- merchant-defined sale units and category types
- future AI JSON, OpenAI and UCP adapters

## Storage

The module uses the current PrestaShop DB prefix:

- `{prefix}agenticcommerce_product_meta`
- `{prefix}agenticcommerce_evidence`

JSON payloads use LONGTEXT for MySQL/MariaDB portability and are validated at application level. Malformed metadata fails closed.

Public exporters must use `CanonicalPublicationPolicy`: unproven technical values are omitted, non-public price contexts are redacted, exact stock quantity is hidden by default, and private evidence/notes are not exposed.

`fdpsucp` contains the generic catalog-provider extension point, but the canonical-to-UCP adapter/provider remains a separate implementation task.

Uninstall preserves merchant metadata/evidence; destructive purge must be explicit. Install/upgrade migrates earlier development table names when present.
