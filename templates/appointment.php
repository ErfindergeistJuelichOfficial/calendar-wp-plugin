<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="container text-dark bd-container-even">
	<div class="row">
		<div class="col">
			<h2 class="m-0"><?php echo esc_html( $summary ); ?></h2>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<?php echo $date_time_info; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?>
			<span>
				📍 <?php echo esc_html( $location ); ?>
				<a href="<?php echo esc_url( 'https://www.openstreetmap.org/search?query=' . $location_url ); ?>" target="_blank" rel="noopener noreferrer">OSM</a>
				<a href="<?php echo esc_url( 'https://maps.google.com/?q=' . $location_url ); ?>" target="_blank" rel="noopener noreferrer">google</a>
				<a href="<?php echo esc_url( 'https://maps.apple.com/?q=' . $location_url ); ?>" target="_blank" rel="noopener noreferrer">apple</a>
				<a href="<?php echo esc_url( 'https://bing.com/maps/default.aspx?where1=' . $location_url ); ?>" target="_blank" rel="noopener noreferrer">bing</a>
				<a href="<?php echo esc_url( 'https://waze.com/ul?q=' . $location_url ); ?>" target="_blank" rel="noopener noreferrer">waze</a>
			</span>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?>
		</div>
	</div>
	<div class="row">
		<div class="col"><?php echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?></div>
	</div>
</section>
