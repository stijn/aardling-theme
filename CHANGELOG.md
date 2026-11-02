# Changelog

## 1.4.1
- Reading boxes: no Twenty Nineteen heading bar above the box titles, no hyphenation inside words, dates on one line, and the first recent post's image as a full-width picture above its title instead of squeezing it.

## 1.4.0
- Three reading boxes under each post, before the footer: Suggested (posts sharing the most tags, then the same category), Trending (most read in the last 7 days, numbered) and Recently (the newest posts, the first with its thumbnail). Box titles follow the site language (Leestips, Populair, Recent).
- Trending comes from Cloudflare Web Analytics through its GraphQL API, refreshed hourly. It needs the account ID, the Web Analytics site tag and a second, read-only token (Account Analytics Read) on Appearance > Aardling; until then, or after three days of failed refreshes, the box stays hidden.
- Trending and Recently load from /aardling-reads.json with a few lines of script, so pages Cloudflare cached weeks ago still show current lists. The theme clears that file at Cloudflare whenever a list changes. Suggested is part of the page.

## 1.3.3
- Drops the DNS prefetch hint for www.googletagmanager.com from every page: since 1.3.2 nothing loads from there.

## 1.3.2
- GA4 loads from aardling.com/ptp3/, the Google tag gateway path set up at Cloudflare, instead of googletagmanager.com. With Site Kit's Analytics code off, the theme prints the Google tag itself (once, not for logged-in users); with it on, only the address of Site Kit's script changes.
- Measurement ID and path can be changed with `AARDLING_THEME_GA4_ID` and `AARDLING_THEME_GA4_PATH` in wp-config.php; an empty ID turns the tag off.

## 1.3.1
- The PWA plugin is gone. Browsers that still run its service worker get, at their next update check, a worker that clears its caches and unregisters itself, so the plugin's offline page can no longer appear. Does nothing while the PWA plugin is active.

## 1.3.0
- Appearance > Customize > Aardling: every option the plugin Options for Twenty Nineteen offers, free and premium, in the theme. Font sizes (also for mobile and tablet), dashes, logo size, background, circle and position, title and description alignment or hiding, a scroll-to-content arrow, menu font sizes, title weight and case, where the date and author show, hiding dates, authors, featured images or titles, category and tag descriptions, full posts in archives, Rank Math or Yoast breadcrumbs, Jetpack infinite scroll, footer colour, removing or replacing the "Proudly powered by WordPress" credit, background and primary colour (hex), overlay opacity and colour levels.
- The plugin's options keep their setting names, so the values saved now carry over. The options stay off while the plugin is active; deactivate it and the theme takes over.
- No scripts: the credit and its comma are removed on the server instead of with JavaScript. Jetpack's Featured Content isn't included (no Jetpack on the site).

## 1.2.0
- Clears pages from Cloudflare's cache when they change, so a Cache Rule can keep pages at the edge for a month without APO. A post that's published, edited, unpublished, retagged or gets an approved comment clears its own page, the homepage, its term, author and year archives and the feeds. Menus, widgets, the Customizer, the site title, theme updates and a full WP Super Cache clear clear the whole site.
- One Cloudflare call for URLs and one for paths per request, 100 items each; more than 300 items (a bulk edit) becomes one purge of everything.
- Appearance > Aardling takes the zone ID and an API token with only Zone > Cache Purge, shows the last purge, and has "Test: clear the homepage" and "Clear the whole site" buttons. Without both settings nothing is sent.

## 1.1.2
- Stops Rank Math from emptying the whole WP Super Cache every time its sitemap goes out of date (every post save and every term change). That purge threw away the preloaded pages and stopped a running preload. WP Super Cache still clears the post, the homepage and the archives itself on publish.

## 1.1.1
- The repo is public now, so updates work without a token. A token (the theme's own or Aardling Schuif's) is only used to lift GitHub's rate limit.
- Adds the GPL v2 licence (LICENSE, also inside aardling.zip) and a copyright and licence notice in style.css.

## 1.1.0
- Updates itself from the private GitHub repo stijn/aardling-theme through plugin-update-checker 5.6, with WordPress auto-updates on for this theme only.
- Appearance > Aardling shows the installed and available version, takes a GitHub token, and has a "Check for updates now" button. Without its own token the theme borrows Aardling Schuif's.
- Abilities `aardling-theme/theme-status` and `aardling-theme/self-update` for the MCP connector.

## 1.0.0
- Child theme of Twenty Nineteen. Twenty Nineteen's stylesheet still loads under its own handle, with no extra request.
- Removes Font Awesome (loaded by WP Mobile Bottom Menu) and draws the bottom menu's home, compass and user icons as inline SVG masks in the menu's own size and colours.
- On first activation, copies Twenty Nineteen's Customizer settings (colours, logo, menus, Additional CSS, Options for Twenty Nineteen).
