/* Homepage: featured dish filter buttons (dish UI lives in dishes.js). */
( function () {
	'use strict';

	var grid = document.getElementById( 'featured-dishes' );
	if ( ! grid ) {
		return;
	}

	document.querySelectorAll( '.filter-tabs__btn' ).forEach( function ( button, index, all ) {
		button.addEventListener( 'click', function () {
			var filter = button.getAttribute( 'data-filter' );
			all.forEach( function ( other ) {
				other.setAttribute( 'aria-pressed', other === button ? 'true' : 'false' );
			} );
			grid.classList.toggle( 'is-all', ! filter );
			grid.querySelectorAll( '.dish-card' ).forEach( function ( card ) {
				var cats = ( card.getAttribute( 'data-cats' ) || '' ).split( ' ' );
				card.hidden = !! filter && cats.indexOf( filter ) === -1;
			} );
			if ( window.wkrCloseDishInfo ) {
				window.wkrCloseDishInfo();
			}
		} );
	} );
}() );
