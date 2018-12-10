<?php
/*
Plugin Name: Conditionally display featured image on singular pages and posts
Plugin URI: https://github.com/cyrillbolliger/conditional-featured-image
Description: Choose if the featured image should be displayed in the single post/page view or not. This plugin doesn't affect the archives view.
Version: 1.3.0
Author: Cyrill Bolliger
Text Domain:  conditionally-display-featured-image-on-singular-pages
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


/**
 * Lock out script kiddies: die an direct call
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Abspath to plugins directory
 */
define( 'CYBOCFI_PLUGIN_PATH', dirname( __FILE__ ) );

/**
 * Version number (don't forget to change it also in the header)
 */
define( 'CYBOCFI_VERSION', '1.3.0' );

/**
 * Plugin prefix
 */
define( 'CYBOCFI_PLUGIN_PREFIX', 'cybocfi' );

/**
 * Add a checkbox to the featured image meta box where the user can select, if
 * the image should only be displayed in the post preview but not in the single
 * view.
 */
if ( ! class_exists( 'Cybocfi_Admin' ) ) {
	
	class Cybocfi_Admin {
		
		/**
		 * Starting point of the magic
		 */
		public function run() {
			// modify the featured image metabox
			add_action( 'add_meta_boxes', array( &$this, 'modify_postimagediv_metabox' ) );
			
			// save the custom meta input
			add_action( 'save_post', array( &$this, 'save_custom_meta_content' ) );
		}
		
		/**
		 * Replace the core metabox by a custom one
		 *
		 * @global array $wp_meta_boxes
		 *
		 * @param string $post_type as given by the add_meta_boxes action
		 */
		public function modify_postimagediv_metabox( $post_type ) {
			global $wp_meta_boxes;
			
			// abort if the current post type has no featured image
			if ( ! isset( $wp_meta_boxes[ $post_type ]['side']['low']['postimagediv'] ) ) {
				return;
			}
			
			// remove core metabox
			remove_meta_box( 'postimagediv', 'post', 'side' );
			
			// add the new metabox
			add_meta_box( 'postimagediv', __( 'Featured Image', 'conditionally-display-featured-image-on-singular-pages' ), array(
				&$this,
				'new_post_thumbnail_meta_box'
			), $post_type, 'side', 'low' );
		}
		
		/**
		 * Create content of the new metabox
		 *
		 * @global type $post
		 */
		public function new_post_thumbnail_meta_box() {
			global $post;
			
			/**
			 * insert the content of the core metabox
			 *
			 * @link https://developer.wordpress.org/reference/functions/post_thumbnail_meta_box/
			 */
			$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
			echo _wp_post_thumbnail_html( $thumbnail_id, $post->ID );
			
			/**
			 * insert our custom code
			 */
			?>
			<?php // close .inside div, so the core js doensn't affect our code. ?>
            </div>
			<?php // put our code in a custom .inside div ?>
        <div class="<?php echo CYBOCFI_PLUGIN_PREFIX . '-inside'; ?>" style="padding: 0 12px;">
			<?php
			
			// insert a nonce
			wp_nonce_field( CYBOCFI_PLUGIN_PREFIX . 'save_custom_meta', CYBOCFI_PLUGIN_PREFIX . '_nonce' );
			$stored_meta = get_post_meta( $post->ID );
			
			// insert form
			?>
            <p>
                <label for="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>">
                    <input type="hidden" name="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>" value="no">
                    <input type="checkbox" name="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>"
                           id="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>"
                           value="yes" <?php if ( isset ( $stored_meta[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ] ) ) {
						checked( $stored_meta[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ][0], 'yes' );
					} ?> />
					<?php _e( 'Display featured image in post lists only, hide on singular views.', 'conditionally-display-featured-image-on-singular-pages' ) ?>
                </label>
            </p>
			<?php // the custom .inside div will be closed by the core
		}
		
		/**
		 * Save the custom meta input
		 *
		 * @param type $post_id as given by the save_post action
		 */
		public function save_custom_meta_content( $post_id ) {
			// check save status
			$is_autosave = wp_is_post_autosave( $post_id );
			$is_revision = wp_is_post_revision( $post_id );
			
			// check nonce
			$is_valid_nonce = ( isset( $_POST[ CYBOCFI_PLUGIN_PREFIX . '_nonce' ] ) && wp_verify_nonce( $_POST[ CYBOCFI_PLUGIN_PREFIX . '_nonce' ], CYBOCFI_PLUGIN_PREFIX . 'save_custom_meta' ) ) ? 'true' : 'false';
			
			// exit script depending on save status and nonce
			if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
				return;
			}
			
			// save input
			if ( isset( $_POST[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ] ) ) {
                $value = 'yes' === $_POST[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ] ? 'yes' : '';
				update_post_meta( $post_id, CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', $value );
			}
		}
	}
}

if ( ! class_exists( 'Cybocfi_Frontend' ) ) {
	
	class Cybocfi_Frontend {
		/**
		 * The id of the post, where the image shall be removed
         *
         * @var int
		 */
	    private $post_id;
		
		/**
		 * Starting point of the magic
		 */
		public function run() {
			add_action( 'the_post', array( &$this, 'set_visibility' ) );
		}
		
		/**
		 * Hide the featured image on single posts where the correspondig flag
		 * was set in the backend.
		 *
		 * @param WP_Post $post as passed by the the_post action
		 */
		public function set_visibility( $post ) {
			
			// abort if it's not a single post
			if ( ! ( is_single( $post->ID ) || is_page( $post->ID ) ) ) {
				return;
			}
			
			// get visibility option
			$hide = (bool) get_post_meta( $post->ID, CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', true );
			
			// hide the featured image if it was set so
			if ( $hide ) {
				$this->filter_featured_image( $post->ID );
			}
		}
		
		/**
		 * Filter the posts metadata to remove the image
		 *
		 * @param int $post_id ID of the post who's featured image shall be
		 * removed
		 */
		public function filter_featured_image( $post_id ) {
		    $this->post_id = $post_id;
			add_filter( 'get_post_metadata', array( &$this, 'hide_featured_image' ), 10, 3 );
		}
		
		/**
		 * Set the thumbnail_id to false to make the wordpress core belive
		 * there is no thumbnail/featured image
		 *
		 * @see has_post_thumbnail()
		 *
		 * @param mixed $value given by the get_post_metadata filter
		 * @param int $object_id
		 * @param string $meta_key
		 *
		 * @return boolean
		 */
		public function hide_featured_image( $value, $object_id, $meta_key ) {
			if ( '_thumbnail_id' == $meta_key && $object_id === $this->post_id ) {
				return false;
			}
		}
	}
}

/**
 * Run admin code
 */
if ( is_admin() ) {
	$cybocfi_admin = new Cybocfi_Admin();
	$cybocfi_admin->run();
}

/**
 * Run frontend code
 */
if ( ! is_admin() ) {
	$cybocfi_frontend = new Cybocfi_Frontend();
	$cybocfi_frontend->run();
}
