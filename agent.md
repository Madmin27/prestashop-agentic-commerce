# Agent Installation Guide

This file is for the AI/automation agent that will download, install, inspect, and test this project on a real PrestaShop server.

## Objective

Install and validate the `psagenticcommerce` module on a real **PrestaShop 8.2+** store without changing the intended architecture.

The working branch is:

```text
agentic-commerce-v1
```

The primary module is:

```text
modules/psagenticcommerce
```

Do not install the entire repository as one PrestaShop module. The module directory must exist exactly as:

```text
<prestashop-root>/modules/psagenticcommerce
```

The module technical name is:

```text
psagenticcommerce
```

## What this module does

`psagenticcommerce` provides a merchant-neutral canonical product layer for AI commerce and publishes that data through several adapters:

```text
PrestaShop product / combination data
        -> canonical product model
        -> public publication policy
        -> AI JSON
        -> UCP catalog provider
        -> OpenAI product feed
```

Main capabilities:

- PrestaShop 8.2+ module install/upgrade path
- multistore-safe product and combination identity
- public canonical product model
- deterministic public pricing context
- verified / declared / derived technical facts
- evidence provenance and `value_hash` binding
- AI JSON discovery/catalog/product endpoints
- ETag/304 and generation-token cache invalidation
- UCP catalog provider
- OpenAI Stable full product snapshot generation
- `products.jsonl.gz` output
- OpenAI SFTP delivery
- encrypted SFTP credentials
- mandatory SSH host-key verification
- scheduled snapshot controller protected by `X-Agentic-Cron-Token`
- English and Turkish Back Office translations

## Related modules

This repository also contains:

```text
modules/fdpsucp
modules/fdpsprism
modules/fdpsdummy
```

`psagenticcommerce` can provide AI JSON and OpenAI feed functionality independently.

For UCP catalog integration, `fdpsucp` must also be installed. `psagenticcommerce` registers its canonical catalog provider through the UCP provider hook.

Do not assume that the complete transactional UCP stack is already UCP 2026-08-25 compliant. The canonical catalog layer targets 2026-08-25 semantics, while legacy cart/checkout code still has older-version protocol debt.

## Minimum requirements

Verify before installation:

```text
PrestaShop >= 8.2
PHP >= 8.0
MySQL/MariaDB supported by the installed PrestaShop version
PHP cURL extension
PHP OpenSSL extension
```

For SFTP delivery, confirm that the installed libcurl build supports the `sftp` protocol.

Useful check:

```bash
php -r '$v=curl_version(); echo implode("\n", $v["protocols"]),"\n";'
```

The output must contain:

```text
sftp
```

Do not weaken host-key verification just to make SFTP work.

## Recommended installation procedure

1. Back up the PrestaShop database and current module directory.
2. Checkout/download branch `agentic-commerce-v1`.
3. Copy only the required module directories into the PrestaShop `modules/` directory.
4. Ensure ownership/permissions match the existing PrestaShop modules.
5. Clear PrestaShop cache if necessary.
6. Install from Back Office Module Manager or CLI.

Primary module path:

```text
modules/psagenticcommerce
```

Optional UCP dependency path:

```text
modules/fdpsucp
```

If using CLI, run commands as the same user that normally owns/writes PrestaShop cache files. Avoid creating root-owned cache files.

Example:

```bash
cd <prestashop-root>
php bin/console prestashop:module install fdpsucp
php bin/console prestashop:module install psagenticcommerce
```

If `fdpsucp` is already installed, do not reinstall it unnecessarily.

## First install checks

After installation, verify all of the following before changing code.

### Module state

Confirm that `psagenticcommerce` appears as installed and enabled in Module Manager.

The module version should currently be:

```text
0.6.0
```

Minimum compatibility declared by the module:

```text
PrestaShop 8.2.0.0
```

### Database

Expected canonical tables:

```text
<prefix>agenticcommerce_product_meta
<prefix>agenticcommerce_evidence
```

Do not delete these tables during uninstall testing unless explicitly asked. Canonical merchant metadata/evidence is intentionally preserved.

### Back Office configuration

Open:

