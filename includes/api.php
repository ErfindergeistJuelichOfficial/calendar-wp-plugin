<?php
/**
 * REST-API-Endpunkte und ICS-Datenabruf für das Erfindergeist Calendar Plugin.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ICal\ICal;

/**
 * Lädt ICS-Daten von einer URL.
 *
 * @param string $ics_url Die URL des ICS-Feeds.
 * @return string|\WP_Error ICS-Inhalt als String, oder WP_Error bei Fehler.
 */
function egj_calendar_get_ics_from_url( string $ics_url ): string|\WP_Error {
	if ( ! filter_var( $ics_url, FILTER_VALIDATE_URL ) ) {
		return new \WP_Error( 'egj_ics_invalid_url', 'Ungültiges ICS-URL-Format' );
	}

	$scheme = wp_parse_url( $ics_url, PHP_URL_SCHEME );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return new \WP_Error( 'egj_ics_invalid_scheme', 'ICS-URL muss HTTP oder HTTPS verwenden' );
	}

	// SSRF-Schutz: wp_safe_remote_get() sperrt private/lokale Adressen, auch nach Redirects.
	$response = wp_safe_remote_get(
		$ics_url,
		array(
			'timeout'             => 7,
			'limit_response_size' => 5 * MB_IN_BYTES,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status_code ) {
		return new \WP_Error( 'egj_ics_http_status', 'ICS-URL antwortet mit HTTP-Status ' . $status_code );
	}

	$body = wp_remote_retrieve_body( $response );
	// Nur gültige ICS-Daten akzeptieren, damit Fehlerseiten nicht den Cache überschreiben.
	if ( ! str_starts_with( ltrim( $body, "\xEF\xBB\xBF \t\r\n" ), 'BEGIN:VCALENDAR' ) ) {
		return new \WP_Error( 'egj_ics_invalid_content', 'Antwort der ICS-URL ist keine gültige ICS-Datei' );
	}

	return $body;
}

/**
 * Gibt ICS-Daten mit Caching zurück (Cache-Lebensdauer: 1 Stunde).
 *
 * Der Cache wird nie zeitbasiert gelöscht: Schlägt der Abruf fehl, wird der letzte
 * gültige Stand weiter ausgeliefert. Nach einem Fehler wird 10 Minuten lang kein
 * neuer Abruf versucht.
 *
 * @return string|null ICS-Inhalt als String, oder null bei Fehler.
 */
function egj_calendar_get_ics_cached(): string|null {
	$cache_lifetime      = HOUR_IN_SECONDS;
	$ics_url             = (string) get_option( 'egj_ics_url', '' );
	$ics_cache           = (string) get_option( 'egj_ics_cache', '' );
	$ics_cache_timestamp = (int) get_option( 'egj_ics_cache_timestamp', 0 );
	$current_time        = time();

	$cached_ics        = '' !== $ics_cache ? $ics_cache : null;
	$has_cache_expired = ( $current_time - $ics_cache_timestamp ) > $cache_lifetime;

	if ( ! $has_cache_expired || get_transient( 'egj_ics_fetch_failed' ) ) {
		return $cached_ics;
	}

	$ics = egj_calendar_get_ics_from_url( $ics_url );

	if ( is_wp_error( $ics ) ) {
		set_transient( 'egj_ics_fetch_failed', 1, 10 * MINUTE_IN_SECONDS );
		update_option(
			'egj_ics_last_error',
			array(
				'time'    => $current_time,
				'message' => $ics->get_error_message(),
			),
			false
		);

		$cache_info = $cached_ics ? 'Es wird weiterhin der vorhandene Cache ausgeliefert.' : 'Es ist kein Cache vorhanden!';
		egj_send_notification_to_admins( "ICS-Abruf fehlgeschlagen. $cache_info \n ICS_URL: $ics_url \n Details siehe Einstellungsseite des Plugins." );
		return $cached_ics;
	}

	update_option( 'egj_ics_cache', $ics, false );
	update_option( 'egj_ics_cache_timestamp', $current_time, false );
	delete_option( 'egj_ics_last_error' );

	return $ics;
}

/**
 * REST-API-Endpunkt: Gibt den rohen ICS-Feed zurück.
 *
 * @return void
 */
function egj_calendar_api_ics(): void {
	$ics = egj_calendar_get_ics_cached();

	if ( ! $ics ) {
		status_header( 500 );
		echo 'Fehler: Kalenderdaten konnten nicht abgerufen werden';
		exit;
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="erfindergeist.ics"' );
	}
	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * REST-API-Endpunkt: Gibt alle Kalender-Events als JSON zurück.
 *
 * @return \WP_REST_Response|\WP_Error
 */
function egj_calendar_api_events(): \WP_REST_Response|\WP_Error {
	try {
		$ics   = egj_calendar_get_ics_cached();
		$i_cal = new ICal();
		$i_cal->initString( $ics );
		$events = $i_cal->eventsFromRange( null, null );

		$response = new \WP_REST_Response( $events );
		$response->set_status( 200 );
		return $response;
	} catch ( \Exception $e ) {
		egj_send_notification_to_admins( 'Fehler beim Parsen der ICS-Daten' );
		return new \WP_Error( 'rest_custom_error', 'Fehler beim Parsen der ICS-Daten', array( 'status' => 500 ) );
	}
}

/**
 * REST-API-Endpunkt: Gibt das erste Ereignis von morgen zurück.
 *
 * @return \WP_REST_Response
 */
function egj_calendar_api_tomorrow(): \WP_REST_Response {
	$ics   = egj_calendar_get_ics_cached();
	$i_cal = new ICal();
	$i_cal->initString( $ics );
	$events = $i_cal->eventsFromRange(
		wp_date( 'Y-m-d 00:00:00', strtotime( 'tomorrow' ) ),
		wp_date( 'Y-m-d 23:59:59', strtotime( 'tomorrow' ) )
	);

	if ( is_array( $events ) && count( $events ) > 0 ) {
		$event          = $events[0];
		$tomorrow_event = new TomorrowEvent();

		$tomorrow_event->summary     = $event->summary ?? '';
		$tomorrow_event->description = $event->description ?? '';
		$tomorrow_event->location    = $event->location ?? '';

		if ( isset( $event->dtstart ) ) {
			$start_date_time           = new \DateTime( $event->dtstart );
			$tomorrow_event->starttime = $start_date_time->format( 'd.m.Y H:i' );
		}

		if ( isset( $event->dtend ) ) {
			$end_date_time           = new \DateTime( $event->dtend );
			$tomorrow_event->endtime = $end_date_time->format( 'd.m.Y H:i' );
		}

		$response = new \WP_REST_Response( $tomorrow_event );
		$response->set_status( 200 );
		return $response;
	}

	$response = new \WP_REST_Response();
	$response->set_status( 404 );
	return $response;
}

// REST-API-Routen registrieren: https://<DOMAIN>/wp-json/erfindergeist/v2/.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'erfindergeist/v2',
			'/ics',
			array(
				'methods'             => 'GET',
				'callback'            => 'egj_calendar_api_ics',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'erfindergeist/v2',
			'/events',
			array(
				'methods'             => 'GET',
				'callback'            => 'egj_calendar_api_events',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'erfindergeist/v2',
			'/tomorrow',
			array(
				'methods'             => 'GET',
				'callback'            => 'egj_calendar_api_tomorrow',
				'permission_callback' => '__return_true',
			)
		);
	}
);
