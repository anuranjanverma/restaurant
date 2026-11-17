/*
 * Menu page: anchor offsets below sticky UI, active category on scroll
 * (sidebar, mobile rail, sheet), «Все категории» bottom sheet, live search.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var header = document.querySelector( '.site-header' );
	var rail = document.querySelector( '.menu-rail' );
	var railNav = document.querySelector( '.menu-rail__nav' );
	var sidebarList = document.querySelector( '.menu-sidebar__list' );
	var sections = Array.prototype.slice.call( document.querySelectorAll( '.menu-section' ) );
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var activeId = null;

	function behavior() {
		return reduceMotion.matches ? 'auto' : 'smooth';
	}

	/* Sticky offset: header (+ rail when it is sticky) ----------------------- */
	function stickyOffset() {
		var offset = header ? header.getBoundingClientRect().bottom : 0;
		if ( rail && getComputedStyle( rail ).position === 'sticky' ) {
			offset = Math.max( offset, rail.getBoundingClientRect().bottom );
		}
		return Math.max( 0, Math.round( offset ) );
	}

	function updateOffset() {
		// Header/rail sizes do not change on scroll; measure at the top of the layout.
		var h = header ? header.offsetHeight : 0;
		var adminBar = parseInt( getComputedStyle( document.body ).getPropertyValue( '--wp-admin--admin-bar--height' ), 10 ) || 0;
		var r = rail && getComputedStyle( rail ).position === 'sticky' ? rail.offsetHeight : 0;
		root.style.setProperty( '--wk-anchor-offset', ( h + r + adminBar + 16 ) + 'px' );
	}

	/* Active category -------------------------------------------------------- */
	function setActive( id ) {
		if ( id === activeId ) {
			return;
		}
		activeId = id;
		document.querySelectorAll( '[data-section]' ).forEach( function ( link ) {
			if ( link.getAttribute( 'data-section' ) === id ) {
				link.setAttribute( 'aria-current', 'location' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );

		// Bring the active chip into view inside the rail only (never scroll the page).
		var chip = railNav && railNav.querySelector( '[data-section="' + id + '"]' );
		if ( chip && railNav.offsetParent !== null ) {
			var left = chip.offsetLeft - railNav.offsetLeft;
			if ( left < railNav.scrollLeft || left + chip.offsetWidth > railNav.scrollLeft + railNav.clientWidth ) {
				railNav.scrollTo( { left: Math.max( 0, left - 16 ), behavior: behavior() } );
			}
		}
		var link = sidebarList && sidebarList.querySelector( '[data-section="' + id + '"]' );
		if ( link && sidebarList.scrollHeight > sidebarList.clientHeight ) {
			var top = link.offsetTop - sidebarList.offsetTop;
			if ( top < sidebarList.scrollTop || top + link.offsetHeight > sidebarList.scrollTop + sidebarList.clientHeight ) {
				sidebarList.scrollTop = top - sidebarList.clientHeight / 2;
			}
		}
	}

	function trackActive() {
		var line = stickyOffset() + 24;
		var current = 'menu-top';
		sections.forEach( function ( section ) {
			if ( ! section.hidden && section.getBoundingClientRect().top <= line ) {
				current = section.id;
			}
		} );
		// At the very bottom the last visible section is active even if short.
		if ( window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2 ) {
			var visible = sections.filter( function ( s ) {
				return ! s.hidden;
			} );
			if ( visible.length ) {
				current = visible[ visible.length - 1 ].id;
			}
		}
		setActive( current );
	}

	var ticking = false;
	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( function () {
				ticking = false;
				trackActive();
			} );
		}
	}, { passive: true } );
	window.addEventListener( 'resize', function () {
		updateOffset();
		trackActive();
	} );
	updateOffset();
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( function () {
			updateOffset();
			trackActive();
		} );
	}
	trackActive();

	/* Jump to a section and continue keyboard navigation there -------------- */
	function jumpTo( id ) {
		var target = document.getElementById( id );
		if ( ! target ) {
			return;
		}
		target.scrollIntoView( { behavior: behavior(), block: 'start' } );
		var focusTarget = target.querySelector( '.menu-section__title' ) || target;
		focusTarget.focus( { preventScroll: true } );
		if ( window.history && window.history.replaceState ) {
			window.history.replaceState( null, '', '#' + id );
		}
		setActive( id );
	}

	document.querySelectorAll( '.menu-sidebar [data-section], .menu-rail [data-section]' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			jumpTo( link.getAttribute( 'data-section' ) );
		} );
	} );

	/* «Все категории» bottom sheet ------------------------------------------- */
	var sheet = document.getElementById( 'category-sheet' );
	var sheetButton = document.querySelector( '.menu-rail__all' );
	if ( sheet && sheetButton && typeof sheet.showModal === 'function' ) {
		var chosen = null;

		sheetButton.addEventListener( 'click', function () {
			chosen = null;
			sheet.showModal();
			root.classList.add( 'has-modal' );
			sheetButton.setAttribute( 'aria-expanded', 'true' );
			var current = sheet.querySelector( '[aria-current]' );
			( current || sheet.querySelector( '[data-sheet-close]' ) ).focus();
		} );

		if ( window.wkrTrapTab ) {
			sheet.addEventListener( 'keydown', window.wkrTrapTab( sheet ) );
		}

		sheet.querySelector( '[data-sheet-close]' ).addEventListener( 'click', function () {
			sheet.close();
		} );

		sheet.addEventListener( 'click', function ( event ) {
			if ( event.target === sheet ) {
				sheet.close(); // Backdrop.
			}
		} );

		sheet.querySelectorAll( '[data-section]' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				chosen = link.getAttribute( 'data-section' );
				sheet.close();
			} );
		} );

		sheet.addEventListener( 'close', function () {
			root.classList.remove( 'has-modal' );
			sheetButton.setAttribute( 'aria-expanded', 'false' );
			if ( chosen ) {
				jumpTo( chosen ); // Focus moves to the chosen section heading.
			} else {
				sheetButton.focus();
			}
		} );
	} else if ( sheetButton ) {
		sheetButton.hidden = true;
	}

	/* Live search ------------------------------------------------------------- */
	var form = document.querySelector( '.menu-search' );
	var input = document.getElementById( 'menu-search-input' );
	var clear = document.querySelector( '.menu-search__clear' );
	var status = document.querySelector( '.menu-status' );
	var empty = document.querySelector( '.menu-empty' );
	var dishes = window.wkrDishes || {};
	var serverQuery = input ? input.value.trim() : '';
	var timer;

	function normalize( value ) {
		return String( value || '' ).toLowerCase().replace( /ё/g, 'е' ).replace( /\s+/g, ' ' ).trim();
	}

	function filter() {
		var query = normalize( input.value );
		var total = 0;
		sections.forEach( function ( section ) {
			var category = normalize( section.querySelector( '.menu-section__title' ).textContent );
			var shown = 0;
			section.querySelectorAll( '.dish-card' ).forEach( function ( card ) {
				var dish = dishes[ card.getAttribute( 'data-product-id' ) ] || {};
				var haystack = normalize( card.getAttribute( 'data-name' ) + ' ' + ( dish.desc || '' ) + ' ' + category );
				var match = ! query || haystack.indexOf( query ) !== -1;
				card.hidden = ! match;
				shown += match ? 1 : 0;
			} );
			section.hidden = shown === 0;
			document.querySelectorAll( '[data-section="' + section.id + '"]' ).forEach( function ( link ) {
				link.closest( 'li' ).hidden = shown === 0;
			} );
			total += shown;
		} );
		clear.hidden = ! input.value;
		empty.hidden = total > 0;
		status.textContent = query ? ( total ? status.getAttribute( 'data-found' ).replace( '%d', total ) : '' ) : '';
		if ( window.wkrCloseDishInfo ) {
			window.wkrCloseDishInfo();
		}
		trackActive();
	}

	if ( form && input ) {
		status.setAttribute( 'data-found', form.getAttribute( 'data-found' ) || '%d' );
		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( filter, 120 );
		} );
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault(); // Filtering is live; hide the phone keyboard.
			filter();
			input.blur();
		} );
		clear.addEventListener( 'click', function () {
			if ( serverQuery ) {
				window.location.href = form.getAttribute( 'data-menu-url' ); // Server-filtered results: reload full menu.
				return;
			}
			input.value = '';
			filter();
			input.focus();
		} );
		clear.hidden = ! input.value;
	}
}() );
