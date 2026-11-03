# aardling-theme

Aardling, the child theme of Twenty Nineteen that runs aardling.com. Twenty Nineteen does the styling; this theme holds the site's own changes and updates itself from this repo. Plans and past releases: [roadmap.md](roadmap.md); full detail: [CHANGELOG.md](CHANGELOG.md).

## What it changes
- Drops Font Awesome, which WP Mobile Bottom Menu loads on every page, and draws the bottom menu's three icons (home, compass, user; solid and outline) as inline SVG masks.
- Loads Twenty Nineteen's stylesheet under its own handle, so the child theme adds no request.
- On first activation, copies Twenty Nineteen's Customizer settings so the site looks the same.
- Twenty Nineteen options in Appearance > Customize > Aardling (`inc/customizer.php`): everything Options for Twenty Nineteen offers, free and premium, with the same setting names so saved values carry over. Off while that plugin is active.
- Stops Rank Math from emptying the whole WP Super Cache whenever its sitemap changes.
- Clears changed pages from Cloudflare's cache (see below), so a Cloudflare Cache Rule can keep the pages at the edge without APO.
- Reading boxes under each post (`inc/reads.php`): Suggested, Trending, Recently and On this day, see below.
- A contents list in posts with three or more h2 headings (`inc/toc.php`): their h2s and h3s, made with the page; headings without an id get one. Below 1168 px a `<details>` box before the top-level block that holds the first heading; from 1168 px a sticky bar of lines in the left margin whose list opens on hover or focus, with a few lines of script marking the section being read and showing only that section's h3s. With the Read Progress Button on the page, the script docks the bar under (or above) that round button as a pill of the same width and glass, shown while the sections are on screen. Titled "Contents" on posts tagged English, "Inhoud" on the others.
- Previous and next arrows at the sides of each post on screens from 1024 px (`inc/nav.php`): a thin < leads to the older post, a > to the newer one. Pointing at one, or tabbing to it, opens a card with the post's title and image. The card is `display:none` until then and its image is `loading="lazy"`, so the image downloads only on hover; no script. Twenty Nineteen's links under the post stay. Changing, publishing or unpublishing a post also clears the cached pages of the posts beside it (WP Super Cache and Cloudflare), before and after the change.
- Instant next post (`inc/nav.php`): WordPress's speculative loading fetches a page when the pointer rests on a link (moderate eagerness) instead of on click. Prefetch only.
- Posts tagged English get the theme's own labels and dates in English (reading boxes, arrows, contents list), the rest of the site stays Dutch: `aardling_theme_in_post_language()` in functions.php switches WordPress to English while those parts are built. Those posts also get `<html lang="en">` and Rank Math's `og:locale` en_GB.
- Switches on Appearance > Aardling (`inc/switches.php`): images in the reading boxes, On this day, the arrows, images in the arrows, instant next post, the contents list. All on until switched off; a change empties WP Super Cache and Cloudflare's cache.
- Keeps comments closed on every post and page, whatever the post's own setting (`inc/comments.php`). Existing comments stay visible, below the reading boxes; Twenty Nineteen's "Comments are closed." line is hidden. Version 1.4.3 also closed them once in the database (posts, pages, attachments and the defaults for new posts; the option `aardling_theme_comments_closed_db` records when and how many). Return false from the `aardling_theme_close_comments` filter to stop the theme closing them.
- Loads GA4 through Google tag gateway (`inc/analytics.php`): the Google tag comes from `aardling.com/ptp3/`, which Cloudflare relays to Google. Prints the tag itself when Site Kit's Analytics code is off, or points Site Kit's script at the path when it's on. Not for logged-in users, and no DNS prefetch for googletagmanager.com. Override with `AARDLING_THEME_GA4_ID` and `AARDLING_THEME_GA4_PATH` in wp-config.php.

## Cloudflare cache purge
- Needs the zone ID (Cloudflare > aardling.com > Overview, API box) and an API token with only Zone > Cache Purge > Purge for aardling.com. Enter both on Appearance > Aardling, or `define( 'AARDLING_THEME_CLOUDFLARE_ZONE', '...' );` and `define( 'AARDLING_THEME_CLOUDFLARE_TOKEN', '...' );` in wp-config.php. Without both, nothing is sent.
- A post change clears the post's path, the homepage and its feed, `/page/`, and the post's term, author and year archives (prefixes, so their `/page/N/` and `/feed/` go too). Site-wide changes purge everything.
- Sent once at the end of the request; more than 300 items becomes a purge of everything. Free plan limits: 5 prefix or everything calls a minute (bursts of 25), 800 URLs a second.
- "Test: clear the homepage" checks the zone ID and token; the page shows the last purge and any Cloudflare error.

## Reading boxes
- Suggested is rendered into the page: posts sharing the most tags with the current one, then posts from its categories.
- The boxes sit after the post navigation: before the comments when there are any, else before the footer. Colours are CSS variables (`--aardling-reads-*`) that switch to Deluminator's `--dl-*` palette under `html.deluminator-palette.deluminator-dark`.
- Trending, Recently and On this day are empty boxes in the page, filled by an inline script from `/aardling-reads.json` (served by the theme, cached by Cloudflare's page rule, cleared by the theme when a list changes). Without JavaScript they stay hidden.
- Each post shows its featured image while that switch is on: the medium file at its own size (width and height set, `loading="lazy"`), above the title; on screens under 600 px beside it at half width. No column is narrower than that image: two columns from 600 px, four from 1536 px.
- Recently is rebuilt whenever a post is published, updated or unpublished.
- On this day: posts from today's date in earlier years, one per year, newest first, with the year and how long ago. Rebuilt with Recently and when the day turns in the site's timezone (hourly check, on the hour); in `/aardling-reads.json` with the other two.
- Trending is rebuilt hourly from Cloudflare Web Analytics (`rumPageloadEventsAdaptiveGroups`, last 7 days, bots excluded), matched to published posts. Needs the account ID, the site tag and an API token with only Account > Account Analytics > Read: on Appearance > Aardling, or `AARDLING_THEME_CLOUDFLARE_ACCOUNT`, `AARDLING_THEME_WEB_ANALYTICS_SITE` and `AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN` in wp-config.php. The box shows from 3 posts; after three days without a good answer it hides.

## Release flow (fully automated)
1. Change code, bump the version in two places: `Version:` in `style.css` and `AARDLING_THEME_VERSION` in `functions.php`. Add a `## x.y.z` section to `CHANGELOG.md` and a dated `## x.y.z (date)` section to `roadmap.md` (move finished items out of "Next").
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
