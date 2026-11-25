#!/usr/bin/env bash
# Local setup for the Riverbend demo (Nightcoders demonstration project).
# Needs PHP 8.3+ with pdo_sqlite, gd, xml, mbstring; Composer 2; SQLite 3.45+.
set -euo pipefail
cd "$(dirname "$0")/.."
composer install --no-interaction
mkdir -p private/files
[ -f private/salt.txt ] || php -r 'echo bin2hex(random_bytes(32));' > private/salt.txt
PASS="${ADMIN_PASS:-$(php -r 'echo bin2hex(random_bytes(8));')}"
vendor/bin/drush site:install --existing-config --account-name=demo-admin --account-pass="$PASS" -y
vendor/bin/drush php:script scripts/sample-content.php
vendor/bin/drush cr
echo
echo "Done. Start a local server:  cd web && php -S 127.0.0.1:8888 .ht.router.php"
echo "Then open http://127.0.0.1:8888  (admin: demo-admin / $PASS — local use only)"
