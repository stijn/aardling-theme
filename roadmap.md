# Roadmap

Plans for the Aardling theme. The full detail of each release is in [CHANGELOG.md](CHANGELOG.md).

## Next
- Choose between GA4 and Cloudflare Web Analytics. Both run side by side for now; Trending already reads Cloudflare's numbers.

## 1.5.0 (2026-11-02)
- Previous and next arrows at the sides of each post, with a title card and a lazy-loaded image on hover.
- Switches for images in the reading boxes, the arrows and images in the arrows.
- Neighbouring posts' cached pages cleared when a post changes.

## 1.4.5 (2026-11-02)
- Reading box images at their full 200 px on every screen from 600 px: four columns from 1,536 px.

## 1.4.4 (2026-11-02)
- Roomier reading boxes on phones, so titles beside their image wrap into fewer lines.

## 1.4.3 (2026-11-02)
- Comments and pings closed once in the database too (posts, pages, attachments, defaults for new posts), without touching modified dates.
- An image for every post in the reading boxes, at the medium size's own dimensions, lazy-loaded; beside the title on phones.

## 1.4.2 (2026-11-02)
- Comments closed on the whole site by the theme; existing comments stay, after the reading boxes.
- Reading box colours follow Deluminator's dark palettes.
- The first recent post's image at its exact size.

## 1.4.1 (2026-11-02)
- Tidier reading boxes: no heading bar, no hyphenation inside words, dates on one line.

## 1.4.0 (2026-11-02)
- Suggested, Trending and Recently reading boxes under each post. Trending comes from Cloudflare Web Analytics, hourly; Trending and Recently load from `/aardling-reads.json`.

## 1.3.3 (2026-11-02)
- No DNS prefetch hint for googletagmanager.com.

## 1.3.2 (2026-11-02)
- GA4 through Google tag gateway at `/ptp3/`.

## 1.3.1 (2026-11-02)
- Retires the old PWA plugin's service worker.

## 1.3.0 (2026-11-02)
- All options of Options for Twenty Nineteen in the Customizer.

## 1.2.0 (2026-11-02)
- Clears changed pages from Cloudflare's cache, so a Cache Rule can keep pages at the edge for a month.

## 1.1.2 (2026-11-02)
- Stops Rank Math from emptying the whole WP Super Cache on sitemap changes.

## 1.1.1 (2026-10-31)
- Public repo, GPL v2 licence and credits.

## 1.1.0 (2026-10-31)
- Updates itself from GitHub releases, with auto-updates on for this theme; abilities to report and install its updates.

## 1.0.0 (2026-10-31, no release)
- Child theme of Twenty Nineteen; inline SVG icons instead of Font Awesome for the bottom menu; copies Twenty Nineteen's Customizer settings on first activation.

## Not planned
- Reopening comments: the site has them off everywhere, so no comment form, no spam handling.
- Cloudflare Web Analytics with "exclude EU visitors": most readers are in the EU, so it would leave Trending and the statistics almost empty.
- Cloudflare APO: the Cache Rule plus the theme's own purge does the job on the free plan.
- Abilities that change content, settings, users, plugins or other themes: the abilities only report and install this theme's updates.
- New external requests, fonts or scripts without Stijn's go-ahead: the theme stays light.
- Copies of Twenty Nineteen's templates: hooks and CSS keep the parent's updates working.
- Jetpack's Featured Content option: there's no Jetpack on the site.
