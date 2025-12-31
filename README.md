# Erfindergeist Calendar - WordPress Plugin

A WordPress plugin for displaying calendar events from ICS (iCalendar) feeds with support for hashtags, custom templates, and multiple display modes.

## Features

- 📅 Import events from ICS/iCalendar URLs (NextCloud, Google Calendar, etc.)
- 🏷️ Hashtag support for event categorization and filtering (needs developer to expand )
- 🎨 Multiple display modes (normal and compact views) (needs developer to add or change templates)
- ⚡ Built-in caching system (1-hour cache lifetime)
- 🔌 Easy integration via WordPress shortcode
- Distribute ICS with wrapper url

## Installation

1. **Download the Plugin**
   - Download all files

2. **Upload to WordPress**
   - Upload all files folder to `/wp-content/plugins/egj-calendar-plugin`

## Configuration

### Step 1: Configure ICS URL

1. Navigate to WordPress Admin
2. Go to **Erfindergeist** → **Calendar Settings**
3. Enter your ICS calendar URL in the "ICS URL" field
   - Example: `https://calendar.google.com/calendar/ical/your-calendar-id/public/basic.ics`
4. Click "Save Settings"

### Step 2: Clear Cache (Optional)

The plugin caches calendar data for 1 hour to improve performance. To force an immediate update:

1. Go to **Erfindergeist** → **Calendar Settings**
2. Click "Clear Cache"
3. The next calendar request will fetch fresh data

### Getting Your ICS URL

**For Google Calendar:**
1. Open Google Calendar
2. Click the three dots next to your calendar
3. Select "Settings and sharing"
4. Scroll to "Integrate calendar"
5. Copy the "Public URL to this calendar" (iCal format)

**For Other Calendar Services:**
- Most calendar applications provide an ICS export or sharing URL
- Look for "Share", "Export", or "Subscribe" options

## Usage

### Basic Shortcode

Add the following shortcode to any WordPress page or post:

```
[egj_calendar]
```

This displays the next 20 upcoming events in normal view.

### Shortcode Parameters

Customize the calendar display with these parameters:

#### `max_events` (default: 20)
Number of events to display (1-100)

```
[egj_calendar max_events="10"]
```

#### `view` (default: "normal")
Display mode for events
- `normal` - Full event details with description
- `compact` - Condensed view with essential information

```
[egj_calendar view="compact"]
```

#### `tag_filter` (default: "")
Filter events by hashtag (must start with #)

```
[egj_calendar tag_filter="#Repaircafe"]
```

### Combined Examples

**Show 5 compact events:**
```
[egj_calendar max_events="5" view="compact"]
```

**Show only 10 Repair Cafe events:**
```
[egj_calendar tag_filter="#Repaircafe" max_events="10"]
```

**Compact view with tag filter:**
```
[egj_calendar view="compact" tag_filter="#OffeneWerkstatt"]
```

## Using Hashtags

### Adding Hashtags to Events

Add hashtags directly in your calendar event descriptions:

### Supported Hashtags (Default Configuration)

The plugin has built-in support for these hashtags with extended descriptions:

- `#Repaircafe` - Links to Repair Cafe information page
- `#OffeneWerkstatt` - Links to Open Workshop page
- `#KreativTag` - Links to Creative Day page
- `#Mobilitaetstag` - Links to Mobility Day page
- `#Stadtbücherei` - Shows callout for city library location
- `#Extern` - Shows callout for external locations

## API Endpoints

The plugin provides REST API endpoints for external integrations:

### Get Events as json
```
GET /wp-json/erfindergeist/v2/events
```

### Get tomorrow Event
```
GET /wp-json/erfindergeist/v2/tomorrow
```

### Get ics file Event
```
GET /wp-json/erfindergeist/v2/ics
```
Returns ics as erfindergeist.ics

## Requirements

- WordPress 6.0 or higher (tested up to 6.8)
- Valid ICS calendar URL

## License

See LICENSE file for details.

## Dependencies

[u01jmg3 / ics-parser](https://github.com/u01jmg3/ics-parser)
