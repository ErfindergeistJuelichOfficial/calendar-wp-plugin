# Project

WordPress plugin for displaying calendar events from an ICS feed.
PHP 8.3+, WordPress 6.4+, proprietary license.
Language: UI, error messages and comments in **German**.

# Architecture

Functional approach — no classes except `TomorrowEvent` (custom data model).
All functions prefixed with `egj_calendar_*` or `egj_*`.

| File | Responsibility |
| --- | --- |
| `vars.php` | Plugin constants (`EGJ_CALENDAR_VERSION`, `EGJ_CALENDAR_PREFIX`) |
| `styles.php` | CSS enqueuing (Bootstrap, calender.css) |
| `includes/class-tomorrowevent.php` | Data model for tomorrow's events |
| `includes/helpers.php` | Admin email notifications, `egj_escape()` |
| `includes/api.php` | ICS fetch with cache, REST API endpoints (`/ics`, `/events`, `/tomorrow`) |
| `includes/renderer.php` | Template loader, hashtag extraction, event rendering (normal + compact) |
| `includes/shortcode.php` | `[egj_calendar]` shortcode |
| `admin/main.php` | Admin menu, settings handler |
| `admin/partials/tab-settings.php` | Settings form partial |
| `templates/` | PHP partials for event display |

ICS data is cached for 1 hour via the WordPress Options API. Library `johngrogg/ics-parser` is provided via Composer.

# Template System

Templates live in `templates/` as PHP partials. Variables are injected via `extract()`:

- Trusted HTML (rendered sub-templates): `echo $var;`
- User data: `echo esc_html( $var );` / `echo esc_attr( $var );`

Template loader: `egj_calendar_render_template( 'name.php', $vars )` in `includes/renderer.php`.

# Code Conventions

- PHP 8.3+ features: full type declarations, union types, short array syntax `[]`
- WPCS-compliant: `esc_html()`, `wp_nonce_field()`, `sanitize_key()`, `wp_kses_post()` etc.
- `wp_date()` instead of `date()` (WordPress timezone)
- Return errors with `WP_Error`, never exceptions for WordPress flows
- Align equals signs in multi-variable assignments (PHPCS requirement)
- KISS — no over-engineering, no OOP without reason

# Quality Tools

```bash
# via Podman (default — no local PHP required)
podman compose run --rm composer phpcs
podman compose run --rm composer phpstan
podman compose run --rm composer psalm
podman compose run --rm composer phpmd
podman compose run --rm composer analyse   # all four

# alternatively Docker
docker compose run --rm composer analyse
```

Configs: `phpcs.xml`, `phpstan.neon`, `psalm.xml`, `.phpmd.xml`

`composer analyse` must pass cleanly before every commit.

**Important:** Changes to quality tools or their configuration must also be reflected in
`.github/workflows/calendar-plugin.yml`.

# CI/CD & Deployment

Pipelines: `.github/workflows/`

| Workflow | Trigger | Target |
| --- | --- | --- |
| `calendar-plugin.yml` | Push, PR, Mondays 06:00 UTC | CI (phpcs, phpstan, psalm, phpmd) on PHP 8.3 & 8.4 |
| `deploy-test.yml` | Manual (`workflow_dispatch`) or after CI success on `feature/**` | <https://spielwiese.erfindergeist.org/> |
| `deploy-prod.yml` | After CI success on `main` | <https://erfindergeist.org/> |
| `release.yml` | Manual (`workflow_dispatch`) with version input | Bump version, Git tag, GitHub Release (ZIP), deploy prod |

The FTP sync is extracted into a composite action: `.github/actions/ftp-deploy/action.yml`.
Both deploy workflows run `composer install --no-dev` before syncing so that `vendor/` is included in the deployment.

## Keep exclude lists in sync

When adding or removing files/folders that should **not** be deployed to the server, update **two places**:

1. **`.github/actions/ftp-deploy/action.yml`** — the `exclude:` block
2. **`.github/workflows/release.yml`** — the `rsync` excludes in the "Release-ZIP erstellen" step
