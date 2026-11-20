<?php
/**
 * Must-use plugin of the test site (.wp-env.json maps it): keeps WooCommerce
 * from redirecting the first admin page to its setup wizard.
 *
 * WooCommerce redirects while its `_wc_activation_redirect` transient is set.
 * tests/env/fixtures.sh deletes it, but it was set again at least once after
 * that (#85), and the login then ended on the setup wizard. The filter works
 * whenever the transient gets set.
 */

add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );
