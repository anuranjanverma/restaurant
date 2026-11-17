/*
 * Floating cart: after scrolling stops, lift it above any dish control or
 * search/category UI it would otherwise cover (cap: 40% of the viewport).
 */
( function () {
	'use strict';

	var fab = document.querySelector( '.floating-cart' );
	if ( ! fab ) {
		return;
	}
	var GUARDED = '.qty, .dish-card__info-btn, .menu-search, .menu-rail, .fulfilment__choice, .featured__more, .site-footer__social';
	var timer;

	function overlaps( a, b ) {
		return a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top;
	}

	function avoid() {
		// Measure the resting position with the transition suspended (an
		// animating translate would report an in-between position).
		var previous = fab.style.translate;
		fab.style.transition = 'none';
		fab.style.translate = '';
		var base = fab.getBoundingClientRect();
		fab.style.translate = previous;
		void fab.offsetWidth; // Commit before re-enabling the transition.
		fab.style.transition = '';
		var max = window.innerHeight * 0.4;
		var shift = 0;
		var guarded = Array.prototype.filter.call( document.querySelectorAll( GUARDED ), function ( el ) {
			return el.offsetParent !== null || getComputedStyle( el ).position === 'sticky';
		} );
		// Move up past each overlapping control; a few passes settle stacked rows.
		for ( var pass = 0; pass < 4; pass++ ) {
			var rect = { left: base.left, right: base.right, top: base.top - shift, bottom: base.bottom - shift };
			var hit = null;
			guarded.forEach( function ( el ) {
				var r = el.getBoundingClientRect();
				if ( r.height && overlaps( rect, r ) && ( ! hit || r.top < hit.top ) ) {
					hit = r;
				}
			} );
			if ( ! hit ) {
				break;
			}
			shift = base.bottom - hit.top + 8;
		}
		if ( shift > max ) {
			shift = 0; // Too far: stay put rather than jump around the screen.
		}
		fab.style.translate = shift ? '0 ' + ( -Math.round( shift ) ) + 'px' : '';
	}

	function schedule() {
		window.clearTimeout( timer );
		timer = window.setTimeout( avoid, 140 );
	}

	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	window.addEventListener( 'load', avoid );
	document.addEventListener( 'click', schedule ); // Controls change size (add → stepper).
	schedule();
}() );

/* Mobile navigation toggle: aria-expanded state, Escape and outside click close. */
( function () {
	'use strict';

	var toggle = document.querySelector( '.nav-toggle' );
	var nav = document.getElementById( 'site-nav' );
	if ( ! toggle || ! nav ) {
		return;
	}

	function setOpen( open ) {
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		document.body.classList.toggle( 'nav-open', open );
	}

	toggle.addEventListener( 'click', function () {
		setOpen( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
			setOpen( false );
			toggle.focus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( toggle.getAttribute( 'aria-expanded' ) === 'true' && ! nav.contains( event.target ) && ! toggle.contains( event.target ) ) {
			setOpen( false );
		}
	} );
}() );
