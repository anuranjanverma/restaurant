<?php
/**
 * Menu page (/menu/ = WooCommerce shop page; also product search results).
 * Approved design: handoff/references/02-menu-desktop.png, 03-menu-mobile.png.
 *
 * Sections follow the «Разделы меню» order set by drag-and-drop in admin;
 * empty categories are hidden. Dishes use the shared dish card.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();

$wkr_home   = function_exists( 'wkrc_get_homepage' ) ? wkrc_get_homepage() : array();
$wkr_title  = ! empty( $wkr_home['menu_title'] ) ? $wkr_home['menu_title'] : get_the_title( wc_get_page_id( 'shop' ) );
$wkr_search = is_search() ? trim( (string) get_search_query( false ) ) : '';

// Dishes: all published, catalogue-visible products (or search matches), menu order.
$wkr_query = new WP_Query(
	array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'orderby'                => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		's'                      => $wkr_search,
		'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
		'update_post_term_cache' => true,
		'no_found_rows'          => true,
	)
);

// Categories in admin order (WooCommerce drag-and-drop), default category excluded.
$wkr_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'menu_order',
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	)
);
$wkr_terms = is_wp_error( $wkr_terms ) ? array() : $wkr_terms;

// Group dishes under their first category in that order; each dish appears once.
$wkr_sections = array();
foreach ( $wkr_terms as $wkr_term ) {
	$wkr_sections[ $wkr_term->term_id ] = array(
		'term'     => $wkr_term,
		'products' => array(),
	);
}
foreach ( $wkr_query->posts as $wkr_id ) {
	$wkr_cat_ids = wp_get_post_terms( $wkr_id, 'product_cat', array( 'fields' => 'ids' ) );
	foreach ( $wkr_terms as $wkr_term ) {
		if ( in_array( $wkr_term->term_id, $wkr_cat_ids, true ) ) {
			$wkr_product = wc_get_product( $wkr_id );
			if ( $wkr_product ) {
				$wkr_sections[ $wkr_term->term_id ]['products'][] = $wkr_product;
			}
			break;
		}
	}
}
$wkr_sections = array_filter( $wkr_sections, static fn( $s ) => (bool) $s['products'] );

/**
 * Category thumbnail: WooCommerce category image, else the first dish photo.
 */
$wkr_thumb = static function ( $section, $size = 'thumbnail' ) {
	$image_id = (int) get_term_meta( $section['term']->term_id, 'thumbnail_id', true );
	if ( ! $image_id ) {
		foreach ( $section['products'] as $product ) {
			if ( $product->get_image_id() ) {
				$image_id = (int) $product->get_image_id();
				break;
			}
		}
	}
	return $image_id ? wp_get_attachment_image(
		$image_id,
		$size,
		false,
		array(
			'class'   => 'cat-thumb',
			'alt'     => '',
			'loading' => 'lazy',
		)
	) : '<span class="cat-thumb cat-thumb--empty" aria-hidden="true"></span>';
};

$wkr_cart = wkr_cart_lines();
?>

