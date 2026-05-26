/**
 * TKA WP Utils Admin Scripts
 */

document.addEventListener( 'DOMContentLoaded', function () {
	// 1. Tab Switching Functionality
	const navItems = document.querySelectorAll( '.tka-nav-item' );
	const panels   = document.querySelectorAll( '.tka-tab-panel' );
	const submitSection = document.querySelector( '.tka-submit-section' );

	navItems.forEach( function ( item ) {
		item.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			// Remove active class from all nav items
			navItems.forEach( ( nav ) => nav.classList.remove( 'active' ) );
			// Add active class to clicked item
			this.classList.add( 'active' );

			const targetTab = this.getAttribute( 'data-tab' );

			// Toggle active class on panels
			panels.forEach( function ( panel ) {
				if ( panel.id === 'panel-' + targetTab ) {
					panel.classList.add( 'active' );
				} else {
					panel.classList.remove( 'active' );
				}
			} );

			// Hide save settings button on the Sandbox tab since it's an interactive utility
			if ( targetTab === 'sandbox' ) {
				submitSection.style.display = 'none';
			} else {
				submitSection.style.display = 'block';
			}

			// Update hash in URL & sessionStorage
			window.location.hash = targetTab;
			sessionStorage.setItem( 'tka_active_tab', targetTab );
		} );
	} );

	// Listen to URL hash or sessionStorage on initial load
	const initialHash = window.location.hash.substring( 1 );
	const savedTab    = sessionStorage.getItem( 'tka_active_tab' );
	const activeTab   = initialHash || savedTab;
	if ( activeTab ) {
		const targetNav = document.querySelector( `.tka-nav-item[data-tab="${activeTab}"]` );
		if ( targetNav ) {
			targetNav.click();
		}
	}

	// 2. Gutenberg Radio Options Toggles
	const gutenbergRadios = document.querySelectorAll( 'input[name="tka_wp_utils_options[disable_gutenberg]"]' );
	const postTypesRow     = document.querySelector( '.nested-gutenberg-post-types' );

	gutenbergRadios.forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			if ( this.value === 'post_types' ) {
				postTypesRow.style.display = 'block';
				// Smooth micro-fade in
				postTypesRow.style.opacity = 0;
				setTimeout( () => {
					postTypesRow.style.transition = 'opacity 0.2s ease-in-out';
					postTypesRow.style.opacity = 1;
				}, 10 );
			} else {
				postTypesRow.style.transition = 'opacity 0.15s ease-in-out';
				postTypesRow.style.opacity = 0;
				setTimeout( () => {
					postTypesRow.style.display = 'none';
				}, 150 );
			}
		} );
	} );

	// 2b. Content Management Toggles
	const orderToggle      = document.getElementById( 'tka-order-enabled-toggle' );
	const orderPostTypes   = document.querySelector( '.nested-order-post-types' );
	const duplicateToggle  = document.getElementById( 'tka-duplicate-enabled-toggle' );
	const duplicatePostTypes = document.querySelector( '.nested-duplicate-post-types' );

	if ( orderToggle && orderPostTypes ) {
		orderToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				orderPostTypes.style.display = 'block';
				orderPostTypes.style.opacity = 0;
				setTimeout( () => {
					orderPostTypes.style.transition = 'opacity 0.2s ease-in-out';
					orderPostTypes.style.opacity = 1;
				}, 10 );
			} else {
				orderPostTypes.style.transition = 'opacity 0.15s ease-in-out';
				orderPostTypes.style.opacity = 0;
				setTimeout( () => {
					orderPostTypes.style.display = 'none';
				}, 150 );
			}
		} );
	}

	if ( duplicateToggle && duplicatePostTypes ) {
		duplicateToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				duplicatePostTypes.style.display = 'block';
				duplicatePostTypes.style.opacity = 0;
				setTimeout( () => {
					duplicatePostTypes.style.transition = 'opacity 0.2s ease-in-out';
					duplicatePostTypes.style.opacity = 1;
				}, 10 );
			} else {
				duplicatePostTypes.style.transition = 'opacity 0.15s ease-in-out';
				duplicatePostTypes.style.opacity = 0;
				setTimeout( () => {
					duplicatePostTypes.style.display = 'none';
				}, 150 );
			}
		} );
	}

	const acfCopyToggle = document.getElementById( 'tka-acf-copy-paste-toggle' );
	const acfMultiselectRow = document.querySelector( '.nested-acf-multiselect' );

	if ( acfCopyToggle && acfMultiselectRow ) {
		acfCopyToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				acfMultiselectRow.style.display = 'flex';
				acfMultiselectRow.style.opacity = 0;
				setTimeout( () => {
					acfMultiselectRow.style.transition = 'opacity 0.2s ease-in-out';
					acfMultiselectRow.style.opacity = 1;
				}, 10 );
			} else {
				acfMultiselectRow.style.transition = 'opacity 0.15s ease-in-out';
				acfMultiselectRow.style.opacity = 0;
				setTimeout( () => {
					acfMultiselectRow.style.display = 'none';
				}, 150 );
			}
		} );
	}



	// 3. SVG Sandbox Dropzone & AJAX Validation
	const dropzone  = document.getElementById( 'tka-svg-dropzone' );
	const fileInput = document.getElementById( 'tka-sandbox-file-input' );
	const results   = document.getElementById( 'tka-sandbox-results' );
	const threatsBox = document.getElementById( 'tka-threat-details' );

	if ( dropzone && fileInput ) {
		// Trigger file input click when dropzone is clicked
		dropzone.addEventListener( 'click', () => fileInput.click() );

		// Drag & Drop event bindings
		[ 'dragenter', 'dragover' ].forEach( ( eventName ) => {
			dropzone.addEventListener( eventName, function ( e ) {
				e.preventDefault();
				dropzone.classList.add( 'dragover' );
			}, false );
		} );

		[ 'dragleave', 'drop' ].forEach( ( eventName ) => {
			dropzone.addEventListener( eventName, function ( e ) {
				e.preventDefault();
				dropzone.classList.remove( 'dragover' );
			}, false );
		} );

		dropzone.addEventListener( 'drop', function ( e ) {
			const dt    = e.dataTransfer;
			const files = dt.files;

			if ( files.length > 0 ) {
				validateSvgFile( files[0] );
			}
		} );

		fileInput.addEventListener( 'change', function () {
			if ( this.files.length > 0 ) {
				validateSvgFile( this.files[0] );
			}
		} );
	}

	function validateSvgFile( file ) {
		// Verify basic client side type check
		if ( file.type !== 'image/svg+xml' && ! file.name.endsWith( '.svg' ) ) {
			renderResults( false, {
				message: 'Rejected: File is not an SVG image.',
				threats: [ 'Invalid File Type: Sandbox only accepts .svg format files.' ]
			} );
			return;
		}

		// Prepare Form Data for WordPress AJAX
		const formData = new FormData();
		formData.append( 'action', 'tka_wp_utils_sandbox_validate_svg' );
		formData.append( 'nonce', tkaWpUtilsAdmin.nonce );
		formData.append( 'svg_file', file );

		// Render a loading state
		results.style.display = 'block';
		results.className     = 'tka-sandbox-results success';
		results.querySelector( '.tka-results-badge' ).textContent = 'Scanning';
		results.querySelector( 'h4' ).textContent = 'Reading XML structures and auditing vectors...';
		threatsBox.style.display = 'none';

		// Perform AJAX Request
		fetch( tkaWpUtilsAdmin.ajaxUrl, {
			method: 'POST',
			body: formData
		} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			if ( data.success ) {
				renderResults( true, data.data );
			} else {
				renderResults( false, data.data );
			}
		} )
		.catch( ( error ) => {
			renderResults( false, {
				message: 'AJAX request encountered an issue.',
				threats: [ 'Validation server was unreachable or returned an error.' ]
			} );
		} );
	}

	function renderResults( success, data ) {
		results.style.display = 'block';
		const badge = results.querySelector( '.tka-results-badge' );
		const title = results.querySelector( 'h4' );
		const list  = threatsBox.querySelector( '.tka-threat-list' );

		if ( success ) {
			results.className = 'tka-sandbox-results success';
			badge.textContent = 'Safe';
			title.textContent = data.message || 'File is clean and safe to upload.';
			threatsBox.style.display = 'none';
		} else {
			results.className = 'tka-sandbox-results danger';
			badge.textContent = 'Danger';
			title.textContent = data.message || 'Malicious components detected.';
			
			// Empty old threats
			list.innerHTML = '';
			
			if ( data.threats && data.threats.length > 0 ) {
				data.threats.forEach( ( threat ) => {
					const li = document.createElement( 'li' );
					li.textContent = threat;
					list.appendChild( li );
				} );
				threatsBox.style.display = 'block';
			} else {
				threatsBox.style.display = 'none';
			}
		}
	}

	// 4. Admin Menu Organizer Drag & Drop Sortable
	const organizerLists = jQuery('.tka-menu-organizer');
	if ( organizerLists.length > 0 ) {
		organizerLists.sortable({
			handle: '.tka-organizer-drag',
			axis: 'y',
			placeholder: 'tka-organizer-placeholder',
			containment: 'parent'
		});
	}


	// 5. Admin Menu Organizer Toggle Visibility Action
	jQuery(document).on('click', '.tka-organizer-toggle-btn', function() {
		const $btn = jQuery(this);
		const $card = $btn.closest('.tka-organizer-item');
		const $checkbox = $card.find('.tka-menu-visibility-checkbox');
		const isHidden = $checkbox.prop('checked');
		
		if (isHidden) {
			$checkbox.prop('checked', false);
			$card.removeClass('menu-hidden').addClass('menu-visible');
			$btn.removeClass('tka-btn-hidden').addClass('tka-btn-visible');
			$btn.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
		} else {
			$checkbox.prop('checked', true);
			$card.removeClass('menu-visible').addClass('menu-hidden');
			$btn.removeClass('tka-btn-visible').addClass('tka-btn-hidden');
			$btn.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
		}
	});

	// 5b. Organizer Sub-Tab Switching
	const subTabButtons = document.querySelectorAll( '.tka-sub-tab-btn' );
	const subTabContents = document.querySelectorAll( '.tka-sub-tab-content' );

	subTabButtons.forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			subTabButtons.forEach( ( b ) => b.classList.remove( 'active' ) );
			this.classList.add( 'active' );

			const targetSubtab = this.getAttribute( 'data-subtab' );

			subTabContents.forEach( function ( panel ) {
				if ( panel.id === 'tka-subtab-' + targetSubtab + '-content' ) {
					panel.classList.add( 'active' );
					panel.style.display = 'block';
				} else {
					panel.classList.remove( 'active' );
					panel.style.display = 'none';
				}
			} );
		} );
	} );

	// 5c. Custom Logo Media Uploaders
	jQuery(document).on('click', '.tka-upload-btn', function(e) {
		e.preventDefault();
		const $button = jQuery(this);
		const $control = $button.closest('.tka-image-upload-control');
		const $input = $control.find('.tka-logo-input');
		const $preview = $control.find('.tka-logo-preview');
		const isNavbarLogo = $input.attr('name').indexOf('admin_logo') !== -1;

		const frame = wp.media({
			title: 'Select or Upload Logo',
			button: {
				text: 'Use this image'
			},
			multiple: false
		});

		frame.on('select', function() {
			const attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url);
			const maxH = isNavbarLogo ? '30px' : '80px';
			$preview.html('<img src="' + attachment.url + '" style="max-height: ' + maxH + '; display: block; margin-top: 10px; border-radius: 4px; border: 1px solid var(--tka-border);">');
		});

		frame.open();
	});

	jQuery(document).on('click', '.tka-remove-btn', function(e) {
		e.preventDefault();
		const $button = jQuery(this);
		const $control = $button.closest('.tka-image-upload-control');
		const $input = $control.find('.tka-logo-input');
		const $preview = $control.find('.tka-logo-preview');
		$input.val('');
		$preview.html('');
	});


	// 6. Retain scroll position on settings save
	const settingsForm = document.querySelector( '.tka-dashboard-content form' );
	if ( settingsForm ) {
		settingsForm.addEventListener( 'submit', function () {
			sessionStorage.setItem( 'tka_scroll_pos', window.scrollY );
		} );
	}

	const savedScroll = sessionStorage.getItem( 'tka_scroll_pos' );
	if ( savedScroll ) {
		setTimeout( function () {
			window.scrollTo( 0, parseInt( savedScroll, 10 ) );
			sessionStorage.removeItem( 'tka_scroll_pos' );
		}, 50 );
	}
} );


