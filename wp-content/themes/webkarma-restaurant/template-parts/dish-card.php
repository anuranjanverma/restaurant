<?php
/**
 * Dish card (homepage featured dishes; reused by the menu in a later phase).
 *
 * Without JavaScript «Добавить» is a normal form submit handled by
 * WooCommerce (add-to-cart); cart.js enhances it into an inline quantity
 * control using the Store API.
 *
 * Expects $args: product (WC_Product), lines (cart lines by product ID),
 * sizes (img sizes attribute).
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $wkr_product */
$wkr_product = $args['product'];
$wkr_id      = $wkr_product->get_id();
$wkr_line    = $args['lines'][ $wkr_id ] ?? array(
	'key'      => '',
	'quantity' => 0,
);
$wkr_name    = $wkr_product->get_name();
$wkr_desc    = trim( wp_strip_all_tags( $wkr_product->get_short_description() ) );
$wkr_weight  = function_exists( 'wkrc_get_portion_weight' ) ? wkrc_get_portion_weight( $wkr_product ) : '';
$wkr_cats    = wp_get_post_terms( $wkr_id, 'product_cat', array( 'fields' => 'slugs' ) );
$wkr_can_buy = $wkr_product->is_purchasable() && $wkr_product->is_in_stock();
$wkr_info_id = 'dish-info-' . $wkr_id;

wkr_dish_registry( $wkr_product );
?>
<article class="dish-card" data-product-id="<?php echo esc_attr( (string) $wkr_id ); ?>" data-cats="<?php echo esc_attr( is_wp_error( $wkr_cats ) ? '' : implode( ' ', $wkr_cats ) ); ?>" data-name="<?php echo esc_attr( $wkr_name ); ?>">
	<div class="dish-card__media">
		<?php // Without JavaScript the photo opens the product page; dishes.js opens the details dialog instead. ?>
		<a class="dish-card__open" href="<?php echo esc_url( $wkr_product->get_permalink() ); ?>" data-dish-open aria-haspopup="dialog">
			<?php
			if ( $wkr_product->get_image_id() ) {
				echo wp_get_attachment_image(
					$wkr_product->get_image_id(),
					'medium_large',
					false,
					array(
						'class'   => 'dish-card__img',
						'alt'     => '',
						'sizes'   => $args['sizes'] ?? '(min-width: 64rem) 22vw, (min-width: 40rem) 30vw, 46vw',
						'loading' => 'lazy',
					)
				);
			} else {
				echo '<span class="dish-card__img dish-card__img--empty" aria-hidden="true">' . wkr_icon( 'ornament' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
			}
			?>
			<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: dish name. */ __( '%s: фото и подробности', 'webkarma-restaurant' ), $wkr_name ) ); ?></span>
		</a>
		<?php if ( $wkr_desc ) : ?>
			<div class="dish-card__info" id="<?php echo esc_attr( $wkr_info_id ); ?>" hidden>
				<p><?php echo esc_html( $wkr_desc ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="dish-card__body">
		<h3 class="dish-card__title">
			<span><?php echo esc_html( $wkr_name ); ?></span>
			<?php if ( $wkr_desc ) : ?>
				<button type="button" class="dish-card__info-btn" aria-expanded="false" aria-controls="<?php echo esc_attr( $wkr_info_id ); ?>">
					<?php echo wkr_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: dish name. */ __( 'Описание: %s', 'webkarma-restaurant' ), $wkr_name ) ); ?></span>
				</button>
			<?php endif; ?>
		</h3>

		<?php if ( '' !== $wkr_weight ) : ?>
			<p class="dish-card__weight"><?php echo esc_html( sprintf( /* translators: %s: portion weight in grams. */ __( '%s г', 'webkarma-restaurant' ), $wkr_weight ) ); ?></p>
		<?php endif; ?>

		<div class="dish-card__buy">
			<p class="dish-card__price"><?php echo wp_kses_post( wc_price( (float) wc_get_price_to_display( $wkr_product ) ) ); ?></p>

			<?php if ( $wkr_can_buy ) : ?>
				<div class="qty" data-qty="<?php echo esc_attr( (string) $wkr_line['quantity'] ); ?>" data-key="<?php echo esc_attr( $wkr_line['key'] ); ?>">
					<form class="qty__add" method="post" action="<?php echo esc_url( wkr_cart_url() ); ?>">
						<button type="submit" class="button button--small" name="add-to-cart" value="<?php echo esc_attr( (string) $wkr_id ); ?>">
							<span><?php esc_html_e( 'Добавить', 'webkarma-restaurant' ); ?></span>
							<?php echo wkr_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
							<span class="screen-reader-text"><?php echo esc_html( $wkr_name ); ?></span>
						</button>
					</form>
					<div class="qty__stepper" role="group" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: dish name. */ __( 'Количество: %s', 'webkarma-restaurant' ), $wkr_name ) ); ?>" hidden>
						<button type="button" class="qty__btn" data-step="-1">
							<?php echo wkr_icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
							<span class="screen-reader-text"><?php esc_html_e( 'Уменьшить', 'webkarma-restaurant' ); ?></span>
						</button>
						<output class="qty__value" aria-live="off"><?php echo esc_html( (string) max( 1, $wkr_line['quantity'] ) ); ?></output>
						<button type="button" class="qty__btn" data-step="1">
							<?php echo wkr_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
							<span class="screen-reader-text"><?php esc_html_e( 'Увеличить', 'webkarma-restaurant' ); ?></span>
						</button>
					</div>
				</div>
			<?php else : ?>
				<p class="dish-card__unavailable"><?php esc_html_e( 'Нет в наличии', 'webkarma-restaurant' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</article>
