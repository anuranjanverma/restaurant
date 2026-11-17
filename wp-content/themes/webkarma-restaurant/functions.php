<?php
/**
 * WebKarma Restaurant theme setup.
 *
 * Presentation only: restaurant data and branding values come from the
 * webkarma-restaurant-core plugin; the theme falls back to the approved
 * defaults when the plugin is inactive.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

define( 'WKR_VERSION', '0.3.0' );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/icons.php';

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'webkarma-restaurant', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 600,
				'single_image_width'    => 900,
			)
		);

		register_nav_menus(
			array(
				'primary' => __( 'Главное меню (шапка)', 'webkarma-restaurant' ),
				'footer'  => __( 'Меню в подвале', 'webkarma-restaurant' ),
			)
		);
	}
);

/*
 * Bundled, locally hosted fonts (SIL OFL 1.1, see assets/fonts/LICENSE-*.txt).
 * Registered as selectable stacks in «Настройки ресторана» and used as the
 * theme defaults.
 */
add_filter(
	'wkrc_font_stacks',
	static function ( $stacks ) {
		return array(
			'wk-playfair' => array(
				'label' => __( 'Playfair Display (шрифт темы)', 'webkarma-restaurant' ),
				'stack' => '"Playfair Display", Georgia, "Times New Roman", serif',
			),
			'wk-pt-serif' => array(
				'label' => __( 'PT Serif (шрифт темы)', 'webkarma-restaurant' ),
				'stack' => '"PT Serif", Georgia, "Times New Roman", serif',
			),
		) + $stacks;
	}
);

add_filter(
	'wkrc_settings_defaults',
	static function ( $defaults ) {
		$defaults['font_heading'] = 'wk-playfair';
		$defaults['font_body']    = 'wk-pt-serif';
		return $defaults;
	}
);

