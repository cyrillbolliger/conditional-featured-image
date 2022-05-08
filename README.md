# Conditionally display featured image on singular posts and pages #
## Plugin for WordPress

Get the plugin from the [WordPress Plugin Repository](https://wordpress.org/plugins/conditionally-display-featured-image-on-singular-pages/).

## Description ##
### Important notice ###
If your theme does a customized call to load the featured image (like the Twenty Seventeen theme), this plugin might not work! Use `get_the_post_thumbnail()`, `wp_get_attachment_image()` or the [Post Featured Image](https://wordpress.org/support/article/post-featured-image-block/) block to be sure it will work.

### Description ###
This plugin lets you choose for each post or page, if the featured image should be shown in the single view. This can get handy, if you use the featured image to show a thumbnail on the archives or front page but you don\'t want the featured image to be shown on every posts view itself.

The plugin adds a simple checkbox to the featured image panel (or meta box if you are using the classic editor), that lets you choose, if the featured image will be shown in the singular view or not.

---
## Contribute

### Getting started
* Clone the repo
* Run `docker-compose up -d`
* Open [localhost:8080](http://localhost/8080)
* Activate the plugin

### JS-Stuff (Gutenberg)
* Install dependencies with `npm install`
* Watch the file changes with `npm run start`
* Build the production files with `npm run build` 