# Changelog

## 1.6.0
- On this day: a fourth reading box with posts from today's date in earlier years, one per year, newest year first, each with its year and how long ago ("2012 · 14 jaar geleden"). It fills from `/aardling-reads.json` like Trending and Recently, and is rebuilt when the day turns (an hourly check on the hour, in the site's timezone) and whenever a post changes. On 360 days of the year at least one earlier year has a post.
- Contents list for long posts: posts with three or more h2 headings get a list of them (with their h3s) right before the first heading, in a box readers can fold shut. Made with the page, no script; headings without an id get one (`inc/toc.php`).
- Instant next post: WordPress's speculative loading now fetches a post as soon as the pointer rests on a link to it (moderate eagerness) instead of when the link is pressed. Prefetch only, so the analytics never count a page nobody opened.
- Each has a switch on Appearance > Aardling, on until switched off.

## 1.5.1
- The arrows get a soft halo in the page colour, so they stay visible over a post's header photo; on the page itself it doesn't show.
- No hyphenation inside words in the arrows' title card, as in the reading boxes.

## 1.5.0
- Previous and next arrows at the sides of each post, on screens from 1024 px wide, after McSweeney's: a thin < at the left edge leads to the older post, a > at the right edge to the newer one. Pointing at an arrow, or tabbing to it, opens a card with the post's title and its image (the medium size, 200 px, as in the reading boxes). The card stays hidden until then and the image is lazy-loaded, so it downloads only when someone points at the arrow. No script; colours follow Deluminator's dark mode. Twenty Nineteen's links under the post stay (`inc/nav.php`).
- Publishing, changing or unpublishing a post now also clears the cached pages of the posts before and after it, in WP Super Cache and at Cloudflare, so their arrows and Twenty Nineteen's links show the right post, title and image.
- Switches on Appearance > Aardling, under "Images and arrows": images in the reading boxes, the arrows, and images in the arrows. All on until switched off. Saving a change empties WP Super Cache and Cloudflare's cache, so every post shows it straight away (`inc/switches.php`).
- The release workflow lints every PHP file in `inc/`.

## 1.4.5
- Reading box images show at their full 200 px on laptop screens too. Four columns now start at 1,536 px instead of 1,168, so the three columns behind the dividers are never narrower than the image (they were down to 131 px); in between, two columns. Just above 768 px (an upright older iPad) the boxes use slightly less padding and gap for the same reason.

## 1.4.4
- Reading boxes on phones: less box padding, slightly smaller titles and a narrower gap beside the image, so titles get about 170 px instead of 140 and wrap into fewer lines.

## 1.4.3
- Comments are now closed in the database too, once, right after this version arrives: every post, page and attachment (1,005 posts and one page still had comments open, 1,741 posts pings), and the defaults for new posts. The update goes straight to the table, so no post's modified date changes and no cache needs clearing. The theme keeps closing them as before.
- Every post in the reading boxes has its image now, not just the first recent one: the medium file at its own size, above the title, lazy-loaded. On phones the image sits beside the title at half width, pixel for pixel on double-density screens. Posts without a featured image stay text only.
- Trending entries carry the image too. The stored lists are rebuilt on the next request for `/aardling-reads.json`; Trending keeps its ranking until the hourly check.

## 1.4.2
- Comments are closed on the whole site: 1,005 older posts and one page still accepted them, each through its own setting. The form, replies and pingbacks are gone; existing comments stay visible and the "Reacties zijn gesloten." line under them is hidden. Nothing in the database changes (`inc/comments.php`).
- On posts with comments, the reading boxes now come before the comments.
- The reading boxes follow Deluminator's dark palettes: their colours are CSS variables that switch to Deluminator's `--dl-*` colours in dark mode.
- The first recent post's image is the medium size at its exact dimensions (width and height set, never stretched), lazy-loaded.

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
