/*
 * Cart page: «−»/«+» buttons drive WooCommerce's own cart form, so totals
 * always come from the server (wc-cart replaces .woocommerce-cart-form and
 * .cart_totals). Without JavaScript the number fields + «Обновить корзину»
 * and the remove links work as normal form actions.
 *
 * Focus: WooCommerce replaces the form after each update and blockUI may
 * move focus when it unblocks, so focus is restored once the form is no
 * longer blocked — to the same control after a quantity change, to the dish
 * now in that position after a removal, or to «Перейти в меню» when the
 * cart becomes empty. Focus never scrolls the page.
 */
( function ( $ ) {
	'use strict';

	var DEBOUNCE_MS = 600;
	var timer;
	var refocus = null;
	var status = document.querySelector( '[data-cart-status]' );
	var labels = window.wkrCartPage || { updated: '', removed: '', emptied: '' };

	function enhance() {
		$( '.woocommerce-cart-form' ).addClass( 'is-enhanced' );
		$( '.cart-qty__btn' ).prop( 'hidden', false );
	}

	function announce( text ) {
		if ( status && text ) {
			status.textContent = '';
			window.setTimeout( function () {
				status.textContent = text;
			}, 50 );
		}
	}

	function syncBadges( count ) {
		document.querySelectorAll( '[data-cart-count]' ).forEach( function ( el ) {
			el.textContent = String( count );
			el.hidden = count < 1;
		} );
	}

	function cartCount() {
		var summary = document.querySelector( '.cart_totals[data-cart-count-value]' );
		return summary ? parseInt( summary.getAttribute( 'data-cart-count-value' ), 10 ) || 0 : 0;
	}

	/*
	 * Run once WooCommerce has finished its own post-update work: the cart is
	 * unblocked, and woocommerce.js has focused its notice (it does so 500 ms
	 * after updated_wc_div so the notice is announced). Ours comes last.
	 */
	var WC_NOTICE_FOCUS_MS = 550;
	function whenWooDone( callback ) {
		var started = Date.now();
		( function wait() {
			var blocked = document.querySelector( '.woocommerce-cart-form .blockUI, .cart_totals .blockUI, .woocommerce-cart-form.processing' );
			if ( ( blocked || Date.now() - started < WC_NOTICE_FOCUS_MS ) && Date.now() - started < 4000 ) {
				window.setTimeout( wait, 50 );
			} else {
				callback();
			}
		}() );
	}

	// Focus without moving the page; stop WooCommerce's scroll-to-notice
	// animation, and scroll only if the target would otherwise be off-screen.
	function focusWithoutScroll( el ) {
		if ( ! el ) {
			return;
		}
		$( 'html, body' ).stop( true );
		el.focus( { preventScroll: true } );
		var rect = el.getBoundingClientRect();
		if ( rect.top < 0 || rect.bottom > window.innerHeight ) {
			el.scrollIntoView( { block: 'center' } );
		}
	}

	function submitUpdate() {
		$( '.woocommerce-cart-form :input[name="update_cart"]' ).prop( 'disabled', false ).trigger( 'click' );
	}

	function schedule() {
		window.clearTimeout( timer );
		timer = window.setTimeout( submitUpdate, DEBOUNCE_MS );
	}

	$( document ).on( 'click', '.cart-qty__btn', function () {
		var $input = $( this ).closest( '.cart-qty' ).find( 'input.qty' );
		var max = parseInt( $input.attr( 'max' ), 10 );
		var next = ( parseInt( $input.val(), 10 ) || 0 ) + parseInt( $( this ).data( 'step' ), 10 );
		next = Math.max( 0, isNaN( max ) ? next : Math.min( max, next ) );
		$input.val( next ).trigger( 'change' );
		refocus = {
			type: next === 0 ? 'removed' : 'qty',
			name: $input.attr( 'name' ),
			step: String( $( this ).data( 'step' ) ),
			index: $( this ).closest( '.cart_item' ).index()
		};
		schedule();
	} );

	// Typed quantities update once the field is left (Enter is handled by WooCommerce).
	$( document ).on( 'change', '.woocommerce-cart-form input.qty', function ( event ) {
		if ( event.originalEvent ) {
			refocus = { type: 'qty', name: $( this ).attr( 'name' ), step: '', index: $( this ).closest( '.cart_item' ).index() };
			schedule();
		}
	} );

	// Removal is handled by WooCommerce (AJAX); remember the row position.
	$( document ).on( 'click', '.woocommerce-cart-form .product-remove > a', function () {
		refocus = { type: 'removed', index: $( this ).closest( '.cart_item' ).index() };
	} );

	$( document.body ).on( 'updated_wc_div', function () {
		var wanted = refocus;
		refocus = null;
		enhance();
		syncBadges( cartCount() );
		if ( ! document.querySelector( '.woocommerce-cart-form' ) ) {
			return; // Emptied: handled by wc_cart_emptied.
		}
		announce( wanted && wanted.type === 'removed' ? labels.removed : labels.updated );
		if ( ! wanted ) {
			return;
		}
		whenWooDone( function () {
			var rows = document.querySelectorAll( '.woocommerce-cart-form .cart_item' );
			var target = null;
			if ( wanted.type === 'qty' ) {
				var input = document.querySelector( '.woocommerce-cart-form input[name="' + wanted.name + '"]' );
				target = input && wanted.step ? input.closest( '.cart-qty' ).querySelector( '.cart-qty__btn[data-step="' + wanted.step + '"]' ) : input;
			}
			if ( ! target && rows.length ) {
				// The dish now in the removed dish's place (or the last one).
				var row = rows[ Math.min( Math.max( wanted.index, 0 ), rows.length - 1 ) ];
				target = row.querySelector( '.cart-item__name a, .cart-qty__btn[data-step="-1"]' );
			}
			focusWithoutScroll( target );
		} );
	} );

	$( document.body ).on( 'wc_cart_emptied', function () {
		refocus = null;
		syncBadges( 0 );
		announce( labels.emptied );
		whenWooDone( function () {
			focusWithoutScroll( document.querySelector( '.cart-empty-state .button' ) );
		} );
	} );

	enhance();
}( jQuery ) );
