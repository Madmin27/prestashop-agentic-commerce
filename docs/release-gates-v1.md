# v1 Release Gates

Resolved in core:
- multistore product/variant identity
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
- deterministic variant dimensions, evidence order and suitability order
- representation-aware AI JSON paths and cache keys
- AI JSON product, catalog and discovery-manifest document generation
- active product/combination enumeration and full export bundle orchestration
- multistore-safe AI JSON HTTP delivery with ETag/304 and generation-token invalidation
- generic UCP catalog-provider extension point with conflict detection
- canonical UCP catalog provider using UCP 2026-08-25 product semantics
- UCP prices use ISO-4217 minor units and structured availability
- UCP category and price filters with cursor pagination
- UCP provider lookup accepts canonical product and canonical variant IDs
- fdpsucp fallback remains on its legacy catalog version when no provider is active
- non-destructive uninstall and idempotent schema migration

Remaining before v1 production release:
1. explicit rule that marketing descriptions are non-authoritative for technical facts
2. canonical-to-UCP provider conformance run against the official UCP conformance suite
3. orphan metadata/evidence audit and explicit purge tooling
4. stale physical cache-file garbage collection (stale generations are already never served)
5. enabled CI execution in the fork (workflow is configured but GitHub currently reports no runs)
6. transactional fdpsucp cart/checkout upgrade from 2026-04-08 to 2026-08-25 as a separate compatibility project
