<?php

/**
 * Plugin Name: Erfindergeist Calendar
 * Description: Calendar WordPress Plugin from Erfindergeist Jülich e.V.
 * Author: Lars 'vreezy' Eschweiler
 * Author URI: https://www.erfindergeist.org
 * Contributor: Erfindergeist Jülich e.V.
 * Version: 2.2.0
 * Text Domain: erfindergeist
 * Domain Path: /languages
 * Tested up to: 6.8
 *
 *
 * @package Erfindergeist-Calendar
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

require_once 'helpers.php';
require_once 'vars.php';
require_once 'styles.php';
require_once 'apis.php';
require_once 'ICal.php';
use ICal\ICal;

/**
 * Extracts hashtags from text and removes them
 * 
 * @param string $text The text with hashtags
 * @return array Array with 'text' (cleaned text) and 'tags' (found hashtags)
 */
function egj_extract_and_remove_hashtags($text)
{
  // Find all words that start with #
  preg_match_all('/#\w+/u', $text, $matches);

  $tags = $matches[0]; // All found hashtags

  // Remove all hashtags from text
  $cleanedText = preg_replace('/#\w+/u', '', $text);

  // Remove excessive whitespace
  $cleanedText = preg_replace('/\s+/', ' ', $cleanedText);
  $cleanedText = trim($cleanedText);

  return array(
    'text' => $cleanedText,
    'tags' => $tags
  );
}

/**
 * Loads a template and replaces placeholders
 * 
 * @param string $templateFile Path to template file
 * @param array $variables Associative array with Placeholder => Value
 * @return string The rendered HTML code
 */
function egj_load_and_render_template($templateFile, $variables): string
{
  // Load template file
  $templatePath = plugin_dir_path(__FILE__) . $templateFile;

  if (!file_exists($templatePath)) {
    return '<div class="error">Template not found: ' . esc_html($templateFile) . '</div>';
  }

  $template = file_get_contents($templatePath);

  // Replace placeholders
  try {
    foreach ($variables as $placeholder => $value) {
      $template = str_replace('{{' . $placeholder . '}}', $value, $template);
    }
  } catch (\Exception $e) {
    return '<div class="error">Error rendering template: ' . esc_html($e->getMessage()) . '</div>';
  }

  return $template;
}
/**
 * Extends the description based on the tag
 * 
 * @param string $description The original description
 * @param string $tag The hashtag
 * @return string The extended description
 */
function egj_extend_description_by_tag($description, $tag)
{
  $tagHtmlMap = array(
    '#Repaircafe' => '<p>Alle Informationen zum Repair Cafe findest du auf der <a href="https://repaircafe.erfindergeist.org">Repair Cafe Seite</a>.</p>',
    '#OffeneWerkstatt' => '<p>Alle Informationen zur Offenen Werkstatt findest du auf der <a href="https://werkstatt.erfindergeist.org">Offene Werkstatt Seite</a>.</p>',
    '#KreativTag' => '<p>Alle Informationen zum KreativTag findest du auf der <a href="https://kreativ-tag.erfindergeist.org">KreativTag Seite</a>.</p>',
    '#Mobilitaetstag' => '<p>Alle Informationen zum Mobilitätstag findest du auf der <a href="/mobilitaetstag">Mobilitätstag Seite</a>.</p>',
    '#Stadtbücherei' => '<div class="bd-callout">Achtung! heute findest du uns in der <a href="https://buecherei.juelich.de/" target="_blank" rel="noopener noreferrer">Stadtbücherei Jülich</a>.</div>',
    '#Extern' => '<div class="bd-callout">Achtung! Dieser Termin findet nicht in unseren Räumlichkeiten statt. Achte auf die Adresse im Standortfeld.</div>',
  );

  if (array_key_exists($tag, $tagHtmlMap)) {
    $description .= $tagHtmlMap[$tag];
  }

  return $description;
}

/**
 * Wraps the summary text with a link based on the tag
 * 
 * @param string $summary The event summary text
 * @param string $tag The hashtag to match
 * @return string The summary text, optionally wrapped in an HTML link
 */