add_action(
	'wp_head',
	static function () {
		$fonts = get_template_directory_uri() . '/assets/fonts/';
		foreach ( array( 'playfair-display-cyrillic-600-normal.woff2', 'pt-serif-cyrillic-400-normal.woff2' ) as $file ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $fonts . $file ) );
		}
	},
	2
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'webkarma-restaurant', get_stylesheet_uri(), array(), WKR_VERSION );
		wp_add_inline_style( 'webkarma-restaurant', wkr_brand_tokens_css() );

		$js = get_template_directory_uri() . '/assets/js/';
		wp_enqueue_script( 'webkarma-restaurant-navigation', $js . 'navigation.js', array(), WKR_VERSION, array( 'strategy' => 'defer' ) );

		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script( 'webkarma-restaurant-cart', $js . 'cart.js', array(), WKR_VERSION, array( 'strategy' => 'defer' ) );
			wp_localize_script(
				'webkarma-restaurant-cart',
				'wkrCart',
				array(
					'api'   => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
					'nonce' => wp_create_nonce( 'wc_store_api' ),
					'i18n'  => array(
						/* translators: %s: dish name. */
						'added'     => __( 'Добавлено в корзину: %s', 'webkarma-restaurant' ),
						/* translators: 1: dish name, 2: quantity. */
						'updated'   => __( '%1$s: %2$s в корзине', 'webkarma-restaurant' ),
						/* translators: %s: dish name. */
						'removed'   => __( 'Удалено из корзины: %s', 'webkarma-restaurant' ),
						'error'     => __( 'Не удалось обновить корзину. Попробуйте ещё раз.', 'webkarma-restaurant' ),
						/* translators: %d: number of items in the cart. */
						'cartLabel' => __( 'Корзина, блюд: %d', 'webkarma-restaurant' ),
					),
				)
			);
		}

		// Cart page: steppers drive WooCommerce's own cart form (wc-cart).
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_script( 'webkarma-restaurant-cart-page', $js . 'cart-page.js', array( 'jquery', 'wc-cart' ), WKR_VERSION, true );
			wp_localize_script(
				'webkarma-restaurant-cart-page',
				'wkrCartPage',
				array(
					'updated' => __( 'Корзина обновлена', 'webkarma-restaurant' ),
					'removed' => __( 'Блюдо удалено из корзины. Корзина обновлена.', 'webkarma-restaurant' ),
					'emptied' => __( 'Корзина пуста', 'webkarma-restaurant' ),
				)
			);
		}
		// Checkout form (not the order-received endpoint).
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			wp_enqueue_script( 'webkarma-restaurant-checkout', $js . 'checkout.js', array( 'jquery', 'wc-checkout' ), WKR_VERSION, true );
			wp_localize_script(
				'webkarma-restaurant-checkout',
				'wkrCheckout',
				array(
					'sending' => __( 'Отправляем заявку…', 'webkarma-restaurant' ),
				)
			);
		}

		if ( wkr_has_dish_cards() ) {
			// In the footer: its dish data (wkr_dish_registry) is attached while cards render.
			wp_enqueue_script(
				'webkarma-restaurant-dishes',
				$js . 'dishes.js',
				array(),
				WKR_VERSION,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			wp_localize_script(
				'webkarma-restaurant-dishes',
				'wkrDishText',
				array(
					/* translators: %d: photo number. */
					'photo'   => __( 'Фото %d', 'webkarma-restaurant' ),
					/* translators: 1: dish name, 2: photo number. */
					'photoOf' => __( '%s, фото %d', 'webkarma-restaurant' ),
				)
			);
		}
		if ( is_front_page() ) {
			wp_enqueue_script( 'webkarma-restaurant-home', $js . 'home.js', array(), WKR_VERSION, array( 'strategy' => 'defer' ) );
		}
		if ( wkr_is_menu_page() ) {
			// After dishes.js (shared dialog focus trap).
			wp_enqueue_script(
				'webkarma-restaurant-menu',
				$js . 'menu.js',
				array( 'webkarma-restaurant-dishes' ),
				WKR_VERSION,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	}
);

/*
 * Homepage, menu, cart and checkout use the theme's own templates/styles;
 * WooCommerce's generic styles remain only for other WooCommerce pages
 * (e.g. the single-product fallback).
 */
add_filter(
	'woocommerce_enqueue_styles',
	static function ( $styles ) {
		return ( wkr_has_dish_cards() || wkr_is_order_flow() ) ? array() : $styles;
	}
);

/**
 * Cart, checkout and the order-received endpoint.
 *
 * @return bool
 */
function wkr_is_order_flow() {
	return function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
}

// Remembered delivery/pickup choice, applied before first paint (cart/checkout summaries).
add_action(
	'wp_head',
	static function () {
		echo "<script>try{if(localStorage.getItem('wk_fulfilment')==='pickup'){document.documentElement.setAttribute('data-fulfilment','pickup');}}catch(e){}</script>\n";
	},
	1
);

// Checkout blocked by cart problems (e.g. a dish sold out): WooCommerce shows a
// generic notice and clears the specific one; print it so the dish is named.
add_action( 'woocommerce_cart_has_errors', 'woocommerce_output_all_notices' );

// Order flow: theme markup replaces WooCommerce defaults that do not fit it.
remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
// The default thank-you table prints billing/shipping details; the theme's thankyou.php shows only what is needed.
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

/*
 * AJAX quantity updates on the cart: the theme announces «Корзина обновлена»
 * in its own polite live region and totals update visibly, so WooCommerce's
 * generic «Корзина обновлена.» notice is dropped there (WooCommerce would
 * otherwise focus it and scroll to it). The no-JavaScript reload keeps it.
 */
add_filter(
	'woocommerce_add_success',
	static function ( $message ) {
		$is_xhr = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
		return ( $is_xhr && __( 'Cart updated.', 'woocommerce' ) === $message ) ? '' : $message;
	}
);

add_action(
	'woocommerce_proceed_to_checkout',
	static function () {
		printf(
			'<a href="%1$s" class="button button--large checkout-button wc-forward order-summary__cta">%2$s%3$s</a>',
			esc_url( wc_get_checkout_url() ),
			esc_html__( 'Перейти к оформлению', 'webkarma-restaurant' ),
			wkr_icon( 'arrow' ) // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
		);
	}
);

/*
 * Inline field errors (no-JavaScript path): WooCommerce re-renders the
 * checkout with error notices that carry the field id; show each message
 * beside its field as well. checkout.js does the same for AJAX submits.
 */
/**
 * Checkout error notices keyed by field id, captured before WooCommerce
 * prints (and clears) them (woocommerce_before_checkout_form_cart_notices).
 *
 * @param array|null $set Notices to store, or null to read.
 * @return array<string, string>
 */
function wkr_checkout_field_errors( $set = null ) {
	static $errors = array();
	if ( null !== $set ) {
		$errors = $set;
	}
	return $errors;
}

add_action(
	'woocommerce_before_checkout_form_cart_notices', // WooCommerce prints (and clears) checkout notices here.
	static function () {
		$errors = array();
		foreach ( (array) wc_get_notices( 'error' ) as $notice ) {
			if ( ! empty( $notice['data']['id'] ) && ! isset( $errors[ $notice['data']['id'] ] ) ) {
				$errors[ $notice['data']['id'] ] = $notice['notice'];
			}
		}
		wkr_checkout_field_errors( $errors );
	},
	5 // Before woocommerce_output_all_notices (10) on the same hook.
);

add_filter(
	'woocommerce_form_field',
	static function ( $html, $key ) {
		$errors = wkr_checkout_field_errors();
		if ( ! isset( $errors[ $key ] ) ) {
			return $html;
		}
		$id = $key . '-error';
		// Merge into an existing aria-describedby (e.g. the phone hint) or add one.
		if ( preg_match( '/(<(?:input|textarea|select)\b[^>]*?)\saria-describedby="([^"]*)"/', $html ) ) {
			$html = preg_replace( '/(<(?:input|textarea|select)\b[^>]*?\saria-describedby=")([^"]*)"/', '$1' . esc_attr( $id ) . ' $2"', $html, 1 );
		} else {
			$html = preg_replace( '/(<(?:input|textarea|select)\b)/', '$1 aria-describedby="' . esc_attr( $id ) . '"', $html, 1 );
		}
		$html = preg_replace( '/(<(?:input|textarea|select)\b)/', '$1 aria-invalid="true"', $html, 1 );
		$html = preg_replace( '/class="form-row /', 'class="form-row woocommerce-invalid ', $html, 1 );
		return preg_replace( '/<\/p>\s*$/', '<span class="field-error" id="' . esc_attr( $id ) . '">' . wp_kses_post( $errors[ $key ] ) . '</span></p>', $html, 1 );
	},
	10,
	2
);

/*
 * Single product page: readable no-JavaScript fallback for the dish photo
 * link (menu uses the details dialog). Native template, trimmed via hooks.
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );

add_action(
	'woocommerce_before_single_product',
	static function () {
		global $product;
		$terms  = $product ? wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) ) : array();
		$anchor = ( $terms && ! is_wp_error( $terms ) ) ? '#cat-' . rawurlencode( $terms[0] ) : '';
		printf(
			'<p class="product-back"><a class="order-head__back" href="%1$s">%2$s%3$s</a></p>',
			esc_url( wkr_menu_url() . $anchor ),
			wkr_icon( 'chevron-left' ), // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
			esc_html__( 'Вернуться к меню', 'webkarma-restaurant' )
		);
	},
	5
);

add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		$weight = ( $product && function_exists( 'wkrc_get_portion_weight' ) ) ? wkrc_get_portion_weight( $product ) : '';
		if ( '' !== $weight ) {
			/* translators: %s: portion weight in grams. */
			echo '<p class="product-weight">' . esc_html( sprintf( __( '%s г', 'webkarma-restaurant' ), $weight ) ) . '</p>';
		}
	},
	6
);

