#!/usr/bin/env bash
# Genera spei-mexico-for-woocommerce.zip listo para subir a wordpress.org (sin pruebas ni entorno local).
set -euo pipefail
cd "$(dirname "$0")/.."
SLUG=spei-mexico-for-woocommerce
TMP=$(mktemp -d)
rsync -a --exclude-from=.distignore ./ "$TMP/$SLUG/"
rm -f "$SLUG.zip"
(cd "$TMP" && zip -qr - "$SLUG") > "$SLUG.zip"
rm -rf "$TMP"
echo "Listo: $(pwd)/$SLUG.zip"
