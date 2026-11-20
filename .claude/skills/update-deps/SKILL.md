---
name: update-deps
description: Upgrade the plugin's npm dependencies in src/. Args:[major|minor].
---

Upgrade the npm dependencies in `src/package.json`. **Read `README.md`
at the repo root first** — it owns the commands (build, wp-env,
fixtures, Playwright).

Out of scope: the WordPress core and Classic Editor pins in `src/.wp-env.json`
(the `update-compat` workflow moves those) and the GitHub Actions versions.

**Arguments:** `[level]` — `major` (default: also take major bumps) | `minor` (within current semver ranges)

## Non-negotiable rules

1. **One package at a time.** Never batch. Jumping one package across several versions is fine; if verification then fails, binary-search the versions in between for the one that broke.
2. **Read the changelog for every version you cross, then judge impact against how the code actually uses the package** before applying.
3. **Verify after each bump with the end-to-end check** (README), not just a build.
4. **Leave changes in the working tree and report** (versions moved, breaking changes as a table, test attribution). Only commit or push if the user asks. When asked to commit: one commit per package, `[DEPS] upgrade <pkg> to <version>`, with only `src/package.json` and `src/package-lock.json` in it.
5. On a test failure, first **re-run the same version once**: a failure before any plugin code runs (the login in `global-setup.js`, the fixtures) that passes on the re-run is flaky. If it fails again, **prove whether it is pre-existing** (fails on the pre-upgrade version too, or on `main`) before blaming the upgrade — e.g. WooCommerce and Custom Post Type UI are installed as `latest-stable` and can change under you. Report the attribution.

## Process

1. `npm outdated` in `src/`; pick targets by level (`minor` = Wanted, `major` = Latest). Exact-pinned packages (see gotchas) always show Wanted = Current, so at `minor` take their newest version within the current major instead.
2. Establish a **green baseline**: run the e2e check (README) before changing anything. If a core beta or RC is newer than the pin, run the baseline on it too: `node tests/env/pins.js override core <version>` writes `.wp-env.override.json`, `npm run env:start` switches to it; delete that file to get back to the pin.
3. Per package: read the changelog (`@wordpress/*`: `packages/<pkg>/CHANGELOG.md` on Gutenberg's `trunk`; Playwright: `docs/src/release-notes-js.md`), grep `js/` (and `tests/` for test tooling) for the import and judge exposure, apply the bump, then rebuild, run the e2e check (also on the core RC if the baseline used one) and the package-specific steps below.
4. Stop wp-env when done (`npm run env:stop`), unless the user wants it running.

## Gotchas

- **Runtime `@wordpress/*` packages** (`components`, `compose`, `data`, `element`, `hooks`) are **not bundled**: wp-scripts externalizes them to the `wp-*` script handles of the WordPress the plugin runs on. Their npm version only affects the build and editor hints. What matters is whether the APIs used in `js/index.js` exist in every core version from `Requires at least` (`README.txt`) up to the pin — judge changelogs against that range, not the npm version.
- A bump can add a script handle to `build/index.asset.php` that older cores lack (e.g. `react-jsx-runtime`, which needs WP 6.6), or `js/index.js` can start using an API they lack. The e2e check on `Requires at least` catches both: run it after the last bump (README), as CI does on every push.
- **`@wordpress/scripts`**: check its `engines` Node version against both the current Node LTS (`/.nvmrc` says `lts/*`) and the fixed `node-version` in `/.github/workflows/*.yml` — CI can lag behind LTS. A major may change lint/format defaults or webpack config; `start`/`build` must still work unchanged.
- **`@wordpress/env`** and **`@playwright/test`** are pinned exactly (no `^`): bump with `npm install --save-exact <pkg>@<version>` and check `package.json` afterwards. After a wp-env bump, run `npx wp-env cleanup --force`, then `npm run env:start` to recreate the containers (`env:clean` only resets the databases), then reload the fixtures. After a Playwright bump, run `npx playwright install chromium`.
- A major bump that fails with **ERESOLVE** can be blocked by the _old_ version's own subtree, which npm keeps while resolving the new one. Find the package behind the conflicting range in `package-lock.json` before blaming another dependency or adding an override. If it sits under the old version: `npm uninstall <pkg>`, install the new version fresh, then confirm `npm ci` accepts the lockfile.
