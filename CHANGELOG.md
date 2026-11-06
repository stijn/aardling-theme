# Changelog

## 1.12.0
- Google Analytics loads after the page has finished loading. The theme still prints the `gtag('config')` line in the head, which only queues the page view; the Google tag (190 KB) that sends it is added once the load event has fired, so on a slow phone it no longer shares the connection with the page's photo and stylesheets (Lighthouse: up to about 0.45 s on phones). Visitors who leave before the page has loaded aren't counted; the reports stay the same otherwise.
- This takes effect when Cloudflare's Google tag gateway is switched off: while it's on, Cloudflare puts its own loader at the top of every page, which downloads straight away, and the theme adds nothing next to it, so one copy runs either way. The delayed tag comes from googletagmanager.com (`AARDLING_THEME_GA4_SRC` to change it); the Site Kit address rewrite to the gateway is gone.
- Tested on WordPress 7.1.4 with PHP 7.4 and 8.5 (tests/images): no Google tag on the page before the load event, one tag requested after it and one page view queued, and nothing added next to a gateway loader.

## 1.11.0
- Cover photos load when the reader gets near them. Cover blocks with a fixed (parallax) or repeated background show their photo as a CSS background, which browsers download straight away wherever it is on the page: "Patriarchal Panic" made a phone fetch ten section photos (2.4 MB) on arrival, and its header photo had to share the connection with them (Lighthouse: up to 9 s before it appeared). The theme now puts each photo in a CSS variable, and a few lines of script switch it on once its cover is within a screen's height of view. Without script the photos show as before. Applies to every cover with a background photo, old and new markup.
- Footnote numbers are readable in dark mode. Deluminator colours every link in a post light blue with a stronger selector, which turned the numbers light blue on blue (contrast 1.9:1). In dark mode they now use the accent pair Deluminator gives footnote markers (cream on dark blue, 5:1), also on hover; light mode is unchanged.
- The "Older posts" and "Newer posts" links have a name for screen readers on phones. Twenty Nineteen hid their words with `display:none` below 768 px; they're now hidden visually only.
- Addresses on the site's own domain that still start with `http://` (old image links in posts, `www.` included) are written as `https://` when a post is shown. Browsers upgraded them anyway but reported mixed content, which cost the posts' Best Practices score. No post is changed.
- WP Super Cache's preload walks the categories and the seven W taxonomies (wie, wat, waar, waardoor, waarmee, wanneer, hoe: about 3,600 term pages) instead of the tags (`wp_cache_preload_taxonomies`). Every tag page is a 301 to its W term, which WP Super Cache can't cache, and the W pages were never preloaded, so a Cloudflare miss on one took PHP 1.2–1.7 s instead of 0.4–0.8 s from a cached copy. Only runs while WP Super Cache's "Preload tags, categories and other taxonomies" is on; taxonomies that don't exist are left out.
- Tested on WordPress 7.1.4 with PHP 7.4 and 8.5 (tests/images): the preload list, the cover photos are fetched only near view and all of them after scrolling, they show without script too, the numbers' colours in light and Deluminator's dark mode (including hover), the pop-up, the pager link's name at 412 and its words at 1280 px, and the https rewrite leaving other domains alone.

## 1.10.0
- Footnotes open in a pop-up. Tapping a footnote number opens its note right beside it, below the number or above it when there's no room; Escape, a tap elsewhere or the same number again closes it, and the page doesn't move. The notes are also listed at the end of the post, where the number jumps to without script, with an arrow back. The pop-up is the browser's own popover: no library, about 1.5 KB of script and styles, and only on pages with footnotes. Colours follow Deluminator's dark palette. The numbers keep the blue pill the site had.
- The theme renders Modern Footnotes' notes itself (`[mfn]…[/mfn]`, `[modern_footnote]` and the editor button's `<mfn>` tags, including notes the editor split into pieces around italics), so that plugin's script, stylesheet and the jQuery its script needed (about 40 KB on 122 posts) no longer load, and the plugin can be deactivated and deleted. While it's active its hooks are switched off.
- WordPress's own footnotes (the Footnotes block) get the same numbers and pop-up. Aardling Schuif 1.9.0 converts the existing notes to them.
- No feed links per tag, category or post, and no oEmbed discovery links, in the page head. Bots followed them to about 7,000 term feeds and to `/wp-json/oembed/` (555 calls in one day), none of which Cloudflare caches. The main `/feed/` link stays; link previews come from Rank Math's Open Graph tags.
- A post whose content Aardling Schuif writes straight to the database (footnote conversion, drop caps) is cleared from WP Super Cache and Cloudflare (`aardling_schuif_post_content_written`).
- Tested on WordPress 7.1.4 with PHP 8.0 and 8.5 (and linted on 7.4): fixture posts modelled on the site's own, before and after conversion, in Chrome at 1280 and 375 px, and in the block editor.

