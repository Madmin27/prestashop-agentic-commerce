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
- marketing descriptions remain non-authoritative content
- deterministic variant dimensions, evidence order and suitability order
- representation-aware AI JSON paths and cache keys
- AI JSON product, catalog and discovery-manifest generation
- active product/combination enumeration and full export orchestration
- shop-aware well-known discovery delivery
- representation-specific catalog/product HTTP delivery
- ETag and conditional GET handling
- Apache rewrites for discovery and AI JSON routes
- atomic cache writes and generation-token invalidation
- stale AI JSON entries fail closed after invalidation
- 0.3.0 runtime upgrade coordinator
- ambiguous multiple UCP catalog providers are rejected
- no provider preserves fdpsucp fallback behavior
- non-destructive uninstall and idempotent schema migration

Remaining before broader v1 release:
1. canonical-to-UCP adapter/provider plus conformance tests
2. orphan metadata/evidence audit and explicit purge tooling
3. optional physical cleanup of superseded cache files
4. enabled CI execution in the fork (workflow is configured but GitHub currently reports no runs)
