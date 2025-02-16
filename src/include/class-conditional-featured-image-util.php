<?php

if ( ! class_exists( 'Cybocfi_Util' ) ) {

	class Cybocfi_Util {
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
	}
}