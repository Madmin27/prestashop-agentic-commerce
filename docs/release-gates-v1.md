# v1 Release Gates

Resolved in core:
- multistore product/variant identity
- language/currency representation context
- product metadata inheritance with variant overrides
- evidence inheritance bound to exact values by `value_hash`
- malformed metadata fails closed
- private evidence and notes do not publish
- runtime/personalized prices and exact stock are redacted by default
- deterministic variant dimensions, evidence order and suitability order
- ambiguous multiple UCP catalog providers are rejected
- no provider preserves fdpsucp fallback behavior
- non-destructive uninstall and idempotent schema migration

Required before public exporter/UCP provider:
1. dedicated anonymous/public pricing resolver
2. suitability provenance semantics, especially positive/safety-sensitive recommendations
3. explicit rule that marketing descriptions are non-authoritative for technical facts
4. representation-aware static export paths/cache keys
5. canonical-to-UCP adapter/provider plus conformance tests
6. orphan metadata/evidence audit and explicit purge tooling
