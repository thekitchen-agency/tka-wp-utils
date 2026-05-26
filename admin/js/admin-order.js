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
			var postIds = [];

			$tbody.children( 'tr' ).each( function () {
				var rowId = $( this ).attr( 'id' );
				if ( rowId && rowId.indexOf( 'post-' ) === 0 ) {
					postIds.push( rowId.replace( 'post-', '' ) );
				}
			} );

			if ( postIds.length === 0 ) {
				return;
			}

			// Add a subtle opacity while saving
			$tbody.css( 'opacity', '0.6' );

			$.post( tkaWpUtilsOrder.ajaxUrl, {
				action: 'tka_wp_utils_save_order',
				nonce: tkaWpUtilsOrder.nonce,
				post_ids: postIds
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
					alert( 'Error: Could not save posts order.' );
				}
			} );
		}
	} );
} );
