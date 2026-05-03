<?php

if ( ! class_exists( 'Cybocfi_Abilities' ) ) {

	class Cybocfi_Abilities {
		/**
		 * @var Cybocfi_Abilities
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
		 * @return Cybocfi_Abilities
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new Cybocfi_Abilities();
			}

			return self::$instance;
		}

		/**
		 * Register the plugin's ability category.
		 */
		public function register_category() {
			wp_register_ability_category(
				'cybocfi-featured-image',
				array(
					'label'       => __( 'Conditional Featured Image', 'conditionally-display-featured-image-on-singular-pages' ),
					'description' => __( 'Read and update featured image visibility behavior on singular views.', 'conditionally-display-featured-image-on-singular-pages' ),
				)
			);
		}

		/**
		 * Register the plugin abilities.
		 */
		public function register_abilities() {
			wp_register_ability(
				'cybocfi/get-featured-image-visibility',
				array(
					'label'               => __( 'Get Featured Image Visibility', 'conditionally-display-featured-image-on-singular-pages' ),
					'description'         => __( 'Returns whether the featured image is hidden on singular view for a given post.', 'conditionally-display-featured-image-on-singular-pages' ),
					'category'            => 'cybocfi-featured-image',
					'input_schema'        => $this->get_post_id_input_schema(),
					'output_schema'       => $this->get_visibility_output_schema(),
					'execute_callback'    => array( $this, 'execute_get_featured_image_visibility' ),
					'permission_callback' => array( $this, 'can_access_post_ability' ),
					'meta'                => array(
						'annotations' => array(
							'readonly'   => true,
						),
						'show_in_rest' => true,
					),
				)
			);

			wp_register_ability(
				'cybocfi/set-featured-image-visibility',
				array(
					'label'               => __( 'Set Featured Image Visibility', 'conditionally-display-featured-image-on-singular-pages' ),
					'description'         => __( 'Updates whether the featured image is hidden on singular view for a given post.', 'conditionally-display-featured-image-on-singular-pages' ),
					'category'            => 'cybocfi-featured-image',
					'input_schema'        => $this->get_set_visibility_input_schema(),
					'output_schema'       => $this->get_visibility_output_schema(),
					'execute_callback'    => array( $this, 'execute_set_featured_image_visibility' ),
					'permission_callback' => array( $this, 'can_access_post_ability' ),
					'meta'                => array(
						'annotations' => array(
							'idempotent' => true,
						),
						'show_in_rest' => true,
					),
				)
			);

			wp_register_ability(
				'cybocfi/bulk-set-featured-image-visibility',
				array(
					'label'               => __( 'Bulk Set Featured Image Visibility', 'conditionally-display-featured-image-on-singular-pages' ),
					'description'         => __( 'Updates whether the featured image is hidden on singular view for multiple posts.', 'conditionally-display-featured-image-on-singular-pages' ),
					'category'            => 'cybocfi-featured-image',
					'input_schema'        => $this->get_bulk_set_visibility_input_schema(),
					'output_schema'       => $this->get_bulk_set_visibility_output_schema(),
					'execute_callback'    => array( $this, 'execute_bulk_set_featured_image_visibility' ),
					'permission_callback' => array( $this, 'can_access_bulk_post_ability' ),
					'meta'                => array(
						'annotations' => array(
							'idempotent' => true,
						),
						'show_in_rest' => true,
					),
				)
			);

			wp_register_ability(
				'cybocfi/get-plugin-status',
				array(
					'label'               => __( 'Get Plugin Status', 'conditionally-display-featured-image-on-singular-pages' ),
					'description'         => __( 'Returns plugin-level status data for integrations and discovery.', 'conditionally-display-featured-image-on-singular-pages' ),
					'category'            => 'cybocfi-featured-image',
					'output_schema'       => $this->get_plugin_status_output_schema(),
					'execute_callback'    => array( $this, 'execute_get_plugin_status' ),
					'permission_callback' => array( $this, 'can_access_status_ability' ),
					'meta'                => array(
						'annotations' => array(
							'readonly' => true,
						),
						'show_in_rest' => true,
					),
				)
			);
		}

		/**
		 * Return shared input schema for operations requiring a post id.
		 *
		 * @return array
		 */
		private function get_post_id_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The post ID to read featured image visibility for.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Input schema for visibility updates.
		 *
		 * @return array
		 */
		private function get_set_visibility_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id'             => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The post ID to update featured image visibility for.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'hide_featured_image' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to hide the featured image on singular views.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array( 'post_id', 'hide_featured_image' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Input schema for bulk visibility updates.
		 *
		 * @return array
		 */
		private function get_bulk_set_visibility_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'post_ids'            => array(
						'type'        => 'array',
						'minItems'    => 1,
						'items'       => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'description' => __( 'The post IDs to update featured image visibility for.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'hide_featured_image' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to hide the featured image on singular views.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array( 'post_ids', 'hide_featured_image' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Output schema for visibility operations.
		 *
		 * @return array
		 */
		private function get_visibility_output_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id'               => array(
						'type'        => 'integer',
						'description' => __( 'The post ID.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'post_type'             => array(
						'type'        => 'string',
						'description' => __( 'The post type slug.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'enabled_for_post_type' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the plugin is enabled for the post type.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'hide_featured_image'   => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the featured image is hidden on singular views.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array(
					'post_id',
					'post_type',
					'enabled_for_post_type',
					'hide_featured_image'
				),
				'additionalProperties' => false,
			);
		}

		/**
		 * Output schema for bulk visibility updates.
		 *
		 * @return array
		 */
		private function get_bulk_set_visibility_output_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'updated' => array(
						'type'        => 'array',
						'items'       => $this->get_visibility_output_schema(),
						'description' => __( 'List of posts updated with their resulting visibility state.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array( 'updated' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Output schema for plugin status.
		 *
		 * @return array
		 */
		private function get_plugin_status_output_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'plugin_name'               => array(
						'type'        => 'string',
						'description' => __( 'The plugin name. Stable identifier.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'plugin_version'            => array(
						'type'        => 'string',
						'description' => __( 'The plugin version.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
					'enabled_public_post_types' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
						),
						'description' => __( 'Public post types currently enabled for this plugin.', 'conditionally-display-featured-image-on-singular-pages' ),
					),
				),
				'required'             => array( 'plugin_name', 'plugin_version', 'enabled_public_post_types' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Shared permission callback for ability operations.
		 *
		 * @param array $input
		 *
		 * @return bool
		 */
		public function can_access_post_ability( $input = array() ) {
			$post = $this->resolve_post_from_input( $input );

			if ( is_wp_error( $post ) ) {
				return false;
			}

			return current_user_can( 'edit_post', $post->ID );
		}

		/**
		 * Permission callback for bulk post operations.
		 *
		 * @param array $input
		 *
		 * @return bool
		 */
		public function can_access_bulk_post_ability( $input = array() ) {
			$posts = $this->resolve_posts_from_input( $input );

			if ( is_wp_error( $posts ) ) {
				return false;
			}

			foreach ( $posts as $post ) {
				if ( ! current_user_can( 'edit_post', $post->ID ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Permission callback for plugin status ability.
		 *
		 * @return bool
		 */
		public function can_access_status_ability() {
			return current_user_can( 'edit_posts' );
		}

		/**
		 * Execute ability: read visibility state.
		 *
		 * @param array $input
		 *
		 * @return array|WP_Error
		 */
		public function execute_get_featured_image_visibility( $input = array() ) {
			$post = $this->resolve_post_from_input( $input );

			if ( is_wp_error( $post ) ) {
				return $post;
			}

			return $this->build_visibility_response( $post->ID, $post->post_type );
		}

		/**
		 * Execute ability: update visibility state.
		 *
		 * @param array $input
		 *
		 * @return array|WP_Error
		 */
		public function execute_set_featured_image_visibility( $input = array() ) {
			$post = $this->resolve_post_from_input( $input );

			if ( is_wp_error( $post ) ) {
				return $post;
			}

			$hide_featured_image = (bool) $input['hide_featured_image'];
			Cybocfi_Util::save_hide_flag( $post->ID, $hide_featured_image );

			return $this->build_visibility_response( $post->ID, $post->post_type );
		}

		/**
		 * Execute ability: bulk update visibility state.
		 *
		 * @param array $input
		 *
		 * @return array|WP_Error
		 */
		public function execute_bulk_set_featured_image_visibility( $input = array() ) {
			$posts = $this->resolve_posts_from_input( $input );

			if ( is_wp_error( $posts ) ) {
				return $posts;
			}

			$hide_featured_image = (bool) $input['hide_featured_image'];
			$updated             = array();

			foreach ( $posts as $post ) {
				Cybocfi_Util::save_hide_flag( $post->ID, $hide_featured_image );

				$updated[] = $this->build_visibility_response( $post->ID, $post->post_type );
			}

			return array(
				'updated' => $updated,
			);
		}

		/**
		 * Execute ability: get plugin status data.
		 *
		 * @return array
		 */
		public function execute_get_plugin_status() {
			$public_post_types         = get_post_types( array( 'public' => true ), 'names' );
			$enabled_public_post_types = array();

			foreach ( $public_post_types as $post_type ) {
				if ( Cybocfi_Util::is_enabled_for_post_type( $post_type ) ) {
					$enabled_public_post_types[] = $post_type;
				}
			}

			return array(
				'plugin_name'               => CYBOCFI_PLUGIN_NAME,
				'plugin_version'            => CYBOCFI_VERSION,
				'enabled_public_post_types' => array_values( $enabled_public_post_types ),
			);
		}

		/**
		 * Validate and resolve a post from ability input.
		 *
		 * @param array $input
		 *
		 * @return WP_Post|WP_Error
		 */
		private function resolve_post_from_input( $input ) {
			$post = get_post( (int) $input['post_id'] );

			if ( ! $post ) {
				return new WP_Error(
					'cybocfi_post_not_found',
					__( 'No post found for the given post_id.', 'conditionally-display-featured-image-on-singular-pages' )
				);
			}

			return $post;
		}

		/**
		 * Validate and resolve multiple posts from ability input.
		 *
		 * @param array $input
		 *
		 * @return array|WP_Error
		 */
		private function resolve_posts_from_input( $input ) {
			$posts = array();

			foreach ( $input['post_ids'] as $raw_post_id ) {
				$post = get_post( (int) $raw_post_id );

				if ( ! $post ) {
					return new WP_Error(
						'cybocfi_post_not_found',
						sprintf(
						/* translators: %d: Post ID. */
							__( 'At least one post in post_ids does not exist. Non-existent post ID: %d.', 'conditionally-display-featured-image-on-singular-pages' ),
							(int) $raw_post_id
						)
					);
				}

				$posts[] = $post;
			}

			return $posts;
		}

		/**
		 * Build a normalized visibility payload.
		 *
		 * @param int $post_id
		 * @param string $post_type
		 *
		 * @return array
		 */
		private function build_visibility_response( $post_id, $post_type ) {
			return array(
				'post_id'               => (int) $post_id,
				'post_type'             => (string) $post_type,
				'enabled_for_post_type' => Cybocfi_Util::is_enabled_for_post_type( $post_type ),
				'hide_featured_image'   => Cybocfi_Util::read_hide_flag( $post_id ),
			);
		}
	}
}
