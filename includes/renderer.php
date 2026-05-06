<?php
/**
 * Template-Rendering und Event-Darstellung für das Erfindergeist Calendar Plugin.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lädt ein PHP-Template-Partial und gibt den gerenderten HTML-String zurück.
 *
 * @param string $template Dateiname des Templates relativ zum templates/-Ordner.
 * @param array  $vars     Assoziatives Array der Template-Variablen.
 * @return string Der gerenderte HTML-Code.
 */
function egj_calendar_render_template( string $template, array $vars = [] ): string {
	$template_path = plugin_dir_path( __DIR__ ) . 'templates/' . $template;

	if ( ! file_exists( $template_path ) ) {
		return '<div class="error">Template nicht gefunden: ' . esc_html( $template ) . '</div>';
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $vars, EXTR_SKIP );
	ob_start();
	include $template_path;
	return (string) ob_get_clean();
}

/**
 * Extrahiert Hashtags aus einem Text und entfernt diese.
 *
 * @param string $text Der Text mit Hashtags.
 * @return array{text: string, tags: string[]} Array mit 'text' (bereinigt) und 'tags' (gefundene Hashtags).
 */
function egj_extract_and_remove_hashtags( string $text ): array {
	preg_match_all( '/#\w+/u', $text, $matches );
	$tags         = $matches[0];
	$cleaned_text = preg_replace( '/#\w+/u', '', $text );
	$cleaned_text = preg_replace( '/\s+/', ' ', $cleaned_text );
	$cleaned_text = trim( $cleaned_text );

	return array(
		'text' => $cleaned_text,
		'tags' => $tags,
	);
}

/**
 * Ergänzt die Beschreibung anhand des Tags mit zusätzlichem HTML-Inhalt.
 *
 * @param string $description Die ursprüngliche Beschreibung.
 * @param string $tag         Der Hashtag.
 * @return string Die erweiterte Beschreibung.
 */
function egj_extend_description_by_tag( string $description, string $tag ): string {
	$tag_html_map = array(
		'#Repaircafe'      => '<p>Alle Informationen zum Repair Cafe findest du auf der <a href="https://repaircafe.erfindergeist.org">Repair Cafe Seite</a>.</p>',
		'#OffeneWerkstatt' => '<p>Alle Informationen zur Offenen Werkstatt findest du auf der <a href="https://werkstatt.erfindergeist.org">Offene Werkstatt Seite</a>.</p>',
		'#KreativTag'      => '<p>Alle Informationen zum KreativTag findest du auf der <a href="https://kreativ-tag.erfindergeist.org">KreativTag Seite</a>.</p>',
		'#Mobilitaetstag'  => '<p>Alle Informationen zum Mobilitätstag findest du auf der <a href="/mobilitaetstag">Mobilitätstag Seite</a>.</p>',
		'#Stadtbücherei'   => '<div class="bd-callout">Achtung! heute findest du uns in der <a href="https://buecherei.juelich.de/" target="_blank" rel="noopener noreferrer">Stadtbücherei Jülich</a>.</div>',
		'#Extern'          => '<div class="bd-callout">Achtung! Dieser Termin findet nicht in unseren Räumlichkeiten statt. Achte auf die Adresse im Standortfeld.</div>',
		'#Stammtisch'      => '<p>Lockerer Austausch für Mitglieder, bringt eure aktuellen Ideen oder Prototypen mit und lasst uns in entspannter Runde fachsimpeln.</p>',
	);

	if ( array_key_exists( $tag, $tag_html_map ) ) {
		$description .= $tag_html_map[ $tag ];
	}

	return $description;
}

/**
 * Verlinkt den Summary-Text anhand des Tags.
 *
 * @param string $summary Der Event-Titel.
 * @param string $tag     Der Hashtag.
 * @return string Summary-Text, ggf. als HTML-Link.
 */
function egj_link_text_by_tag( string $summary, string $tag ): string {
	$tag_link_map = array(
		'#Repaircafe'      => 'https://repaircafe.erfindergeist.org',
		'#OffeneWerkstatt' => 'https://werkstatt.erfindergeist.org',
		'#KreativTag'      => 'https://kreativ-tag.erfindergeist.org',
		'#Mobilitaetstag'  => '/mobilitaetstag',
	);

	if ( array_key_exists( $tag, $tag_link_map ) ) {
		return '<a href="' . esc_url( $tag_link_map[ $tag ] ) . '">' . esc_html( $summary ) . '</a>';
	}

	return $summary;
}

