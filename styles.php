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
 * Geladen werden die Styles nur auf Seiten, die den Shortcode enthalten. Für andere
 * Einbindungen (z. B. Widgets) lädt der Shortcode-Handler sie selbst nach.
 *
 * @return void
 */
function egj_calendar_enqueue_styles(): void {
	wp_register_style(
		'egj-calendar-bootstrap',
		plugins_url( '/', __FILE__ ) . 'bootstrap.min.css',
		array(),
		'5.3.3'
	);

	wp_register_style(
		'egj-calendar-style',
		plugins_url( '/', __FILE__ ) . 'calender.css',
		array( 'egj-calendar-bootstrap' ),
		EGJ_CALENDAR_VERSION
	);

	$post = get_post();
	if ( is_singular() && $post && has_shortcode( $post->post_content, 'egj_calendar' ) ) {
		wp_enqueue_style( 'egj-calendar-style' );
	}
}

add_action( 'wp_enqueue_scripts', 'egj_calendar_enqueue_styles' );
