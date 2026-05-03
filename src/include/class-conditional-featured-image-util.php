<?php

if ( ! class_exists( 'Cybocfi_Util' ) ) {

	class Cybocfi_Util {

		const HIDE_FLAG_META_KEY = CYBOCFI_PLUGIN_PREFIX . '_hide_featured_image';

		/**
		 * Wrapper for 'cybocfi_enabled_for_post_type' filter.
		 *
		 * Only returns false if the filter returns false. Any other falsy
		 * values returned by the filter are considered as true (be resilient to
		 * flaws in filter usage).
		 *
		 * @param string $post_type
		 *
		 * @return bool
		 */
		public static function is_enabled_for_post_type( $post_type ) {
			/**
			 * Allow to disable the plugin for certain post types.
			 *
			 * The filter function must return false to disable the plugin.
			 *
			 * @param bool $enabled Enable plugin for this post type. Default: true
			 * @param string $post_type The current post type.
			 *
			 * @since 2.10.0
			 */
			$enabled = apply_filters( 'cybocfi_enabled_for_post_type', true, $post_type );

			/**
			 * DEPRECATED. Allow to disable the plugin for certain post types.
			 *
			 * The filter function must return false to disable the plugin.
			 *
			 * @param string $post_type The current post type.
			 *
			 * @since 2.3.0
			 *
			 * @deprecated 3.0.0  This filter will be removed in the
			 *                    future. Use 'cybocfi_enabled_for_post_type'
			 *                    filter instead.
			 */
			$deprecated = apply_filters_deprecated(
				'cybocfi_post_type',
				array( $post_type, true ),
				'2.11.0',
				'cybocfi_enabled_for_post_type',
				'See <a href="https://wordpress.org/plugins/conditionally-display-featured-image-on-singular-pages/#faq-header">FAQ</a> for further assistance.'
			);

			// check for not false so the plugin will still work if the filter
			// doesn't return anything
			return false !== $enabled && false !== $deprecated;
		}

		/**
		 * Persist the hide flag in the post meta.
		 *
		 * @param int $post_id The post id.
		 * @param bool $bool True to hide the featured image.
		 */
		public static function save_hide_flag( $post_id, $bool ) {
			$value = $bool ? 'yes' : '';
			update_post_meta( $post_id, self::HIDE_FLAG_META_KEY, $value );
		}

		/**
		 * Read the hide flag from the post meta.
		 *
		 * @param int $post_id The post id.
		 *
		 * @return bool True if the featured image should be hidden.
		 */
		public static function read_hide_flag( $post_id ) {
			$value = get_post_meta( $post_id, self::HIDE_FLAG_META_KEY, true );

			return (bool) $value;
		}

		/**
		 * Check if the hide flag meta key exists for the given post.
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 */
		public static function has_hide_flag_value( $post_id ) {
			$meta = get_post_meta( $post_id );

			return array_key_exists( self::HIDE_FLAG_META_KEY, $meta );
		}
	}
}