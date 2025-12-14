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
