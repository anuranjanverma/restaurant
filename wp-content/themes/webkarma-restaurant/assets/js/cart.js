/*
 * Dish quantity controls and cart badges via the WooCommerce Store API.
 * Without JavaScript the «Добавить» form posts add-to-cart to WooCommerce.
 *
 * Every control for a dish (cards, details dialog) is an element with
 * data-product-id containing a .qty block, rendered from one shared state:
 * - `lines`: server cart (product id -> { key, quantity });
 * - `desired`: quantities chosen but not yet confirmed by the server.
 * Taps update the UI at once; requests are coalesced per dish and run one
 * at a time, so rapid taps never race or duplicate. On failure the server
 * cart is reloaded and the error is shown.
 */
( function () {
	'use strict';

	var cfg = window.wkrCart;
	if ( ! cfg || ! window.fetch || cfg.ready ) {
		return; // Missing config/fetch, or already initialised.
	}
	cfg.ready = true;

	var COALESCE_MS = 350;
	var DOUBLE_CLICK_MS = 350;

	var nonce = cfg.nonce;
	var queue = Promise.resolve();
	var lines = {};
	var desired = {};
	var timers = {};
	var addedAt = {};
	var serverCount = 0;
	var status = document.querySelector( '[data-cart-status]' );
	var toast = document.querySelector( '[data-cart-error]' );
	var toastTimer;

	function format( template ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return template.replace( /%(\d\$)?[sd]/g, function ( match, pos ) {
			return pos ? args[ parseInt( pos, 10 ) - 1 ] : args[ i++ ];
		} );
	}

	function decode( html ) {
		var el = document.createElement( 'textarea' );
		el.innerHTML = html;
		return el.value.replace( /<[^>]*>/g, '' );
	}

	function announce( message ) {
		if ( status ) {
			status.textContent = '';
			window.setTimeout( function () {
				status.textContent = message;
			}, 50 );
		}
	}

	function showError( message ) {
		if ( ! toast ) {
			return;
		}
		toast.textContent = message || cfg.i18n.error;
		toast.setAttribute( 'role', 'alert' );
		toast.hidden = false;
		window.clearTimeout( toastTimer );
		toastTimer = window.setTimeout( function () {
			toast.hidden = true;
			toast.removeAttribute( 'role' );
		}, 6000 );
	}

	function request( method, path, body ) {
		return fetch( cfg.api + path, {
			method: method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', Nonce: nonce },
			body: body ? JSON.stringify( body ) : undefined
		} ).then( function ( response ) {
			var fresh = response.headers.get( 'Nonce' );
			if ( fresh ) {
				nonce = fresh;
			}
			return response.json().catch( function () {
				return {};
			} ).then( function ( data ) {
				if ( ! response.ok ) {
					throw new Error( data && data.message ? decode( data.message ) : cfg.i18n.error );
				}
				return data;
			} );
		} );
	}

	function enqueue( task ) {
		queue = queue.then( task, task );
		return queue;
	}

	function quantityOf( id ) {
		if ( Object.prototype.hasOwnProperty.call( desired, id ) ) {
			return desired[ id ];
		}
		return lines[ id ] ? lines[ id ].quantity : 0;
	}

	function totalCount() {
		var count = serverCount;
		Object.keys( desired ).forEach( function ( id ) {
			count += desired[ id ] - ( lines[ id ] ? lines[ id ].quantity : 0 );
		} );
		return Math.max( 0, count );
	}

	function nameOf( id ) {
		var box = document.querySelector( '[data-product-id="' + id + '"][data-name]' );
		return box ? box.getAttribute( 'data-name' ) : '';
	}

	function renderBox( box ) {
		var qty = box.querySelector( '.qty' );
		if ( ! qty ) {
			return;
		}
		var id = box.getAttribute( 'data-product-id' );
		var quantity = quantityOf( id );
		qty.setAttribute( 'data-qty', String( quantity ) );
		qty.setAttribute( 'aria-busy', Object.prototype.hasOwnProperty.call( desired, id ) ? 'true' : 'false' );
		qty.querySelector( '.qty__add' ).hidden = quantity > 0;
		qty.querySelector( '.qty__stepper' ).hidden = quantity < 1;
		qty.querySelector( '.qty__value' ).textContent = String( Math.max( 1, quantity ) );
	}

	function renderBadges() {
		var count = totalCount();
		document.querySelectorAll( '[data-cart-count]' ).forEach( function ( el ) {
			el.textContent = String( count );
			el.hidden = count < 1;
		} );
		document.querySelectorAll( '[data-cart-link]' ).forEach( function ( el ) {
			el.setAttribute( 'aria-label', format( cfg.i18n.cartLabel, count ) );
		} );
	}

	function renderId( id ) {
		document.querySelectorAll( '[data-product-id="' + id + '"]' ).forEach( renderBox );
		renderBadges();
	}

	function renderAll() {
		document.querySelectorAll( '[data-product-id]' ).forEach( renderBox );
		renderBadges();
	}

	function applyCart( cart ) {
		lines = {};
		( cart.items || [] ).forEach( function ( item ) {
			var existing = lines[ item.id ];
			lines[ item.id ] = {
				key: item.key,
				quantity: ( existing ? existing.quantity : 0 ) + item.quantity
			};
		} );
		serverCount = cart.items_count || 0;
		renderAll();
	}

	// Send the latest desired quantity for one dish (runs inside the queue).
	function sync( id ) {
		if ( ! Object.prototype.hasOwnProperty.call( desired, id ) ) {
			return Promise.resolve();
		}
		var target = desired[ id ];
		var line = lines[ id ];
		var current = line ? line.quantity : 0;
		var call;

		if ( target === current ) {
			delete desired[ id ];
			renderId( id );
			return Promise.resolve();
		}
		if ( ! line ) {
			call = request( 'POST', 'cart/add-item', { id: parseInt( id, 10 ), quantity: target } );
		} else if ( target > 0 ) {
			call = request( 'POST', 'cart/update-item', { key: line.key, quantity: target } );
		} else {
			call = request( 'POST', 'cart/remove-item', { key: line.key } );
		}

		return call.then( function ( cart ) {
			if ( desired[ id ] === target ) {
				delete desired[ id ];
			}
			applyCart( cart );
			var name = nameOf( id );
			if ( target === 0 ) {
				announce( format( cfg.i18n.removed, name ) );
			} else if ( current === 0 ) {
				announce( format( cfg.i18n.added, name ) );
			} else {
				announce( format( cfg.i18n.updated, name, target ) );
			}
		} ).catch( function ( error ) {
			delete desired[ id ];
			window.clearTimeout( timers[ id ] );
			delete timers[ id ];
			showError( error && error.message );
			announce( ( error && error.message ) || cfg.i18n.error );
			return request( 'GET', 'cart' ).then( applyCart ).catch( renderAll );
		} );
	}

	function schedule( id, delay ) {
		window.clearTimeout( timers[ id ] );
		timers[ id ] = window.setTimeout( function () {
			delete timers[ id ];
			enqueue( function () {
				return sync( id );
			} );
		}, delay );
	}

	function change( box, delta, pressed ) {
		var id = box.getAttribute( 'data-product-id' );
		var before = quantityOf( id );
		var after = Math.max( 0, before + delta );
		if ( after === before ) {
			return;
		}
		desired[ id ] = after;
		renderId( id );

		// Keep focus on a visible control in the same box.
		if ( before === 0 ) {
			addedAt[ id ] = Date.now();
			box.querySelector( '.qty__btn[data-step="1"]' ).focus();
		} else if ( after === 0 ) {
			box.querySelector( '.qty__add button' ).focus();
		} else if ( pressed ) {
			pressed.focus();
		}

		schedule( id, before === 0 ? 0 : COALESCE_MS );
	}

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( '.qty__add' );
		var box = form && form.closest( '[data-product-id]' );
		if ( ! box ) {
			return;
		}
		event.preventDefault();
		change( box, 1, null );
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.qty__btn' );
		var box = button && button.closest( '[data-product-id]' );
		if ( ! box ) {
			return;
		}
		// The second click of a double-click on «Добавить» lands on the stepper.
		if ( Date.now() - ( addedAt[ box.getAttribute( 'data-product-id' ) ] || 0 ) < DOUBLE_CLICK_MS ) {
			return;
		}
		change( box, parseInt( button.getAttribute( 'data-step' ), 10 ), button );
	} );

	// Re-render on demand (e.g. after dishes.js fills the details dialog).
	document.addEventListener( 'wkr:cart-render', renderAll );

	// Initial state: server-rendered data-qty/data-key and the badge count.
	document.querySelectorAll( '[data-product-id] .qty' ).forEach( function ( qty ) {
		var id = qty.closest( '[data-product-id]' ).getAttribute( 'data-product-id' );
		var quantity = parseInt( qty.getAttribute( 'data-qty' ), 10 ) || 0;
		if ( quantity > 0 ) {
			lines[ id ] = { key: qty.getAttribute( 'data-key' ), quantity: quantity };
		}
	} );
	var badge = document.querySelector( '[data-cart-count]' );
	serverCount = badge ? parseInt( badge.textContent, 10 ) || 0 : 0;
	renderAll();

	// Back/forward cache can show a stale page: refresh from the server.
	window.addEventListener( 'pageshow', function ( event ) {
		if ( event.persisted ) {
			enqueue( function () {
				return request( 'GET', 'cart' ).then( applyCart ).catch( function () {} );
			} );
		}
	} );
}() );
