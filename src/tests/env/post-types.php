<?php
/**
 * Must-use plugin of the test site (.wp-env.json maps it): post types
 * registered in code. `cybocfi_note` and `cybocfi_memo` without
 * `custom-fields` support: the plugin adds it to `cybocfi_note`, which has a
 * featured image, not to `cybocfi_memo`. `cybocfi_quote` with
 * `custom-fields` support but no featured image. `cybocfi_sketch` gets its
 * featured image after registration: the plugin can't add the support.
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
		register_post_type(
			'cybocfi_quote',
			array(
				'label'        => 'Quotes',
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
			)
		);
		register_post_type(
			'cybocfi_sketch',
			array(
				'label'        => 'Sketches',
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor' ),
			)
		);
		add_post_type_support( 'cybocfi_sketch', 'thumbnail' );
	}
);
