<?php
/**
 * Checkout submit area (refreshed by WooCommerce via update_order_review).
 *
 * Override of WooCommerce templates/checkout/payment.php @version 10.9.0.
 * With a single method («Подтверждение по телефону») no radio list is shown:
 * the method travels as a hidden payment_method field and its description
 * explains that the restaurant calls to confirm. Button, nonce and hooks are
 * WooCommerce's own.
 *
 * @package WebKarmaRestaurant
 * @var WC_Payment_Gateway[] $available_gateways
 * @var string               $order_button_text
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}

$wkr_gateways = ! empty( $available_gateways ) ? $available_gateways : array();
?>
<div id="payment" class="woocommerce-checkout-payment checkout-payment">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<?php if ( 1 === count( $wkr_gateways ) ) : ?>
			<?php $wkr_gateway = reset( $wkr_gateways ); ?>
			<input type="hidden" name="payment_method" value="<?php echo esc_attr( $wkr_gateway->id ); ?>">
			<?php if ( $wkr_gateway->get_description() ) : ?>
				<p class="checkout-payment__info">
					<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span><?php echo wp_kses_post( $wkr_gateway->get_description() ); ?></span>
				</p>
			<?php endif; ?>
		<?php elseif ( $wkr_gateways ) : ?>
			<ul class="wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e( 'Способ подтверждения', 'webkarma-restaurant' ); ?>">
				<?php
				foreach ( $wkr_gateways as $wkr_gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $wkr_gateway ) );
				}
				?>
			</ul>
		<?php else : ?>
			<?php wc_print_notice( __( 'Сейчас заявки не принимаются. Пожалуйста, позвоните в ресторан.', 'webkarma-restaurant' ), 'notice' ); ?>
		<?php endif; ?>
	<?php endif; ?>

	<div class="form-row place-order">
		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php
		echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce filter; markup escaped below.
			'woocommerce_order_button_html',
			'<button type="submit" class="button button--large checkout-payment__submit" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '"><span>' . esc_html( $order_button_text ) . '</span>' . wkr_icon( 'arrow' ) . '</button>'
		);
		?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>

	<div class="checkout-payment__legal">
		<p>
			<?php esc_html_e( 'Отправляя заявку, вы просите ресторан связаться с вами для подтверждения заказа.', 'webkarma-restaurant' ); ?>
			<?php if ( get_privacy_policy_url() ) : ?>
				<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Политика конфиденциальности', 'webkarma-restaurant' ); ?></a>
			<?php endif; ?>
		</p>
		<p class="checkout-payment__offline">
			<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			<span><?php esc_html_e( 'Без онлайн-оплаты. Подтверждение по телефону.', 'webkarma-restaurant' ); ?></span>
		</p>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
