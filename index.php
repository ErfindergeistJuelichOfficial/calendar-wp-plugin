<?php

/**
 * Plugin Name: Erfindergeist Calendar
 * Description: Calendar WordPress Plugin from Erfindergeist Jülich e.V.
 * Author: Lars 'vreezy' Eschweiler
 * Author URI: https://www.erfindergeist.org
 * Contributor: Erfindergeist Jülich e.V.
 * Version: 2.1.0
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
require_once 'vars.php';

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

  $erfindergeist_ics_url_option_name = $_SESSION['erfindergeist_ics_url_option_name'];
  $erfindergeist_ics_url = get_option( $erfindergeist_ics_url_option_name );
  $erfindergeist_ics_url_field_name = 'erfindergeist_ics_url_field';

  $erfindergeist_feature_switch_option_name = $_SESSION['erfindergeist_feature_switch_option_name'];
  $erfindergeist_feature_switch = get_option( $erfindergeist_feature_switch_option_name );
  $erfindergeist_feature_switch_field_name = 'erfindergeist_feature_switch_field';

  $apikey_opt_name = 'g_Calendar_apikey';
  $google_calendar_id_opt_name = 'g_Calendar_id';

  $apikey_field_name = 'apikey';
  $google_calendar_id_field_name = 'gcid';
  
  $apikey_opt_val = get_option( $apikey_opt_name );
  $google_calendar_id_opt_val = get_option( $google_calendar_id_opt_name );

  if ( !empty($_POST) || wp_verify_nonce(egj_escape($_POST['egj_calendar_nonce_field']),'egj_calendar_action') ) {
    if ( $_POST[ $apikey_field_name ]) {
      $apikey_opt_val = $_POST[ $apikey_field_name ];
      update_option( $apikey_opt_name, $apikey_opt_val );
    }

    if ( $_POST[ $google_calendar_id_field_name ]) {
      $google_calendar_id_opt_val = $_POST[ $google_calendar_id_field_name ];
      update_option( $google_calendar_id_opt_name, $google_calendar_id_opt_val );
    }
    
    if( $_POST[ $erfindergeist_ics_url_field_name ])
    {
      $erfindergeist_ics_url = $_POST[ $erfindergeist_ics_url_field_name ];
      update_option( $erfindergeist_ics_url_option_name, $erfindergeist_ics_url );
    }

    if( $_POST[ $erfindergeist_feature_switch_field_name ])
    {
      $erfindergeist_feature_switch = $_POST[ $erfindergeist_feature_switch_field_name ];
      update_option( $erfindergeist_feature_switch_option_name, $erfindergeist_feature_switch );
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

  <p><?php _e("Apikey:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $apikey_field_name; ?>" value="<?php echo $apikey_opt_val; ?>" size="40">
  find the apikey in the <a href="https://console.cloud.google.com/apis/api/calendar-json.googleapis.com" target="_blank" rel="nooper">google console</a>.
  </p><hr />

  <p><?php _e("google calendar id:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $google_calendar_id_field_name; ?>" value="<?php echo $google_calendar_id_opt_val; ?>" size="60">
  </p><hr />

  <p><?php _e("Ics Url:", 'menu-test' ); ?>
  <input type="text" name="<?php echo $erfindergeist_ics_url_field_name; ?>" value="<?php echo $erfindergeist_ics_url; ?>" size="60">
  </p><hr />

  <label for="feature_switch"><?php _e("Feature Switch:", 'menu-test' ); ?></label>
  <select id="feature_switch" name="<?php echo $erfindergeist_feature_switch_field_name; ?>">
    <option value="google" <?php if ($erfindergeist_feature_switch == 'google') echo 'selected'; ?>">Google</option>
    <option value="nextcloud" <?php if ($erfindergeist_feature_switch == 'nextcloud') echo 'selected'; ?>>Nextcloud</option>
  </select>
  <hr />

  <p class="submit">
  <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

  </form>
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
