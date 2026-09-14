#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE="$ROOT/modules/psagenticcommerce"
DIST="$ROOT/dist"
STAGE="$DIST/stage"
PACKAGE="$DIST/psagenticcommerce.zip"

command -v zip >/dev/null 2>&1 || { echo "zip command is required" >&2; exit 1; }
[ -f "$SOURCE/psagenticcommerce.php" ] || { echo "module source not found" >&2; exit 1; }

rm -rf "$STAGE" "$PACKAGE"
mkdir -p "$STAGE/psagenticcommerce"
cp -a "$SOURCE/." "$STAGE/psagenticcommerce/"

# Development-only files are not needed by a production PrestaShop install.
rm -rf "$STAGE/psagenticcommerce/tests"
rm -f "$STAGE/psagenticcommerce/phpunit.xml"

(
  cd "$STAGE"
  zip -q -r "$PACKAGE" psagenticcommerce
)
rm -rf "$STAGE"

echo "$PACKAGE"
