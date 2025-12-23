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
 * Extrahiert Hashtags aus einem Text und entfernt sie
 * 
 * @param string $text Der Text mit Hashtags
 * @return array Array mit 'text' (bereinigter Text) und 'tags' (gefundene Hashtags)
 */
function egj_extract_and_remove_hashtags($text)
{
  // Finde alle Wörter, die mit # beginnen
  preg_match_all('/#\w+/u', $text, $matches);

  $tags = $matches[0]; // Alle gefundenen Hashtags

  // Entferne alle Hashtags aus dem Text
  $cleanedText = preg_replace('/#\w+/u', '', $text);

  // Entferne überflüssige Leerzeichen
  $cleanedText = preg_replace('/\s+/', ' ', $cleanedText);
  $cleanedText = trim($cleanedText);

  return array(
    'text' => $cleanedText,
    'tags' => $tags
  );
}

/**
 * Lädt ein Template und ersetzt die Placeholder
 * 
 * @param string $templateFile Pfad zur Template-Datei
 * @param array $variables Assoziatives Array mit Placeholder => Wert
 * @return string Der gerenderte HTML-Code
 */
function egj_load_and_render_template($templateFile, $variables): string
{
  // Template-Datei laden
  $templatePath = plugin_dir_path(__FILE__) . $templateFile;

  if (!file_exists($templatePath)) {
    return '<div class="error">Template nicht gefunden: ' . esc_html($templateFile) . '</div>';
  }

  $template = file_get_contents($templatePath);

  // Placeholder ersetzen
  try
  {
    foreach ($variables as $placeholder => $value) {
      $template = str_replace('{{' . $placeholder . '}}', $value, $template);
    }
  }
  catch (\Exception $e)
  {
    return '<div class="error">Fehler beim Rendern des Templates: ' . esc_html($e->getMessage()) . '</div>';
  }

  return $template;
}
/**
 * Erweitert die Beschreibung basierend auf dem Tag
 * 
 * @param string $description Die ursprüngliche Beschreibung
 * @param string $tag Der Hashtag
 * @return string Die erweiterte Beschreibung
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

function egj_render_small_calendar_events($arrayOfEvents, $filterTag = '')
{
  $renderedAppointments = array();
  foreach ($arrayOfEvents as $event) {
    $summary = $event->summary ?? '';
    $description = $event->description ?? '';
    $descriptionData = egj_extract_and_remove_hashtags($description);
    $description = $descriptionData['text'];
    $tags = $descriptionData['tags'];

    $startDate = '';
    $startTime = '';

    if (!empty($event->dtstart)) {
      $startDateTime = new DateTime($event->dtstart);
      $startDate = $startDateTime->format('d.m.Y');
      $startTime = $startDateTime->format('H:i');
    }

    // Konvertiere dtend ins deutsche Format
    $endDate = '';
    $endTime = '';
    if (!empty($event->dtend)) {
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
        $summary =  egj_link_text_by_tag($summary, $tag);
      }
    }
    
    if (!$filterTag !== '') {
      $hasFilterTag = false;
      foreach ($tags as $tag) {
        if (in_array($tag, $filterTag)) {
          $hasFilterTag = true;
          break;
        }
      }
      if (!$hasFilterTag) {
        continue; // Überspringe diesen Termin, wenn kein Filter-Tag übereinstimmt
      }
    }

    $renderedAppointment = egj_load_and_render_template('template_appointment_small.html', array(
      'linkText' => $summary,
      'dateTimeInfo' => $renderedDateTimeInfo
    ));

    array_push($renderedAppointments, $renderedAppointment);
  }

  $renderedEvents = egj_load_and_render_template('template_events_small.html', array(
    'appointments' => join(' ', $renderedAppointments),
  ));

  echo $renderedEvents;
}
function egj_render_big_calendar_events($arrayOfEvents)
{
  $renderedAppointments = array();
  foreach ($arrayOfEvents as $event) {
    $summary = $event->summary ?? '';
    $description = $event->description ?? '';
    $location = $event->location ?? '';

    // Hashtags aus der Description extrahieren und entfernen
    $descriptionData = egj_extract_and_remove_hashtags($description);
    $description = $descriptionData['text'];
    $tags = $descriptionData['tags'];

    $startDate = '';
    $startTime = '';

    if (!empty($event->dtstart)) {
      $startDateTime = new DateTime($event->dtstart);
      $startDate = $startDateTime->format('d.m.Y');
      $startTime = $startDateTime->format('H:i');
    }

    // Konvertiere dtend ins deutsche Format
    $endDate = '';
    $endTime = '';
    if (!empty($event->dtend)) {
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

    $renderedAppointment = egj_load_and_render_template('template_appointment_big.html', array(
      'summary' => esc_html($summary),
      'description' => $description,
      'location' => esc_html($location),
      'dateTimeInfo' => $renderedDateTimeInfo,
      'tags' => join(' ', $renderedTags),
    ));

    array_push($renderedAppointments, $renderedAppointment);
  }

  $renderedEvents = egj_load_and_render_template('template_events_big.html', array(
    'appointments' => join(' ', $renderedAppointments),
  ));

  echo $renderedEvents;

  // echo json_encode($arrayOfEvents, JSON_PRETTY_PRINT);
}
function egj_calendar_display_shortcode($atts)
{
  // Attribute mit Defaults
  $attributes = shortcode_atts(array(
    'max_events' => 20,
    'view' => 'normal', // normal, compact
    'filter' => ''
  ), $atts);

  try {
    $ics = getIcsInternal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(null, null);
  } catch (\Exception $e) {
    return '<div class="egj-calendar-error">Fehler beim Laden der Termine</div>';
  }

  $arrayOfEvents = array_slice($arrayOfEvents, 0 , egj_escape($attributes['max_events']));

  // Rendere die Termine
  ob_start();
  if(egj_escape($attributes['view']) === 'compact') {
    egj_render_small_calendar_events($arrayOfEvents, $attributes['filter']);
  } else {
    egj_render_big_calendar_events($arrayOfEvents);
    
  }
  return ob_get_clean();
}

// Registriere Shortcode
add_shortcode('egj_calendar', 'egj_calendar_display_shortcode');

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

function egj_calendar_settings_page()
{

  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  // updatable options  
  $ics_url = get_option($_SESSION['ics_url_option_name']);
  $ics_url_field_name = 'erfindergeist_ics_url_field';

  // read-only options
  $ics_cache = get_option($_SESSION['ics_cache_option_name']);
  $ics_cache_timestamp = get_option($_SESSION['ics_cache_timestamp_option_name']);

  if (!empty($_POST) || wp_verify_nonce(egj_escape($_POST['egj_calendar_nonce_field']), 'egj_calendar_action')) {
    // update ics_url
    if ($_POST[$ics_url_field_name]) {
      $ics_url = $_POST[$ics_url_field_name];
      update_option($_SESSION['ics_url_option_name'], $ics_url);
    }

    if (isset($_POST['clear_cache'])) {
      // clear cache
      delete_option($_SESSION['ics_cache_option_name']);
      delete_option($_SESSION['ics_cache_timestamp_option_name']);
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
      <input type="text" name="<?php echo $ics_url_field_name; ?>" value="<?php echo $ics_url; ?>" size="60">
    </p>

    <p>
      <input type="checkbox" name="clear_cache" > Clear ICS Cache
    </p>

    <p class="submit">
      <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>

  </form>

  <div>
    <h3>ICS Cache Information</h3>
    Timestamp: <?php echo $ics_cache_timestamp ? date('d.m.Y H:i:s', $ics_cache_timestamp) : 'No ics cache timestamp available'; ?>
    
    <span>
      <p>Cache Pretty Print</p>

      <pre>
        <?php 
          if ($ics_cache && $ics_cache != "") {
            $iCal = new ICal();
            $iCal->initString( $ics_cache);
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
