#!/usr/bin/env bash
# Prepares the wp-env site for the end-to-end check: the plugin active, the
# Classic Editor not (classic-editor.spec.js switches it on), WooCommerce's
# store open, no welcome screens after activation, then the fixture content
# (tests/fixtures/load.php).
set -euo pipefail

cd "$(dirname "$0")/../.."

# After `--`, wp-env passes the arguments on; it would read `--version` as
# its own.
wp() {
	npx wp-env run cli wp -- "$@"
}

# wp-env keeps the database when core changes; the login would stop at the
# upgrade screen.
wp core update-db
wp plugin activate conditional-featured-image
wp plugin deactivate classic-editor
# New stores start in »coming soon« mode, which hides them from visitors.
wp option update woocommerce_coming_soon no
# For 30 s after activation, WooCommerce and CPT UI redirect the next admin
# page to their welcome screen (a transient); in CI that is the login. All
# transients: the unpinned plugins may add more.
wp transient delete --all
wp rewrite structure '/%postname%/'
wp eval-file wp-content/plugins/conditional-featured-image/tests/fixtures/load.php
wp rewrite flush
