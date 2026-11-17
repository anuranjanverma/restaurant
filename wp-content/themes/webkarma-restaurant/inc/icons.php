<?php
/**
 * Inline SVG icons (decorative unless a label is given). Colour follows
 * currentColor so icons respond to brand settings.
 *
 * @package WebKarmaRestaurant
 */

defined( 'ABSPATH' ) || exit;

/**
 * SVG markup for an icon.
 *
 * @param string $name  Icon name.
 * @param string $class Extra class.
 * @return string
 */
function wkr_icon( $name, $class = '' ) {
	$stroke = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
	$icons  = array(
		'arrow'     => '<path d="M4 12h15M13.5 6.5 19 12l-5.5 5.5"/>',
		'bag'       => '<path d="M5.5 8.5h13l-1.1 11.2a1.5 1.5 0 0 1-1.5 1.3H8.1a1.5 1.5 0 0 1-1.5-1.3z"/><path d="M9 10.5V7a3 3 0 0 1 6 0v3.5"/>',
		'truck'     => '<path d="M2.5 6.5h11v9.5h-11zM13.5 9.5h4l3 3.2V16h-7"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="16.5" cy="17.5" r="1.8"/>',
		'store'     => '<path d="M4.5 10.5V20h15v-9.5M3.5 7l1.8-3h13.4l1.8 3v1.2a2.6 2.6 0 0 1-5.5 0 2.6 2.6 0 0 1-5.5 0 2.6 2.6 0 0 1-5.5 0z"/><path d="M10 20v-5h4v5"/>',
		'phone'     => '<path d="M6.6 3.5 9 3.9l1.3 4-1.9 1.6a12 12 0 0 0 6.1 6.1l1.6-1.9 4 1.3.4 2.4a1.8 1.8 0 0 1-1.8 2.1A15.6 15.6 0 0 1 4.5 5.3a1.8 1.8 0 0 1 2.1-1.8z"/>',
		'pin'       => '<path d="M12 21s-6.5-5.7-6.5-11a6.5 6.5 0 0 1 13 0c0 5.3-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'minus'     => '<path d="M5 12h14"/>',
		'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7.2v6.3"/><circle cx="12" cy="16.6" r=".4" fill="currentColor"/>',
		'close'     => '<path d="m6 6 12 12M18 6 6 18"/>',
		'search'    => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
		'grid'      => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.2"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.2"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.2"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.2"/>',
		'chevron'   => '<path d="m9.5 6 6 6-6 6"/>',
		'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'trash'     => '<path d="M5 7h14M10 7V5h4v2M7 7l.8 12.2a1.5 1.5 0 0 0 1.5 1.3h5.4a1.5 1.5 0 0 0 1.5-1.3L17 7M10.5 11v6M13.5 11v6"/>',
		'clock'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'chevron-left' => '<path d="m14.5 6-6 6 6 6"/>',
		'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".5" fill="currentColor"/>',
	);

	if ( 'vk' === $name ) {
		$svg = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.8 17.6C7 17.6 3.7 13.6 3.5 7h2.9c.1 4.8 2.2 6.9 3.9 7.3V7h2.7v4.2c1.7-.2 3.4-2.1 4-4.2h2.7a8 8 0 0 1-3.7 5.2 8.3 8.3 0 0 1 4.3 5.4h-3a5.2 5.2 0 0 0-4.3-3.8v3.8z"/></svg>';
	} elseif ( 'ornament' === $name ) {
		// Four-petal lotus flourish used above section headings.
		$svg = '<svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"><path d="M20 2.5c3.2 4 3.4 8.2.1 11.5-3.4-3.3-3.2-7.5-.1-11.5zM20 37.5c3.2-4 3.4-8.2.1-11.5-3.4 3.3-3.2 7.5-.1 11.5zM2.5 20c4-3.2 8.2-3.4 11.5-.1-3.3 3.4-7.5 3.2-11.5.1zM37.5 20c-4-3.2-8.2-3.4-11.5-.1 3.3 3.4 7.5 3.2 11.5.1z"/><path d="m20 16.2 3.8 3.8-3.8 3.8-3.8-3.8z" fill="currentColor"/><path d="M14.6 14.6c-2.4-.4-3.8-2-3.9-4.3 2.3.1 3.9 1.5 3.9 4.3zM25.4 14.6c2.4-.4 3.8-2 3.9-4.3-2.3.1-3.9 1.5-3.9 4.3zM14.6 25.4c-2.4.4-3.8 2-3.9 4.3 2.3-.1 3.9-1.5 3.9-4.3zM25.4 25.4c2.4.4 3.8 2 3.9 4.3-2.3-.1-3.9-1.5-3.9-4.3z"/></svg>';
	} elseif ( isset( $icons[ $name ] ) ) {
		$svg = '<svg viewBox="0 0 24 24" ' . $stroke . '>' . $icons[ $name ] . '</svg>';
	} else {
		return '';
	}

	return str_replace(
		'<svg ',
		'<svg class="icon icon--' . esc_attr( $name ) . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-hidden="true" focusable="false" ',
		$svg
	);
}
