<?php
/**
 * Homepage (approved design: handoff/references/01-homepage.png).
 *
 * Content: «Настройки ресторана → Главная страница» (texts/images),
 * ★ featured WooCommerce products (dishes), «Вопросы и ответы» (FAQ).
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();

$wkr_home     = function_exists( 'wkrc_get_homepage' ) ? wkrc_get_homepage() : array();
$wkr_h        = static fn( $key ) => isset( $wkr_home[ $key ] ) ? $wkr_home[ $key ] : '';
$wkr_menu_url = wkr_menu_url();
$wkr_arrow    = wkr_icon( 'arrow' );
$wkr_products = function_exists( 'wkrc_get_featured_products' ) ? wkrc_get_featured_products( (int) ( $wkr_h( 'featured_limit' ) ? $wkr_h( 'featured_limit' ) : 6 ) ) : array();
$wkr_faqs     = function_exists( 'wkrc_get_faq_items' ) ? wkrc_get_faq_items() : array();
?>

<section class="hero" aria-labelledby="hero-title">
	<div class="hero__decor hero__decor--top" aria-hidden="true"></div>
	<div class="hero__decor hero__decor--bottom" aria-hidden="true"></div>

	<div class="container hero__inner">
		<div class="hero__content">
			<?php if ( $wkr_h( 'hero_eyebrow' ) ) : ?>
				<p class="eyebrow"><?php echo esc_html( $wkr_h( 'hero_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h1 id="hero-title" class="hero__title"><?php echo $wkr_h( 'hero_title' ) ? wkr_lines( $wkr_h( 'hero_title' ) ) : esc_html( wkr_restaurant_name() ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in wkr_lines(). ?></h1>
			<?php if ( $wkr_h( 'hero_text' ) ) : ?>
				<p class="hero__text"><?php echo esc_html( $wkr_h( 'hero_text' ) ); ?></p>
			<?php endif; ?>
			<div class="hero__actions">
				<a class="button button--large" href="<?php echo esc_url( $wkr_menu_url ); ?>">
					<?php echo esc_html( $wkr_h( 'hero_cta_label' ) ? $wkr_h( 'hero_cta_label' ) : __( 'Выбрать блюда', 'webkarma-restaurant' ) ); ?>
					<?php echo $wkr_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				</a>
				<?php if ( $wkr_h( 'hero_link_label' ) && wkr_page_url( $wkr_h( 'hero_link_page' ) ) ) : ?>
					<a class="link-arrow" href="<?php echo esc_url( wkr_page_url( $wkr_h( 'hero_link_page' ) ) ); ?>">
						<?php echo esc_html( $wkr_h( 'hero_link_label' ) ); ?>
						<?php echo $wkr_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( $wkr_h( 'hero_image_id' ) ) : ?>
		<div class="hero__media">
			<div class="hero__arch">
				<?php
				echo wp_get_attachment_image(
					(int) $wkr_h( 'hero_image_id' ),
					'full',
					false,
					array(
						'class'         => 'hero__img',
						'sizes'         => '(min-width: 64rem) 54vw, 100vw',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
					)
				);
				?>
			</div>
			<?php
			// One arch shape in bounding-box units: clip for the photo, stroke for the gold outline.
			$wkr_arch = 'C0,.275 .045,.24 .1,.24 C.1,.175 .16,.13 .23,.13 C.3,.13 .36,.1 .42,.06 C.46,.035 .49,.018 .5,0 C.51,.018 .54,.035 .58,.06 C.64,.1 .7,.13 .77,.13 C.84,.13 .9,.175 .9,.24 C.955,.24 1,.275 1,.34';
			?>
			<svg class="hero__outline" viewBox="0 0 1 1" preserveAspectRatio="none" aria-hidden="true" focusable="false">
				<defs>
					<clipPath id="wk-arch" clipPathUnits="objectBoundingBox">
						<path d="M0,1 L0,.34 <?php echo esc_attr( $wkr_arch ); ?> L1,1 Z" />
					</clipPath>
				</defs>
				<path d="M0,.62 L0,.34 <?php echo esc_attr( $wkr_arch ); ?> L1,1" />
			</svg>
		</div>
	<?php endif; ?>
</section>

<section class="fulfilment" aria-label="<?php echo esc_attr( $wkr_h( 'fulfilment_title' ) ? $wkr_h( 'fulfilment_title' ) : __( 'Способ получения', 'webkarma-restaurant' ) ); ?>">
	<div class="container fulfilment__inner">
		<?php
		get_template_part(
			'template-parts/fulfilment-choice',
			null,
			array(
				'legend'         => $wkr_h( 'fulfilment_title' ) ? $wkr_h( 'fulfilment_title' ) : __( 'Как вам удобнее?', 'webkarma-restaurant' ),
				'legend_visible' => true,
			)
		);
		?>

		<?php if ( $wkr_h( 'fulfilment_note' ) ) : ?>
			<p class="fulfilment__note">
				<?php echo wkr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<span><?php echo esc_html( $wkr_h( 'fulfilment_note' ) ); ?></span>
			</p>
		<?php endif; ?>
	</div>
</section>

<?php if ( $wkr_products ) : ?>
	<?php
	// Filter buttons: categories of the featured dishes, in category order.
	$wkr_filters = array();
	foreach ( $wkr_products as $wkr_product ) {
		foreach ( wp_get_post_terms( $wkr_product->get_id(), 'product_cat' ) as $wkr_term ) {
			$wkr_filters[ $wkr_term->slug ] = $wkr_term;
		}
	}
	uasort( $wkr_filters, static fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true ) );
	$wkr_cart = wkr_cart_lines();
	?>
	<section class="featured" aria-labelledby="featured-title">
		<div class="container">
			<header class="section-head">
				<?php wkr_ornament(); ?>
				<h2 id="featured-title"><?php echo esc_html( $wkr_h( 'featured_title' ) ? $wkr_h( 'featured_title' ) : __( 'Популярные блюда', 'webkarma-restaurant' ) ); ?></h2>
				<?php if ( $wkr_h( 'featured_subtitle' ) ) : ?>
					<p class="section-head__sub"><?php echo esc_html( $wkr_h( 'featured_subtitle' ) ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( count( $wkr_filters ) > 1 ) : ?>
				<div class="filter-tabs" role="group" aria-label="<?php esc_attr_e( 'Фильтр блюд по разделам', 'webkarma-restaurant' ); ?>">
					<button type="button" class="filter-tabs__btn" aria-pressed="true" data-filter=""><?php esc_html_e( 'Всё меню', 'webkarma-restaurant' ); ?></button>
					<?php foreach ( $wkr_filters as $wkr_term ) : ?>
						<button type="button" class="filter-tabs__btn" aria-pressed="false" data-filter="<?php echo esc_attr( $wkr_term->slug ); ?>"><?php echo esc_html( $wkr_term->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="dish-grid dish-grid--featured is-all" id="featured-dishes">
				<?php
				foreach ( $wkr_products as $wkr_product ) {
					get_template_part(
						'template-parts/dish-card',
						null,
						array(
							'product' => $wkr_product,
							'lines'   => $wkr_cart,
						)
					);
				}
				?>
			</div>

			<p class="featured__more">
				<a class="link-arrow" href="<?php echo esc_url( $wkr_menu_url ); ?>">
					<?php echo esc_html( $wkr_h( 'featured_link_label' ) ? $wkr_h( 'featured_link_label' ) : __( 'Смотреть всё меню', 'webkarma-restaurant' ) ); ?>
					<?php echo $wkr_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				</a>
			</p>
		</div>
	</section>
<?php endif; ?>

<?php if ( $wkr_h( 'intro_title' ) || $wkr_h( 'intro_text' ) ) : ?>
	<section class="intro" aria-labelledby="intro-title">
		<?php if ( $wkr_h( 'intro_image_id' ) ) : ?>
			<div class="intro__media">
				<?php
				echo wp_get_attachment_image(
					(int) $wkr_h( 'intro_image_id' ),
					'large',
					false,
					array(
						'class'   => 'intro__img',
						'sizes'   => '(min-width: 56rem) 50vw, 100vw',
						'loading' => 'lazy',
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="intro__content">
			<?php wkr_ornament(); ?>
			<h2 id="intro-title"><?php echo wkr_lines( $wkr_h( 'intro_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in wkr_lines(). ?></h2>
			<?php if ( $wkr_h( 'intro_text' ) ) : ?>
				<p><?php echo esc_html( $wkr_h( 'intro_text' ) ); ?></p>
			<?php endif; ?>
			<?php if ( $wkr_h( 'intro_link_label' ) && wkr_page_url( $wkr_h( 'intro_link_page' ) ) ) : ?>
				<a class="link-arrow" href="<?php echo esc_url( wkr_page_url( $wkr_h( 'intro_link_page' ) ) ); ?>">
					<?php echo esc_html( $wkr_h( 'intro_link_label' ) ); ?>
					<?php echo $wkr_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				</a>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $wkr_h( 'band_title' ) ) : ?>
	<section class="band" aria-labelledby="band-title">
		<span class="band__ornament band__ornament--start" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
		<div class="container band__inner">
			<h2 id="band-title" class="band__title"><?php echo esc_html( $wkr_h( 'band_title' ) ); ?></h2>
			<a class="button button--light" href="<?php echo esc_url( $wkr_menu_url ); ?>">
				<?php echo esc_html( $wkr_h( 'band_cta_label' ) ? $wkr_h( 'band_cta_label' ) : __( 'Выбрать блюда', 'webkarma-restaurant' ) ); ?>
				<?php echo $wkr_arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			</a>
		</div>
		<span class="band__ornament band__ornament--end" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
	</section>
<?php endif; ?>

<?php if ( $wkr_faqs ) : ?>
	<section class="faq" aria-labelledby="faq-title">
		<div class="container faq__inner">
			<header class="section-head">
				<?php wkr_ornament(); ?>
				<h2 id="faq-title"><?php echo esc_html( $wkr_h( 'faq_title' ) ? $wkr_h( 'faq_title' ) : __( 'Частые вопросы', 'webkarma-restaurant' ) ); ?></h2>
			</header>
			<div class="faq__list">
				<?php foreach ( $wkr_faqs as $wkr_i => $wkr_faq ) : ?>
					<details class="faq__item" <?php echo 0 === $wkr_i ? 'open' : ''; ?>>
						<summary class="faq__question"><?php echo esc_html( get_the_title( $wkr_faq ) ); ?></summary>
						<div class="faq__answer"><?php echo wkrc_faq_answer_html( $wkr_faq ); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses_post in plugin. ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
