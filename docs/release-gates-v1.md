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
- ambiguous multiple UCP catalog providers are rejected
- no provider preserves fdpsucp fallback behavior
- non-destructive uninstall and idempotent schema migration

Required before public production delivery/UCP provider:
1. explicit rule that marketing descriptions are non-authoritative for technical facts
2. multistore-safe HTTP/static delivery for `/.well-known/ai-catalog.json` and representation documents
3. atomic publish/cache invalidation and stale-document cleanup policy
4. canonical-to-UCP adapter/provider plus conformance tests
5. orphan metadata/evidence audit and explicit purge tooling
6. enabled CI execution in the fork (workflow is configured but GitHub currently reports no runs)
