( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var dismissButton = event.target.closest( '.dgw-feedback__dismiss' );
		if ( dismissButton ) {
			var feedback = dismissButton.closest( '.dgw-feedback' );
			if ( feedback ) {
				feedback.remove();
			}

			if ( window.history && window.URL ) {
				var cleanUrl = new URL( window.location.href );
				cleanUrl.searchParams.delete( 'dgw_message' );
				window.history.replaceState( {}, document.title, cleanUrl.toString() );
			}
		}
	} );

	document.addEventListener( 'submit', function ( event ) {
		var button = event.target.querySelector( '[data-dgw-confirm]' );
		if ( button && ! window.confirm( button.getAttribute( 'data-dgw-confirm' ) ) ) {
			event.preventDefault();
		}
	} );
}() );
