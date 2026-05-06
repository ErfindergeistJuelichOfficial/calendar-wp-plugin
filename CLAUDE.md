# Project

WordPress plugin for displaying calendar events from an ICS feed.
PHP 8.3+, WordPress 6.4+, proprietary license.
Language: UI, error messages and comments in **German**.

# Architecture

Functional approach — no classes except `TomorrowEvent` (custom data model).
All functions prefixed with `egj_calendar_*` or `egj_*`.

| File | Responsibility |
| --- | --- |
| `vars.php` | Plugin-Konstanten (`EGJ_CALENDAR_VERSION`, `EGJ_CALENDAR_PREFIX`) |
| `styles.php` | CSS-Enqueuing (Bootstrap, calender.css) |
| `includes/TomorrowEvent.php` | Datenmodell für morgige Events |
| `includes/helpers.php` | Admin-Benachrichtigung per E-Mail, `egj_escape()` |
| `includes/api.php` | ICS-Abruf mit Cache, REST-API-Endpunkte (`/ics`, `/events`, `/tomorrow`) |
| `includes/renderer.php` | Template-Loader, Hashtag-Extraktion, Event-Rendering (Normal + Kompakt) |
| `includes/shortcode.php` | `[egj_calendar]`-Shortcode |
| `admin/main.php` | Admin-Menü, Settings-Handler |
| `admin/partials/tab-settings.php` | Settings-Formular-Partial |
| `templates/` | PHP-Partials für Event-Darstellung |

ICS-Daten werden 1 Stunde im Cache gehalten (WordPress Options API). Bibliothek `u01jmg3/ics-parser` kommt via Composer.

# Template-System

Templates liegen in `templates/` als PHP-Partials. Variablen werden per `extract()` übergeben:

- Trusted HTML (gerenderte Sub-Templates): `echo $var;`
- Nutzerdaten: `echo esc_html( $var );` / `echo esc_attr( $var );`

Template-Loader: `egj_calendar_render_template( 'name.php', $vars )` in `includes/renderer.php`.

# Code Conventions

- PHP 8.3+ features: full type declarations, union types, short array syntax `[]`
- WPCS-compliant: `esc_html()`, `wp_nonce_field()`, `sanitize_key()`, `wp_kses_post()` etc.
- `wp_date()` statt `date()` (WordPress-Timezone)
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
podman compose run --rm composer analyse   # alle vier

# alternativ Docker
docker compose run --rm composer analyse
```

Konfigurationen: `phpcs.xml`, `phpstan.neon`, `psalm.xml`, `.phpmd.xml`

Vor jedem Commit muss `composer analyse` fehlerfrei durchlaufen.

**Wichtig:** Änderungen an Quality-Tools oder deren Konfiguration müssen auch in
`.github/workflows/calendar-plugin.yml` nachgezogen werden.

# CI/CD & Deployment

Pipelines: `.github/workflows/`

| Workflow | Trigger | Ziel |
| --- | --- | --- |
| `calendar-plugin.yml` | Push, PR, Montags 06:00 UTC | CI (phpcs, phpstan, psalm, phpmd) auf PHP 8.3 & 8.4 |
| `deploy-test.yml` | Manuell | <https://spielwiese.erfindergeist.org/> |
| `deploy-prod.yml` | Nach CI-Erfolg auf `main` | <https://erfindergeist.org/> |
