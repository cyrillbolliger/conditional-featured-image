=== Conditionally display featured image on singular posts and pages ===
Contributors: cyrillbolliger
Tags: thumbnail, featured image, featured, image, featuredimage
Requires at least: 6.7
Requires PHP: 5.6
Tested up to: 6.9
Stable tag: 3.3.2
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily control whether the featured image appears in the single post or page view (doesn't hide it in archive/list view).

== Description ==

Easily control the visibility of the featured image on singular posts and pages–while keeping it visible in archive pages, query loops, and other list views. This plugin provides a simple checkbox option within the post editor, allowing you to enable or disable the display of the featured image on individual posts and pages.

= Key Features =
* Show or hide the featured image on singular pages and posts.
* Seamlessly integrates with the WordPress post editor.
* Simple checkbox toggle—no technical knowledge needed.
* Compatible with most themes.
* Supports WooCommerce product pages.
* Lightweight and optimized for performance.
* 100% free—no ads, no upsells, no premium versions!

Perfect for bloggers, content creators, and developers who want precise control over the visibility of featured images on a per-post basis.

= Important Notice =

If your theme uses a custom method to load the featured image (such as the Twenty Seventeen theme), this plugin may not work. To ensure compatibility, use standard WordPress functions like `get_the_post_thumbnail()`, `wp_get_attachment_image()`, or the [Post Featured Image](https://wordpress.org/support/article/post-featured-image-block/) block.

Additionally, by default, this plugin only hides the featured image when it is loaded inside the loop. If your theme loads it outside the loop check out the first FAQ entry for a solution.

== Frequently asked questions ==

= The plugin doesn’t work with my theme. What can I do? =

Some themes load featured images in custom ways, which may cause compatibility issues. The two most common reasons are:

1) The theme loads the featured image before the loop (e.g., in the header).
2) The theme manually calls the featured image using custom functions.

**Solution for case 1**

If your theme loads the featured image before the loop, you can modify the plugin's behavior by adding the following snippet to your `functions.php` file:

    function cybocfi_set_startup_hook() {
        return 'get_header';
    }

    add_filter( 'cybocfi_startup_hook', 'cybocfi_set_startup_hook' );
    add_filter( 'cybocfi_only_hide_in_the_loop', '__return_false' );

*Note:* This may hide the featured image from other plugins that rely on it, such as SEO plugins or the 'latest posts' plugin.

**Solution for case 2**

If your theme uses custom functions to display featured images, try the following options:

