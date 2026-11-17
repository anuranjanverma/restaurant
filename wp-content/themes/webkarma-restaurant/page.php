<?php
/**
 * Default page template (also wraps the classic cart/checkout shortcodes).
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="container content">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
			<?php if ( ! wkr_is_order_flow() ) : // Cart/checkout templates render their own heading. ?>
				<h1 class="page-title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="entry__content">
				<?php the_content(); ?>
			</div>
		</article>
	</div>
	<?php
endwhile;

get_footer();
