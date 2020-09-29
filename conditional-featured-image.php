<?php
/*
Plugin Name: Conditionally display featured image on singular pages and posts
Plugin URI: https://github.com/cyrillbolliger/conditional-featured-image
Description: Choose if the featured image should be displayed in the single post/page view or not. This plugin doesn't affect the archives view.
Version: 2.4.0
Author: Cyrill Bolliger
Text Domain: conditionally-display-featured-image-on-singular-pages
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
define( 'CYBOCFI_VERSION', '2.4.0' );

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
         * The label of the checkbox in the featured image meta box.
         *
         * @string
         */
	    private $label;

		/**
		 * Starting point of the magic
		 */
		public function run() {
			add_action( 'current_screen', array($this, 'check_post_type_and_load') );
		}

        /**
         * Get the post type, expose it to the filter to disable the plugin,
         * then load it, if not disabled.
         *
         * @param WP_Screen $current_screen
         */
		public function check_post_type_and_load( $current_screen ) {
		    $post_type = $current_screen->post_type;
            /*
             * Allow to disable the plugin for certain post types.
             *
             * The filter function must return false to disable the plugin.
             *
             * @since 2.3.0
             *
             * @param string $post_type The current post type.
             */
            $enabled = apply_filters( 'cybocfi_post_type', $post_type, true );

            if ( false !== $enabled ) { // check for not false so it will work if the filter doesn't return anything
                $this->initialize_metabox();
            }
        }

        /**
         * Load the modified featured image meta box.
         */
		private function initialize_metabox() {
            $this->set_checkbox_label();

            // distinguish between the block editor and the classic editor
            if ( $this->is_block_editor() ) {
                // register the js
                add_action( 'enqueue_block_editor_assets', array( &$this, 'load_block_editor_js' ) );

                // expose the meta field to the rest api
                self::expose_meta_field_to_rest_api();
            } else {
                // modify the featured image metabox
                add_action( 'add_meta_boxes', array( &$this, 'modify_postimagediv_metabox' ) );

                // save the custom meta input
                add_action( 'save_post', array( &$this, 'save_custom_meta_content' ) );
            }
        }

        /**
         * Define the text of the label of the checkbox in the featured image
         * meta box. Use the 'cibocfi_checkbox_label' filter to change it.
         */
		private function set_checkbox_label() {
		    $label = __(
                'Display featured image in post lists only, hide on singular views.',
                'conditionally-display-featured-image-on-singular-pages'
            );

            /*
             * Filter the label of the checkbox in the featured image meta box.
             *
             * @since 2.2.0
             *
             * @param string $label The localized label text.
             */
            $this->label = apply_filters( 'cibocfi_checkbox_label', $label );
        }

        /**
         * Expose the meta field to the rest api so we can use it with the block editor
         */
		public static function expose_meta_field_to_rest_api() {
			register_meta( 'post', CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', array(
				'show_in_rest'      => true,
				'type'              => 'string', // compatibility with classic editor
				'single'            => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => function ( $value ) {
					return 'yes' === $value ? 'yes' : '';
				},
			) );
		}

		/**
		 * Load the js that modifies the block editor
		 */
		public function load_block_editor_js() {
			wp_enqueue_script(
				'cybocfi-script',
				plugins_url( 'build/index.js', __FILE__ ),
				array(
					'wp-components',
					'wp-compose',
					'wp-data',
					'wp-element',
					'wp-hooks',
					'wp-i18n',
				)
			);

			wp_localize_script(
			        'cybocfi-script',
                    'cybocfiL10n',
                    array( 'featuredImageCheckboxLabel' => $this->label )
            );

            wp_add_inline_script(
                'cybocfi-script',
                $this->get_block_editor_inline_script(),
                'before'
            );
		}

        /**
         * Return the JS code, that will be inlined before the block editor script
         *
         * Add any data, that must be accessible in the block editor, here.
         *
         * @return string
         */
        private function get_block_editor_inline_script() {
		    $data = array(
		            'hideByDefault' => $this->get_default_checkbox_value(),
            );

            return 'var cybocfi = ' . json_encode($data) . ';';
		}

		/**
		 * Replace the core metabox by a custom one
		 *
		 * @param string $post_type as given by the add_meta_boxes action
		 *
		 * @global array $wp_meta_boxes
		 *
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
		 * Check if we are using the block editor
		 *
		 * Compatible with gutenberg plugin and classic editor plugin
		 *
		 * @return bool
		 */
		private function is_block_editor() {
			global $current_screen;

			$current_screen = get_current_screen();
			if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
				return true;
			}

			if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
				return true;
			}

			return false;
		}

		/**
		 * Create content of the new metabox
		 *
		 * @global WP_Post $post
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
			<?php // close .inside div, so the core js doesn't affect our code. ?>
            </div>
			<?php // put our code in a custom .inside div ?>
        <div class="<?php echo CYBOCFI_PLUGIN_PREFIX . '-inside'; ?>" style="padding: 0 12px;">
			<?php

			// insert a nonce
			wp_nonce_field( CYBOCFI_PLUGIN_PREFIX . 'save_custom_meta', CYBOCFI_PLUGIN_PREFIX . '_nonce' );

			// insert form
			?>
            <p>
                <label for="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>">
                    <input type="hidden" name="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>"
                           value="no">
                    <input type="checkbox" name="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>"
                           id="<?php echo CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image'; ?>"
                           value="yes" <?php $this->the_checked_tag() ?> />
					<?php echo $this->label ?>
                </label>
            </p>
			<?php // the custom .inside div will be closed by the core
		}

        /**
         * Echo the featured image checkbox' checked tag if needed.
         *
         * The checked tag is omitted, if it should not be checked.
         */
        public function the_checked_tag() {
            global $post;

            $new = get_current_screen()->action === 'add';

            if ( $new ) {
                checked( $this->get_default_checkbox_value() );
            } else {
                $stored_meta = get_post_meta( $post->ID );

                if ( isset ( $stored_meta[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ] ) ) {
                    checked( $stored_meta[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ][0], 'yes' );
                }
            }
        }

		/**
		 * Save the custom meta input
		 *
		 * @param int $post_id as given by the save_post action
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

        /**
         * Add a filter to control if the featured image should be hidden by
         * default for new posts and pages.
         *
         * @return boolean
         */
        private function get_default_checkbox_value() {
            /*
             * Filter hook to hide the image by default for any new posts and
             * pages (preselecting the checkbox).
             *
             * The filter function must return true to hide the image by default.
             *
             * @since 2.4.0
             *
             * @param boolean $enabled  The current default value.
             * @param string $post_type The current post type.
             */
            $enabled = apply_filters( 'cybocfi_hide_by_default', false, get_current_screen()->post_type );

            return true === $enabled; // check explicitly for true to handle misused filter functions.
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
            /**
             * Take loop_start as entry point since it doesn't affect the header
             * stuff, where the featured image might be used for the open graph
             * or a twitter card.
             *
             * @since 2.1.0
             */
            add_action('loop_start', function ( $wp_query ) {
                if ( $wp_query->is_main_query() ) {
                    add_action( 'the_post', array( &$this, 'set_visibility' ) );
                }
            });

            /**
             * Remove the featured image from Yoast SEO's schema.org if needed.
             *
             * @since 2.1.0
             */
            add_filter('wpseo_schema_graph_pieces', array( &$this, 'set_schema_visibility' ), 10, 2 );
		}

        /**
         * Hide the featured image in the Yoast SEO schema.org output, if the
         * corresponding flag is set.
         *
         * @param array $pieces The schema pieces.
         * @param WPSEO_Schema_Context $context An object with context variables.
         *
         * @return array
         */
        public function set_schema_visibility($pieces, $context) {
            $post_id = $context->id;

            if ( $this->is_image_marked_hidden( $post_id ) ) {
                return $this->remove_mainimage_schema_block( $pieces );
            } else {
                return $pieces;
            }
		}

        /**
         * Remove the Yoast SEO schema block that carries the main image
         *
         * @param array $pieces
         *
         * @return array
         */
        private function remove_mainimage_schema_block( $pieces ) {
            foreach($pieces as $key => $piece) {
                if ($piece instanceof WPSEO_Schema_MainImage) {
                    unset($pieces[$key]);
                    break;
                }
            }

            return $pieces;
		}

		/**
		 * Hide the featured image on single posts where the corresponding flag
		 * was set in the backend.
		 *
		 * @param WP_Post $post as passed by the the_post action
		 */
		public function set_visibility( $post ) {

			// abort if it's not a single post
			if ( ! ( is_single( $post->ID ) || is_page( $post->ID ) ) ) {
				return;
			}

			// hide the featured image if it was set so
			if ( $this->is_image_marked_hidden( $post->ID ) ) {
				$this->filter_featured_image( $post->ID );
			}
		}

        /**
         * Should the featured image of the given post be hidden?
         *
         * @param int $post_id the post id of the post with the featured image
         *
         * @return bool
         */
        private function is_image_marked_hidden( $post_id )
        {
            // get visibility option
            return (bool) get_post_meta( $post_id, CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', true );
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
		 * Set the thumbnail_id to false to make the wordpress core believe
		 * there is no thumbnail/featured image
		 *
		 * @param mixed $value given by the get_post_metadata filter
		 * @param int $object_id
		 * @param string $meta_key
		 *
		 * @return boolean
		 * @see has_post_thumbnail()
		 *
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

/**
 * Run this to save the block editor value
 */
add_action( 'rest_api_init', array( Cybocfi_Admin::class, 'expose_meta_field_to_rest_api' ) );