```text
Modules > Module Manager > PrestaShop Agentic Commerce > Configure
```

Verify that the page renders without PHP/Symfony errors.

Expected sections include:

- OpenAI Merchant Feed
- OpenAI SFTP Delivery
- OpenAI Feed Operations

Verify Turkish and English Back Office languages if both languages exist on the store.

Translation files are under:

```text
modules/psagenticcommerce/translations/en-US/
modules/psagenticcommerce/translations/tr-TR/
```

## OpenAI feed configuration

Merchant-specific values are intentionally not hard-coded in the module.

Configure the current shop with real values for:

- seller name
- seller URL
- return policy URL
- target countries
- store country
- optional default brand
- search eligibility
- checkout eligibility
- privacy/terms URLs if checkout is enabled
- optional ads eligibility

Configuration is intended to be shop-scoped.

Do not insert Hepsiantep-specific constants into source code. This module must remain reusable by other merchants.

## Snapshot test

From Back Office use:

```text
Generate snapshot
```

Expected result:

- public canonical variants are enumerated
- valid rows are exported
- invalid rows are skipped with diagnostics
- if every source row is invalid, an existing known-good snapshot must not be replaced by an accidental invalid empty feed

Expected file name:

```text
products.jsonl.gz
```

Expected runtime location is under the PrestaShop cache directory, scoped by shop:

```text
<cache>/psagenticcommerce/openai/s<shop-id>/products.jsonl.gz
```

Inspect the gzip file and verify that each line is valid JSON.

Useful commands:

```bash
gzip -t <path>/products.jsonl.gz
zcat <path>/products.jsonl.gz | head
```

Each product combination/variant is normally exported as its own OpenAI feed row.

## SFTP delivery

SFTP values must come from the actual OpenAI merchant onboarding account. Do not invent hostnames, usernames, fingerprints, credentials, or remote paths.

The implementation supports account-secret and SSH public-key authentication.

Authentication secrets and private-key passphrases are stored encrypted.

Host-key verification is mandatory. Use one of the supported verification mechanisms supplied/confirmed by the real server, such as the configured SHA-256 fingerprint, supported fallback fingerprint, or `known_hosts` verification.

Do not disable TLS/SSH verification, host-key checks, or certificate validation as a workaround.

After valid onboarding credentials are available, test:

```text
Generate & send by SFTP
```

Confirm both local snapshot creation and remote upload.

## Scheduled snapshot / cron

The scheduled controller is:

```text
/module/psagenticcommerce/openaisnapshot
```

Use HTTP POST only.

Authentication must be sent through the header:

```text
X-Agentic-Cron-Token: <token>
```

Do not place the token in the query string.

A successful request generates the snapshot and, when SFTP delivery is enabled and correctly configured, uploads it.

Do not expose the cron token in logs, command history, screenshots, issue bodies, or chat output.

## AI JSON validation

The module exposes AI-oriented public JSON using representation context:

```text
shop + locale + currency + tax country
```

Discovery endpoint:

```text
/.well-known/ai-catalog.json
```

Representation examples:

```text
/ai/v1/s1/tr-TR/TRY/TR/catalog.json
/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json
```

Use the actual store/shop/language/currency/country values during testing.

Validate:

- HTTP 200 for valid public representations
- JSON content type
- stable canonical IDs
- correct shop isolation
- ETag header
- `If-None-Match` returns 304 where applicable
- product/catalog output changes after relevant product/stock/price mutations rather than serving stale generations

Do not expose customer-specific prices or exact private stock information through public endpoints.

## UCP catalog validation

If `fdpsucp` is installed, validate these catalog routes:

```text
POST /module/fdpsucp/api/catalog/search
POST /module/fdpsucp/api/catalog/lookup
POST /module/fdpsucp/api/catalog/product
```

The canonical provider currently targets UCP catalog semantics dated:

```text
2026-08-25
```

Validate at least:

- product IDs
- variant IDs
- product/variant descriptions
- integer minor-unit prices
- structured availability
- GTIN mapping where available
- selected options
- category
- media
- pagination cursors
- lookup input correlation
- currency-aware price filtering behavior

