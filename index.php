<?php

/**
 * Plugin Name: Erfindergeist Calendar
 * Description: Calendar WordPress Plugin from Erfindergeist Jülich e.V.
 * Author: Lars 'vreezy' Eschweiler
 * Author URI: https://www.erfindergeist.org
 * Contributor: Erfindergeist Jülich e.V.
 * Version: 2.0.0
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

require_once 'styles.php';
require_once 'apis.php';

function egj_calendar_plugin_options() {

  //must check that the user has the required capability
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


  //must check that the user has the required capability
  if (!current_user_can('manage_options'))
  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

  // variables for the field and option names
  $apikey_opt_name = 'g_Calendar_apikey';
  $google_calendar_id_opt_name = 'g_Calendar_id';

  $apikey_field_name = 'apikey';
  $google_calendar_id_field_name = 'gcid';

  // Read in existing option value from database
  $apikey_opt_val = get_option( $apikey_opt_name );
  $google_calendar_id_opt_val = get_option( $google_calendar_id_opt_name );

  // See if the user has posted us some information
  // If they did, this hidden field will be set to 'Y'
  if ( !empty($_POST) || wp_verify_nonce(egj_escape($_POST['egj_calendar_nonce_field']),'egj_calendar_action') ) {
    // Read their posted value
    $apikey_opt_val = $_POST[ $apikey_field_name ];
    $google_calendar_id_opt_val = $_POST[ $google_calendar_id_field_name ];

    // Save the posted value in the database
    update_option( $apikey_opt_name, $apikey_opt_val );
    update_option( $google_calendar_id_opt_name, $google_calendar_id_opt_val );

    // Put a "settings saved" message on the screen
    ?>
      <div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
    <?php
  }

  

  // Now display the settings editing screen

  echo '<div class="wrap">';

  // header

  echo "<h2>" . __( 'Erfindergeist Calendar Settings', 'menu-test' ) . "</h2>";

  // settings form

?>

  <form name="form1" method="post" action="">

  <?php wp_nonce_field('egj_calendar_action','egj_calendar_nonce_field'); ?>

  <p><?php _e("Apikey:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $apikey_field_name; ?>" value="<?php echo $apikey_opt_val; ?>" size="40">
  find the apikey in the <a href="https://console.cloud.google.com/apis/api/calendar-json.googleapis.com" target="_blank" rel="nooper">google console</a>.
  </p><hr />

  <p><?php _e("google calendar id:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $google_calendar_id_field_name; ?>" value="<?php echo $google_calendar_id_opt_val; ?>" size="60">
  </p><hr />

  <p class="submit">
  <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

  </form>
  </div>

<?php
}
  /** Step 1. */
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

  /** Step 2 (from text above). */
  add_action( 'admin_menu', 'egj_calendar_menu' );
