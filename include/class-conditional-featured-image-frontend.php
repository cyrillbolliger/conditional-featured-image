<?php

if ( ! class_exists( 'Cybocfi_Frontend' ) ) {

	class Cybocfi_Frontend {
		/**
		 * @var Cybocfi_Frontend
		 */
		private static $instance;

		/**
		 * Disallow regular instantiation.
		 */
		private function __construct() {
		}

		/**
		 * Constructor for singleton.
		 *
		 * @return Cybocfi_Frontend
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new Cybocfi_Frontend();
			}

			return self::$instance;
		}

		/**
		 * The id of the post, where the image shall be removed
		 *
		 * @var int
		 */
		private $post_id;

		/**
		 * The currently processed query
		 *
		 * @var WP_Query
		 */
		private $query;

		/**
		 * Starting point of the magic
		 */
		public function run() {
			/**
			 * Allow customizing the hook, at which the plugin starts hiding the
			 * featured image.
			 *
			 * The default action hook, 'loop_start', doesn't affect the header
			 * stuff, where the featured image might be used for the open graph
			 * or a twitter card.
			 *
			 * As some themes load the featured image already in the header, you
			 * might have to go with an earlier hook like 'get_header' or even
			 * 'wp'. Be aware, that this might have some side effects, as
			 * it might also hide the featured image for plugins that would
			 * normally need it, like SEO plugins et al.
			 *
			 * @param string $startup_hook WordPress action
			 *
			 * @since 2.8.0
			 *
			 */
			$startup_hook = apply_filters( 'cybocfi_startup_hook', 'loop_start' );
			add_action( $startup_hook, array( &$this, 'set_visibility' ) );

			/**
			 * Support for themes that render the post-featured-image before $startup_hook
			 *
			 * @since 2.13.0
			 */
			add_filter( 'render_block_core/post-featured-image', array( &$this, 'featured_image_block' ) );

			/**
			 * Remove the featured image from Yoast SEO's schema.org if needed.
			 *
			 * @since 2.1.0
			 */
			add_filter( 'wpseo_schema_graph_pieces', array( &$this, 'set_schema_visibility' ), 10, 2 );

			/**
			 * Support for the twentynineteen theme
			 *
			 * @since 2.6.0
			 */
			add_filter( 'twentynineteen_can_show_post_thumbnail', array( &$this, 'twentynineteen' ) );
		}

		/**
		 * Support for the twentynineteen theme
		 *
		 * @param bool $can_show_thumbnail
		 *
		 * @return bool
		 */
		public function twentynineteen( $can_show_thumbnail ) {
			if ( is_singular() && is_main_query() && $this->is_image_marked_hidden( get_the_ID() ) ) {
				return false;
			}

			return $can_show_thumbnail;
		}

		/**
		 * Prevent block rendering needed
		 *
		 * If the featured image is marked hidden, we are in the main query and
		 * the page is singular, the given block content is removed.
		 *
		 * @param string $block_content
		 *
		 * @return string
		 *
		 * @since 2.13.0
		 */
		public function featured_image_block( $block_content ) {
			if (! $this->query) {
				// required if using Gutenberg 16.6.0 (and probably future versions)
				// see https://wordpress.org/support/topic/plugin-working-with-wp6-3-on-tt3/
				// see https://wordpress.org/support/topic/duplicate-featured-image-10/
				// added in 3.1.1
				$this->set_query();
			}

			if ( $this->query
			     && $this->query->is_singular()
			     && $this->query->is_main_query()
			     && ! $this->is_query_block() // check added in 2.14.0
			     && $this->is_image_marked_hidden( get_the_ID() )
			) {
				return '';
			}

			return $block_content;
		}

		/**
		 * Test if the current query origins from a query block
		 *
		 * @see https://github.com/cyrillbolliger/conditional-featured-image/issues/43
		 * @see https://wordpress.org/support/topic/featured-image-removed-from-query-loop-block/
		 *
		 * @return bool
		 *
		 * @since 2.14.0
		 */
		private function is_query_block() {
			return array_key_exists( 'pagename', $this->query->query )
			       && 'query-block' === $this->query->query['pagename'];
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
		public function set_schema_visibility( $pieces, $context ) {
			$post_id = $context->id;

			if ( $this->is_image_marked_hidden( $post_id ) ) {
				return $this->remove_mainimage_schema_block( $pieces );
			}

			return $pieces;
		}

		/**
		 * Remove the Yoast SEO schema block that carries the main image
		 *
		 * @param array $pieces
		 *
		 * @return array
		 */
		private function remove_mainimage_schema_block( $pieces ) {
			foreach ( $pieces as $key => $piece ) {
				if ( $piece instanceof \Yoast\WP\SEO\Generators\Schema\Main_Image ) {
					unset( $pieces[ $key ] );
					break;
				}
			}

			return $pieces;
		}

		/**
		 * Hide the featured image on single posts where the corresponding flag
		 * was set in the backend.
		 *
		 * @param mixed|null $startup_hook_value
		 */
		public function set_visibility( $startup_hook_value = null ) {
			if ( ! $this->set_query( $startup_hook_value ) ) {
				return;
			}

			/**
			 * Remove the filters, if it's not the main query. This is the case,
			 * if the current query is executed after the main query.
			 *
			 * @since 2.5.1
			 */
			if ( ! $this->query->is_main_query() ) {
				$this->remove_featured_image_filter();

				return;
			}

			$post_id = get_the_ID();

			// abort if it's not a single post
			if ( ! ( is_single( $post_id ) || is_page( $post_id ) ) ) {
				return;
			}

			/**
			 * Don't hide from oembeds.
			 *
			 * @since 2.12.0
			 */
			if ( $this->query->is_embed() ) {
				$this->remove_featured_image_filter();

				return;
			}

			// hide the featured image if it was set so
			if ( $this->is_image_marked_hidden( $post_id ) ) {
				$this->add_featured_image_filter( $post_id );
			}
		}

		/**
		 * Populate $this->query.
		 *
		 * If $startup_hook_value is a WP_Query use it, else try the global
		 * $wp_query. If both aren't instances of WP_Query, return false;
		 *
		 * @param mixed $startup_hook_value
		 *
		 * @return bool
		 *
		 * @since 3.0.0
		 */
		private function set_query( $startup_hook_value = null ) {
			global $wp_query;

			// If the cybocfi_startup_hook was customized, $startup_hook_value
			// might be anything.
			if ( $startup_hook_value instanceof WP_Query ) {
				$this->query = $startup_hook_value;

				return true;
			}

			if ( $wp_query instanceof WP_Query ) {
				$this->query = $wp_query;

				return true;
			}

			return false;
		}

		/**
		 * Should the featured image of the given post be hidden?
		 *
		 * @param int $post_id the post id of the post with the featured image
		 *
		 * @return bool
		 */
		private function is_image_marked_hidden( $post_id ) {
			/**
			 * Never hide the featured image on post types the plugin is not enabled for
			 *
			 * @since 2.13.0
			 */
			$post_type = get_post_type( $post_id );
			if ( $post_type && ! Cybocfi_Util::is_enabled_for_post_type( $post_type ) ) {
				return false;
			}

			// get visibility option
			return (bool) get_post_meta( $post_id, CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image', true );
		}

		/**
		 * Filter the posts metadata to remove the image
		 *
		 * @param int $post_id ID of the post who's featured image shall be
		 * removed
		 */
		public function add_featured_image_filter( $post_id ) {
			$this->post_id = $post_id;
			add_filter( 'get_post_metadata', array( &$this, 'hide_featured_image_in_the_loop' ), 10, 3 );
		}

		/**
		 * Filter the posts metadata to remove the image
		 */
		public function remove_featured_image_filter() {
			remove_filter( 'get_post_metadata', array( &$this, 'hide_featured_image_in_the_loop' ) );
			$this->post_id = null;
		}

		/**
		 * Set the thumbnail_id to false if in the loop, to make the wordpress
		 * core believe there is no thumbnail/featured image
		 *
		 * @param mixed $value given by the get_post_metadata filter
		 * @param int $object_id
		 * @param string $meta_key
		 *
		 * @return mixed
		 * @see has_post_thumbnail()
		 *
		 */
		public function hide_featured_image_in_the_loop( $value, $object_id, $meta_key ) {
			if ( '_thumbnail_id' !== $meta_key ) {
				return $value;
			}

			if ( $object_id !== $this->post_id ) {
				return $value;
			}

			/**
			 * Bypass circuit in_the_loop() test.
			 *
			 * Some themes load the featured image outside the loop. By passing
			 * false to this filter, the plugin skips the in_the_loop test and
			 * hides the featured image also outside the loop. Passing false
			 * also hides the featured image in the 'latest posts' widget.
			 *
			 * @param boolean $only_hide_in_the_loop
			 *
			 * @since 2.9.0
			 *
			 */
			$only_in_the_loop = apply_filters( 'cybocfi_only_hide_in_the_loop', true );

			if ( ! $only_in_the_loop ) {
				return false;
			}

			if ( in_the_loop() ) {
				return false;
			}

			return $value;
		}
	}
}