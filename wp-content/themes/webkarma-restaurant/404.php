<?php
/**
 * Not found.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container content">
	<h1 class="page-title"><?php esc_html_e( 'Страница не найдена', 'webkarma-restaurant' ); ?></h1>
	<p><?php esc_html_e( 'Возможно, страница была перемещена.', 'webkarma-restaurant' ); ?></p>
	<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'webkarma-restaurant' ); ?></a></p>
</div>
<?php
get_footer();
