<?php
/**
 * Hilfsfunktionen für das Erfindergeist Calendar Plugin.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sendet Benachrichtigungen an alle Administrator-Benutzer.
 *
 * @param string $message Die zu sendende Nachricht.
 * @param string $subject Betreff der E-Mail.
 * @return bool True wenn mindestens eine Mail gesendet wurde, sonst false.
 */
function egj_send_notification_to_admins( string $message, string $subject = 'Erfindergeist Calendar Notification' ): bool {
	$admins       = get_users( array( 'role' => 'administrator' ) );
	$current_time = time();
	$message     .= " \n ------------------ \n ";
	$message     .= ' Server dateTime: ' . wp_date( 'd.m.Y H:i:s', $current_time ) . " \n ";
	$message     .= ' Site URL: ' . get_site_url() . " \n ";
	$sent         = false;

	foreach ( $admins as $admin ) {
		$result = wp_mail( $admin->user_email, $subject, $message );
		if ( $result ) {
			$sent = true;
		}
	}

	return $sent;
}

/**
 * Escaped einen String über sanitize_text_field().
 *
 * @param string $input Der zu escapende String.
 * @return string Escapeter String.
 */
function egj_escape( string $input ): string {
	return sanitize_text_field( $input );
}
