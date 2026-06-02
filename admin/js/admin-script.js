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

	// 7. Image Compression Quality Slider
	const qualitySlider = document.getElementById( 'tka-image-quality-slider' );
	const qualityDisplay = document.getElementById( 'tka-image-quality-display' );
	if ( qualitySlider && qualityDisplay ) {
		qualitySlider.addEventListener( 'input', function () {
			qualityDisplay.textContent = this.value + '%';
		} );
	}

	// 8. Bulk Retroactive Image Optimizer sequential batch executor
	const bulkStartBtn = document.getElementById( 'tka-bulk-optimize-start-btn' );
	if ( bulkStartBtn ) {
		bulkStartBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			// Fetch all eligible attachment IDs
			const formData = new FormData();
			formData.append( 'action', 'tka_wp_utils_bulk_get_images' );

			bulkStartBtn.disabled = true;
			bulkStartBtn.textContent = 'Scanning...';

			const progressPanel = document.getElementById( 'tka-bulk-progress-panel' );
			const progressBar = document.getElementById( 'tka-bulk-progress-bar' );
			const progressPercentage = document.getElementById( 'tka-bulk-progress-percentage' );
			const progressStatus = document.getElementById( 'tka-bulk-progress-status' );
			const optimizedCountSpan = document.getElementById( 'tka-bulk-optimized-count' );
			const totalSavingsSpan = document.getElementById( 'tka-bulk-total-savings' );
			const eligibleTotalCount = document.getElementById( 'tka-bulk-eligible-total-count' );
			const totalCountSpan = document.getElementById( 'tka-bulk-total-count' );
			const logBox = document.getElementById( 'tka-bulk-log-box' );

			progressPanel.style.display = 'block';
			logBox.innerHTML = '<div style="color: #64748b;">>> Querying media library database...</div>';

			fetch( tkaWpUtilsAdmin.ajaxUrl, {
				method: 'POST',
				body: formData
			} )
			.then( response => response.json() )
			.then( data => {
				if ( ! data.success || ! data.data.ids || data.data.ids.length === 0 ) {
					progressStatus.textContent = 'No eligible images found.';
					logBox.innerHTML += '<div style="color: #ef4444;">>> Error: No unoptimized JPEGs or PNGs found in the library.</div>';
					bulkStartBtn.disabled = false;
					bulkStartBtn.textContent = 'Start Bulk Optimization';
					return;
				}

				const ids = data.data.ids;
				const total = ids.length;
				eligibleTotalCount.textContent = total;

				logBox.innerHTML += '<div style="color: #10b981;">>> Found ' + total + ' images to process. Starting sequential batches...</div>';
				logBox.scrollTop = logBox.scrollHeight;

				let index = 0;
				let totalSavings = 0;

				function formatBytes( bytes ) {
					if ( bytes === 0 ) return '0 KB';
					const k = 1024;
					const sizes = [ 'Bytes', 'KB', 'MB', 'GB' ];
					const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
					return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[i];
				}

				function processNext() {
					if ( index >= total ) {
						progressStatus.textContent = 'Bulk Optimization Complete!';
						progressPercentage.textContent = '100%';
						progressBar.style.width = '100%';
						bulkStartBtn.textContent = 'Finished';
						
						const finalLine = document.createElement( 'div' );
						finalLine.style.color = '#10b981';
						finalLine.textContent = '>> Optimization Complete! Saved ' + formatBytes( totalSavings ) + ' storage in total.';
						logBox.appendChild( finalLine );
						logBox.scrollTop = logBox.scrollHeight;

						// Update main count on success
						totalCountSpan.textContent = '0';
						return;
					}

					// Update UI progress
					const pct = Math.round( ( index / total ) * 100 );
					progressPercentage.textContent = pct + '%';
					progressBar.style.width = pct + '%';
					progressStatus.textContent = 'Processing image ' + ( index + 1 ) + ' of ' + total + '...';
					optimizedCountSpan.textContent = index;

					const singleData = new FormData();
					singleData.append( 'action', 'tka_wp_utils_bulk_optimize_image' );
					singleData.append( 'attachment_id', ids[index] );

					fetch( tkaWpUtilsAdmin.ajaxUrl, {
						method: 'POST',
						body: singleData
					} )
					.then( r => r.json() )
					.then( res => {
						const attachmentId = ids[index];
						const rowEl = document.getElementById( 'tka-image-row-' + attachmentId );
						const logLine = document.createElement( 'div' );

						if ( res.success ) {
							totalSavings += res.data.bytes_saved;
							totalSavingsSpan.textContent = formatBytes( totalSavings );
							logLine.textContent = '>> ' + res.data.message + ' (Saved ' + formatBytes( res.data.bytes_saved ) + ')';
							
							// Real-time table updates
							if ( rowEl ) {
								// 1. Update format badge
								const badgeEl = document.getElementById( 'tka-format-badge-' + attachmentId );
								if ( badgeEl ) {
									badgeEl.className = 'tka-badge-format';
									if ( res.data.mime_type === 'image/webp' ) {
										badgeEl.classList.add( 'tka-badge-format-webp' );
										badgeEl.textContent = 'WebP';
									} else if ( res.data.mime_type === 'image/png' ) {
										badgeEl.classList.add( 'tka-badge-format-png' );
										badgeEl.textContent = 'PNG';
									} else {
										badgeEl.classList.add( 'tka-badge-format-jpeg' );
										badgeEl.textContent = 'JPEG';
									}
								}

								// 2. Update status pill
								const statusPill = document.getElementById( 'tka-status-pill-' + attachmentId );
								if ( statusPill ) {
									statusPill.className = 'tka-status-pill status-optimized';
									const statusText = statusPill.querySelector( '.tka-status-text' );
									if ( statusText ) {
										statusText.textContent = 'Optimized';
									}
								}

								// 3. Update size savings
								const savingsCell = document.getElementById( 'tka-savings-cell-' + attachmentId );
								if ( savingsCell ) {
									const savingsVal = res.data.bytes_saved > 0 ? formatBytes( res.data.bytes_saved ) : '0 KB';
									savingsCell.innerHTML = `<span class="tka-savings-value" style="color: var(--tka-success);">${savingsVal}</span>`;
								}

								// 4. Add subtle micro-animation flash
								rowEl.classList.add( 'tka-row-optimized' );
							}
						} else {
							logLine.style.color = '#f59e0b';
							logLine.textContent = '>> Skip: ' + ( res.data.message || 'Error occurred.' );
						}
						logBox.appendChild( logLine );
						logBox.scrollTop = logBox.scrollHeight;

						index++;
						processNext();
					} )
					.catch( err => {
						const logLine = document.createElement( 'div' );
						logLine.style.color = '#ef4444';
						logLine.textContent = '>> Failed: Connection error processing ID: ' + ids[index];
						logBox.appendChild( logLine );
						logBox.scrollTop = logBox.scrollHeight;

						index++;
						processNext();
					} );
				}

				processNext();
			} )
			.catch( error => {
				progressStatus.textContent = 'Scan encountered a network error.';
				bulkStartBtn.disabled = false;
				bulkStartBtn.textContent = 'Start Bulk Optimization';
			} );
		} );
	}
} );


