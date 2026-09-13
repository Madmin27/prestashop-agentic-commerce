# hepsiantepai

Hepsiantep-specific canonical product data and AI commerce exporter module.

## Scope

This module owns:

- variant-scoped AI metadata
- evidence and provenance
- canonical product building
- AI/OpenAI export adapters
- future `fdpsucp` catalog provider registration

It must **not** contain transaction/payment logic already owned by `fdpsucp`, and `fdpsucp` must not contain Hepsiantep-specific business rules.

## Current status

### TASK-001 - in progress

Implemented:

- module skeleton
- multistore-safe `hepsiantep_ai_product_meta` table
- relational `hepsiantep_ai_evidence` table
- LONGTEXT JSON storage for compatibility
- JSON encode/decode validation helper
- private-by-default evidence (`is_public = 0`)
- canonical product JSON Schema draft in `/schemas/canonical-product-v1.schema.json`

Still required before TASK-001 is complete:

- install/upgrade tests against supported PrestaShop versions
- repository classes for meta/evidence
- application-level validation for evidence class, status and confidence range
- migration/version mechanism for future schema upgrades

## Data rules

- `id_shop + id_product + id_product_attribute` is the sellable source identity.
- `id_product_attribute = 0` represents a simple product.
- Evidence class is application-validated as `verified`, `declared` or `derived`.
- Technical values without evidence are not invented.
- Derived values require a deterministic method/rule version.
- Public exporters must filter evidence where `is_public != 1`.
- Static commercial values are snapshots; transaction-time price and stock are resolved live.

## Planned tasks

- TASK-002: Canonical DTO + Builder
- TASK-003: generic `fdpsucp` CatalogProvider extension
- TASK-004: Hepsiantep provider + AI/OpenAI exporters
- TASK-005: discovery surfaces + integration/conformance tests
