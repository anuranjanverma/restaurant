<?php
/**
 * «Заявка получена» (approved design: handoff/references/07-order-received.png).
 *
 * Override of WooCommerce templates/checkout/thankyou.php @version 11.2.0.
 * WooCommerce only passes an order here after its own access checks (order
 * key via hash_equals, logged-in owner); otherwise $order is false and no
 * details are shown. The delivery address is shown only to the browser
 * session that placed the order (WKRC_Checkout::viewer_placed_order), so a
 * forwarded link reveals no personal details.
 *
 * @package WebKarmaRestaurant
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order order-received">
	<?php if ( ! $order ) : ?>

		<div class="order-received__missing">
			<h1 class="order-head__title"><?php esc_html_e( 'Заявка не найдена', 'webkarma-restaurant' ); ?></h1>
			<p><?php esc_html_e( 'Ссылка неполная или устарела. Если вы отправили заявку, ресторан позвонит вам для подтверждения.', 'webkarma-restaurant' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( wkr_menu_url() ); ?>"><?php esc_html_e( 'Вернуться в меню', 'webkarma-restaurant' ); ?><?php echo wkr_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a></p>
		</div>

	<?php elseif ( $order->has_status( 'failed' ) ) : ?>

		<div class="order-received__missing">
			<h1 class="order-head__title"><?php esc_html_e( 'Заявку не удалось сохранить', 'webkarma-restaurant' ); ?></h1>
			<p><?php esc_html_e( 'Пожалуйста, попробуйте ещё раз или позвоните в ресторан.', 'webkarma-restaurant' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Вернуться в корзину', 'webkarma-restaurant' ); ?></a></p>
		</div>

	<?php else : ?>
		<?php
		do_action( 'woocommerce_before_thankyou', $order->get_id() );

		$wkr_choice   = (string) $order->get_meta( '_wk_fulfilment' );
		$wkr_pickup   = 'pickup' === $wkr_choice;
		$wkr_personal = class_exists( 'WKRC_Checkout' ) && WKRC_Checkout::viewer_placed_order( $order );
		$wkr_details  = class_exists( 'WKRC_Checkout' ) ? WKRC_Checkout::details( $order, $wkr_personal ) : array();
		$wkr_waiting  = $order->has_status( 'on-hold' );
		$wkr_phone    = (string) wkr_setting( 'phone' );
		$wkr_tel      = function_exists( 'wkrc_phone_href' ) ? wkrc_phone_href( $wkr_phone ) : '';
		?>

		<header class="order-received__head">
			<div class="order-received__mark" aria-hidden="true">
				<span class="order-head__ornament"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<span class="order-received__check"><?php echo wkr_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<span class="order-head__ornament"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
			</div>
			<h1 class="order-received__title"><?php esc_html_e( 'Заявка получена', 'webkarma-restaurant' ); ?></h1>
			<p class="order-received__number"><?php echo esc_html( sprintf( /* translators: %s: order number. */ __( '№ %s', 'webkarma-restaurant' ), $order->get_order_number() ) ); ?></p>
		</header>

		<?php
		get_template_part(
			'template-parts/order-head',
			null,
			array(
				'title' => '',
				'step'  => 3,
				'done'  => true,
				'align' => 'center',
			)
		);
		?>

		<div class="order-received__intro">
			<p class="order-received__thanks">
				<?php
				echo esc_html(
					$wkr_pickup
						? __( 'Спасибо! Мы позвоним, чтобы подтвердить заказ и время, когда его можно забрать.', 'webkarma-restaurant' )
						: __( 'Спасибо! Мы позвоним, чтобы подтвердить заказ, стоимость доставки и время получения.', 'webkarma-restaurant' )
				);
				?>
			</p>
			<p class="status-pill">
				<?php echo wkr_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<span><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
			</p>
			<?php if ( $wkr_waiting ) : ?>
				<p class="order-received__wait"><?php esc_html_e( 'Пожалуйста, дождитесь звонка ресторана.', 'webkarma-restaurant' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="order-received__grid">
			<section class="order-received__items" aria-labelledby="order-items-title">
				<h2 id="order-items-title"><?php esc_html_e( 'Состав заказа', 'webkarma-restaurant' ); ?></h2>
				<ul class="order-review__items">
					<?php foreach ( $order->get_items() as $wkr_item ) : ?>
						<?php
						/** @var WC_Order_Item_Product $wkr_item */
						$wkr_product = $wkr_item->get_product();
						?>
						<li class="order-review__item">
							<?php
							echo ( $wkr_product && $wkr_product->get_image_id() )
								? wp_get_attachment_image( $wkr_product->get_image_id(), 'thumbnail', false, array( 'class' => 'order-review__img', 'alt' => '' ) )
								: '<span class="order-review__img dish-card__img--empty" aria-hidden="true"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by core.
							?>
							<span class="order-review__name"><?php echo esc_html( $wkr_item->get_name() ); ?>
								<span class="order-review__sub"><?php echo esc_html( sprintf( /* translators: %d: quantity. */ __( '%d шт.', 'webkarma-restaurant' ), $wkr_item->get_quantity() ) ); ?></span>
							</span>
							<span class="order-review__price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $wkr_item ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="order-received__totals" aria-labelledby="order-totals-title">
				<h2 id="order-totals-title"><?php esc_html_e( 'Итого', 'webkarma-restaurant' ); ?></h2>
				<dl class="order-summary__rows">
					<?php foreach ( $order->get_order_item_totals() as $wkr_key => $wkr_total ) : ?>
						<?php
						if ( 'payment_method' === $wkr_key ) {
							continue; // Shown as the confirmation-by-phone message instead.
						}
						?>
						<div class="order-summary__row<?php echo 'order_total' === $wkr_key ? ' order-summary__row--strong' : ''; ?>">
							<dt><?php echo esc_html( rtrim( wp_strip_all_tags( $wkr_total['label'] ), ':' ) ); ?></dt>
							<dd><?php echo wp_kses_post( $wkr_total['value'] ); ?></dd>
						</div>
					<?php endforeach; ?>
					<?php foreach ( $wkr_details as $wkr_key => $wkr_row ) : ?>
						<?php
						if ( 'delivery_cost' === $wkr_key ) {
							continue; // Already in the totals.
						}
						?>
						<div class="order-summary__row">
							<dt><?php echo esc_html( $wkr_row[0] ); ?></dt>
							<dd>
								<?php if ( 'fulfilment' === $wkr_key ) : ?>
									<?php echo wkr_icon( $wkr_pickup ? 'store' : 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
								<?php endif; ?>
								<?php echo esc_html( $wkr_row[1] ); ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</section>
		</div>

		<div class="order-received__actions">
			<a class="button button--large" href="<?php echo esc_url( wkr_menu_url() ); ?>"><?php esc_html_e( 'Вернуться в меню', 'webkarma-restaurant' ); ?><?php echo wkr_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a>
			<?php if ( $wkr_tel ) : ?>
				<a class="button button--large button--outline order-received__call" href="<?php echo esc_url( $wkr_tel, array( 'tel' ) ); ?>">
					<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span><?php esc_html_e( 'Позвонить в ресторан', 'webkarma-restaurant' ); ?><br><strong><?php echo esc_html( $wkr_phone ); ?></strong></span>
				</a>
			<?php endif; ?>
		</div>

		<?php
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
		?>
	<?php endif; ?>
</div>