function egj_link_text_by_tag($summary, $tag)
{
  $tagHtmlMap = array(
    '#Repaircafe' => '<a href="https://repaircafe.erfindergeist.org">' . $summary . '</a>',
    '#OffeneWerkstatt' => '<a href="https://werkstatt.erfindergeist.org">' . $summary . '</a>',
    '#KreativTag' => '<a href="https://kreativ-tag.erfindergeist.org">' . $summary . '</a>',
    '#Mobilitaetstag' => '<a href="/mobilitaetstag">' . $summary . '</a>',
  );

  if (array_key_exists($tag, $tagHtmlMap)) {
    $summary = $tagHtmlMap[$tag];
  }

  return $summary;
}

/**
 * Renders calendar events in compact view
 * 
 * @param array $arrayOfEvents Array of calendar event objects
 * @param string $tag_filter Optional tag filter to show only events with specific hashtag
 * @return void Outputs HTML directly
 */
function egj_render_compact_calendar_events($arrayOfEvents, $tag_filter)
{
  $renderedAppointments = array();
  foreach ($arrayOfEvents as $event) {
    $summary = $event->summary ?? '';
    $location = $event->location ?? '';
    $description = $event->description ?? '';
    $descriptionData = egj_extract_and_remove_hashtags($description);
    $description = $descriptionData['text'];
    $tags = $descriptionData['tags'];

    $startDate = '';
    $startTime = '';

    if (isset($event->dtstart)) {
      $startDateTime = new DateTime($event->dtstart);
      $startDate = $startDateTime->format('d.m.Y');
      $startTime = $startDateTime->format('H:i');
    }

    // Convert dtend to date format
    $endDate = '';
    $endTime = '';
    if (isset($event->dtend)) {
      $endDateTime = new DateTime($event->dtend);
      $endDate = $endDateTime->format('d.m.Y');
      $endTime = $endDateTime->format('H:i');
    }

    $renderedDateTimeInfo = '';
    if ($endDate === $startDate) {
      $renderedDateTimeInfo = egj_load_and_render_template('template_same_day.html', array(
        'startDate' => esc_html($startDate),
        'startTime' => esc_html($startTime),
        'endTime' => esc_html($endTime),
      ));
    } else {
      $renderedDateTimeInfo = egj_load_and_render_template('template_several_days.html', array(
        'startDate' => esc_html($startDate),
        'startTime' => esc_html($startTime),
        'endDate' => esc_html($endDate),
        'endTime' => esc_html($endTime),
      ));
    }

    if (!empty($tags)) {
      foreach ($tags as $tag) {
        $summary = egj_link_text_by_tag($summary, $tag);
      }
    }

    $hasFilterTag = false;
    if ($tag_filter !== '') {
      foreach ($tags as $tag) {
        if ($tag === $tag_filter) {
          $hasFilterTag = true;
          break;
        }
      }
      if (!$hasFilterTag) {
        continue; // Skip this appointment if no filter tag matches
      }
    }

    if ($hasFilterTag) {
      $renderedAppointment = egj_load_and_render_template('template_appointment_compact_filtered.html', array(
        'location' => $location,
        'dateTimeInfo' => $renderedDateTimeInfo
      ));
    } else {
      $renderedAppointment = egj_load_and_render_template('template_appointment_compact.html', array(
        'linkText' => $summary,
        'dateTimeInfo' => $renderedDateTimeInfo
      ));
    }

    array_push($renderedAppointments, $renderedAppointment);
  }

  $renderedEvents = egj_load_and_render_template('template_events_compacts.html', array(
    'appointments' => join(' ', $renderedAppointments),
  ));

  echo $renderedEvents;
}

/**
 * Renders calendar events in normal (full) view
 * 
 * @param array $arrayOfEvents Array of calendar event objects
 * @return void Outputs HTML directly
 */
