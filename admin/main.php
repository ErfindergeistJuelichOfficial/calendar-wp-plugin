<?php
/**
 * WordPress-Admin-Menü und Settings-Handler für das Erfindergeist Calendar Plugin.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platzhalter für die Hauptmenü-Seite (Untermenüs für Einstellungen nutzen).
 *
 * @return void
 */
function egj_calendar_plugin_options(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sie haben keine ausreichenden Berechtigungen für diese Seite.', 'erfindergeist' ) );
	}
	?>
	<div>
		<h3>Erfindergeist</h3>
		<p><?php esc_html_e( 'Bitte nutze die Untermenüs für Einstellungen.', 'erfindergeist' ); ?></p>
	</div>
	<?php
}

/**
 * Verarbeitet das Settings-Formular und zeigt die Einstellungsseite an.
 *
 * @return void
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
function egj_calendar_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sie haben keine ausreichenden Berechtigungen für diese Seite.', 'erfindergeist' ) );
	}

	$ics_url_field_name  = 'erfindergeist_ics_url_field';
	$ics_url             = get_option( 'egj_ics_url', '' );
	$ics_cache           = get_option( 'egj_ics_cache', '' );
	$ics_cache_timestamp = get_option( 'egj_ics_cache_timestamp', '' );
	$ics_last_error      = get_option( 'egj_ics_last_error', [] );
	$notify_on_error     = get_option( 'egj_notify_on_error', '1' );
	$settings_saved      = false;

	if ( ! empty( $_POST ) && isset( $_POST['egj_calendar_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['egj_calendar_nonce_field'] ) ), 'egj_calendar_action' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird direkt in dieser Zeile geprüft.
		if ( isset( $_POST[ $ics_url_field_name ] ) ) {
			$ics_url = esc_url_raw( wp_unslash( $_POST[ $ics_url_field_name ] ) );
			update_option( 'egj_ics_url', $ics_url );
		}

		$notify_on_error = isset( $_POST['egj_notify_on_error'] ) ? '1' : '0';
		update_option( 'egj_notify_on_error', $notify_on_error );

		// Erlaubt nach dem Speichern sofort einen neuen Abrufversuch.
		delete_transient( 'egj_ics_fetch_failed' );

		if ( isset( $_POST['clear_cache'] ) ) {
			delete_option( 'egj_ics_cache' );
			delete_option( 'egj_ics_cache_timestamp' );
			$ics_cache           = '';
			$ics_cache_timestamp = '';
		}

		$settings_saved = true;
	}

	include plugin_dir_path( __FILE__ ) . 'partials/tab-settings.php';
}

/**
 * Registriert das Admin-Menü und das Untermenü.
 *
 * @return void
 */
function egj_calendar_menu(): void {
	if ( empty( $GLOBALS['admin_page_hooks']['erfindergeist'] ) ) {
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
