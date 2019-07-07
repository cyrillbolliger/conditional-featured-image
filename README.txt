=== Conditionally display featured image on singular posts and pages ===
Contributors: cyrillbolliger
Tags: thumbnail, featuredimage, featured, image, hide, condition, display, post, single, singular, page
Requires at least: 4.6
Tested up to: 5.2.2
Stable tag: 2.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Choose if the featured image should be displayed in the single post/page view or not. This plugin doesn't affect the archives view.

== Description ==
= Important notice =
If your theme does a customized call to load the featured image (like the Twenty Seventeen theme), this plugin might not work! Use `get_the_post_thumbnail()` or `wp_get_attachment_image()` to be sure it will work.

= Description =
This plugin lets you choose for each post or page, if the featured image should be shown in the single view. This can get handy, if you use the featured image to show a thumbnail on the archives or front page but you don\'t want the featured image to be shown on every posts view itself.

The plugin adds a simple checkbox to the featured image panel (or meta box if you are using the classic editor), that lets you choose, if the featured image will be shown in the singular view or not.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/conditional-featured-image` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the `Plugins` screen in WordPress

== Screenshots ==
1. Backend
2. Frontend

== Changelog ==
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
* Removed lanuage folder. Languages are now loaded from wordpress.org

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
