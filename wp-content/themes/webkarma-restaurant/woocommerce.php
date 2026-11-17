<?php
/**
 * Wrapper for WooCommerce catalogue/product templates.
 * The menu catalogue layout is built in the menu phase.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container content">
	<?php woocommerce_content(); ?>
</div>
<?php
get_footer();
