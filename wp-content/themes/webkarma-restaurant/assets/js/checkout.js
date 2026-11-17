/*
 * Checkout enhancements on top of WooCommerce's checkout.js:
 * - delivery/pickup: remembered choice, aria-required on the street field;
 *   visibility itself is CSS (:has / data-fulfilment), so it also works
 *   without JavaScript;
 * - validation: WooCommerce 11 renders inline errors for fields itself
 *   (.checkout-inline-error-message + aria-describedby/aria-invalid); this
 *   adds the error for the delivery/pickup group (a fieldset, which
 *   WooCommerce skips) and moves focus to the first invalid field;
 * - «Отправляем заявку…» while submitting; WooCommerce blocks repeated
 *   submits while processing and the server rejects a reused form token.
 */
( function ( $ ) {
	'use strict';

	var STORAGE_KEY = 'wk_fulfilment';
	var ADDRESS = [ 'wk_street', 'wk_apartment', 'wk_entrance', 'wk_floor', 'wk_intercom' ];
	var root = document.documentElement;
	var $form = $( 'form.checkout' );
	if ( ! $form.length ) {
		return;
	}

	/* Errors ---------------------------------------------------------------------- */
	// Remove an error from a field, whether rendered by WooCommerce (JS) or the server (no-JS fallback).
	function clearError( id ) {
		var $field = id === 'wk_fulfilment' ? $( '#wk_fulfilment_field' ) : $( '#' + id );
		var $row = $field.closest( '.form-row, fieldset' );
		$row.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
		$row.find( '.checkout-inline-error-message, .field-error' ).remove();
		var described = ( $field.attr( 'aria-describedby' ) || '' ).split( ' ' ).filter( function ( v ) {
			return v && v !== id + '_description' && v !== id + '-error';
		} ).join( ' ' );
		if ( described ) {
			$field.attr( 'aria-describedby', described );
		} else {
			$field.removeAttr( 'aria-describedby' );
		}
		$field.removeAttr( 'aria-invalid' );
	}

	function focusField( id ) {
		var $target = id === 'wk_fulfilment' ? $form.find( 'input[name="wk_fulfilment"]:checked, input[name="wk_fulfilment"]' ).first() : $( '#' + id );
		if ( ! $target.length || ! $target.is( ':visible' ) ) {
			return false;
		}
		$( 'html, body' ).stop( true ); // WooCommerce animates to the notices; focus wins.
		$target.trigger( 'focus' );
		return true;
	}

	$( document.body ).on( 'checkout_error', function ( event, html ) {
		var ids = [];
		$( '<div>' ).html( html || '' ).find( 'li[data-id]' ).each( function () {
			var id = $( this ).attr( 'data-id' );
			ids.push( id );
			if ( id === 'wk_fulfilment' ) {
				clearError( id );
				$( '#wk_fulfilment_field' ).addClass( 'woocommerce-invalid' ).append(
					$( '<p class="checkout-inline-error-message" id="wk_fulfilment_description"></p>' ).text( $( this ).text().trim() )
				);
			}
		} );
		restoreButton();
		// After WooCommerce has inserted its notices/inline errors.
		window.setTimeout( function () {
			ids.some( focusField );
		}, 60 );
	} );

	/* Delivery / pickup ------------------------------------------------------ */
	function current() {
		return $form.find( 'input[name="wk_fulfilment"]:checked' ).val() || 'delivery';
	}

	function applyChoice() {
		var choice = current();
		root.setAttribute( 'data-fulfilment', choice );
		$form.find( '#wk_street' ).attr( 'aria-required', choice === 'delivery' ? 'true' : 'false' );
		clearError( 'wk_fulfilment' );
		if ( choice === 'pickup' ) {
			ADDRESS.forEach( clearError );
		}
	}

	// Use the choice made on the homepage/menu unless the server re-rendered a submitted form.
	try {
		var saved = window.localStorage.getItem( STORAGE_KEY );
		var submitted = document.querySelector( '.woocommerce-error, .field-error' );
		if ( ! submitted && ( saved === 'pickup' || saved === 'delivery' ) ) {
			$form.find( 'input[name="wk_fulfilment"][value="' + saved + '"]' ).prop( 'checked', true );
		}
	} catch ( e ) {}

	$form.on( 'change', 'input[name="wk_fulfilment"]', function () {
		applyChoice();
		try {
			window.localStorage.setItem( STORAGE_KEY, current() );
		} catch ( e ) {}
	} );
	applyChoice();

	// Typing clears that field's error (WooCommerce re-validates on blur/submit).
	$form.on( 'input', 'input, textarea', function () {
		if ( this.getAttribute( 'aria-invalid' ) === 'true' && this.id ) {
			clearError( this.id );
		}
	} );

	/* Submit state ------------------------------------------------------------- */
	function restoreButton() {
		var $button = $( '#place_order' );
		if ( $button.data( 'wkLabel' ) ) {
			$button.find( 'span' ).first().text( $button.data( 'wkLabel' ) );
		}
		$button.removeAttr( 'aria-busy' );
	}

	$form.on( 'checkout_place_order', function () {
		var $button = $( '#place_order' );
		var $label = $button.find( 'span' ).first();
		$button.data( 'wkLabel', $label.text() ).attr( 'aria-busy', 'true' );
		$label.text( ( window.wkrCheckout && window.wkrCheckout.sending ) || $label.text() );
		return true;
	} );
}( jQuery ) );
