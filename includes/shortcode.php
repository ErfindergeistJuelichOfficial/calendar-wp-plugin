<?php
/**
 * WordPress-Shortcode [egj_calendar] für das Erfindergeist Calendar Plugin.
 *
 * Verwendung: [egj_calendar max_events="20" view="normal" tag_filter="#Repaircafe"]
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ICal\ICal;

/**
 * Shortcode-Handler für die Kalenderanzeige.
 *
 * @param array $raw_attributes Shortcode-Attribute aus WordPress.
 * @return string Gerendertes HTML.
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
function egj_calendar_display_shortcode( array $raw_attributes ): string {
	$attributes = shortcode_atts(
		array(
			'max_events' => 20,
			'view'       => 'normal',
			'tag_filter' => '',
			'test_ics'   => '',
		),
		$raw_attributes
	);

	$max_events = absint( $attributes['max_events'] );
	if ( $max_events < 1 ) {
		$max_events = 1;
	} elseif ( $max_events > 100 ) {
		$max_events = 100;
	}

	$allowed_views = array( 'normal', 'compact' );
	$view          = in_array( $attributes['view'], $allowed_views, true ) ? $attributes['view'] : 'normal';

	$tag_filter = '';
	if ( ! empty( $attributes['tag_filter'] ) ) {
		$tag = sanitize_text_field( $attributes['tag_filter'] );
		if ( preg_match( '/^#\w+$/u', $tag ) ) {
			$tag_filter = $tag;
		}
	}

	try {
		$ics = null;

		// test_ics: Lädt eine lokale .ics-Datei aus dem test/-Verzeichnis (nur für Admins).
		if ( ! empty( $attributes['test_ics'] ) && current_user_can( 'manage_options' ) ) {
			$filename  = sanitize_file_name( $attributes['test_ics'] );
			$test_path = plugin_dir_path( __DIR__ ) . 'test/' . $filename;

			if ( file_exists( $test_path ) && 'ics' === pathinfo( $test_path, PATHINFO_EXTENSION ) ) {
				$ics = file_get_contents( $test_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}

		if ( ! $ics ) {
			$ics = egj_calendar_get_ics_cached();
		}

		$i_cal = new ICal();
		$i_cal->initString( $ics );
		$array_of_events = $i_cal->eventsFromRange( null, null );
	} catch ( \Exception $e ) {
		return '<div class="egj-calendar-error">Termine konnten nicht geladen werden</div>';
	}

	$array_of_events = array_slice( $array_of_events, 0, $max_events );

	ob_start();
	if ( 'compact' === $view ) {
		egj_render_compact_calendar_events( $array_of_events, $tag_filter );
	} else {
		egj_render_calendar_events( $array_of_events );
	}
	return (string) ob_get_clean();
}

add_shortcode( 'egj_calendar', 'egj_calendar_display_shortcode' );
