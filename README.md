# aardling-theme

Aardling, the child theme of Twenty Nineteen that runs aardling.com. Twenty Nineteen does the styling; this theme holds the site's own changes and updates itself from this private repo.

## What it changes
- Drops Font Awesome, which WP Mobile Bottom Menu loads on every page, and draws the bottom menu's three icons (home, compass, user; solid and outline) as inline SVG masks.
- Loads Twenty Nineteen's stylesheet under its own handle, so the child theme adds no request.
- On first activation, copies Twenty Nineteen's Customizer settings so the site looks the same.

## Release flow (fully automated)
1. Change code, bump the version in two places: `Version:` in `style.css` and `AARDLING_THEME_VERSION` in `functions.php`. Add a `## x.y.z` section to `CHANGELOG.md`.
2. Commit and push to `main`.
3. GitHub Actions sees a version without a release, checks both version numbers match, lints, builds `aardling.zip` (folder `aardling/`) and creates release `vx.y.z`. Pushes that don't bump the version release nothing.
4. aardling.com sees the release (checks every 6 hours) and auto-updates via WordPress's update cron, or immediately through the `aardling-theme/self-update` ability or the button on Appearance > Aardling.

## One-time setup on the site
1. Install `aardling.zip` from the latest release manually (Appearance > Themes > Add New Theme > Upload Theme) and activate it.
2. Give the theme read access to this repo, one of:
   - edit the fine-grained token Aardling Schuif already uses (GitHub > Settings > Developer settings > Fine-grained tokens) and add `stijn/aardling-theme` to its repositories; nothing needs pasting, or
   - create a new fine-grained token for `stijn/aardling-theme` only, permission Contents: Read-only, and paste it on Appearance > Aardling (or `define( 'AARDLING_THEME_GITHUB_TOKEN', '...' );` in wp-config.php).

## Safety
- The abilities only report and install updates of this theme. Nothing touches content, settings, plugins or other themes.
- The token is never shown after saving and never returned by an ability.
- Vendored: plugin-update-checker 5.6 by Yahnis Elsts (MIT), in `vendor/plugin-update-checker`.
