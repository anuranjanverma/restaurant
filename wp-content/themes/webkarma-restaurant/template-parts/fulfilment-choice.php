<?php
/**
 * Delivery / pickup choice (homepage strip and menu header). The choice is
 * remembered for checkout by dishes.js (localStorage wk_fulfilment).
 *
 * Expects $args: legend (string), legend_visible (bool).
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

$wkr_address = (string) wkr_setting( 'address' );
?>
<fieldset class="fulfilment__choice">
	<legend class="fulfilment__title<?php echo empty( $args['legend_visible'] ) ? ' screen-reader-text' : ''; ?>"><?php echo esc_html( $args['legend'] ); ?></legend>
	<div class="segmented">
		<label class="segmented__option">
			<input type="radio" name="wk-fulfilment" value="delivery" checked>
			<span><?php echo wkr_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php esc_html_e( 'Доставка', 'webkarma-restaurant' ); ?></span>
		</label>
		<label class="segmented__option">
			<input type="radio" name="wk-fulfilment" value="pickup">
			<span><?php echo wkr_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php esc_html_e( 'Самовывоз', 'webkarma-restaurant' ); ?></span>
		</label>
	</div>
	<p class="fulfilment__hint" aria-live="polite"
		data-delivery="<?php esc_attr_e( 'Стоимость доставки уточнит ресторан', 'webkarma-restaurant' ); ?>"
		data-pickup="<?php echo esc_attr( $wkr_address ? sprintf( /* translators: %s: restaurant address. */ __( 'Самовывоз: %s', 'webkarma-restaurant' ), $wkr_address ) : __( 'Заберите заказ в ресторане', 'webkarma-restaurant' ) ); ?>"><?php esc_html_e( 'Стоимость доставки уточнит ресторан', 'webkarma-restaurant' ); ?></p>
</fieldset>
