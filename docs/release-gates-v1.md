# v1 Release Gates

Resolved in core:
- PrestaShop 8.2+ module baseline and install/upgrade path through module version 0.6.0
- installable `psagenticcommerce.zip` build script and GitHub package workflow
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
- AI JSON product, catalog and discovery-manifest generation and HTTP delivery
- multistore-safe AI JSON cache with ETag/304 and generation-token invalidation
- generic UCP catalog-provider extension point with conflict detection
- canonical UCP catalog provider using UCP 2026-08-25 product semantics
- UCP prices, availability, required descriptions, media, GTIN, quantity units and lookup correlation
- UCP category and currency-aware price filters with opaque cursor pagination
- invalid catalog cursors return a recoverable 400 response
- fdpsucp fallback remains on its legacy catalog version when no canonical provider is active
- OpenAI Stable product-feed DTO mapping from public canonical variants
- shop-scoped OpenAI merchant/feed configuration
- OpenAI full-snapshot JSONL generation with per-row skip reporting
- atomic gzip snapshot publication (`products.jsonl.gz`)
- Back Office OpenAI merchant and SFTP settings surface
- encrypted OpenAI SFTP authentication secrets using AES-256-GCM
- SFTP secret/public-key authentication and mandatory SSH host-key verification
- manual snapshot generation and manual SFTP send
- token-protected POST scheduled snapshot + optional SFTP delivery
- snapshot job refuses to replace a previous feed with an all-invalid empty export
- delivery status/audit metadata
- non-destructive canonical-data uninstall; protected OpenAI credentials are removed
- CI workflows now trigger on the development branch

Remaining before v1 production release:
1. targeted catalog schema/conformance validation against the official UCP 2026-08-25 schemas and fixtures
2. validate a real OpenAI onboarding SFTP account end-to-end (host/user/auth/fingerprint are merchant-specific and cannot be hard-coded)
3. canonical availability-date support before exporting `pre_order` rows
4. decide and validate measured-sale/unit-pricing mapping before publishing `unit_pricing_measure` / `base_measure`
5. orphan metadata/evidence audit and explicit purge tooling
6. stale physical cache-file garbage collection (stale generations are already never served)
7. transactional fdpsucp cart/checkout upgrade from 2026-04-08 to 2026-08-25 before claiming full-stack UCP 2026-08-25 conformance
