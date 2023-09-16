=== Conditionally display featured image on singular posts and pages ===
Contributors: cyrillbolliger
Tags: thumbnail, featuredimage, featured, image, hide, condition, display, post, single, singular, page
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 6.3.1
Stable tag: 3.1.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Choose if the featured image should be displayed in the single post/page view or not. This plugin doesn't affect the archives view.

== Description ==
= Important notice =
If your theme does a customized call to load the featured image (like the Twenty Seventeen theme), this plugin might not work! Use `get_the_post_thumbnail()`, `wp_get_attachment_image()` or the [Post Featured Image](https://wordpress.org/support/article/post-featured-image-block/) block to be sure it will work.
By default, the plugin also only hides the featured image, if it is loaded within the loop. See the FAQ on how to use the plugin if you theme loads the featured image outside the loop.

= Description =
This plugin lets you choose for each post or page, if the featured image should be shown in the single view. This can get handy, if you use the featured image to show a thumbnail on the archives or front page but you don't want the featured image to be shown on every posts view itself.

The plugin adds a simple checkbox to the featured image panel (or meta box if you are using the classic editor), that lets you choose, if the featured image will be shown in the singular view or not.

== Frequently asked questions ==
= The plugin doesn't work with my theme. What can I do? =
Typically there are two possibilities why the plugin is not compatible with your theme:

1) The theme loads the featured image before the loop (e.g. in the header).
2) The theme makes a custom call to load the featured image.

**In case 1** you can initialize the plugin early and disable the in_the_loop check. To do so, add the following snippet to your functions.php:
`
function cybocfi_set_startup_hook() {
    return 'get_header';
}

add_filter( 'cybocfi_startup_hook', 'cybocfi_set_startup_hook' );
add_filter( 'cybocfi_only_hide_in_the_loop', '__return_false' );
`
Be aware, that this might have some side effects: e.g. it might also hide the featured image from plugins that would normally see it, like SEO plugins or the 'latest posts' plugin.

**In case 2** either

*   kindly ask the theme developer to use one of the dedicated WordPress functions (`wp_get_attachment_image()`, `get_the_post_thumbnail()`, `the_post_thumbnail()`) to load the featured image in the singular views.
*   or create a [child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/) that replaces the call, that loads the featured image, with one of the methods listed above.

= Is this plugin GDPR compliant? =
This plugin does not process or store any personal information. Hence, it is fully GDPR compliant without any further ado.

= Can I hide featured images by default? =
Yes. Just add the following line to your functions.php:
`
add_filter('cybocfi_hide_by_default', '__return_true');
`
All *new* posts and pages will now hide the featured image by default (checkbox is checked by default). Existing posts and pages won't be changed.

You may also set different default values depending on the post type:
`
function cybocfi_set_default_hiding_state( $default, $post_type ) {
    if ( 'post' === $post_type ) {
        $default = true; // set the default state for posts
    } else if ( 'page' === $post_type ) {
        $default = false; // set the default state for pages
    }

    return $default;
}
add_filter( 'cybocfi_hide_by_default', 'cybocfi_set_default_hiding_state', 10, 2 );
`

= Can I limit this plugin to posts (and exclude other post types)? =
Yes. By default, the plugin is available on any post type, that has a featured image. But there is a filter, that lets you control, for which post types the plugin should be available. The following example disables it for anything except for posts:
`
function cybocfi_limit_to_posts( $enabled, $post_type ) {
    if ( 'post' === $post_type ) {
        return $enabled;
    }

    return false;
}
add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts', 10, 2 );
`
The filter provides you the current post type and you can decide if you want to use the plugin for this post type by returning `true` to enable and `false` to disable it. Add the following snippet to your `functions.php` to enable the plugin for posts and pages, but disable it for any other post type:
`
function cybocfi_limit_to_posts_and_pages( $enabled, $post_type ) {
    $allowed_post_types = array( 'post', 'page' ); // add any post type you want to use the plugin with
    return in_array( $post_type, $allowed_post_types );
}
add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts_and_pages', 10, 2 );
`

= Is it possible to get the plugin in my language? =
Absolutely. You're invited to [contribute a translation](https://translate.wordpress.org/projects/wp-plugins/conditionally-display-featured-image-on-singular-pages/) in your language. Please keep in mind, that the translation needs to be reviewed by the community, so it will take a little while until it gets accepted.

= How can I change the text of the checkbox? =
There is a filter hook for this. Add the following snippet to your functions.php:
`
function cybocfi_set_featured_image_label( $label ) {
    return 'Hide featured image in post'; // change this string
}
add_filter( 'cibocfi_checkbox_label', 'cybocfi_set_featured_image_label' );
`

= I can't save posts in WordPress 5.7.0 =
A bug in WordPress core [#52787](https://core.trac.wordpress.org/ticket/52787) may render this plugin unusable if a second plugin uses post meta values in a certain way. People who are affected by this problem see the following error message "Updating failed. Could not delete meta value from database.". As the issue is related to WordPress core the workaround is to downgrade to WordPress 5.6.2 or to upgrade to WordPress 5.7.1. To our current knowledge, only very few users are affected by this defect. The Conditionally display featured image on singular posts and pages plugin itself works as expected for WordPress 5.7.0 and the issue may only appear if a second plugin triggers the bug in WordPress core.

= I'm getting a deprecation notice, what must I do? =
The `cybocfi_post_type` filter was deprecated in favor of `cybocfi_enabled_for_post_type`, as the filter arguments were used in an unusual way. Transitioning from the former to the latter is easy. Here an example:
`
// Using the deprecated filter - REMOVE THIS CALL
function cybocfi_limit_to_posts( $post_type, $enabled ) {
    if ( 'post' === $post_type ) {
        return $enabled;
    }

    return false;
}
add_filter( 'cybocfi_post_type', 'cybocfi_limit_to_posts', 10, 2 );

// Using the new filter - THIS IS HOW IT SHOULD BE DONE NOW
function cybocfi_limit_to_posts( $enabled, $post_type ) {
    if ( 'post' === $post_type ) {
        return $enabled;
    }

    return false;
}
add_filter( 'cybocfi_enabled_for_post_type', 'cybocfi_limit_to_posts', 10, 2 );
`
All you've got to do is:

1) Change the filter hook from `cybocfi_post_type` to `cybocfi_enabled_for_post_type`.
2) Swap the filter functions arguments. `$enabled` is now the first argument `$post_type` the second.

In case you've only used one argument (`$post_type`), you must not only adapt the function signature, but also add the priority and number of arguments to your `add_filter()` function call. Just as it is shown in the example above.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/conditional-featured-image` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the `Plugins` screen in WordPress

== Screenshots ==
1. Backend
2. Frontend

== Changelog ==

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
