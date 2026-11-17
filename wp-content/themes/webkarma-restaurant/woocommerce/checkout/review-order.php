<?php
/**
 * Checkout order summary (refreshed by WooCommerce via update_order_review).
 *
 * Override of WooCommerce templates/checkout/review-order.php @version 11.2.0.
 * Shows «Сумма за блюда»; delivery cost is never shown as zero/free — it is
 * agreed by phone. Pickup shows the pickup address instead.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="shop_table woocommerce-checkout-review-order-table order-review">
	<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
	<ul class="order-review__items">
		<?php
		foreach ( WC()->cart->get_cart() as $wkr_key => $wkr_item ) :
			$wkr_product = apply_filters( 'woocommerce_cart_item_product', $wkr_item['data'], $wkr_item, $wkr_key );
			if ( ! $wkr_product || ! $wkr_product->exists() || $wkr_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $wkr_item, $wkr_key ) ) {
				continue;
			}
			?>
			<li class="order-review__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $wkr_item, $wkr_key ) ); ?>">
				<?php
				echo $wkr_product->get_image_id()
					? wp_get_attachment_image( $wkr_product->get_image_id(), 'thumbnail', false, array( 'class' => 'order-review__img', 'alt' => '' ) )
					: '<span class="order-review__img dish-card__img--empty" aria-hidden="true"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by core.
				?>
				<span class="order-review__name product-name"><?php echo esc_html( $wkr_product->get_name() ); ?></span>
				<span class="order-review__qty product-quantity">
					<span aria-hidden="true">× <?php echo esc_html( (string) $wkr_item['quantity'] ); ?></span>
					<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %d: quantity. */ __( 'количество: %d', 'webkarma-restaurant' ), $wkr_item['quantity'] ) ); ?></span>
				</span>
				<span class="order-review__price product-total"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $wkr_product, $wkr_item['quantity'] ), $wkr_item, $wkr_key ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

	<dl class="order-summary__rows">
		<div class="order-summary__row order-summary__row--strong cart-subtotal order-total">
			<dt><?php esc_html_e( 'Сумма за блюда', 'webkarma-restaurant' ); ?></dt>
			<dd><?php wc_cart_totals_order_total_html(); ?></dd>
		</div>
		<div class="order-summary__row when-delivery">
			<dt><?php esc_html_e( 'Доставка', 'webkarma-restaurant' ); ?></dt>
			<dd><?php esc_html_e( 'Стоимость доставки уточнит ресторан', 'webkarma-restaurant' ); ?></dd>
		</div>
		<div class="order-summary__row when-pickup">
			<dt><?php esc_html_e( 'Самовывоз', 'webkarma-restaurant' ); ?></dt>
			<dd><?php echo esc_html( wkr_pickup_address() ); ?></dd>
		</div>
	</dl>

	<p class="order-review__notice">
		<?php echo wkr_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
		<span class="when-delivery"><?php esc_html_e( 'Стоимость доставки, время и оплату подтвердим во время звонка.', 'webkarma-restaurant' ); ?></span>
		<span class="when-pickup"><?php esc_html_e( 'Время готовности и оплату подтвердим во время звонка.', 'webkarma-restaurant' ); ?></span>
	</p>

	<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
	<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
</div>
