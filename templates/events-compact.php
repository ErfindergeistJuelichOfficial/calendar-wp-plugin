<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="container text-dark p-0">
	<?php echo $appointments; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered HTML. ?>
</article>
