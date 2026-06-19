/**
 * TKA WP Utils Admin Drag & Drop Sorting Script
 */

jQuery( document ).ready( function ( $ ) {
	var $tbody = $( '.wp-list-table tbody#the-list' );

	if ( ! $tbody.length ) {
		return;
	}

	$tbody.sortable( {
		items: 'tr',
		placeholder: 'ui-sortable-placeholder',
		update: function ( event, ui ) {
			var ids = [];
			var isTerm = false;

			$tbody.children( 'tr' ).each( function () {
				var rowId = $( this ).attr( 'id' );
				if ( rowId && rowId.indexOf( 'post-' ) === 0 ) {
					ids.push( rowId.replace( 'post-', '' ) );
				} else if ( rowId && rowId.indexOf( 'tag-' ) === 0 ) {
					ids.push( rowId.replace( 'tag-', '' ) );
					isTerm = true;
				}
			} );

			if ( ids.length === 0 ) {
				return;
			}

			// Add a subtle opacity while saving
			$tbody.css( 'opacity', '0.6' );

			var actionName = isTerm ? 'tka_wp_utils_save_term_order' : 'tka_wp_utils_save_order';

			$.post( tkaWpUtilsOrder.ajaxUrl, {
				action: actionName,
				nonce: tkaWpUtilsOrder.nonce,
				ids: ids
			}, function ( response ) {
				$tbody.css( 'opacity', '1' );

				if ( response.success ) {
					// Flash the dragged row green briefly to signal successful database write!
					ui.item.css( 'transition', 'background-color 0.1s ease' );
					ui.item.css( 'background-color', '#ecfdf5' );
					setTimeout( function () {
						ui.item.css( 'transition', 'background-color 0.8s ease' );
						ui.item.css( 'background-color', '' );
					}, 200 );
				} else {
					alert( 'Error: Could not save order.' );
				}
			} );
		}
	} );
} );
