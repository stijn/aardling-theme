# aardling-theme

Aardling, the child theme of Twenty Nineteen that runs aardling.com. Twenty Nineteen does the styling; this theme holds the site's own changes and updates itself from this repo.

## What it changes
- Drops Font Awesome, which WP Mobile Bottom Menu loads on every page, and draws the bottom menu's three icons (home, compass, user; solid and outline) as inline SVG masks.
- Loads Twenty Nineteen's stylesheet under its own handle, so the child theme adds no request.
- On first activation, copies Twenty Nineteen's Customizer settings so the site looks the same.
- Twenty Nineteen options in Appearance > Customize > Aardling (`inc/customizer.php`): everything Options for Twenty Nineteen offers, free and premium, with the same setting names so saved values carry over. Off while that plugin is active.
- Stops Rank Math from emptying the whole WP Super Cache whenever its sitemap changes.
- Clears changed pages from Cloudflare's cache (see below), so a Cloudflare Cache Rule can keep the pages at the edge without APO.
- Reading boxes under each post (`inc/reads.php`): Suggested, Trending and Recently, see below.
- Loads GA4 through Google tag gateway (`inc/analytics.php`): the Google tag comes from `aardling.com/ptp3/`, which Cloudflare relays to Google. Prints the tag itself when Site Kit's Analytics code is off, or points Site Kit's script at the path when it's on. Not for logged-in users, and no DNS prefetch for googletagmanager.com. Override with `AARDLING_THEME_GA4_ID` and `AARDLING_THEME_GA4_PATH` in wp-config.php.

## Cloudflare cache purge
- Needs the zone ID (Cloudflare > aardling.com > Overview, API box) and an API token with only Zone > Cache Purge > Purge for aardling.com. Enter both on Appearance > Aardling, or `define( 'AARDLING_THEME_CLOUDFLARE_ZONE', '...' );` and `define( 'AARDLING_THEME_CLOUDFLARE_TOKEN', '...' );` in wp-config.php. Without both, nothing is sent.
- A post change clears the post's path, the homepage and its feed, `/page/`, and the post's term, author and year archives (prefixes, so their `/page/N/` and `/feed/` go too). Site-wide changes purge everything.
- Sent once at the end of the request; more than 300 items becomes a purge of everything. Free plan limits: 5 prefix or everything calls a minute (bursts of 25), 800 URLs a second.
- "Test: clear the homepage" checks the zone ID and token; the page shows the last purge and any Cloudflare error.

## Reading boxes
- Suggested is rendered into the page: posts sharing the most tags with the current one, then posts from its categories.
- Trending and Recently are empty boxes in the page, filled by an inline script from `/aardling-reads.json` (served by the theme, cached by Cloudflare's page rule, cleared by the theme when a list changes). Without JavaScript they stay hidden.
- Recently is rebuilt whenever a post is published, updated or unpublished.
- Trending is rebuilt hourly from Cloudflare Web Analytics (`rumPageloadEventsAdaptiveGroups`, last 7 days, bots excluded), matched to published posts. Needs the account ID, the site tag and an API token with only Account > Account Analytics > Read: on Appearance > Aardling, or `AARDLING_THEME_CLOUDFLARE_ACCOUNT`, `AARDLING_THEME_WEB_ANALYTICS_SITE` and `AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN` in wp-config.php. The box shows from 3 posts; after three days without a good answer it hides.

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
- The Twenty Nineteen options follow the feature list of [Options for Twenty Nineteen](https://wordpress.org/plugins/options-for-twenty-nineteen/) by Webd Ltd (GPL v2 or later) and reuse its setting names so saved values carry over. The code is written for this theme; none of the plugin's files are included.
- Vendored: [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) 5.6 by Jānis Elsts, MIT licence (`vendor/plugin-update-checker/license.txt`), compatible with the GPL.
- The bottom menu icons are original SVG drawings made for this theme. No Font Awesome files or artwork are included; the CSS only targets the class names WP Mobile Bottom Menu prints.
- WordPress is a trademark of the WordPress Foundation. This theme is not affiliated with or endorsed by it, and doesn't use the name in its own.