<section class="menu-head" aria-labelledby="menu-title">
	<div class="container menu-head__inner">
		<div class="menu-head__intro">
			<h1 id="menu-title" class="menu-head__title">
				<span class="menu-head__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<span><?php echo esc_html( $wkr_title ); ?></span>
				<span class="menu-head__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
			</h1>
			<?php if ( ! empty( $wkr_home['menu_subtitle'] ) ) : ?>
				<p class="menu-head__sub"><?php echo esc_html( $wkr_home['menu_subtitle'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="menu-head__tools">
			<?php
			get_template_part(
				'template-parts/fulfilment-choice',
				null,
				array(
					'legend'         => __( 'Способ получения', 'webkarma-restaurant' ),
					'legend_visible' => false,
				)
			);
			?>
			<form class="menu-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"
				data-menu-url="<?php echo esc_url( wkr_menu_url() ); ?>"
				data-found="<?php echo esc_attr( /* translators: %d: number of dishes found. */ __( 'Найдено блюд: %d', 'webkarma-restaurant' ) ); ?>">
				<label class="screen-reader-text" for="menu-search-input"><?php esc_html_e( 'Найти блюдо', 'webkarma-restaurant' ); ?></label>
				<?php echo wkr_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<input id="menu-search-input" class="menu-search__input" type="search" name="s" value="<?php echo esc_attr( $wkr_search ); ?>" placeholder="<?php esc_attr_e( 'Найти блюдо', 'webkarma-restaurant' ); ?>" autocomplete="off" enterkeyhint="search">
				<input type="hidden" name="post_type" value="product">
				<button class="menu-search__clear" type="button" hidden>
					<?php echo wkr_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Очистить поиск', 'webkarma-restaurant' ); ?></span>
				</button>
			</form>
		</div>
	</div>
</section>

<?php if ( $wkr_sections ) : ?>
	<div class="menu-rail">
		<div class="menu-rail__inner">
			<button class="menu-rail__all" type="button" aria-haspopup="dialog" aria-controls="category-sheet">
				<?php echo wkr_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<span><?php esc_html_e( 'Все категории', 'webkarma-restaurant' ); ?></span>
			</button>
			<nav class="menu-rail__nav" aria-label="<?php esc_attr_e( 'Разделы меню', 'webkarma-restaurant' ); ?>">
				<ul class="menu-rail__list">
					<?php foreach ( $wkr_sections as $wkr_section ) : ?>
						<li>
							<a class="chip" href="#cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>" data-section="cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>">
								<?php echo $wkr_thumb( $wkr_section ); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image. ?>
								<span><?php echo esc_html( $wkr_section['term']->name ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</div>
	</div>
<?php endif; ?>

<div class="container menu-layout">
	<?php if ( $wkr_sections ) : ?>
		<aside class="menu-sidebar">
			<nav class="menu-sidebar__nav" aria-label="<?php esc_attr_e( 'Разделы меню', 'webkarma-restaurant' ); ?>">
				<h2 class="menu-sidebar__title"><?php esc_html_e( 'Категории', 'webkarma-restaurant' ); ?></h2>
				<ul class="menu-sidebar__list">
					<li><a href="#menu-top" data-section="menu-top"><?php esc_html_e( 'Все блюда', 'webkarma-restaurant' ); ?></a></li>
					<?php foreach ( $wkr_sections as $wkr_section ) : ?>
						<li>
							<a href="#cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>" data-section="cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>">
								<span><?php echo esc_html( $wkr_section['term']->name ); ?></span>
								<?php echo wkr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</aside>
	<?php endif; ?>

	<div class="menu-content" id="menu-top" tabindex="-1">
		<p class="menu-status" role="status" aria-live="polite"><?php
		if ( '' !== $wkr_search ) {
			/* translators: 1: search query, 2: number of dishes. */
			echo esc_html( sprintf( __( 'По запросу «%1$s» найдено блюд: %2$d', 'webkarma-restaurant' ), $wkr_search, count( $wkr_query->posts ) ) );
		}
		?></p>

		<?php foreach ( $wkr_sections as $wkr_section ) : ?>
			<?php $wkr_slug = $wkr_section['term']->slug; ?>
			<section class="menu-section" id="cat-<?php echo esc_attr( $wkr_slug ); ?>" aria-labelledby="cat-<?php echo esc_attr( $wkr_slug ); ?>-title">
				<header class="menu-section__head">
					<span class="menu-section__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
					<h2 class="menu-section__title" id="cat-<?php echo esc_attr( $wkr_slug ); ?>-title" tabindex="-1"><?php echo esc_html( $wkr_section['term']->name ); ?></h2>
				</header>
				<div class="dish-grid">
					<?php
					foreach ( $wkr_section['products'] as $wkr_product ) {
						get_template_part(
							'template-parts/dish-card',
							null,
							array(
								'product' => $wkr_product,
								'lines'   => $wkr_cart,
								'sizes'   => '(min-width: 80rem) 230px, (min-width: 64rem) 19vw, (min-width: 40rem) 30vw, 46vw',
							)
						);
					}
					?>
				</div>
			</section>
		<?php endforeach; ?>

		<div class="menu-empty" <?php echo $wkr_sections ? 'hidden' : ''; ?>>
			<p><?php esc_html_e( 'Ничего не найдено. Попробуйте другое название блюда.', 'webkarma-restaurant' ); ?></p>
			<?php if ( '' !== $wkr_search ) : ?>
				<p><a class="link-arrow" href="<?php echo esc_url( wkr_menu_url() ); ?>"><?php esc_html_e( 'Показать всё меню', 'webkarma-restaurant' ); ?><?php echo wkr_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( $wkr_sections ) : ?>
	<dialog class="sheet" id="category-sheet" aria-labelledby="category-sheet-title">
		<div class="sheet__inner">
			<span class="sheet__handle" aria-hidden="true"></span>
			<header class="sheet__head">
				<h2 class="sheet__title" id="category-sheet-title"><?php esc_html_e( 'Все категории', 'webkarma-restaurant' ); ?></h2>
				<button class="sheet__close" type="button" data-sheet-close>
					<?php echo wkr_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Закрыть', 'webkarma-restaurant' ); ?></span>
				</button>
			</header>
			<ul class="sheet__grid">
				<li class="sheet__item sheet__item--all">
					<a class="sheet__link" href="#menu-top" data-section="menu-top">
						<?php echo $wkr_thumb( reset( $wkr_sections ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image. ?>
						<span><?php esc_html_e( 'Все блюда', 'webkarma-restaurant' ); ?></span>
						<?php echo wkr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					</a>
				</li>
				<?php foreach ( $wkr_sections as $wkr_section ) : ?>
					<li class="sheet__item">
						<a class="sheet__link" href="#cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>" data-section="cat-<?php echo esc_attr( $wkr_section['term']->slug ); ?>">
							<?php echo $wkr_thumb( $wkr_section ); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image. ?>
							<span><?php echo esc_html( $wkr_section['term']->name ); ?></span>
							<?php echo wkr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</dialog>
<?php endif; ?>

<?php
get_footer();
