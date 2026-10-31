# aardling-theme

Aardling, the child theme of Twenty Nineteen that runs aardling.com. Twenty Nineteen does the styling; this theme holds the site's own changes and updates itself from this repo.

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
2. Nothing else: the repo is public. A GitHub token only lifts GitHub's rate limit (60 anonymous requests an hour, shared by every site on the server's IP). The theme borrows Aardling Schuif's token when it has none of its own; any fine-grained token can read public repos. To set one anyway: Appearance > Aardling, or `define( 'AARDLING_THEME_GITHUB_TOKEN', '...' );` in wp-config.php.

## Safety
- The abilities only report and install updates of this theme. Nothing touches content, settings, plugins or other themes.
- The token is never shown after saving and never returned by an ability.

## License and credits
- Aardling is free software under the GNU General Public License v2 or later; see [LICENSE](LICENSE). That's the same licence as WordPress and Twenty Nineteen.
- Parent theme: [Twenty Nineteen](https://wordpress.org/themes/twentynineteen/) by the WordPress team, GPL v2 or later. This theme only loads it; no Twenty Nineteen files are copied here.
- Vendored: [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) 5.6 by Jānis Elsts, MIT licence (`vendor/plugin-update-checker/license.txt`), compatible with the GPL.
- The bottom menu icons are original SVG drawings made for this theme. No Font Awesome files or artwork are included; the CSS only targets the class names WP Mobile Bottom Menu prints.
- WordPress is a trademark of the WordPress Foundation. This theme is not affiliated with or endorsed by it, and doesn't use the name in its own.