/**
 * Menu page: the WooCommerce shop page (/menu/) and product search results.
 *
 * @return bool
 */
function wkr_is_menu_page() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}
	return is_shop() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

/**
 * Pages that render dish cards (and therefore the dish details dialog).
 *
 * @return bool
 */
function wkr_has_dish_cards() {
	return is_front_page() || wkr_is_menu_page();
}

// The menu page uses its own template instead of the WooCommerce archive loop.
add_filter(
	'template_include',
	static function ( $template ) {
		return wkr_is_menu_page() ? get_theme_file_path( 'menu.php' ) : $template;
	},
	99
);

// The menu template queries all dishes itself; keep the main shop query light.
add_action(
	'pre_get_posts',
	static function ( $query ) {
		if ( ! is_admin() && $query->is_main_query() && function_exists( 'is_shop' ) && ( $query->is_post_type_archive( 'product' ) || ( $query->is_search() && 'product' === $query->get( 'post_type' ) ) ) ) {
			$query->set( 'posts_per_page', 1 );
			$query->set( 'no_found_rows', true );
		}
	}
);

// A single search hit stays on the menu (WooCommerce would jump to the product).
add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

// Category archives are sections of the one-page menu.
add_action(
	'template_redirect',
	static function () {
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			wp_safe_redirect( wkr_menu_url() . '#cat-' . rawurlencode( $term->slug ), 301 );
			exit;
		}
	}
);

