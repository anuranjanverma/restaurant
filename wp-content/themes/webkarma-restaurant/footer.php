<?php
/**
 * Site footer. Contact details come from «Настройки ресторана»; links from
 * the «Меню в подвале» navigation menu.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

$wkr_address = (string) wkr_setting( 'address' );
$wkr_map     = (string) wkr_setting( 'map_url' );
$wkr_phone   = wkr_phone_link( 'phone' );
$wkr_social  = function_exists( 'wkrc_get_social_links' ) ? wkrc_get_social_links() : array();
$wkr_icons   = array(
	'vk_url'        => 'vk',
	'instagram_url' => 'instagram',
);
?>
</main>

<footer class="site-footer">
	<div class="container site-footer__inner">
		<div class="site-footer__brand">
			<?php wkr_brand(); ?>
		</div>

		<?php if ( $wkr_address || $wkr_phone ) : ?>
			<ul class="site-footer__contacts">
				<?php if ( $wkr_address ) : ?>
					<li>
						<?php echo wkr_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
						<?php if ( $wkr_map ) : ?>
							<a href="<?php echo esc_url( $wkr_map ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $wkr_address ); ?><span class="screen-reader-text"> <?php esc_html_e( '(карта откроется в новой вкладке)', 'webkarma-restaurant' ); ?></span></a>
						<?php else : ?>
							<span><?php echo esc_html( $wkr_address ); ?></span>
						<?php endif; ?>
					</li>
				<?php endif; ?>
				<?php if ( $wkr_phone ) : ?>
					<li class="site-footer__phone">
						<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
						<?php echo $wkr_phone; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in wkr_phone_link(). ?>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Навигация в подвале', 'webkarma-restaurant' ); ?>">
				<?php wkr_nav_menu( 'footer', 'site-footer__list' ); ?>
			</nav>
		<?php endif; ?>

		<?php if ( $wkr_social ) : ?>
			<ul class="site-footer__social">
				<?php foreach ( $wkr_social as $wkr_link ) : ?>
					<li>
						<a href="<?php echo esc_url( $wkr_link['url'] ); ?>" target="_blank" rel="noopener">
							<?php echo isset( $wkr_icons[ $wkr_link['key'] ] ) ? wkr_icon( $wkr_icons[ $wkr_link['key'] ] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
							<span><?php echo esc_html( 'vk_url' === $wkr_link['key'] ? 'VK' : $wkr_link['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="container site-footer__legal">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . wkr_restaurant_name() ); ?></p>
		<?php if ( get_privacy_policy_url() ) : ?>
			<p><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Политика конфиденциальности', 'webkarma-restaurant' ); ?></a></p>
		<?php endif; ?>
	</div>
</footer>

<?php if ( wkr_show_floating_cart() ) : ?>
	<?php $wkr_count = wkr_cart_count(); ?>
	<a class="floating-cart" href="<?php echo esc_url( wkr_cart_url() ); ?>" data-cart-link
		aria-label="<?php echo esc_attr( sprintf( /* translators: %d: number of items. */ __( 'Корзина, блюд: %d', 'webkarma-restaurant' ), $wkr_count ) ); ?>">
		<?php echo wkr_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
		<span class="floating-cart__count" data-cart-count aria-hidden="true" <?php echo $wkr_count ? '' : 'hidden'; ?>><?php echo esc_html( (string) $wkr_count ); ?></span>
	</a>
<?php endif; ?>

<div class="screen-reader-text" role="status" aria-live="polite" data-cart-status></div>
<?php // role="alert" is set only while an error is shown (a hidden alert would attract WooCommerce's scroll-to-notices). ?>
<div class="cart-toast" data-cart-error hidden></div>

<?php if ( wkr_has_dish_cards() ) : ?>
	<?php // Dish details/gallery, filled by dishes.js from window.wkrDishes. ?>
	<dialog class="dish-dialog" id="dish-dialog" aria-labelledby="dish-dialog-title">
		<div class="dish-dialog__inner">
			<button class="dish-dialog__close" type="button" data-dialog-close autofocus>
				<?php echo wkr_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Закрыть', 'webkarma-restaurant' ); ?></span>
			</button>
			<div class="dish-dialog__gallery">
				<figure class="dish-dialog__figure"></figure>
				<button class="dish-dialog__nav dish-dialog__nav--prev" type="button" data-gallery-step="-1" hidden>
					<?php echo wkr_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Предыдущее фото', 'webkarma-restaurant' ); ?></span>
				</button>
				<button class="dish-dialog__nav dish-dialog__nav--next" type="button" data-gallery-step="1" hidden>
					<?php echo wkr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Следующее фото', 'webkarma-restaurant' ); ?></span>
				</button>
				<p class="dish-dialog__count" aria-live="polite" hidden></p>
				<div class="dish-dialog__thumbs" role="group" aria-label="<?php esc_attr_e( 'Фото блюда', 'webkarma-restaurant' ); ?>" hidden></div>
			</div>
			<div class="dish-dialog__body">
				<h2 class="dish-dialog__title" id="dish-dialog-title"></h2>
				<p class="dish-dialog__weight" hidden></p>
				<p class="dish-dialog__desc" hidden></p>
				<div class="dish-dialog__buy">
					<p class="dish-dialog__price"></p>
					<div class="dish-dialog__controls"></div>
				</div>
			</div>
		</div>
	</dialog>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
