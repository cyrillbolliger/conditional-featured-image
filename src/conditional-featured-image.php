<?php
/*
Plugin Name: Conditionally display featured image on singular pages and posts
Plugin URI: https://github.com/cyrillbolliger/conditional-featured-image
Description: Easily control whether the featured image appears in the single post or page view (doesn't hide it in archive/list view).
Version: 3.4.0
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
define( 'CYBOCFI_VERSION', '3.4.0' );

/**
 * Plugin prefix
 */
define( 'CYBOCFI_PLUGIN_PREFIX', 'cybocfi' );

/**
 * Plugin name
 */
define( 'CYBOCFI_PLUGIN_NAME', 'conditionally-display-featured-image-on-singular-pages' );

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

/**
 * Abilities API
 */
require_once 'include/class-conditional-featured-image-abilities.php';
add_action( 'wp_abilities_api_categories_init', array( Cybocfi_Abilities::get_instance(), 'register_category' ) );
add_action( 'wp_abilities_api_init', array( Cybocfi_Abilities::get_instance(), 'register_abilities' ) );