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
 * @return string|null ICS-Inhalt als String, oder null bei Fehler.
 */
function egj_calendar_get_ics_from_url( string $ics_url ): string|null {
	try {
		if ( ! filter_var( $ics_url, FILTER_VALIDATE_URL ) ) {
			egj_send_notification_to_admins( 'Ungültiges ICS-URL-Format' );
			return null;
		}

		$scheme = wp_parse_url( $ics_url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			egj_send_notification_to_admins( 'ICS-URL muss HTTP oder HTTPS verwenden' );
			return null;
		}

		// SSRF-Schutz: Private und reservierte IP-Bereiche sperren.
		$host = wp_parse_url( $ics_url, PHP_URL_HOST );
		if ( $host ) {
			$ip = gethostbyname( $host );
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				egj_send_notification_to_admins( 'ICS-URL zeigt auf private/lokale IP-Adresse' );
				return null;
			}
		}

		$response = wp_remote_get(
			$ics_url,
			array( 'timeout' => 7 )
		);

		if ( is_wp_error( $response ) ) {
			egj_send_notification_to_admins( 'Fehler beim Abrufen der ICS-Datei: ' . $response->get_error_message() );
			return null;
		}

		return wp_remote_retrieve_body( $response );
	} catch ( \Exception $e ) {
		egj_send_notification_to_admins( 'Fehler beim ICS-Abruf: ' . $e->getMessage() );
		return null;
	}
}

/**
 * Gibt ICS-Daten mit Caching zurück (Cache-Lebensdauer: 1 Stunde).
 *
 * @return string|null ICS-Inhalt als String, oder null bei Fehler.
 */
function egj_calendar_get_ics_cached(): string|null {
	$cache_lifetime      = 3600;
	$ics_url             = get_option( 'egj_ics_url' );
	$ics_cache           = get_option( 'egj_ics_cache' );
	$ics_cache_timestamp = get_option( 'egj_ics_cache_timestamp' );
	$current_time        = time();

	$is_cache_valid    = $ics_cache && '' !== $ics_cache;
	$has_cache_expired = $ics_cache_timestamp && ( $current_time - $ics_cache_timestamp ) > $cache_lifetime;

	$ics = null;
	if ( ! $ics_cache_timestamp || $has_cache_expired ) {
		try {
			$ics = egj_calendar_get_ics_from_url( $ics_url );
		} catch ( \Exception $e ) {
			egj_send_notification_to_admins( "Fehler beim ICS-Abruf: $ics_url" );
			$ics = null;
		}
	}

	if ( ! $ics ) {
		if ( $is_cache_valid ) {
			$ics = $ics_cache;
		} else {
			egj_send_notification_to_admins( "ICS-Abruf fehlgeschlagen, kein Cache vorhanden. ICS_URL: $ics_url" );
		}
	} else {
		update_option( 'egj_ics_cache', $ics );
		update_option( 'egj_ics_cache_timestamp', $current_time );
	}

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
