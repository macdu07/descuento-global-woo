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

	function syncScopeFields() {
		var checked = document.querySelector( '.dgw-scope input[name="scope"]:checked' );
		var scope = checked ? checked.value : 'all';

		document.querySelectorAll( '[data-dgw-scope-field]' ).forEach( function ( field ) {
			field.hidden = field.getAttribute( 'data-dgw-scope-field' ) !== scope;
		} );
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( '.dgw-scope input[name="scope"]' ) ) {
			syncScopeFields();
		}
	} );

	// Move focus to the field that failed validation, once select2 has initialized.
	function focusInvalidField() {
		var form = document.querySelector( '.dgw-form[data-dgw-invalid]' );
		var field = form ? document.getElementById( form.getAttribute( 'data-dgw-invalid' ) ) : null;
		if ( ! field ) {
			return;
		}

		window.setTimeout( function () {
			var container = field.closest( '.dgw-field' );
			var search = container ? container.querySelector( '.select2-search__field' ) : null;
			( search || field ).focus();
			( container || field ).scrollIntoView( { block: 'center' } );
		}, 150 );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		syncScopeFields();
		focusInvalidField();
	} );
}() );
