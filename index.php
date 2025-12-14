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
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require_once 'helpers.php';
require_once 'vars.php';
require_once 'styles.php';
require_once 'apis.php';
require_once 'ICal.php';
use ICal\ICal;

function egj_render_calendar_events($arrayOfEvents, $attributes)
{
  echo '<div class="container p-0 text-dark">';
  $tags = array();
   foreach ($arrayOfEvents as $event) {
    $summary = $event->summary || '';
    $description = $event->description || '';
    $location = $event->location || '';
    
    
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

        echo ' <div class="row">';
          echo '<div class="col-1" style="font-size: 3rem">{{weekDayShort}}</div>';
          echo '<div class="col">'.$summary.', '.$description.', '.$location.' {{startDate}}, {{startTime}}, ';
           echo'{{endDate}}, {{endTime}}, {{weekDayShort}}';

            echo '<div>';
            foreach ($tags as $tag) {
              echo '<span class="badge text-bg-primary">' . $tag . '</span>';
            }
            echo '</div>';
          echo '</div>';
        echo '</div>';
  }
  echo '</div>';
}
function egj_calendar_display_shortcode($atts) {
  // Attribute mit Defaults
  $attributes = shortcode_atts(array(
    'max_events' => 20,
    'view' => 'list' // list, compact
  ), $atts);

  try {
    $ics = getIcsInternal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(null, null);
  } catch (\Exception $e) {
    return '<div class="egj-calendar-error">Fehler beim Laden der Termine</div>';
  }
  
  // Rendere die Termine
  ob_start();
  egj_render_calendar_events($arrayOfEvents, $attributes);
  return ob_get_clean();
}

// Registriere Shortcode
add_shortcode('egj_calendar', 'egj_calendar_display_shortcode');

function egj_calendar_plugin_options() {

	if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

  ?>
    <div>
      <h3>Erfindergeist</h3>
      <p>Please use Submenus for Options</p>
    </div>
  <?php
}

function egj_calendar_settings_page() {

  if (!current_user_can('manage_options'))
  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

  // updatable options  
  $ics_url = get_option( $_SESSION['ics_url_option_name']);
  $ics_url_field_name = 'erfindergeist_ics_url_field';

  // read-only options
  $ics_cache = get_option($_SESSION['ics_cache_option_name']);
  $ics_cache_timestamp = get_option($_SESSION['ics_cache_timestamp_option_name']);

  if ( !empty($_POST) || wp_verify_nonce(egj_escape($_POST['egj_calendar_nonce_field']),'egj_calendar_action') ) {
    // update ics_url
    if( $_POST[ $ics_url_field_name ])
    {
      $ics_url = $_POST[ $ics_url_field_name ];
      update_option( $_SESSION['ics_url_option_name'], $ics_url );
    }

    // Put a "settings saved" message on the screen
    ?>
      <div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
    <?php
  }

  
  echo '<div class="wrap">';
  echo "<h2>" . __( 'Erfindergeist Calendar Settings', 'menu-test' ) . "</h2>";
?>

  <form name="form1" method="post" action="">

  <?php wp_nonce_field('egj_calendar_action','egj_calendar_nonce_field'); ?>

  <p><?php _e("Ics Url:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $ics_url_field_name; ?>" value="<?php echo $ics_url; ?>" size="60">
  </p><hr />

  <hr />

  <p class="submit">
  <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

  </form>

  <div>
    <h3>ICS Cache Information</h3>
    <p>Cached ICS: </p><div><?php echo $ics_cache ? $ics_cache : 'No ics cache available'; ?></div>
    <p>Last Cache Timestamp: <?php echo $ics_cache_timestamp ? date('d.m.Y H:i:s', $ics_cache_timestamp) : 'No ics cache timestamp available'; ?></p>
  </div>

<?php
}

function egj_calendar_menu() {
  if ( empty ( $GLOBALS['admin_page_hooks']['erfindergeist'] ) ) {
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

  add_action( 'admin_menu', 'egj_calendar_menu' );
