/*
 * Dish UI shared by the homepage and the menu:
 * - delivery/pickup choice (remembered for checkout);
 * - «!» descriptions: mouse hover or keyboard focus opens, click/tap pins,
 *   Escape / outside tap / second press closes;
 * - details dialog with photo gallery (native <dialog>: focus trap, Escape).
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'wk_fulfilment';

	/**
	 * Keep Tab / Shift+Tab cycling inside an open modal (native <dialog> would
	 * otherwise let focus leave to the browser UI). Shared with menu.js.
	 */
	window.wkrTrapTab = function ( container ) {
		return function ( event ) {
			if ( event.key !== 'Tab' ) {
				return;
			}
			var focusable = Array.prototype.filter.call(
				container.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), [tabindex]:not([tabindex="-1"])' ),
				function ( el ) {
					return el.offsetParent !== null && ! el.closest( '[hidden]' );
				}
			);
			if ( ! focusable.length ) {
				return;
			}
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];
			if ( event.shiftKey && ( document.activeElement === first || ! container.contains( document.activeElement ) ) ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && ( document.activeElement === last || ! container.contains( document.activeElement ) ) ) {
				event.preventDefault();
				first.focus();
			}
		};
	};
	var text = window.wkrDishText || { photo: '%d', photoOf: '%s, %d' };

	/* Delivery / pickup ------------------------------------------------------ */
	var radios = document.querySelectorAll( 'input[name="wk-fulfilment"]' );

	function showChoice( value ) {
		radios.forEach( function ( radio ) {
			radio.checked = radio.value === value;
		} );
		document.querySelectorAll( '.fulfilment__hint' ).forEach( function ( hint ) {
			hint.textContent = hint.getAttribute( value === 'pickup' ? 'data-pickup' : 'data-delivery' );
		} );
	}

	try {
		var saved = window.localStorage.getItem( STORAGE_KEY );
		if ( saved === 'pickup' || saved === 'delivery' ) {
			showChoice( saved );
		}
	} catch ( e ) {}

	radios.forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			showChoice( radio.value );
			try {
				window.localStorage.setItem( STORAGE_KEY, radio.value );
			} catch ( e ) {}
		} );
	} );

	/* Descriptions («!») ------------------------------------------------------ */
	var openButton = null;
	var pinned = false;

	function panelFor( button ) {
		return document.getElementById( button.getAttribute( 'aria-controls' ) );
	}

	function openInfo( button, pin ) {
		if ( openButton && openButton !== button ) {
			closeInfo( openButton );
		}
		panelFor( button ).hidden = false;
		button.setAttribute( 'aria-expanded', 'true' );
		openButton = button;
		pinned = !! pin;
	}

	function closeInfo( button ) {
		panelFor( button ).hidden = true;
		button.setAttribute( 'aria-expanded', 'false' );
		if ( openButton === button ) {
			openButton = null;
			pinned = false;
		}
	}

	window.wkrCloseDishInfo = function () {
		if ( openButton ) {
			closeInfo( openButton );
		}
	};

	document.querySelectorAll( '.dish-card__info-btn' ).forEach( function ( button ) {
		var card = button.closest( '.dish-card' );

		button.addEventListener( 'click', function () {
			if ( openButton === button && pinned ) {
				closeInfo( button );
			} else {
				openInfo( button, true );
			}
		} );
		button.addEventListener( 'pointerenter', function ( event ) {
			if ( event.pointerType === 'mouse' && openButton !== button ) {
				openInfo( button, false );
			}
		} );
		card.addEventListener( 'pointerleave', function ( event ) {
			if ( event.pointerType === 'mouse' && openButton === button && ! pinned ) {
				closeInfo( button );
			}
		} );
		button.addEventListener( 'focus', function () {
			if ( button.matches( ':focus-visible' ) && openButton !== button ) {
				openInfo( button, false );
			}
		} );
		button.addEventListener( 'blur', function () {
			if ( openButton === button && ! pinned ) {
				closeInfo( button );
			}
		} );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && openButton && ! document.querySelector( 'dialog[open]' ) ) {
			var button = openButton;
			closeInfo( button );
			button.focus();
		}
	} );

	document.addEventListener( 'pointerdown', function ( event ) {
		if ( openButton && ! openButton.contains( event.target ) && ! panelFor( openButton ).contains( event.target ) ) {
			closeInfo( openButton );
		}
	} );

	/* Details dialog ----------------------------------------------------------- */
	var dialog = document.getElementById( 'dish-dialog' );
	var dishes = window.wkrDishes || {};
	if ( ! dialog || typeof dialog.showModal !== 'function' ) {
		return; // No dialog support: photo links open the product page.
	}

	var figure = dialog.querySelector( '.dish-dialog__figure' );
	var thumbs = dialog.querySelector( '.dish-dialog__thumbs' );
	var count = dialog.querySelector( '.dish-dialog__count' );
	var prev = dialog.querySelector( '.dish-dialog__nav--prev' );
	var next = dialog.querySelector( '.dish-dialog__nav--next' );
	var opener = null;
	var images = [];
	var current = 0;

	function setText( selector, text ) {
		var el = dialog.querySelector( selector );
		el.textContent = text || '';
		el.hidden = ! text;
	}

	function showImage( index ) {
		if ( ! images.length ) {
			return;
		}
		current = ( index + images.length ) % images.length;
		var data = images[ current ];
		var img = document.createElement( 'img' );
		img.src = data.src;
		if ( data.srcset ) {
			img.srcset = data.srcset;
			img.sizes = '(min-width: 56rem) 560px, 100vw';
		}
		img.width = data.width;
		img.height = data.height;
		img.alt = images.length > 1 ? text.photoOf.replace( '%s', dialog.dataset.name ).replace( '%d', current + 1 ) : dialog.dataset.name;
		figure.replaceChildren( img );
		count.textContent = ( current + 1 ) + ' / ' + images.length;
		thumbs.querySelectorAll( 'button' ).forEach( function ( button, i ) {
			button.setAttribute( 'aria-pressed', i === current ? 'true' : 'false' );
		} );
	}

	function fill( card ) {
		var id = card.getAttribute( 'data-product-id' );
		var dish = dishes[ id ] || {};
		images = dish.images || [];
		dialog.dataset.productId = id;
		dialog.setAttribute( 'data-product-id', id );
		dialog.setAttribute( 'data-name', dish.name || card.getAttribute( 'data-name' ) );
		dialog.dataset.name = dish.name || card.getAttribute( 'data-name' );

		setText( '.dish-dialog__title', dialog.dataset.name );
		setText( '.dish-dialog__weight', dish.weight );
		setText( '.dish-dialog__desc', dish.desc );
		setText( '.dish-dialog__price', dish.price );

		// Same quantity component as the card (kept in sync by cart.js).
		var controls = dialog.querySelector( '.dish-dialog__controls' );
		var source = card.querySelector( '.qty, .dish-card__unavailable' );
		controls.replaceChildren( source ? source.cloneNode( true ) : '' );

		thumbs.replaceChildren();
		images.forEach( function ( data, i ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'dish-dialog__thumb';
			button.setAttribute( 'aria-label', text.photo.replace( '%d', i + 1 ) );
			var img = document.createElement( 'img' );
			img.src = data.src;
			img.alt = '';
			img.loading = 'lazy';
			button.appendChild( img );
			button.addEventListener( 'click', function () {
				showImage( i );
			} );
			thumbs.appendChild( button );
		} );

		var many = images.length > 1;
		thumbs.hidden = ! many;
		count.hidden = ! many;
		prev.hidden = ! many;
		next.hidden = ! many;
		dialog.classList.toggle( 'has-no-image', ! images.length );
		if ( images.length ) {
			showImage( 0 );
		} else {
			figure.innerHTML = '<span class="dish-card__img--empty dish-dialog__empty" aria-hidden="true"></span>';
		}
		document.dispatchEvent( new CustomEvent( 'wkr:cart-render' ) );
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '[data-dish-open]' );
		if ( ! link || event.metaKey || event.ctrlKey || event.shiftKey || event.button > 0 ) {
			return; // Modified clicks still open the product page.
		}
		var card = link.closest( '.dish-card' );
		if ( ! card ) {
			return;
		}
		event.preventDefault();
		window.wkrCloseDishInfo();
		opener = link;
		fill( card );
		dialog.showModal();
		document.documentElement.classList.add( 'has-modal' );
		dialog.querySelector( '.dish-dialog__inner' ).scrollTop = 0;
	} );

	prev.addEventListener( 'click', function () {
		showImage( current - 1 );
	} );
	next.addEventListener( 'click', function () {
		showImage( current + 1 );
	} );

	dialog.addEventListener( 'keydown', window.wkrTrapTab( dialog ) );

	dialog.addEventListener( 'keydown', function ( event ) {
		if ( images.length > 1 && ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) && ! event.target.closest( 'input, textarea' ) ) {
			event.preventDefault();
			showImage( current + ( event.key === 'ArrowRight' ? 1 : -1 ) );
		}
	} );

	dialog.querySelector( '[data-dialog-close]' ).addEventListener( 'click', function () {
		dialog.close();
	} );

	// Click on the backdrop (outside the panel) closes.
	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );

	dialog.addEventListener( 'close', function () {
		document.documentElement.classList.remove( 'has-modal' );
		dialog.querySelector( '.dish-dialog__controls' ).replaceChildren();
		dialog.removeAttribute( 'data-product-id' );
		if ( opener && document.contains( opener ) ) {
			opener.focus();
		}
	} );
}() );
