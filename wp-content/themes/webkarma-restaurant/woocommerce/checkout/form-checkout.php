<?php
/**
 * Checkout (approved design: handoff/references/05-checkout.png).
 *
 * Override of WooCommerce templates/checkout/form-checkout.php @version 9.4.0.
 * Fields are defined by webkarma-restaurant-core (WKRC_Checkout::fields) and
 * rendered with woocommerce_form_field(); values survive validation errors
 * via $checkout->get_value(). The fulfilment radios drive address/summary
 * visibility through CSS :has() (works without JavaScript); the server
 * validates the address only for delivery.
 *
 * @package WebKarmaRestaurant
 * @var WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/order-head',
	null,
	array(
		'title'      => __( 'Оформление заказа', 'webkarma-restaurant' ),
		'subtitle'   => __( 'Оставьте контакты — мы позвоним для подтверждения.', 'webkarma-restaurant' ),
		'step'       => 2,
		'back_url'   => wc_get_cart_url(),
		'back_label' => __( 'Вернуться в корзину', 'webkarma-restaurant' ),
		'align'      => 'center',
	)
);

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$wkr_fields  = $checkout->get_checkout_fields();
$wkr_field   = static function ( $group, $key, $extra = array() ) use ( $wkr_fields, $checkout ) {
	if ( isset( $wkr_fields[ $group ][ $key ] ) ) {
		woocommerce_form_field( $key, array_merge( $wkr_fields[ $group ][ $key ], $extra ), $checkout->get_value( $key ) );
	}
};
$wkr_choice  = $checkout->get_value( 'wk_fulfilment' );
$wkr_choice  = in_array( $wkr_choice, array( 'delivery', 'pickup' ), true ) ? $wkr_choice : 'delivery';
$wkr_city    = (string) get_option( 'woocommerce_store_city' );
$wkr_pickup  = wkr_pickup_address();
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout checkout-layout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Оформление заказа', 'webkarma-restaurant' ); ?>" novalidate>

	<div class="checkout-main" id="customer_details">
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<fieldset class="checkout-section checkout-section--choice" id="wk_fulfilment_field">
			<legend class="checkout-section__title">
				<span class="checkout-section__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php esc_html_e( 'Способ получения', 'webkarma-restaurant' ); ?>
			</legend>
			<div class="segmented segmented--wide">
				<label class="segmented__option">
					<input type="radio" id="wk_fulfilment_delivery" name="wk_fulfilment" value="delivery" <?php checked( 'delivery', $wkr_choice ); ?>>
					<span><?php echo wkr_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php esc_html_e( 'Доставка', 'webkarma-restaurant' ); ?></span>
				</label>
				<label class="segmented__option">
					<input type="radio" id="wk_fulfilment_pickup" name="wk_fulfilment" value="pickup" <?php checked( 'pickup', $wkr_choice ); ?>>
					<span><?php echo wkr_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php esc_html_e( 'Самовывоз', 'webkarma-restaurant' ); ?></span>
				</label>
			</div>
			<p class="checkout-section__hint when-delivery"><?php esc_html_e( 'При самовывозе адрес не требуется.', 'webkarma-restaurant' ); ?></p>
			<?php if ( $wkr_pickup ) : ?>
				<p class="checkout-pickup when-pickup">
					<?php echo wkr_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span><?php esc_html_e( 'Заберите заказ в ресторане:', 'webkarma-restaurant' ); ?> <strong><?php echo esc_html( $wkr_pickup ); ?></strong></span>
				</p>
			<?php endif; ?>
		</fieldset>

		<section class="checkout-section" aria-labelledby="checkout-contacts">
			<h2 class="checkout-section__title" id="checkout-contacts">
				<span class="checkout-section__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php esc_html_e( 'Контактные данные', 'webkarma-restaurant' ); ?>
			</h2>
			<div class="checkout-grid">
				<?php
				$wkr_field( 'billing', 'billing_first_name' );
				$wkr_field( 'billing', 'billing_phone' );
				?>
			</div>
		</section>

		<section class="checkout-section when-delivery" aria-labelledby="checkout-address">
			<h2 class="checkout-section__title" id="checkout-address">
				<span class="checkout-section__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php esc_html_e( 'Адрес доставки', 'webkarma-restaurant' ); ?>
			</h2>
			<?php if ( $wkr_city ) : ?>
				<p class="checkout-city"><?php echo esc_html( $wkr_city ); ?></p>
			<?php endif; ?>
			<?php
			$wkr_field(
				'order',
				'wk_street',
				array(
					'label'             => __( 'Улица и дом', 'webkarma-restaurant' ) . ' <span class="required" aria-hidden="true">*</span>',
					'class'             => array( 'form-row-wide', 'wk-required-delivery' ),
					'custom_attributes' => array( 'aria-required' => 'true' ),
				)
			);
			?>
			<div class="checkout-grid checkout-grid--4">
				<?php
				foreach ( array( 'wk_apartment', 'wk_entrance', 'wk_floor', 'wk_intercom' ) as $wkr_key ) {
					$wkr_field( 'order', $wkr_key );
				}
				?>
			</div>
		</section>

		<section class="checkout-section" aria-labelledby="checkout-comment">
			<h2 class="checkout-section__title" id="checkout-comment">
				<span class="checkout-section__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php esc_html_e( 'Комментарий к заказу', 'webkarma-restaurant' ); ?>
			</h2>
			<?php $wkr_field( 'order', 'order_comments', array( 'label' => __( 'Комментарий к заказу', 'webkarma-restaurant' ), 'label_class' => array( 'screen-reader-text' ) ) ); ?>
		</section>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	</div>

	<aside class="checkout-summary order-summary" aria-labelledby="order_review_heading">
		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
		<header class="order-summary__head order-summary__head--split">
			<h2 class="order-summary__title" id="order_review_heading"><?php esc_html_e( 'Ваш заказ', 'webkarma-restaurant' ); ?></h2>
			<a class="order-summary__edit" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Изменить', 'webkarma-restaurant' ); ?><span class="screen-reader-text"> <?php esc_html_e( 'состав заказа', 'webkarma-restaurant' ); ?></span></a>
		</header>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php woocommerce_order_review(); ?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</aside>

	<div class="checkout-submit">
		<?php woocommerce_checkout_payment(); ?>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
