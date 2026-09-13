# PrestaShop Agentic Commerce

`psagenticcommerce` is a merchant-neutral canonical product and AI commerce module for PrestaShop.

It intentionally contains no store name, brand, vertical taxonomy or product-specific suitability rule. Each merchant installs the module and supplies its own metadata, technical specification keys, evidence, taxonomy and suitability vocabulary.

## Core responsibilities

- multistore-safe product and variant identity
- canonical product DTO
- verified / declared / derived specification tiers
- evidence provenance and publication controls
- merchant-defined sale units and category types
- future AI JSON, OpenAI and UCP adapters

## Storage

The module uses the current PrestaShop DB prefix:

- `{prefix}agentic_product_meta`
- `{prefix}agentic_evidence`

JSON payloads use LONGTEXT for MySQL/MariaDB compatibility and are validated at application level.

Uninstall preserves merchant metadata/evidence; destructive purge must be explicit.
