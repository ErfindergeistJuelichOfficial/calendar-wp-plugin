<?php
/**
 * Admin-Partial: Calendar-Einstellungsformular.
 *
 * Erwartet folgende Variablen aus egj_calendar_settings_page():
 *   string  $ics_url_field_name   Feldname für die ICS-URL
 *   string  $ics_url              Aktuelle ICS-URL
 *   string  $ics_cache            Aktueller ICS-Cache-Inhalt
 *   string  $ics_cache_timestamp  Unix-Timestamp des Caches
 *   array   $ics_last_error       Letzter fehlgeschlagener Abruf (time, message) oder leer
 *   string  $notify_on_error      '1' wenn Admins bei Fehlern per E-Mail benachrichtigt werden
 *   bool    $settings_saved       Ob gerade gespeichert wurde
 *   string  $settings_error       Fehlermeldung beim Speichern oder leer
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h2><?php esc_html_e( 'Erfindergeist Calendar Einstellungen', 'erfindergeist' ); ?></h2>

	<?php if ( $settings_saved ) : ?>
		<div class="updated">
			<p><strong><?php esc_html_e( 'Einstellungen gespeichert.', 'erfindergeist' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $settings_error ) : ?>
		<div class="notice notice-error">
			<p><strong><?php echo esc_html( $settings_error ); ?></strong></p>
		</div>
	<?php endif; ?>

	<form name="form1" method="post" action="">
		<?php wp_nonce_field( 'egj_calendar_action', 'egj_calendar_nonce_field' ); ?>

		<p>
			<?php esc_html_e( 'ICS URL:', 'erfindergeist' ); ?>
			<input
				type="url"
				name="<?php echo esc_attr( $ics_url_field_name ); ?>"
				value="<?php echo esc_attr( $ics_url ); ?>"
				size="60"
			>
		</p>

		<p>
			<label>
				<input type="checkbox" name="egj_notify_on_error" value="1" <?php checked( $notify_on_error, '1' ); ?>>
				<?php esc_html_e( 'Admins per E-Mail benachrichtigen, wenn die ICS-URL nicht erreichbar ist', 'erfindergeist' ); ?>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="clear_cache">
				<?php esc_html_e( 'ICS-Cache leeren', 'erfindergeist' ); ?>
			</label>
		</p>

		<p class="submit">
			<input
				type="submit"
				name="Submit"
				class="button-primary"
				value="<?php esc_attr_e( 'Änderungen speichern', 'erfindergeist' ); ?>"
			>
		</p>
	</form>

	<div>
		<h3><?php esc_html_e( 'ICS Cache Information', 'erfindergeist' ); ?></h3>
		<p>
			<?php esc_html_e( 'Timestamp:', 'erfindergeist' ); ?>
			<?php echo $ics_cache_timestamp ? esc_html( wp_date( 'd.m.Y H:i:s', (int) $ics_cache_timestamp ) ) : esc_html__( 'Kein Cache-Timestamp vorhanden', 'erfindergeist' ); ?>
		</p>

		<?php if ( ! empty( $ics_last_error ) && is_array( $ics_last_error ) ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Letzter fehlgeschlagener Abruf:', 'erfindergeist' ); ?></strong>
					<?php echo esc_html( wp_date( 'd.m.Y H:i:s', (int) ( $ics_last_error['time'] ?? 0 ) ) ); ?>
					&ndash;
					<?php echo esc_html( (string) ( $ics_last_error['message'] ?? '' ) ); ?>
				</p>
				<p><?php esc_html_e( 'Bis zum nächsten erfolgreichen Abruf wird der vorhandene Cache ausgeliefert.', 'erfindergeist' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $ics_cache ) : ?>
			<details>
				<summary><?php esc_html_e( 'Cache-Inhalt (Events)', 'erfindergeist' ); ?></summary>
				<pre>
				<?php
				try {
					$events = egj_calendar_parse_ics( $ics_cache );
					echo esc_html( (string) wp_json_encode( $events, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP ) );
				} catch ( \Throwable $e ) {
					echo esc_html__( 'Fehler beim Parsen des Caches.', 'erfindergeist' );
				}
				?>
				</pre>
			</details>
			<details>
				<summary><?php esc_html_e( 'Roh-ICS', 'erfindergeist' ); ?></summary>
				<pre><?php echo esc_html( $ics_cache ); ?></pre>
			</details>
		<?php else : ?>
			<p><?php esc_html_e( 'Kein ICS-Cache vorhanden.', 'erfindergeist' ); ?></p>
		<?php endif; ?>
	</div>
</div>