## 1.9.0
- No more jQuery on the front end. WP Mobile Bottom Menu's only script updates the counter of YITH WooCommerce Wishlist, and it brought in jQuery and jQuery Migrate (38 KB) to do so. The site has no wishlist, so the theme drops that script, and jQuery goes with it. With YITH WooCommerce Wishlist installed, the script stays.
- Only the first image on a page loads straight away; the others wait until they scroll into view. WordPress loads the first three at once, and on the homepage the second and third posts' photos (about 90 KB on a phone) competed with the first, which is the largest thing on screen.
- The contents bar no longer measures the page while it scrolls. The browser now reports which headings have passed the line where the bar sits (IntersectionObserver), and the script measures only the Read Progress Button and the arrows, on load and on resize. Below 1168 px, where the box shows instead, it watches nothing; before, it measured every heading on every scrolled frame there too, which Lighthouse flagged as a 122 ms forced layout on phones. Tested in Chrome against the old script at 25 scroll positions, including jumps, at 1440 and 1280 px: the same marks, docking and position at each.

## 1.8.1
- GA4 downloads once instead of twice. Cloudflare's Google tag gateway puts its own loader (`<script async src="/ptp3/">`) at the top of every page it sends to a browser, and the theme printed a second one (`/ptp3/?id=…`), so every visitor fetched the 190 KB tag twice and ran it twice. The theme now prints only the `gtag('config')` line, which sends the page view, and adds its own script only when Cloudflare's loader isn't on the page. Tested in Chrome both ways: one download, one page view.

## 1.8.0
- The contents bar docks to the Read Progress Button: a pill of the button's 44 px width and glass, right under the round button (above it in a bottom corner), shown while the post's sections are on screen and fading away before and after. The list opens towards the text. It follows the button to whichever corner its settings choose and keeps clear of the previous and next arrows. Without the button, or without the script, the bar stays sticky in the margin.
- More contrast: the bar's glass is nearly solid (86% instead of 62%), so it stays white over dark photos, and its lines are darker. In dark mode the docked pill uses the button's charcoal glass.

## 1.7.3
- Posts tagged English say so: `<html lang="en">` instead of `nl-NL`, so screen readers read them with an English voice and browsers don't offer to translate them from Dutch, and Rank Math's `og:locale` is `en_GB` there.

## 1.7.2
- Posts tagged English show all of the theme's own labels in English, not only the contents list: the reading boxes (Suggested reads, Trending, Recently, On this day), their dates ("29 October 2026", "14 years ago"), and the arrows (Previous Post, Next Post). The theme switches WordPress to English just while it builds those parts (`aardling_theme_in_post_language()`). Other posts stay Dutch.
- The JSON file's On this day entries carry the year and how many years ago, so the script can word them in the page's language. The stored lists are rebuilt on the next request.
- The contents bar sits on frosted glass in the page colour (Deluminator's dark surface in dark mode), so its lines stay readable over photos; on a plain page it barely shows. Its lines are a little darker.

## 1.7.1
- Long contents lists stay short: the bar and the list show the h2s, plus only the h3s of the section being read. They fold open and shut as the reader moves on. Patriarchal Panic's 80 entries become 10 headings plus the current section's subheadings, so the bar no longer runs off the screen. Without the script everything shows.
- The contents list is titled "Contents" on posts tagged English, "Inhoud" on the others.

## 1.7.0
- The contents list on wide screens (from 1168 px): a small bar of lines in the left margin, one per heading, after Stack Overflow's. It stays in view while the sections scroll past and leaves with the end of the post. Pointing at it, or tabbing into it, opens the list beside it: it fades and slides in, its entries follow one after another, and the section being read is marked in the bar and the list. Colours follow Deluminator's dark palette; motion stops for readers who ask for less. Narrower screens keep the box.
- A few lines of script mark the section being read; without them the list still works.
- The contents list now goes before the top-level block that holds the first heading, so a heading inside a cover or group no longer gets the box inside it.
- On posts with the bar, Twenty Nineteen's `overflow: hidden` on `.site-content` becomes `overflow: clip`, which looks the same but lets the bar stick.
- The 36 posts that had Rank Math's own contents block (23 published, 13 drafts) no longer have it, so there's one list per post. Each post's previous version is kept as a revision.

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
