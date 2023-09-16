<?php
/*
Plugin Name: Conditionally display featured image on singular pages and posts
Plugin URI: https://github.com/cyrillbolliger/conditional-featured-image
Description: Choose if the featured image should be displayed in the single post/page view or not. This plugin doesn't affect the archives view.
Version: 3.1.1
Author: Cyrill Bolliger
Text Domain: conditionally-display-featured-image-on-singular-pages
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


/**
 * Lock out script kiddies: die an direct call
 */
defined( 'ABSPATH' ) or die;

/**
 * Abspath to plugins directory
 */
define( 'CYBOCFI_PLUGIN_PATH', __DIR__ );

/**
 * Version number (don't forget to change it also in the header)
 */
define( 'CYBOCFI_VERSION', '3.1.1' );

/**
 * Plugin prefix
 */
define( 'CYBOCFI_PLUGIN_PREFIX', 'cybocfi' );

/**
 * Shared code
 */
require_once 'include/class-conditional-featured-image-util.php';

/**
 * Run admin code
 */
require_once 'include/class-conditional-featured-image-admin.php';
add_action( 'current_screen', array( Cybocfi_Admin::get_instance(), 'check_post_type_and_load' ) );
add_action( 'save_post', array( Cybocfi_Admin::get_instance(), 'handle_imports' ), 10, 3 );
add_action( 'rest_api_init', array( Cybocfi_Admin::class, 'expose_meta_field_to_rest_api' ) );
add_action( 'cptui_user_supports_params', array( Cybocfi_Admin::get_instance(), 'cptui_compatibility' ), 10, 3 );

/**
 * Run frontend code
 */
if ( ! is_admin() ) {
	require_once 'include/class-conditional-featured-image-frontend.php';
	add_action( 'init', array( Cybocfi_Frontend::get_instance(), 'run' ) );
}
