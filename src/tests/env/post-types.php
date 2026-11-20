<?php
/**
 * Must-use plugin of the test site (.wp-env.json maps it): post types
 * registered in code without `custom-fields` support. The plugin adds it to
 * `cybocfi_note`, which has a featured image, not to `cybocfi_memo`.
 */

add_action(
	'init',
	function () {
		register_post_type(
			'cybocfi_note',
			array(
				'label'        => 'Notes',
				'public'       => true,
				'show_in_rest' => true,
				// revisions: core's footnotes meta needs them
				'supports'     => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			)
		);
		register_post_type(
			'cybocfi_memo',
			array(
				'label'        => 'Memos',
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}
);