function egj_render_calendar_events($arrayOfEvents)
{
  $renderedAppointments = array();
  foreach ($arrayOfEvents as $event) {
    $summary = $event->summary ?? '';
    $description = $event->description ?? '';
    $location = $event->location ?? '';

    // Extract and remove hashtags from description
    $descriptionData = egj_extract_and_remove_hashtags($description);
    $description = $descriptionData['text'];
    $tags = $descriptionData['tags'];

    $startDate = '';
    $startTime = '';

    if (isset($event->dtstart)) {
      $startDateTime = new DateTime($event->dtstart);
      $startDate = $startDateTime->format('d.m.Y');
      $startTime = $startDateTime->format('H:i');
    }

    // Convert dtend to date format
    $endDate = '';
    $endTime = '';
    if (isset($event->dtend)) {
      $endDateTime = new DateTime($event->dtend);
      $endDate = $endDateTime->format('d.m.Y');
      $endTime = $endDateTime->format('H:i');
    }

    $renderedDateTimeInfo = '';
    if ($endDate === $startDate) {
      $renderedDateTimeInfo = egj_load_and_render_template('template_same_day.html', array(
        'startDate' => esc_html($startDate),
        'startTime' => esc_html($startTime),
        'endTime' => esc_html($endTime),
      ));
    } else {
      $renderedDateTimeInfo = egj_load_and_render_template('template_several_days.html', array(
        'startDate' => esc_html($startDate),
        'startTime' => esc_html($startTime),
        'endDate' => esc_html($endDate),
        'endTime' => esc_html($endTime),
      ));
    }

    $renderedTags = array();
    if (!empty($tags)) {
      foreach ($tags as $tag) {
        $renderedTag = egj_load_and_render_template('template_tag.html', array(
          'tag' => esc_html($tag),
        ));
        array_push($renderedTags, $renderedTag);

        $description = egj_extend_description_by_tag($description, $tag);
      }
    }

    $renderedAppointment = egj_load_and_render_template('template_appointment.html', array(
      'summary' => esc_html($summary),
      'description' => $description,
      'location' => esc_html($location),
      'dateTimeInfo' => $renderedDateTimeInfo,
      'tags' => join(' ', $renderedTags),
    ));

    array_push($renderedAppointments, $renderedAppointment);
  }

  $renderedEvents = egj_load_and_render_template('template_events.html', array(
    'appointments' => join(' ', $renderedAppointments),
  ));

  echo $renderedEvents;

  // echo json_encode($arrayOfEvents, JSON_PRETTY_PRINT);
}

/**
 * WordPress shortcode handler for displaying calendar events
 * 
 * Usage: [egj_calendar max_events="20" view="normal" tag_filter="#Repaircafe"]
 * 
 * @param array $raw_attributes Shortcode attributes from WordPress
 * @return string The rendered HTML output
 */
function egj_calendar_display_shortcode($raw_attributes)
{
  // Attributes with defaults
  $attributes = shortcode_atts(array(
    'max_events' => 20,
    'view' => 'normal',
    'tag_filter' => ''
  ), $raw_attributes);

  // Validation: max_events
  // Cast to integer and limit to sensible range (1-100)
  $max_events = absint($attributes['max_events']);
  if ($max_events < 1) {
    $max_events = 1;
  } elseif ($max_events > 100) {
    $max_events = 100;
  }

  // Validation: view
  // Only allow permitted values (whitelist)
  $allowed_views = array('normal', 'compact');
  $view = in_array($attributes['view'], $allowed_views, true) ? $attributes['view'] : 'normal';

  // Validation: tag_filter
  // Check hashtag format: must start with #, only letters/numbers/underscores
  $tag_filter = '';
  if (!empty($attributes['tag_filter'])) {
    $tag = sanitize_text_field($attributes['tag_filter']);
    // Check if format is correct: #wordcharacters
    if (preg_match('/^#\w+$/u', $tag)) {
      $tag_filter = $tag;
    }
  }

  try {
    $ics = get_ics_internal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(null, null);
  } catch (\Exception $e) {
    return '<div class="egj-calendar-error">Error loading appointments</div>';
  }

  $arrayOfEvents = array_slice($arrayOfEvents, 0, $max_events);

  // Render the appointments
  ob_start();
  if ($view === 'compact') {
    egj_render_compact_calendar_events($arrayOfEvents, $tag_filter);
  } else {
    egj_render_calendar_events($arrayOfEvents);
  }
  return ob_get_clean();
}