/**
 * Rendert Kalender-Events in der Kompaktansicht.
 *
 * @param array  $array_of_events Array von Kalender-Event-Objekten.
 * @param string $tag_filter      Optionaler Hashtag-Filter.
 * @return void Gibt HTML direkt aus.
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
function egj_render_compact_calendar_events( array $array_of_events, string $tag_filter ): void {
	$rendered_appointments = array();

	foreach ( $array_of_events as $event ) {
		$summary     = $event->summary ?? '';
		$location    = $event->location ?? '';
		$description = $event->description ?? '';

		$description_data = egj_extract_and_remove_hashtags( $description );
		$tags             = $description_data['tags'];

		$start_date = '';
		$start_time = '';
		if ( isset( $event->dtstart ) ) {
			$start_date_time = new \DateTime( $event->dtstart );
			$start_date      = $start_date_time->format( 'd.m.Y' );
			$start_time      = $start_date_time->format( 'H:i' );
		}

		$end_date = '';
		$end_time = '';
		if ( isset( $event->dtend ) ) {
			$end_date_time = new \DateTime( $event->dtend );
			$end_date      = $end_date_time->format( 'd.m.Y' );
			$end_time      = $end_date_time->format( 'H:i' );
		}

		if ( $end_date === $start_date ) {
			$rendered_date_time_info = egj_calendar_render_template(
				'same-day.php',
				array(
					'start_date' => $start_date,
					'start_time' => $start_time,
					'end_time'   => $end_time,
				)
			);
		} else {
			$rendered_date_time_info = egj_calendar_render_template(
				'several-days.php',
				array(
					'start_date' => $start_date,
					'start_time' => $start_time,
					'end_date'   => $end_date,
					'end_time'   => $end_time,
				)
			);
		}

		$linked_summary = null;
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( null === $linked_summary ) {
					$result = egj_link_text_by_tag( $summary, $tag );
					if ( $result !== $summary ) {
						$linked_summary = $result;
					}
				}
			}
		}
		$resolved_summary = $linked_summary ?? esc_html( $summary );

		$has_filter_tag = false;
		if ( '' !== $tag_filter ) {
			foreach ( $tags as $tag ) {
				if ( $tag === $tag_filter ) {
					$has_filter_tag = true;
					break;
				}
			}
			if ( ! $has_filter_tag ) {
				continue;
			}
		}

		if ( $has_filter_tag ) {
			$rendered_appointment = egj_calendar_render_template(
				'appointment-compact-filtered.php',
				array(
					'location'       => $location,
					'date_time_info' => $rendered_date_time_info,
				)
			);
		} else {
			$rendered_appointment = egj_calendar_render_template(
				'appointment-compact.php',
				array(
					'link_text'      => $resolved_summary,
					'date_time_info' => $rendered_date_time_info,
				)
			);
		}

		$rendered_appointments[] = $rendered_appointment;
	}

	$rendered_events = egj_calendar_render_template(
		'events-compact.php',
		array( 'appointments' => implode( ' ', $rendered_appointments ) )
	);

	echo $rendered_events; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Rendert Kalender-Events in der Normalansicht (vollständige Details).
 *
 * @param array $array_of_events Array von Kalender-Event-Objekten.
 * @return void Gibt HTML direkt aus.
 */
function egj_render_calendar_events( array $array_of_events ): void {
	$rendered_appointments = array();

	foreach ( $array_of_events as $event ) {
		$summary     = $event->summary ?? '';
		$description = $event->description ?? '';
		$location    = $event->location ?? '';

		$description_data = egj_extract_and_remove_hashtags( $description );
		$description      = esc_html( $description_data['text'] );
		$tags             = $description_data['tags'];

		$start_date = '';
		$start_time = '';
		if ( isset( $event->dtstart ) ) {
			$start_date_time = new \DateTime( $event->dtstart );
			$start_date      = $start_date_time->format( 'd.m.Y' );
			$start_time      = $start_date_time->format( 'H:i' );
		}

		$end_date = '';
		$end_time = '';
		if ( isset( $event->dtend ) ) {
			$end_date_time = new \DateTime( $event->dtend );
			$end_date      = $end_date_time->format( 'd.m.Y' );
			$end_time      = $end_date_time->format( 'H:i' );
		}

		if ( $end_date === $start_date ) {
			$rendered_date_time_info = egj_calendar_render_template(
				'same-day.php',
				array(
					'start_date' => $start_date,
					'start_time' => $start_time,
					'end_time'   => $end_time,
				)
			);
		} else {
			$rendered_date_time_info = egj_calendar_render_template(
				'several-days.php',
				array(
					'start_date' => $start_date,
					'start_time' => $start_time,
					'end_date'   => $end_date,
					'end_time'   => $end_time,
				)
			);
		}

		$rendered_tags = array();
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$rendered_tags[] = egj_calendar_render_template( 'tag.php', array( 'tag' => $tag ) );
				$description     = egj_extend_description_by_tag( $description, $tag );
			}
		}

		$rendered_appointments[] = egj_calendar_render_template(
			'appointment.php',
			array(
				'summary'        => $summary,
				'description'    => $description,
				'location'       => $location,
				'location_url'   => rawurlencode( $location ),
				'date_time_info' => $rendered_date_time_info,
				'tags'           => implode( ' ', $rendered_tags ),
			)
		);
	}

	$rendered_events = egj_calendar_render_template(
		'events.php',
		array( 'appointments' => implode( ' ', $rendered_appointments ) )
	);

	echo $rendered_events; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
