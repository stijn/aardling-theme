# Changelog

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