/**
 * Collect dish data for the details dialog while cards render; printed once
 * as JSON before the footer scripts.
 *
 * @param WC_Product|null $product Product to register, or null to read.
 * @return array<int, array>
 */
function wkr_dish_registry( $product = null ) {
	static $dishes = array();
	if ( $product instanceof WC_Product && ! isset( $dishes[ $product->get_id() ] ) ) {
		$images = array();
		foreach ( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) as $image_id ) {
			$src = wp_get_attachment_image_src( (int) $image_id, 'large' );
			if ( ! $src ) {
				continue;
			}
			$images[] = array(
				'src'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'srcset' => (string) wp_get_attachment_image_srcset( (int) $image_id, 'large' ),
			);
		}
		$weight = function_exists( 'wkrc_get_portion_weight' ) ? wkrc_get_portion_weight( $product ) : '';

		$dishes[ $product->get_id() ] = array(
			'name'   => $product->get_name(),
			/* translators: %s: portion weight in grams. */
			'weight' => '' !== $weight ? sprintf( __( '%s г', 'webkarma-restaurant' ), $weight ) : '',
			// Plain text for textContent: strip tags and decode &nbsp; / &#8381;.
			'price'  => html_entity_decode( wp_strip_all_tags( wc_price( (float) wc_get_price_to_display( $product ) ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'desc'   => trim( wp_strip_all_tags( $product->get_short_description() ) ),
			'images' => $images,
		);
	}
	return $dishes;
}

add_action(
	'wp_footer',
	static function () {
		$dishes = wkr_dish_registry();
		if ( $dishes && wp_script_is( 'webkarma-restaurant-dishes', 'enqueued' ) ) {
			wp_add_inline_script( 'webkarma-restaurant-dishes', 'window.wkrDishes = ' . wp_json_encode( $dishes ) . ';', 'before' );
		}
	},
	5
);

/**
 * Floating cart: everywhere except cart and checkout (which are the cart).
 *
 * @return bool
 */
function wkr_show_floating_cart() {
	return function_exists( 'WC' ) && ! is_cart() && ! is_checkout();
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( wkr_show_floating_cart() ) {
			$classes[] = 'has-floating-cart';
		}
		return $classes;
	}
);

/**
 * :root custom properties from «Настройки ресторана» (or theme defaults).
 *
 * @return string
 */
function wkr_brand_tokens_css() {
	if ( function_exists( 'wkrc_get_brand_tokens' ) ) {
		$tokens = wkrc_get_brand_tokens();
	} else {
		$tokens = array(
			'--wk-color-primary'     => '#154c43',
			'--wk-color-accent'      => '#b39054',
			'--wk-color-background'  => '#f7f1e6',
			'--wk-color-text'        => '#1f2b28',
			'--wk-color-button'      => '#154c43',
			'--wk-color-button-text' => '#ffffff',
			'--wk-font-heading'      => '"Playfair Display", Georgia, "Times New Roman", serif',
			'--wk-font-body'         => '"PT Serif", Georgia, "Times New Roman", serif',
		);
	}

	$css = '';
	foreach ( $tokens as $property => $value ) {
		// Values are validated hex colours / whitelisted font stacks; strip
		// anything that could end the declaration as defence in depth.
		$css .= $property . ':' . str_replace( array( ';', '{', '}', '<' ), '', (string) $value ) . ';';
	}
	return ':root{' . $css . '}';
}

// WooCommerce: render inside the theme's own wrapper (see woocommerce.php).
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
