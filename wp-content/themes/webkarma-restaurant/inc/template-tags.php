<?php
/**
 * Template helpers. All restaurant data is read from the core plugin.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

/**
 * Restaurant setting, or '' when the core plugin is inactive.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function wkr_setting( $key ) {
	return function_exists( 'wkrc_get_setting' ) ? wkrc_get_setting( $key ) : '';
}

/**
 * Restaurant name from settings, falling back to the site title.
 *
 * @return string
 */
function wkr_restaurant_name() {
	$name = (string) wkr_setting( 'restaurant_name' );
	return '' !== $name ? $name : get_bloginfo( 'name' );
}

/**
 * Header brand: configured logo image, otherwise the restaurant name.
 */
function wkr_brand() {
	$logo_id = (int) wkr_setting( 'logo_id' );
	$name    = wkr_restaurant_name();

	echo '<a class="site-brand" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
		echo wp_get_attachment_image(
			$logo_id,
			'medium',
			false,
			array(
				'class' => 'site-brand__logo',
				'alt'   => $name,
				'sizes' => '(max-width: 56rem) 96px, 156px',
			)
		);
	} else {
		echo '<span class="site-brand__name">' . esc_html( $name ) . '</span>';
	}
	echo '</a>';
}

/**
 * tel: link for a configured phone, or ''.
 *
 * @param string $key   Setting key (phone|phone_alt).
 * @param string $class Link class.
 * @return string
 */
function wkr_phone_link( $key = 'phone', $class = '' ) {
	$phone = (string) wkr_setting( $key );
	$href  = function_exists( 'wkrc_phone_href' ) ? wkrc_phone_href( $phone ) : '';
	if ( ! $href ) {
		return '';
	}
	return '<a' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . ' href="' . esc_url( $href, array( 'tel' ) ) . '">' . esc_html( $phone ) . '</a>';
}

/**
 * Escaped text with line breaks kept (for editable multi-line headings).
 *
 * @param string $text Plain text.
 * @return string
 */
function wkr_lines( $text ) {
	return nl2br( esc_html( trim( (string) $text ) ), false );
}

/**
 * Permalink of a page ID, or a fallback URL.
 *
 * @param int    $page_id  Page ID.
 * @param string $fallback Fallback URL.
 * @return string
 */
function wkr_page_url( $page_id, $fallback = '' ) {
	$url = $page_id ? get_permalink( (int) $page_id ) : '';
	return $url ? $url : $fallback;
}

/**
 * Cart URL (WooCommerce), or ''.
 *
 * @return string
 */
function wkr_cart_url() {
	return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
}

/**
 * Number of items in the current cart.
 *
 * @return int
 */
function wkr_cart_count() {
	return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Cart lines keyed by product ID: [key, quantity] (server-side initial state
 * for dish cards; cart.js keeps it in sync afterwards).
 *
 * @return array<int, array{key:string, quantity:int}>
 */
function wkr_cart_lines() {
	$lines = array();
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$lines[ (int) $item['product_id'] ] = array(
				'key'      => (string) $key,
				'quantity' => (int) $item['quantity'],
			);
		}
	}
	return $lines;
}

/**
 * «N блюдо / блюда / блюд» with the Russian plural rule (three forms; each
 * form is a separate translatable string).
 *
 * @param int $count Number of dishes.
 * @return string
 */
function wkr_dish_count( $count ) {
	$count = (int) $count;
	$mod10 = $count % 10;
	$mod100 = $count % 100;
	if ( 1 === $mod10 && 11 !== $mod100 ) {
		/* translators: %d: number of dishes (1, 21, 31…). */
		$form = __( '%d блюдо', 'webkarma-restaurant' );
	} elseif ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
		/* translators: %d: number of dishes (2–4, 22–24…). */
		$form = __( '%d блюда', 'webkarma-restaurant' );
	} else {
		/* translators: %d: number of dishes (0, 5–20, 25…). */
		$form = __( '%d блюд', 'webkarma-restaurant' );
	}
	return sprintf( $form, $count );
}

/**
 * Pickup address text from settings (restaurant address).
 *
 * @return string
 */
function wkr_pickup_address() {
	return (string) wkr_setting( 'address' );
}

/**
 * Small ornament heading decoration.
 */
function wkr_ornament() {
	echo '<span class="ornament">' . wkr_icon( 'ornament' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
}

/**
 * URL of the menu catalogue (WooCommerce shop page), or home as a fallback.
 *
 * @return string
 */
function wkr_menu_url() {
	$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	return $url ? $url : home_url( '/' );
}

/**
 * Native nav menu for a theme location; prints nothing when unassigned.
 *
 * @param string $location Theme location.
 * @param string $class    Menu <ul> class.
 */
function wkr_nav_menu( $location, $class ) {
	if ( ! has_nav_menu( $location ) ) {
		return;
	}
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => $class,
			'depth'          => 1,
			'fallback_cb'    => false,
		)
	);
}
