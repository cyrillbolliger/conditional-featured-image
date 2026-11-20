<?php
/**
 * Must-use plugin of the test site (.wp-env.json maps it): turns the
 * `cybocfi_hide_by_default` filter on as the `cybocfi_test_hide_by_default`
 * option says, so a spec sets it with WP-CLI.
 *
 * `on` from the start of the request. `late` only from
 * enqueue_block_editor_assets on: after post-new.php created the auto-draft,
 * so only the editor script applies the default.
 */

switch ( get_option( 'cybocfi_test_hide_by_default' ) ) {
	case 'on':
		add_filter( 'cybocfi_hide_by_default', '__return_true' );
		break;
	case 'late':
		add_action(
			'enqueue_block_editor_assets',
			function () {
				add_filter( 'cybocfi_hide_by_default', '__return_true' );
			},
			0
		);
		break;
}
