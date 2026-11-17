<?php
/**
 * Fallback template.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container content">
	<?php if ( have_posts() ) : ?>
		<?php if ( ! is_singular() ) : ?>
			<h1 class="page-title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<?php endif; ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<?php if ( is_singular() ) : ?>
					<h1 class="page-title"><?php the_title(); ?></h1>
				<?php else : ?>
					<h2 class="entry__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php endif; ?>
				<div class="entry__content">
					<?php is_singular() ? the_content() : the_excerpt(); ?>
				</div>
			</article>
		<?php endwhile; ?>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<h1 class="page-title"><?php esc_html_e( 'Ничего не найдено', 'webkarma-restaurant' ); ?></h1>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'webkarma-restaurant' ); ?></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();
