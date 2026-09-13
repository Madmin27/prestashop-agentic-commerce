# v1 Release Gates

Resolved in core:
- multistore product/variant identity, including explicit product-shop and combination-shop isolation
- language/locale/currency/tax-country representation context
- product metadata inheritance with variant overrides
- evidence inheritance bound to exact values by `value_hash`
- malformed metadata fails closed
- private evidence and notes do not publish
- deterministic anonymous/public pricing using the shop default tax country
- runtime/personalized prices and exact stock are redacted by default
- direct recommendations require verified or declared provenance
- derived suitability may support conditional claims only
- conservative `not_recommended_for` safety exclusions survive missing provenance
- marketing descriptions are non-authoritative for technical facts
- deterministic variant dimensions, evidence order and suitability order
- representation-aware AI JSON paths and cache keys
- AI JSON product, catalog and discovery-manifest document generation
- active product/combination enumeration and full export bundle orchestration
- multistore-safe AI JSON HTTP delivery with ETag/304 and generation-token invalidation
- generic UCP catalog-provider extension point with conflict detection
- canonical UCP catalog provider using UCP 2026-08-25 product semantics
- UCP prices use ISO-4217 minor units and structured availability
- required UCP variant descriptions and schema-correct media `alt_text`
- UCP GTIN barcode and quantity-unit mapping
- UCP category and price filters with opaque cursor pagination
- UCP price filters are currency-aware and emit schema-correct warning messages when ignored
- UCP provider lookup accepts canonical product and canonical variant IDs with input correlation
- invalid catalog cursors return a recoverable 400 response
- fdpsucp fallback remains on its legacy catalog version when no provider is active
- OpenAI Stable product-feed DTO mapping from public canonical variants
- OpenAI required merchant/feed configuration is merchant-neutral and fail-closed
- shop-scoped OpenAI feed configuration resolver
- OpenAI variant grouping, GTIN/MPN, material, price, availability and geo targeting mapping
- OpenAI full-snapshot JSONL generation with per-row skip reporting
- atomic gzip snapshot publication (`products.jsonl.gz`)
- token-protected POST snapshot job endpoint and module upgrade token initialization
- snapshot job refuses to replace a previous feed with an all-invalid empty export
- non-destructive uninstall and idempotent schema migration

Remaining before v1 production release:
1. targeted catalog schema/conformance validation against the official UCP 2026-08-25 schemas and fixtures
2. Back Office/provisioning surface for shop-scoped OpenAI merchant feed settings
3. OpenAI SFTP delivery adapter plus delivery/audit status tracking (credential mechanism must follow onboarding details)
4. canonical availability-date support before exporting `pre_order` rows
5. decide and validate measured-sale/unit-pricing mapping before publishing `unit_pricing_measure` / `base_measure`
6. orphan metadata/evidence audit and explicit purge tooling
7. stale physical cache-file garbage collection (stale generations are already never served)
8. enabled CI execution in the fork (workflow is configured correctly but GitHub currently reports zero workflow runs)
9. transactional fdpsucp cart/checkout upgrade from 2026-04-08 to 2026-08-25 before claiming full-stack UCP 2026-08-25 conformance
