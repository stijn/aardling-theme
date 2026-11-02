# Changelog

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
