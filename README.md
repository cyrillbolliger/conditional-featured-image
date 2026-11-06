# Conditionally display featured image on singular posts and pages

A WordPress plugin. Get it from the [WordPress Plugin Repository](https://wordpress.org/plugins/conditionally-display-featured-image-on-singular-pages/).

## Description

Easily control whether the featured image appears in the single post or page view (doesn't hide it in archive/list view).

## Key Features

- Show or hide the featured image on singular pages and posts.
- Seamlessly integrates with the WordPress post editor.
- Simple checkbox toggle—no technical knowledge needed.
- AI ready.
- Compatible with most themes.
- Supports WooCommerce product pages.
- Lightweight and optimized for performance.
- 100% free—no ads, no upsells, no premium versions!

Perfect for bloggers, content creators, and developers who want precise control over the visibility of featured images on a per-post basis.

---

## Contribute

### Getting started

- Clone the repo.
- Open your local WordPress site in [WordPress Studio](https://developer.wordpress.com/studio/).
- Link `src/` into the site as `wp-content/plugins/conditional-featured-image`.
  - Use a symlink or folder link instead of copying the files. WordPress core documentation recommends linking plugin folders to local working directories for development.
- Activate the plugin in the site.

### JavaScript tooling

The JavaScript tooling lives in `src/`.

- Install dependencies with `cd src && npm install`
- Watch file changes with `cd src && npm run start`
- Build the production files with `cd src && npm run build`

### End-to-end check

Runs on [wp-env](https://www.npmjs.com/package/@wordpress/env) (needs Docker) at http://localhost:8890, against the versions pinned in `src/.wp-env.json`.

```bash
cd src
npm run build
npm run env:start
npm run env:fixtures
npx playwright install chromium # once
npm run test:e2e
```

`.github/workflows/update-compat.yml` runs the same check against new WordPress core and Classic Editor versions every Monday and Thursday. A pass on a stable version opens a PR that moves the pin (for core, also `Tested up to`); a failure opens an issue labelled `compat`. After merging a core PR, push `README.txt` to SVN by hand (see the README one level up).
