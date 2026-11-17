<?php
/**
 * Empty cart.
 *
 * Override of WooCommerce templates/cart/cart-empty.php @version 7.0.1.
 * Keeps .wc-empty-cart-message, which WooCommerce's cart.js swaps in after
 * the last dish is removed via AJAX.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<div class="wc-empty-cart-message cart-empty-state">
	<span class="cart-empty-state__icon" aria-hidden="true"><?php echo wkr_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
	<h1 class="cart-empty-state__title"><?php esc_html_e( 'Ваша корзина пуста', 'webkarma-restaurant' ); ?></h1>
	<p class="cart-empty-state__text"><?php esc_html_e( 'Выберите блюда в меню — оформить заявку можно в пару шагов.', 'webkarma-restaurant' ); ?></p>
	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<p><a class="button button--large" href="<?php echo esc_url( wkr_menu_url() ); ?>"><?php esc_html_e( 'Перейти в меню', 'webkarma-restaurant' ); ?><?php echo wkr_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a></p>
	<?php endif; ?>
</div>
