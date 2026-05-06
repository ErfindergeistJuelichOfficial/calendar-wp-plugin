<?php
/**
 * Style-Enqueuing für das Erfindergeist Calendar Plugin.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert Bootstrap und das Plugin-Stylesheet im WordPress-Frontend.
 *
 * @return void
 */
function egj_calendar_enqueue_styles(): void {
	wp_enqueue_style(
		'bootstrap',
		plugins_url( '/', __FILE__ ) . 'bootstrap.min.css',
		array(),
		'5.3.3'
	);

	wp_enqueue_style(
		'calender-style',
		plugins_url( '/', __FILE__ ) . 'calender.css',
		array( 'bootstrap' ),
		EGJ_CALENDAR_VERSION
	);
}

add_action( 'wp_enqueue_scripts', 'egj_calendar_enqueue_styles' );
