<?php
/**
 * Cart (approved design: handoff/references/04-cart.png).
 *
 * Override of WooCommerce templates/cart/cart.php @version 11.2.0.
 * Keeps WooCommerce's cart form contract (.woocommerce-cart-form,
 * .woocommerce-cart-form__contents, .cart_item, input.qty, update_cart,
 * .product-remove > a, .cart_totals) so the native AJAX update and the
 * no-JavaScript form both work with server-side totals.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

$wkr_count = (int) WC()->cart->get_cart_contents_count();

get_template_part(
	'template-parts/order-head',
	null,
	array(
		'title'      => __( 'Ваша корзина', 'webkarma-restaurant' ),
		'subtitle'   => __( 'Проверьте выбранные блюда и оформите заявку. Мы свяжемся с вами по телефону для подтверждения.', 'webkarma-restaurant' ),
		'step'       => 1,
		'back_url'   => wkr_menu_url(),
		'back_label' => __( 'Вернуться к меню', 'webkarma-restaurant' ),
	)
);

do_action( 'woocommerce_before_cart' );
?>
<div class="cart-layout">
	<form class="woocommerce-cart-form cart-items" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<h2 class="screen-reader-text"><?php esc_html_e( 'Блюда в корзине', 'webkarma-restaurant' ); ?></h2>
		<ul class="woocommerce-cart-form__contents cart-items__list">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $wkr_key => $wkr_item ) :
				/** @var WC_Product $wkr_product */
				$wkr_product = apply_filters( 'woocommerce_cart_item_product', $wkr_item['data'], $wkr_item, $wkr_key );
				if ( ! $wkr_product || ! $wkr_product->exists() || $wkr_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $wkr_item, $wkr_key ) ) {
					continue;
				}
				$wkr_name   = $wkr_product->get_name();
				$wkr_link   = $wkr_product->is_visible() ? $wkr_product->get_permalink( $wkr_item ) : '';
				$wkr_weight = function_exists( 'wkrc_get_portion_weight' ) ? wkrc_get_portion_weight( $wkr_product ) : '';
				$wkr_desc   = trim( wp_strip_all_tags( $wkr_product->get_short_description() ) );
				$wkr_max    = $wkr_product->is_sold_individually() ? 1 : $wkr_product->get_max_purchase_quantity();
				$wkr_qty_id = 'qty-' . $wkr_key;
				?>
				<li class="cart-item cart_item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', '', $wkr_item, $wkr_key ) ); ?>">
					<div class="cart-item__media">
						<?php
						$wkr_thumb = $wkr_product->get_image_id()
							? wp_get_attachment_image( $wkr_product->get_image_id(), 'woocommerce_thumbnail', false, array( 'class' => 'cart-item__img', 'alt' => '' ) )
							: '<span class="cart-item__img dish-card__img--empty" aria-hidden="true">' . wkr_icon( 'ornament' ) . '</span>';
						echo $wkr_link ? '<a href="' . esc_url( $wkr_link ) . '" tabindex="-1" aria-hidden="true">' . $wkr_thumb . '</a>' : $wkr_thumb; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
						?>
					</div>

					<div class="cart-item__body">
						<div class="cart-item__head">
							<h3 class="cart-item__name product-name">
								<?php echo $wkr_link ? '<a href="' . esc_url( $wkr_link ) . '">' . esc_html( $wkr_name ) . '</a>' : esc_html( $wkr_name ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped. ?>
							</h3>
							<p class="cart-item__total product-subtotal">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $wkr_product, $wkr_item['quantity'] ), $wkr_item, $wkr_key ) ); ?>
							</p>
						</div>

						<?php if ( '' !== $wkr_weight ) : ?>
							<p class="cart-item__weight"><?php echo esc_html( sprintf( /* translators: %s: grams. */ __( '%s г', 'webkarma-restaurant' ), $wkr_weight ) ); ?></p>
						<?php endif; ?>
						<p class="cart-item__unit product-price">
							<?php
							/* translators: %s: unit price. */
							echo wp_kses_post( sprintf( __( '%s за 1 шт.', 'webkarma-restaurant' ), apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $wkr_product ), $wkr_item, $wkr_key ) ) );
							?>
						</p>
						<?php if ( $wkr_desc ) : ?>
							<p class="cart-item__desc"><?php echo esc_html( $wkr_desc ); ?></p>
						<?php endif; ?>

						<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $wkr_item ) ); ?>

						<div class="cart-item__actions">
							<div class="cart-qty product-quantity" role="group" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: dish name. */ __( 'Количество: %s', 'webkarma-restaurant' ), $wkr_name ) ); ?>">
								<button type="button" class="qty__btn cart-qty__btn" data-step="-1" hidden>
									<?php echo wkr_icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
									<span class="screen-reader-text"><?php esc_html_e( 'Уменьшить', 'webkarma-restaurant' ); ?></span>
								</button>
								<label class="screen-reader-text" for="<?php echo esc_attr( $wkr_qty_id ); ?>"><?php echo esc_html( sprintf( /* translators: %s: dish name. */ __( 'Количество: %s', 'webkarma-restaurant' ), $wkr_name ) ); ?></label>
								<input type="number" id="<?php echo esc_attr( $wkr_qty_id ); ?>" class="input-text qty text cart-qty__input" name="cart[<?php echo esc_attr( $wkr_key ); ?>][qty]" value="<?php echo esc_attr( (string) $wkr_item['quantity'] ); ?>" min="0" <?php echo $wkr_max > 0 ? 'max="' . esc_attr( (string) $wkr_max ) . '"' : ''; ?> step="1" inputmode="numeric" autocomplete="off">
								<button type="button" class="qty__btn cart-qty__btn" data-step="1" hidden>
									<?php echo wkr_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
									<span class="screen-reader-text"><?php esc_html_e( 'Увеличить', 'webkarma-restaurant' ); ?></span>
								</button>
							</div>

							<span class="product-remove">
								<a href="<?php echo esc_url( wc_get_cart_remove_url( $wkr_key ) ); ?>" class="remove cart-item__remove" data-product_id="<?php echo esc_attr( (string) $wkr_product->get_id() ); ?>" data-cart_item_key="<?php echo esc_attr( $wkr_key ); ?>">
									<?php echo wkr_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
									<span><?php esc_html_e( 'Удалить', 'webkarma-restaurant' ); ?></span>
									<span class="screen-reader-text"><?php echo esc_html( $wkr_name ); ?></span>
								</a>
							</span>
						</div>
					</div>
				</li>
			<?php endforeach; ?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
		</ul>

		<div class="cart-items__update">
			<button type="submit" class="button button--small button--outline" name="update_cart" value="<?php esc_attr_e( 'Обновить корзину', 'webkarma-restaurant' ); ?>"><?php esc_html_e( 'Обновить корзину', 'webkarma-restaurant' ); ?></button>
			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<aside class="cart_totals order-summary" data-cart-count-value="<?php echo esc_attr( (string) $wkr_count ); ?>" aria-labelledby="cart-summary-title">
		<header class="order-summary__head">
			<span class="order-summary__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
			<div>
				<h2 class="order-summary__title" id="cart-summary-title"><?php esc_html_e( 'Ваш заказ', 'webkarma-restaurant' ); ?></h2>
				<p class="order-summary__count"><?php echo esc_html( wkr_dish_count( $wkr_count ) ); ?></p>
			</div>
		</header>

		<dl class="order-summary__rows" data-fulfilment-view>
			<div class="order-summary__row order-summary__row--strong">
				<dt><?php esc_html_e( 'Сумма за блюда', 'webkarma-restaurant' ); ?></dt>
				<dd><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></dd>
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

		<p class="order-summary__note">
			<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			<span class="when-delivery"><?php esc_html_e( 'Ресторан позвонит, чтобы подтвердить заказ, доставку и оплату.', 'webkarma-restaurant' ); ?></span>
			<span class="when-pickup"><?php esc_html_e( 'Ресторан позвонит, чтобы подтвердить заказ, время и оплату.', 'webkarma-restaurant' ); ?></span>
		</p>

		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>

		<p class="order-summary__free">
			<?php echo wkr_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			<?php esc_html_e( 'Онлайн-оплата не требуется', 'webkarma-restaurant' ); ?>
		</p>
	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
