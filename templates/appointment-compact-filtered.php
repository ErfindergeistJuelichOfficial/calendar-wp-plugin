<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="container text-dark p-0">
	<p>
		<?php echo $date_time_info; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?> 📍 <?php echo esc_html( $location ); ?>
	</p>
</section>
