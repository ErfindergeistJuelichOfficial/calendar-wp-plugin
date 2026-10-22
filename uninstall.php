<?php
/**
 * Entfernt alle Plugin-Daten beim Löschen des Plugins im WordPress-Admin.
 *
 * Läuft nur beim Löschen, nicht beim Deaktivieren oder bei einem Deploy.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'egj_ics_url' );
delete_option( 'egj_ics_cache' );
delete_option( 'egj_ics_cache_timestamp' );
delete_option( 'egj_ics_last_error' );
delete_option( 'egj_notify_on_error' );
delete_transient( 'egj_ics_fetch_failed' );