Do not claim full-stack UCP 2026-08-25 compliance based only on catalog validation. Cart/checkout migration remains a separate release item.

## Multistore validation

This is critical.

If the PrestaShop installation has more than one shop, test each shop independently.

Confirm:

- a product/combination not assigned to the current shop is not published for that shop
- canonical IDs include the correct shop ID
- OpenAI merchant settings are resolved for the intended shop
- snapshots are stored in separate `s<shop-id>` directories
- AI JSON host/shop selection cannot be used to read another shop's representation
- prices use the correct shop context

Do not silently fall back to another shop's sensitive connection settings.

If a configuration value appears to inherit unexpectedly from a global PrestaShop configuration row, report it explicitly before changing architecture.

## Security rules

Do not make any of these changes without explicit approval:

- disabling SFTP host-key verification
- logging decrypted credentials
- returning filesystem paths from public controllers
- accepting cron token from query parameters
- exposing personalized/customer-specific public prices
- exposing exact private stock quantities
- making technical marketing text authoritative evidence
- deleting retained canonical metadata on uninstall
- hard-coding merchant-specific data into the generic module

## Known release items that are not installation blockers

These are known remaining v1/release-hardening items and should not be mistaken for an installation regression:

1. targeted UCP 2026-08-25 schema/fixture conformance validation
2. real OpenAI onboarding SFTP end-to-end validation
3. canonical `availability_date` support before exporting OpenAI `pre_order` rows
4. measured-sale/unit-pricing mapping validation
5. orphan metadata/evidence audit and explicit purge tooling
6. stale physical AI JSON cache-file garbage collection
7. full transactional `fdpsucp` cart/checkout migration from its older protocol version to UCP 2026-08-25

## How to handle failures

When something fails on the real server:

1. Reproduce the failure.
2. Record the exact command/request/action used.
3. Capture the exact exception/error and relevant PrestaShop/PHP log lines.
4. Identify whether the issue is install, PHP compatibility, PrestaShop runtime, SQL, rewrite/routing, multistore context, feed validation, cURL/SFTP, or translation related.
5. Inspect the responsible code before modifying it.
6. Apply the smallest safe fix.
7. Re-run the failed step and nearby regression checks.
8. Do not rewrite working architecture merely to silence an environment-specific error.

Useful locations may include the PrestaShop `var/logs` directory, web-server error logs, PHP-FPM logs, and PrestaShop application logs depending on the server setup.

## Report format back to the project owner

Keep reports concise and factual.

Use this structure:

```text
Environment:
- PrestaShop:
- PHP:
- Web server:
- Database:
- Multistore: yes/no

Installation:
- psagenticcommerce: PASS/FAIL
- fdpsucp: PASS/FAIL/not tested

Runtime:
- Back Office config: PASS/FAIL
- Turkish translations: PASS/FAIL
- AI JSON discovery: PASS/FAIL
- AI JSON catalog/product: PASS/FAIL
- UCP search/lookup/product: PASS/FAIL/not tested
- OpenAI snapshot: PASS/FAIL
- SFTP upload: PASS/FAIL/not tested
- Cron: PASS/FAIL/not tested
- Multistore isolation: PASS/FAIL/not applicable

Errors:
- exact error message and log location

Changes made:
- files changed
- reason

Remaining issue:
- concise description
```

Do not report unexecuted checks as PASS.

## Important project files

Start with these files when diagnosing behavior:

```text
README.md
modules/psagenticcommerce/README.md
modules/psagenticcommerce/psagenticcommerce.php
modules/psagenticcommerce/src/Config/OpenAiBackOfficeForm.php
modules/psagenticcommerce/src/Config/OpenAiSettings.php
modules/psagenticcommerce/src/Export/OpenAi/
modules/psagenticcommerce/src/Ucp/
modules/psagenticcommerce/controllers/front/
modules/psagenticcommerce/src/Install/
modules/psagenticcommerce/translations/
modules/fdpsucp/src/Catalog/
modules/fdpsucp/src/Router.php
docs/release-gates-v1.md
```

The release-gates document is the authoritative list of known unfinished release work. Do not confuse a documented release gate with a newly introduced server error.
