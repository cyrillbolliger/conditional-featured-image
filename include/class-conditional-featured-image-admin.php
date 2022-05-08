<?php

/**
 * Add a checkbox to the featured image meta box where the user can select, if
 * the image should only be displayed in the post preview but not in the single
 * view.
 */
if ( ! class_exists( 'Cybocfi_Admin' ) ) {

	class Cybocfi_Admin {
		/**
		 * @var Cybocfi_Admin
		 */
		private static $instance;

		/**
		 * The label of the checkbox in the featured image meta box.
		 *
		 * @string
		 */
		private $label;

		/**
		 * Disallow regular instantiation.
		 */
		private function __construct() {
		}

		/**
		 * Constructor for singleton.
		 *
		 * @return Cybocfi_Admin
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new Cybocfi_Admin();
			}

			return self::$instance;
		}

		/**
		 * Get the post type, expose it to the filter to disable the plugin,
		 * then load it, if not disabled.
		 *
		 * @param WP_Screen $current_screen
		 */
		public function check_post_type_and_load( $current_screen ) {
			$post_type = $current_screen->post_type;
			$enabled   = Cybocfi_Util::is_enabled_for_post_type( $post_type );

			if ( $enabled ) {
				$this->initialize_metabox();
			}
		}

		/**
		 * Mark the featured image as hidden for programmatically added posts,
		 * if the default is set to be hidden ('cybocfi_hide_by_default'
		 * filter).
		 *
		 * @param int $id
		 * @param WP_Post $post
		 * @param bool $update
		 *
		 * @since 2.5.0
		 *
		 */
		public function handle_imports( $id, $post, $update ) {
			// only hide the featured image for new posts. Existing posts must
			// be changed explicitly.
			if ( $update ) {
				return;
			}

			// only if the featured image should be hidden by default, we have
			// to add the flag. see: cybocfi_hide_by_default filter
			if ( ! $this->get_default_checkbox_value( $post->post_type ) ) {
				return;
			}

			// check, that we do only add the flag to post types that should be
			// handled by this plugin. see cybocfi_enabled_for_post_type filter
			if ( ! Cybocfi_Util::is_enabled_for_post_type( $post->post_type ) ) {
				return;
			}

			// ensure, we do not overwrite the flag if set explicitly.
			// (update_post_meta() is called before the post is actually
			// inserted and thus the save_post action is triggered).
			$meta           = get_post_meta( $id );
			$has_hide_value = array_key_exists( CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', $meta );
			if ( $has_hide_value ) {
				return;
			}

			$this->save_hide_flag( $id, true );
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

			/**
			 * Filter the label of the checkbox in the featured image meta box.
			 *
			 * @param string $label The localized label text.
			 *
			 * @since 2.2.0
			 *
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
				plugins_url( 'build/index.js', CYBOCFI_PLUGIN_PATH . '/conditional-featured-image.php' ),
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

			return 'var cybocfi = ' . json_encode( $data ) . ';';
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
				$value = 'yes' === $_POST[ CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image' ];
				$this->save_hide_flag( $post_id, $value );
			}
		}

		/**
		 * Persist the checkbox value.
		 *
		 * @param int $post_id The post id.
		 * @param bool $bool True to hide the featured image.
		 */
		private function save_hide_flag( $post_id, $bool ) {
			$value = $bool ? 'yes' : '';
			update_post_meta( $post_id, CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', $value );
		}

		/**
		 * Add a filter to control if the featured image should be hidden by
		 * default for new posts and pages.
		 *
		 * @param string $post_type The current post type. If not provided, it
		 * is detected by get_current_screen(). Thus, it must be given for posts
		 * that are inserted programmatically.
		 *
		 * @return boolean
		 */
		private function get_default_checkbox_value( $post_type = null ) {
			if ( null === $post_type ) {
				$post_type = get_current_screen()->post_type;
			}
			/**
			 * Filter hook to hide the image by default for any new posts and
			 * pages (preselecting the checkbox).
			 *
			 * The filter function must return true to hide the image by default.
			 *
			 * @param boolean $enabled The current default value.
			 * @param string $post_type The current post type.
			 *
			 * @since 2.4.0
			 *
			 */
			$enabled = apply_filters( 'cybocfi_hide_by_default', false, $post_type );

			return true === $enabled; // check explicitly for true to handle misused filter functions.
		}

		/**
		 * Add support for the CPT UI plugin
		 *
		 * The plugin needs support for 'custom-fields' when the post type is
		 * registered {@see register_post_type()}. This function adds this
		 * support for any post type that has this plugin enabled and that does
		 * not explicitly disable any support (passes false to the support arg
		 * of the register_post_type() function).
		 *
		 * @param array $value Empty array to add supports keys to.
		 * @param string $name Post type slug being registered.
		 *
		 * @return array
		 *
		 * @since 2.7.0
		 *
		 * @link https://developer.wordpress.org/reference/functions/register_post_type/
		 * @link https://github.com/WebDevStudios/custom-post-type-ui/blob/master/custom-post-type-ui.php
		 */
		public function cptui_compatibility( $value, $name ) {
			if ( Cybocfi_Util::is_enabled_for_post_type( $name ) ) {
				return array_merge( $value, array( 'custom-fields' ) );
			}

			return $value;
		}
	}
}