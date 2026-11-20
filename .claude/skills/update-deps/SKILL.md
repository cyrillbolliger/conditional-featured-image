---
name: update-deps
description: Upgrade the plugin's npm dependencies in src/. Args:[major|minor].
---

# update-deps

Upgrade the npm dependencies in `src/package.json`. **Read the root
[README](../../../README.md) first** — it owns the commands (build, wp-env,
fixtures, Playwright). This skill adds only the process, verification, and
non-obvious gotchas; it names _what_ to do and defers _how_ to the README, so
it survives tooling changes.

Out of scope: the WordPress core and Classic Editor pins in `src/.wp-env.json`
(the `update-compat` workflow moves those) and the GitHub Actions versions.

**Arguments:** `[level]`

- `level` — `major` (default: also take major bumps) | `minor` (within current semver ranges)

## Non-negotiable rules

1. **One package/version at a time.** Never batch. Jumping a package across several versions in one bump is fine (rule 2 still covers every version crossed); if verification then fails, find the version where it broke with a binary search over the versions in between.
2. **Read the changelog for every version you cross, then judge impact against how the code actually uses the package** before applying. Surface breaking changes to the user (a table works well).
3. **Verify after each bump with the end-to-end check** (README), not just a build.
4. **Leave changes in the working tree and report** (versions moved, breaking changes found, test attribution). Only commit or push if the user asks.
5. On a test failure, **prove whether it is pre-existing** (fails on the pre-upgrade version too, or on `main`) before blaming the upgrade. Report the attribution.
6. Track progress with a task list (one task per package/version).

## Process

1. `npm outdated` in `src/`; pick targets by level (`minor` = Wanted, `major` = Latest). Exact-pinned packages (see gotchas) always show Wanted = Current, so at `minor` take their newest version within the current major instead.
2. Establish a **green baseline**: build, start wp-env, load fixtures, run the e2e check (README) before changing anything. Then copy `build/index.asset.php` to the scratchpad — every build overwrites it and `build/` is gitignored, so this copy is the only baseline.
3. Per package: read the changelog, grep `js/` (and `tests/` for test tooling) for the import and judge exposure, apply the bump, then rebuild, run the e2e check (rule 3) and the asset-file diff and package-specific steps from the gotchas below.
4. Stop wp-env when done (`npm run env:stop`), unless the user wants it running.

## Gotchas / judgment (raise the relevant ones)

- **Runtime `@wordpress/*` packages** (`components`, `compose`, `data`, `element`, `hooks`) are **not bundled**: wp-scripts externalizes them to the `wp-*` script handles of the WordPress the plugin runs on. Their npm version only affects the build and editor hints. So a major bump changes nothing at runtime — what matters is whether the APIs used in `js/index.js` still exist in every core version from `Requires at least` (`README.txt`) up to the pin. Judge changelogs against that range, not against the npm version.
- After every bump, **diff `build/index.asset.php`** against the baseline copy from Process step 2. A new dependency handle (e.g. `react-jsx-runtime`, which needs WP 6.6) can raise the real minimum WordPress version — if one appears, check it against `Requires at least` and tell the user. A changed `version` hash alone is expected.
- `build/` is gitignored, so a broken build will not show up in `git diff`; always rebuild before the e2e run, or you test the stale bundle.
- **`@wordpress/scripts`**: check its required Node version (`engines`) against both the current Node LTS (the repo-root `/.nvmrc` says `lts/*`) and the fixed `node-version` in `/.github/workflows/*.yml` — CI can lag behind LTS. A major may also change lint/format defaults or webpack config — the `start`/`build` scripts must still work unchanged.
- **`@wordpress/env`** and **`@playwright/test`** are pinned exactly (no `^`); bump them with `npm install --save-exact <pkg>@<version>` (a plain install writes `^`) and check `package.json` afterwards. After a wp-env bump, run `npx wp-env cleanup --force`, then `npm run env:start`, so the containers are recreated with the new version (`env:clean` only resets the databases, stop/start reuses the old containers); then reload the fixtures. After a Playwright bump, run `npx playwright install chromium` so the browser matches.
- A failing e2e test can come from a changed **WordPress plugin** that wp-env downloads as `latest-stable` (WooCommerce, Custom Post Type UI), not from the bump. Rule 5 catches this: re-run on the old package version.
