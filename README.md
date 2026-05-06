# Erfindergeist Calendar — WordPress Plugin

A WordPress plugin for displaying calendar events from an ICS (iCalendar) feed with hashtag support, multiple display modes, and a REST API.

## Requirements

- WordPress 6.4 or higher (tested up to 6.8)
- PHP 8.3 or higher

## Features

- Import events from any ICS/iCalendar URL (Nextcloud, Google Calendar, etc.)
- Hashtag support for event categorization and filtering
- Multiple display modes: normal and compact
- 1-hour cache via the WordPress Options API
- WordPress shortcode for easy embedding
- REST API endpoints for external integrations (e.g. Home Assistant)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/egj-calendar-plugin`
2. Activate the plugin in **WordPress Admin → Plugins**
3. Go to **Erfindergeist → Calendar Settings** and enter your ICS URL

## Configuration

### ICS URL

Navigate to **Erfindergeist → Calendar Settings**, enter your ICS calendar URL and save.

Example URLs:

- Google Calendar: `https://calendar.google.com/calendar/ical/<id>/public/basic.ics`
- Nextcloud: `https://your-instance/remote.php/dav/public-calendars/<token>/`

### Cache

Calendar data is cached for 1 hour. To force a refresh, check **Clear Cache** in the settings and save.

## Shortcode

```
[egj_calendar]
```

Displays the next 20 upcoming events in normal view. All parameters are optional.

| Parameter | Default | Description |
| --- | --- | --- |
| `max_events` | `20` | Number of events to display (1–100) |
| `view` | `normal` | Display mode: `normal` or `compact` |
| `tag_filter` | _(none)_ | Only show events with this hashtag, e.g. `#Repaircafe` |

**Examples:**

```
[egj_calendar max_events="5" view="compact"]
[egj_calendar tag_filter="#Repaircafe" max_events="10"]
[egj_calendar view="compact" tag_filter="#OffeneWerkstatt"]
```

## Hashtags

Add hashtags directly in your calendar event descriptions. The plugin extracts them, displays them as badges, and optionally enriches them with links or callouts.

Built-in hashtags:

| Hashtag | Effect |
| --- | --- |
| `#Repaircafe` | Link to Repair Café info page |
| `#OffeneWerkstatt` | Link to Open Workshop page |
| `#KreativTag` | Link to Creative Day page |
| `#Mobilitaetstag` | Link to Mobility Day page |
| `#Stammtisch` | Link to Stammtisch page |
| `#Stadtbücherei` | Callout for city library location |
| `#Extern` | Callout for external locations |

## REST API

| Endpoint | Description |
| --- | --- |
| `GET /wp-json/erfindergeist/v2/events` | All events as JSON |
| `GET /wp-json/erfindergeist/v2/tomorrow` | First event of tomorrow as JSON |
| `GET /wp-json/erfindergeist/v2/ics` | Raw ICS feed (as `erfindergeist.ics`) |

## Home Assistant

The `/tomorrow` endpoint can be used to push next-day event notifications to Discord or other services. See the `homeassistant/` folder for a configuration example.

## Dependencies

- [johngrogg/ics-parser](https://github.com/johngrogg/ics-parser) — ICS parsing
- [Bootstrap 5.3](https://getbootstrap.com/) — bundled CSS

## License

See LICENSE file for details.

Run all checks locally before making a commit.

## Develop

### Prerequisites

Podman or Docker (no local PHP required).

### Setup

The included `compose.yml` + `Dockerfile` build a container with PHP 8.3 + Composer.
Build the image once, it will be cached afterwards:

```bash
podman compose build
```

Then install dependencies:

```bash
# Development (incl. analysis tools):
podman compose run --rm composer install

# Production (runtime dependencies only):
podman compose run --rm composer install --no-dev --optimize-autoloader
```

### Individual Checks

| Command | Podman equivalent | Checks |
| --- | --- | --- |
| `composer phpcs` | `podman compose run --rm composer phpcs` | Code style (WordPress Coding Standards + PHP compatibility) |
| `composer phpstan` | `podman compose run --rm composer phpstan` | Static analysis — types, undefined variables, logic errors |
| `composer psalm` | `podman compose run --rm composer psalm` | Security — taint analysis (XSS, SQL injection, path traversal) |
| `composer phpmd` | `podman compose run --rm composer phpmd` | Code quality — complexity, naming, unused code |
| `composer audit` | `podman compose run --rm composer audit` | Known CVEs in dependencies |

### All Checks at Once

```bash
podman compose run --rm composer analyse
```

Runs phpcs → phpstan → psalm → phpmd sequentially.
Run `composer audit` separately afterwards.
