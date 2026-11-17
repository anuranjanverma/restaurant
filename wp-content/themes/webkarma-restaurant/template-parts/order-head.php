<?php
/**
 * Heading for cart / checkout / confirmation with the 3-step progress.
 *
 * Expects $args: title, subtitle (optional), step (1–3), back_url, back_label
 * (optional), align ('start'|'center'), done (bool: all steps complete).
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

$wkr_steps = array(
	1 => __( 'Корзина', 'webkarma-restaurant' ),
	2 => __( 'Контакты', 'webkarma-restaurant' ),
	3 => __( 'Заявка', 'webkarma-restaurant' ),
);
$wkr_step  = (int) ( $args['step'] ?? 1 );
$wkr_done  = ! empty( $args['done'] );
$wkr_align = ( $args['align'] ?? 'start' ) === 'center' ? 'center' : 'start';
?>
<header class="order-head order-head--<?php echo esc_attr( $wkr_align ); ?>">
	<?php if ( ! empty( $args['back_url'] ) ) : ?>
		<a class="order-head__back" href="<?php echo esc_url( $args['back_url'] ); ?>">
			<?php echo wkr_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			<?php echo esc_html( $args['back_label'] ); ?>
		</a>
	<?php endif; ?>

	<div class="order-head__main">
		<?php if ( ! empty( $args['title'] ) ) : // The confirmation page has its own heading. ?>
		<div class="order-head__text">
			<h1 class="order-head__title">
				<?php if ( 'center' === $wkr_align ) : ?>
					<span class="order-head__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php endif; ?>
				<span><?php echo esc_html( $args['title'] ); ?></span>
				<?php if ( 'center' === $wkr_align ) : ?>
					<span class="order-head__ornament" aria-hidden="true"><?php echo wkr_icon( 'ornament' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
				<?php endif; ?>
			</h1>
			<?php if ( ! empty( $args['subtitle'] ) ) : ?>
				<p class="order-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<ol class="order-steps" aria-label="<?php esc_attr_e( 'Этапы оформления', 'webkarma-restaurant' ); ?>">
			<?php foreach ( $wkr_steps as $wkr_n => $wkr_label ) : ?>
				<?php
				$wkr_state = ( $wkr_done || $wkr_n < $wkr_step ) ? 'done' : ( $wkr_n === $wkr_step ? 'current' : 'next' );
				?>
				<li class="order-steps__item is-<?php echo esc_attr( $wkr_state ); ?>"<?php echo 'current' === $wkr_state || ( $wkr_done && 3 === $wkr_n ) ? ' aria-current="step"' : ''; ?>>
					<span class="order-steps__mark" aria-hidden="true"><?php echo 'done' === $wkr_state ? wkr_icon( 'check' ) : esc_html( (string) $wkr_n ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
					<span class="order-steps__label"><?php echo esc_html( $wkr_label ); ?><?php if ( 'done' === $wkr_state ) : ?><span class="screen-reader-text"> <?php esc_html_e( '(выполнено)', 'webkarma-restaurant' ); ?></span><?php endif; ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</header>
