<?php
/**
 * Site header.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

$wkr_cart_count = wkr_cart_count();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти к содержимому', 'webkarma-restaurant' ); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<?php wkr_brand(); ?>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e( 'Основная навигация', 'webkarma-restaurant' ); ?>">
				<?php wkr_nav_menu( 'primary', 'site-nav__list' ); ?>
			</nav>
		<?php endif; ?>

		<div class="site-header__actions">
			<?php if ( wkr_cart_url() ) : ?>
				<a class="header-cart" href="<?php echo esc_url( wkr_cart_url() ); ?>">
					<?php echo wkr_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="header-cart__label"><?php esc_html_e( 'Корзина', 'webkarma-restaurant' ); ?></span>
					<span class="header-cart__count" data-cart-count <?php echo $wkr_cart_count ? '' : 'hidden'; ?>><?php echo esc_html( (string) $wkr_cart_count ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
					<span class="nav-toggle__bars" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Меню сайта', 'webkarma-restaurant' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
</header>

<main id="main" class="site-main" tabindex="-1">
