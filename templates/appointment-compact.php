<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="container text-dark p-0">
	<p>
		<b><?php echo $date_time_info; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?></b>
		<br>
		<?php echo $link_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?>
	</p>
</section>