* Ask the theme developer to use standard WordPress functions like `wp_get_attachment_image()`, `get_the_post_thumbnail()` or `the_post_thumbnail()`.
* Create a [child theme]((https://developer.wordpress.org/themes/advanced-topics/child-themes/)) and load the featured image with one of the functions above.

= Is this plugin GDPR compliant? =
Yes! This plugin does not collect, process, or store any personal information, making it fully GDPR-compliant.

= Can I hide featured images by default? =

Yes. Add the following code to your `functions.php` file to hide featured images by default:

    add_filter('cybocfi_hide_by_default', '__return_true');

This will automatically check the "Hide Featured Image" option for all **new** posts and pages. Existing content remains unchanged.

For different default behaviors based on the post type, use:

    function cybocfi_set_default_hiding_state( $default, $post_type ) {
        if ( 'post' === $post_type ) {
            $default = true; // Hide featured images on posts by default
        } else if ( 'page' === $post_type ) {
            $default = false; // Show featured images on pages by default
        }
        return $default;
    }
    add_filter( 'cybocfi_hide_by_default', 'cybocfi_set_default_hiding_state', 10, 2 );

= Can I limit this plugin to posts (and exclude other post types)? =

Yes. By default, the plugin works on all post types that support featured images. To restrict it to posts only, add the following snippet to your `functions.php`:

    function cybocfi_limit_to_posts( $enabled, $post_type ) {
        if ( 'post' === $post_type ) {
            return $enabled;
        }

        return false;
    }
    add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts', 10, 2 );

If you want it to work for both posts and pages but disable it for other post types:

    function cybocfi_limit_to_posts_and_pages( $enabled, $post_type ) {
        $allowed_post_types = array( 'post', 'page' ); // add any post type you want to use the plugin with
        return in_array( $post_type, $allowed_post_types );
    }
    add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts_and_pages', 10, 2 );

= WooCommerce: How does the plugin handle product images? =

If the featured image is hidden for a WooCommerce product, it will still appear as a thumbnail in the cart, checkout, and product lists. However, it will not be displayed in the single product view. If a product gallery is available, all gallery images will be shown as usual, except for the hidden featured image.

= WooCommerce: Can I remove empty space left by the hidden image? =

Yes. The plugin applies CSS adjustments automatically for standard themes. If needed, customize it with this snippet:

    function cybocfi_woocommerce_styles( $css ) {
        return '.wp-block-woocommerce-product-image-gallery {display: none;}';
    }
    add_filter( 'cybocfi_woocommerce_style_overrides', 'cybocfi_woocommerce_styles' );

These styles apply only when the featured image is hidden in WooCommerce product pages.

= Can I translate this plugin into my language? =

Absolutely! You can [contribute a translation](https://translate.wordpress.org/projects/wp-plugins/conditionally-display-featured-image-on-singular-pages/) here. Keep in mind that translations need community approval before they go live.

= How can I change the text of the checkbox? =

You can customize the checkbox label using this filter in your `functions.php` file:

    function cybocfi_set_featured_image_label( $label ) {
        return 'Hide featured image in post'; // change this text
    }
    add_filter( 'cibocfi_checkbox_label', 'cybocfi_set_featured_image_label' );

= I can’t save posts in WordPress 5.7.0 =

A WordPress core bug ([#52787](https://core.trac.wordpress.org/ticket/52787)) may cause this issue when another plugin uses post meta values in a specific way. If you see the error "Updating failed. Could not delete meta value from database.", try:

* Downgrading to WordPress 5.6.2.
* Upgrading to WordPress 5.7.1 or later.

= I'm getting a deprecation notice. What should I do? =

The `cybocfi_post_type` filter has been replaced with `cybocfi_enabled_for_post_type`. To update your code:

1) Change the filter hook from `cybocfi_post_type` to `cybocfi_enabled_for_post_type`.
2) Swap the filter functions arguments. `$enabled` is now the first argument `$post_type` the second.

In case you've only used one argument (`$post_type`), you must not only adapt the function signature, but also add the priority and number of arguments to your `add_filter()` function call.

Here's an example:

    // BEFORE UPDATE: Using the deprecated filter
    function cybocfi_limit_to_posts( $post_type, $enabled ) {
        if ( 'post' === $post_type ) {
            return $enabled;
        }

        return false;
    }
    add_filter( 'cybocfi_post_type', 'cybocfi_limit_to_posts', 10, 2 );

    // AFTER UPDATE: Using the new filter
    function cybocfi_limit_to_posts( $enabled, $post_type ) {
        if ( 'post' === $post_type ) {
            return $enabled;
        }

        return false;
    }
    add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts', 10, 2 );

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/conditional-featured-image` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the `Plugins` screen in WordPress

== Screenshots ==
1. Backend (Block Editor)
2. Frontend (Front Page / Post List / Query Loop Block / Archive View)
3. Frontend (Post / Page / Singular View)
4. Backend (Classic Editor)
5. Backend (WooCommerce Product)

== Changelog ==

= 3.3.2 =
* Compatibility up to WordPress 6.9
* Updated dependencies

= 3.3.1 =
* Fixed violation of the WordPress coding standards

= 3.3.0 =
* Added support for WooCommerce
* Fixed `bottom margin` deprecation notice
* Updated dependencies

= 3.2.0 =
* Requires at least WordPress 6.6
* Compatibility up to WordPress 6.7
* Fixed `withState` deprecation notice
* Updated dependencies

= 3.1.1 =
* Compatibility with Gutenberg 16.6.0
* Updated dependencies

= 3.0.1 =
* Fixes fatal error for users that customized the startup hook so the query could not be set.

= 3.0.0 =
* Improved compatibility with the block editor
* Updated dependencies

We've tested the release thoroughly - however depending on the theme and plugin you use, this release might be breaking in some exceptional cases.

= 2.14.0 =
* The featured image is now displayed inside the query block
* Small performance and readability improvement
* Updated dependencies

= 2.13.0 =
* Improved compatibility for block themes
* The `cybocfi_enabled_for_post_type` filter now also applies directly to the output in the frontend
* Refactored plugin architecture from single file to single class per file
* Updated dependencies

= 2.12.0 =
* Don't hide featured image from oEmbed requests.
* Updated dependencies

= 2.11.0 =
* Show deprecation notice if `cybocfi_post_type` filter is used. Props to @swissspidy for bringing `apply_filters_deprecated()` to my attention.

= 2.10.0 =
* Deprecated `cybocfi_post_type` filter in favor of the new `cybocfi_enabled_for_post_type` filter. Props to @swissspidy for highlighting the issues with `cybocfi_post_type`.
* Updated dependencies

= 2.9.0 =
* Added filter to bypass the in_the_loop() test so the plugin can be made compatible with themes that load the featured image outside the main loop.

= 2.8.2 =
* Fixed bug that was hiding the featured image in the latest posts widget. Props to @molcsa for pointing this out.
* Updated dependencies

= 2.8.1 =
* Extended FAQ
* Updated dependencies
* Tested up to WordPress 5.8.2

= 2.8.0 =
* Added hook for early initialization
* Extended FAQ
* Small refactorings
* Updated dependencies

= 2.7.1 =
* Tested up to WordPress 5.7
* Updated dependencies

= 2.7.0 =
* Added support for the Custom Post Type UI plugin
* Updated dependencies

= 2.6.0 =
* Added support for the twentynineteen theme

= 2.5.1 =
* Fix: Do not remove the featured image in queries executed after the main query
* Updated dependencies

= 2.5.0 =
* Respect the `cybocfi_hide_by_default` filter for programmatically added posts
* Small refactorings
* Tested up to WordPress 5.6.0
* Updated dependencies

= 2.4.0 =
* Added filter to hide featured images by default
* Tested up to WordPress 5.5.1
* Extended FAQ
* Updated dependencies

= 2.3.1 =
* Tested up to WordPress 5.5 (RC1)
* Extended FAQ
* Updated dependencies

= 2.3.0 =
* Allow to enable/disable the plugin by post type

= 2.2.0 =
* Allow filtering the featured image checkbox label
* Update readme
* Update dependencies

= 2.1.2 =
* Exclude none essential data from SVN

= 2.1.1 =
* Update dependencies

= 2.1.0 =
* Add support for Yoast SEO (don't filter image for the social header data)

= 2.0.0 =
* Add support for the block editor (Gutenberg)
* Tested up to WordPress 5.2.2

= 1.4.0 =
* Makes sure, we do only modify the main post
* Tested up to WordPress 5.0.0

= 1.3.0 =
* Make it more robust so it will also work with [Elementor](https://elementor.com/)
* Tested up to WordPress 4.9.6

= 1.2.2 =
* Tested up to WordPress 4.7.3
* Tested up to WordPress 4.8.0
* Tested up to WordPress 4.9.0

= 1.2.1 =
* Tested up to WordPress 4.7.2

= 1.2.0 =
* Get ready for language packs (set text domain equal to the name of the plugins folder, remove load_plugin_textdomain)

= 1.1.3 =
* Tested up to WordPress 4.7.0
* Removed language folder. Languages are now loaded from wordpress.org

= 1.1.2 =
* Improve plugin title
* Improve checkbox string
* Improve documentation
* Updated stable tag

= 1.1.1 =
* Updated stable tag

= 1.1 =
* Extended functionality to pages

= 1.0 =
* Initial public release