// Register shortcode
add_shortcode('egj_calendar', 'egj_calendar_display_shortcode');

/**
 * Renders the main plugin options page in WordPress admin
 * 
 * @return void Outputs HTML directly
 */
function egj_calendar_plugin_options()
{

  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  ?>
  <div>
    <h3>Erfindergeist</h3>
    <p>Please use Submenus for Options</p>
  </div>
  <?php
}

/**
 * Renders the calendar settings page in WordPress admin
 * Handles ICS URL configuration and cache management
 * 
 * @return void Outputs HTML directly
 */
function egj_calendar_settings_page()
{

  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  // updatable options  
  $ics_url = get_option('egj_ics_url');
  $ics_url_field_name = 'erfindergeist_ics_url_field';

  // read-only options
  $ics_cache = get_option('egj_ics_cache');
  $ics_cache_timestamp = get_option('egj_ics_cache_timestamp');

  if (!empty($_POST) && wp_verify_nonce(egj_escape($_POST['egj_calendar_nonce_field']), 'egj_calendar_action')) {
    // update ics_url
    if (isset($_POST[$ics_url_field_name])) {
      $ics_url = esc_url_raw($_POST[$ics_url_field_name]);
      update_option('egj_ics_url', $ics_url);
    }

    if (isset($_POST['clear_cache'])) {
      // clear cache
      delete_option('egj_ics_cache');
      delete_option('egj_ics_cache_timestamp');
      $ics_cache = "";
      $ics_cache_timestamp = "";
    }

    // Put a "settings saved" message on the screen
    ?>
    <div class="updated">
      <p><strong><?php _e('settings saved.', 'menu-test'); ?></strong></p>
    </div>
    <?php
  }


  echo '<div class="wrap">';
  echo "<h2>" . __('Erfindergeist Calendar Settings', 'menu-test') . "</h2>";
  ?>

  <form name="form1" method="post" action="">

    <?php wp_nonce_field('egj_calendar_action', 'egj_calendar_nonce_field'); ?>

    <p><?php _e("Ics Url:", 'menu-test'); ?>
      <input type="text" name="<?php echo $ics_url_field_name; ?>" value="<?php echo esc_attr($ics_url); ?>" size="60">
    </p>

    <p>
      <input type="checkbox" name="clear_cache"> Clear ICS Cache
    </p>

    <p class="submit">
      <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>

  </form>

  <div>
    <h3>ICS Cache Information</h3>
    Timestamp:
    <?php echo $ics_cache_timestamp ? date('d.m.Y H:i:s', $ics_cache_timestamp) : 'No ics cache timestamp available'; ?>

    <span>
      <p>Cache</p>

      <pre>
          <?php
          if ($ics_cache && $ics_cache != "") {
            $iCal = new ICal();
            $iCal->initString($ics_cache);
            $arrayOfEvents = $iCal->eventsFromRange(null, null);
            echo json_encode($arrayOfEvents, JSON_PRETTY_PRINT);
          }
          ?>
        </pre>
    </span>

    <br>
    <div><?php echo $ics_cache ? $ics_cache : 'No ics cache available'; ?></div>


    </p>
  </div>

  <?php
}

/**
 * Registers the plugin menu and submenu pages in WordPress admin
 * 
 * @return void Registers admin menu items
 */
function egj_calendar_menu()
{
  if (empty($GLOBALS['admin_page_hooks']['erfindergeist'])) {
    add_menu_page(
      'Erfindergeist',
      'Erfindergeist',
      'manage_options',
      'erfindergeist',
      'egj_calendar_plugin_options'
    );
  }

  add_submenu_page(
    'erfindergeist',
    'Calendar',
    'Calendar Settings',
    'manage_options',
    'egj-calendar-submenu-handle',
    'egj_calendar_settings_page'
  );
}

add_action('admin_menu', 'egj_calendar_menu');
