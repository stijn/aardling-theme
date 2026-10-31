# Working on aardling-theme

- This child theme runs on Stijn's live site aardling.com and auto-updates from GitHub releases. Every push to main that bumps the version ships to production.
- Before pushing a version bump: `php -l` every PHP file you touched, bump the version in the two places listed in README.md, add a CHANGELOG section.
- Twenty Nineteen stays the parent and keeps its updates; override only what the site needs. Prefer CSS and hooks over copying parent templates.
- The theme must stay light: no new external requests, fonts or scripts without Stijn's go-ahead.
- Abilities in this theme may only report and install updates of this theme. Never add abilities that change content, settings, users, plugins or other themes.
- Never print, log or return the GitHub token.
- This repo is public. Never commit secrets, tokens, personal email addresses, drafts or anything from wp-config.php. Commit as `102652+stijn@users.noreply.github.com`.
- Keep the theme GPL v2 or later. Only bring in code under a GPL-compatible licence (MIT, BSD, GPL v2+), keep its licence file, and list it under "License and credits" in README.md. Code under GPL v3 only (Twentig is GPLv3 or later) means switching this theme to GPL v3 first: ask Stijn. Never copy icons, fonts or images whose licence asks for attribution or forbids reuse; draw them or use public-domain ones.
- Don't use "WordPress" as part of the theme's name, and don't use the WordPress logo.
- After the release workflow finishes, call `aardling-theme/self-update` through the aardling.com connector, then `aardling-theme/theme-status` to confirm the new version.
