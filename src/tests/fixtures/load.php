<?php
/**
 * The fixture content of the end-to-end check, run by tests/env/fixtures.sh.
 *
 * Each entry gets its own copy of an image, named after its slug, so a spec
 * finds an entry's featured image by its file name. Replaces the fixtures of
 * an earlier run (marked by the _cybocfi_fixture meta).
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$old_fixtures = get_posts( array(
	'post_type'   => 'any',
	'post_status' => 'any',
	'numberposts' => -1,
	'meta_key'    => '_cybocfi_fixture',
	'fields'      => 'ids',
) );
foreach ( $old_fixtures as $id ) {
	wp_delete_post( $id, true );
}
$old_images = get_posts( array(
	'post_type'   => 'attachment',
	'post_status' => 'inherit',
	'numberposts' => -1,
	'meta_key'    => '_cybocfi_fixture',
	'fields'      => 'ids',
) );
foreach ( $old_images as $id ) {
	wp_delete_attachment( $id, true );
}

// A post type as CPT UI registers it, without `custom-fields`: the plugin
// adds that support (cptui_user_supports_params).
// CPT UI reads every key its form saves.
update_option( 'cptui_post_types', array(
	'book' => array(
		'name'                  => 'book',
		'label'                 => 'Books',
		'singular_label'        => 'Book',
		'description'           => '',
		'public'                => 'true',
		'publicly_queryable'    => 'true',
		'show_ui'               => 'true',
		'show_in_nav_menus'     => 'true',
		'delete_with_user'      => 'false',
		'show_in_rest'          => 'true',
		'rest_base'             => '',
		'rest_controller_class' => '',
		'rest_namespace'        => '',
		'has_archive'           => 'false',
		'has_archive_string'    => '',
		'exclude_from_search'   => 'false',
		'capability_type'       => 'post',
		'hierarchical'          => 'false',
		'can_export'            => 'true',
		'rewrite'               => 'true',
		'rewrite_slug'          => '',
		'rewrite_withfront'     => 'true',
		'query_var'             => 'true',
		'query_var_slug'        => '',
		'menu_position'         => '',
		'show_in_menu'          => 'true',
		'show_in_menu_string'   => '',
		'menu_icon'             => '',
		'register_meta_box_cb'  => '',
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
		'taxonomies'            => array(),
		'labels'                => array(),
		'custom_supports'       => '',
	),
) );

$image = function ( $source, $name ) {
	$tmp = wp_tempnam( $name );
	copy( __DIR__ . "/$source.jpeg", $tmp );
	$id = media_handle_sideload( array( 'name' => "$name.jpeg", 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		throw new Exception( $id->get_error_message() );
	}
	update_post_meta( $id, '_cybocfi_fixture', 1 );

	return $id;
};

$entry = function ( $post_type, $slug, $hidden, $content = '' ) use ( $image ) {
	$id = wp_insert_post( array(
		'post_type'    => $post_type,
		'post_name'    => $slug,
		'post_title'   => ucwords( str_replace( '-', ' ', $slug ) ),
		'post_content' => $content,
		'post_status'  => 'publish',
		'meta_input'   => array(
			'_cybocfi_fixture'            => 1,
			'cybocfi_hide_featured_image' => $hidden ? 'yes' : '',
		),
	), true );
	if ( is_wp_error( $id ) ) {
		throw new Exception( $id->get_error_message() );
	}
	set_post_thumbnail( $id, $image( 'bike', $slug ) );

	return $id;
};

$entry( 'post', 'hidden-post', true );
$entry( 'post', 'shown-post', false );
$entry( 'post', 'block-editor-post', false );
$entry( 'post', 'classic-editor-post', false );
$entry( 'page', 'hidden-page', true );
$entry( 'page', 'shown-page', false );
// Lists the posts with their featured images, inside a hidden page.
$entry(
	'page',
	'query-loop-page',
	true,
	'<!-- wp:query {"query":{"perPage":20,"postType":"post","inherit":false}} -->'
	. '<div class="wp-block-query"><!-- wp:post-template -->'
	. '<!-- wp:post-title /--><!-- wp:post-featured-image /-->'
	. '<!-- /wp:post-template --></div><!-- /wp:query -->'
);
$entry( 'book', 'hidden-book', true );

foreach ( array( 'hidden-product', 'gallery-product', 'shown-product' ) as $slug ) {
	$id      = $entry( 'product', $slug, 'shown-product' !== $slug );
	$product = wc_get_product( $id );
	$product->set_regular_price( '10' );
	if ( 'gallery-product' === $slug ) {
		$product->set_gallery_image_ids( array( $image( 'hike', 'gallery-image' ) ) );
	}
	$product->save();
}
