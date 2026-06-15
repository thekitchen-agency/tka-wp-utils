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

	// 2c. Gravity Forms Text Change Toggle
	const gfTextToggle    = document.getElementById( 'tka-gf-text-change-toggle' );
	const gfLoadingTextRow = document.querySelector( '.nested-gf-loading-text' );

	if ( gfTextToggle && gfLoadingTextRow ) {
		gfTextToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				gfLoadingTextRow.style.display = 'block';
				gfLoadingTextRow.style.opacity = 0;
				setTimeout( () => {
					gfLoadingTextRow.style.transition = 'opacity 0.2s ease-in-out';
					gfLoadingTextRow.style.opacity = 1;
				}, 10 );
			} else {
				gfLoadingTextRow.style.transition = 'opacity 0.15s ease-in-out';
				gfLoadingTextRow.style.opacity = 0;
				setTimeout( () => {
					gfLoadingTextRow.style.display = 'none';
				}, 150 );
			}
		} );
	}

	// 2d. WP Cron Toggle
	const wpCronToggle    = document.getElementById( 'tka-disable-wp-cron-toggle' );
	const wpCronNoticeRow = document.querySelector( '.nested-wp-cron-notice' );

	if ( wpCronToggle && wpCronNoticeRow ) {
		wpCronToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				wpCronNoticeRow.style.display = 'block';
				wpCronNoticeRow.style.opacity = 0;
				setTimeout( () => {
					wpCronNoticeRow.style.transition = 'opacity 0.2s ease-in-out';
					wpCronNoticeRow.style.opacity = 1;
				}, 10 );
			} else {
				wpCronNoticeRow.style.transition = 'opacity 0.15s ease-in-out';
				wpCronNoticeRow.style.opacity = 0;
				setTimeout( () => {
					wpCronNoticeRow.style.display = 'none';
				}, 150 );
			}
		} );
	}

	// 2e. Page Transitions Toggle
	const transitionsToggle    = document.getElementById( 'tka-page-transitions-enabled-toggle' );
	const transitionsDetailsRow = document.querySelector( '.nested-page-transitions-settings' );

	if ( transitionsToggle && transitionsDetailsRow ) {
		transitionsToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				transitionsDetailsRow.style.display = 'block';
				transitionsDetailsRow.style.opacity = 0;
				setTimeout( () => {
					transitionsDetailsRow.style.transition = 'opacity 0.2s ease-in-out';
					transitionsDetailsRow.style.opacity = 1;
				}, 10 );
			} else {
				transitionsDetailsRow.style.transition = 'opacity 0.15s ease-in-out';
				transitionsDetailsRow.style.opacity = 0;
				setTimeout( () => {
					transitionsDetailsRow.style.display = 'none';
				}, 150 );
			}
		} );
	}

	// 2g. WPML Optimization Toggle
	const wpmlToggle = document.getElementById( 'tka-wpml-optimization-enabled-toggle' );
	const wpmlDetailsRow = document.querySelector( '.nested-wpml-optimization-settings' );

	if ( wpmlToggle && wpmlDetailsRow ) {
		wpmlToggle.addEventListener( 'change', function () {
			if ( this.checked ) {
				wpmlDetailsRow.style.display = 'block';
				wpmlDetailsRow.style.opacity = 0;
				setTimeout( () => {
					wpmlDetailsRow.style.transition = 'opacity 0.2s ease-in-out';
					wpmlDetailsRow.style.opacity = 1;
				}, 10 );
			} else {
				wpmlDetailsRow.style.transition = 'opacity 0.15s ease-in-out';
				wpmlDetailsRow.style.opacity = 0;
				setTimeout( () => {
					wpmlDetailsRow.style.display = 'none';
				}, 150 );
			}
		} );
	}

	// 2f. Page Transitions rules editor
	const addRuleBtn = document.getElementById('tka-add-rule-btn');
	const rulesList = document.getElementById('tka-transitions-rules-list');

	if (addRuleBtn && rulesList) {
		addRuleBtn.addEventListener('click', function (e) {
			e.preventDefault();
			const ruleIndex = rulesList.children.length;

			const newRow = document.createElement('div');
			newRow.className = 'tka-rule-row-item';
			newRow.style.display = 'flex';
			newRow.style.gap = '15px';
			newRow.style.alignItems = 'center';
			newRow.style.padding = '10px';
			newRow.style.border = '1px solid var(--tka-border)';
			newRow.style.borderRadius = '8px';
			newRow.style.background = '#ffffff';
			newRow.style.boxShadow = 'var(--tka-shadow)';

			newRow.innerHTML = `
				<!-- FROM PAGE -->
				<div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
					<select name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][from_type]" class="tka-rule-from-select" style="width: 100%; padding: 5px; border-radius: 6px;">
						<option value="any">Any Page</option>
						<option value="home">Homepage</option>
						<option value="archive">Archive/Category</option>
						<option value="single_post">Single Post</option>
						<option value="single_page">Single Page</option>
						<option value="custom_url">Custom URL Pattern...</option>
					</select>
					<input type="text" name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][from_url]" value="" placeholder="/blog/*" class="tka-rule-from-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: none;">
				</div>

				<!-- TO PAGE -->
				<div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
					<select name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][to_type]" class="tka-rule-to-select" style="width: 100%; padding: 5px; border-radius: 6px;">
						<option value="any">Any Page</option>
						<option value="home">Homepage</option>
						<option value="archive">Archive/Category</option>
						<option value="single_post">Single Post</option>
						<option value="single_page">Single Page</option>
						<option value="custom_url">Custom URL Pattern...</option>
					</select>
					<input type="text" name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][to_url]" value="" placeholder="/shop/*" class="tka-rule-to-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: none;">
				</div>

				<!-- ANIMATION -->
				<div style="flex: 1.5;">
					<select name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][animation]" class="tka-rule-anim-select" style="width: 100%; padding: 5px; border-radius: 6px;">
						<option value="fade">Fade (default)</option>
						<option value="slide-from-right">Slide (from right)</option>
						<option value="slide-from-left">Slide (from left)</option>
						<option value="slide-from-bottom">Slide (from bottom)</option>
						<option value="slide-from-top">Slide (from top)</option>
						<option value="swipe-from-right">Swipe (from right)</option>
						<option value="swipe-from-left">Swipe (from left)</option>
						<option value="swipe-from-bottom">Swipe (from bottom)</option>
						<option value="swipe-from-top">Swipe (from top)</option>
						<option value="wipe-from-right">Wipe (from right)</option>
						<option value="wipe-from-left">Wipe (from left)</option>
						<option value="wipe-from-bottom">Wipe (from bottom)</option>
						<option value="wipe-from-top">Wipe (from top)</option>
						<option value="custom">Custom CSS Class...</option>
					</select>
				</div>

				<!-- CUSTOM CLASS -->
				<div style="flex: 1.5;">
					<input type="text" name="tka_wp_utils_options[page_transitions_rules][${ruleIndex}][custom_class]" value="" placeholder="tka-transition-zoom" class="tka-rule-class-input" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); display: none;">
				</div>

				<!-- DELETE BUTTON -->
				<button type="button" class="button tka-rule-delete-btn" style="color: var(--tka-danger); border-color: rgba(239, 68, 68, 0.2); padding: 5px 8px; border-radius: 6px;" title="Delete rule">
					<span class="dashicons dashicons-trash" style="vertical-align: middle; margin: 0;"></span>
				</button>
			`;

			rulesList.appendChild(newRow);
		});
	}

	jQuery(document).on('change', '.tka-rule-from-select', function() {
		const $select = jQuery(this);
		const $urlInput = $select.siblings('.tka-rule-from-url');
		if ($select.val() === 'custom_url') {
			$urlInput.show();
		} else {
			$urlInput.hide().val('');
		}
	});

	jQuery(document).on('change', '.tka-rule-to-select', function() {
		const $select = jQuery(this);
		const $urlInput = $select.siblings('.tka-rule-to-url');
		if ($select.val() === 'custom_url') {
			$urlInput.show();
		} else {
			$urlInput.hide().val('');
		}
	});

	jQuery(document).on('change', '.tka-rule-anim-select', function() {
		const $select = jQuery(this);
		const $classInput = $select.closest('.tka-rule-row-item').find('.tka-rule-class-input');
		if ($select.val() === 'custom') {
			$classInput.show();
		} else {
			$classInput.hide().val('');
		}
	});

	jQuery(document).on('click', '.tka-rule-delete-btn', function(e) {
		e.preventDefault();
		jQuery(this).closest('.tka-rule-row-item').remove();
		jQuery('#tka-transitions-rules-list .tka-rule-row-item').each(function(index, el) {
			const $row = jQuery(el);
			$row.find('.tka-rule-from-select').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][from_type]`);
			$row.find('.tka-rule-from-url').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][from_url]`);
			$row.find('.tka-rule-to-select').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][to_type]`);
			$row.find('.tka-rule-to-url').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][to_url]`);
			$row.find('.tka-rule-anim-select').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][animation]`);
			$row.find('.tka-rule-class-input').attr('name', `tka_wp_utils_options[page_transitions_rules][${index}][custom_class]`);
		});
	});

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
	const bulkPauseBtn = document.getElementById( 'tka-bulk-optimize-pause-btn' );
	
	// Table and pagination state
	let currentStatus = 'all';
	let currentPage = 1;
	const itemsPerPage = 50;
	let isPaused = false;
	
	// Fetch and render image list
	function loadMediaLibraryTable() {
		const tableBody = document.getElementById( 'tka-bulk-status-table-body' );
		if ( ! tableBody ) return;
		
		tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--tka-text-muted);"><span class="spinner is-active" style="float: none; margin-right: 8px;"></span> Loading images...</td></tr>`;
		
		const formData = new FormData();
		formData.append( 'action', 'tka_wp_utils_bulk_get_image_list' );
		formData.append( 'page', currentPage );
		formData.append( 'per_page', itemsPerPage );
		formData.append( 'status', currentStatus );
		
		fetch( tkaWpUtilsAdmin.ajaxUrl, {
			method: 'POST',
			body: formData
		} )
		.then( r => r.json() )
		.then( res => {
			if ( res.success ) {
				const data = res.data;
				renderTableRows( data.rows );
				updatePaginationUI( data );
			}
		} );
	}
	
	function renderTableRows( rows ) {
		const tableBody = document.getElementById( 'tka-bulk-status-table-body' );
		if ( rows.length === 0 ) {
			tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--tka-text-muted); padding: 30px 15px;">No image attachments found for this filter.</td></tr>`;
			return;
		}
		
		let html = '';
		rows.forEach( row => {
			const sizesHtml = row.sizes && row.sizes.length > 0 ? `<div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px;">` + row.sizes.map(s => `<span style="font-size: 10px; background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px; color: var(--tka-text-muted);">${s}</span>`).join('') + `</div>` : '';
			
			const savingsHtml = row.is_optimized ? `<span class="tka-savings-value" style="color: var(--tka-success);">${row.savings_text}</span>` : `<span class="tka-savings-pending">Pending</span>`;
			
			html += `
				<tr id="tka-image-row-${row.id}">
					<td>
						<img src="${row.thumbnail_url}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--tka-border); display: block;">
					</td>
					<td style="font-weight: 500; color: var(--tka-text-main);">
						<div class="tka-filename-text" title="${row.file_path}">${row.filename}</div>
						${sizesHtml}
					</td>
					<td>
						<span class="tka-badge-format ${row.format_class}" id="tka-format-badge-${row.id}">${row.format_label}</span>
					</td>
					<td>
						<span class="tka-status-pill ${row.status_class}" id="tka-status-pill-${row.id}">
							<span class="tka-status-dot"></span>
							<span class="tka-status-text">${row.status_label}</span>
						</span>
					</td>
					<td style="text-align: right;" id="tka-savings-cell-${row.id}">
						${savingsHtml}
					</td>
				</tr>
			`;
		} );
		tableBody.innerHTML = html;
	}
	
	function updatePaginationUI( data ) {
		const totalEl = document.getElementById( 'tka-pagination-total' );
		if (totalEl) totalEl.textContent = data.total_items;
		const start = data.total_items > 0 ? ( ( data.current_page - 1 ) * itemsPerPage ) + 1 : 0;
		const end = Math.min( data.current_page * itemsPerPage, data.total_items );
		
		const startEl = document.getElementById( 'tka-pagination-start' );
		if (startEl) startEl.textContent = start;
		const endEl = document.getElementById( 'tka-pagination-end' );
		if (endEl) endEl.textContent = end;
		const currEl = document.getElementById( 'tka-pagination-current' );
		if (currEl) currEl.textContent = data.current_page;
		
		const prevBtn = document.getElementById( 'tka-pagination-prev' );
		const nextBtn = document.getElementById( 'tka-pagination-next' );
		
		if ( prevBtn ) prevBtn.disabled = data.current_page <= 1;
		if ( nextBtn ) nextBtn.disabled = data.current_page >= data.total_pages;
	}
	
	// Initial Load
	if ( document.getElementById( 'tka-bulk-status-table-body' ) ) {
		loadMediaLibraryTable();
		
		// Tab listeners
		document.querySelectorAll( '.tka-status-tab-btn' ).forEach( btn => {
			btn.addEventListener( 'click', function() {
				document.querySelectorAll( '.tka-status-tab-btn' ).forEach( b => b.classList.remove( 'active' ) );
				this.classList.add( 'active' );
				currentStatus = this.getAttribute( 'data-status' );
				currentPage = 1;
				loadMediaLibraryTable();
			});
		});
		
		// Pagination listeners
		const prevBtn = document.getElementById( 'tka-pagination-prev' );
		const nextBtn = document.getElementById( 'tka-pagination-next' );
		if ( prevBtn ) prevBtn.addEventListener( 'click', () => { currentPage--; loadMediaLibraryTable(); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', () => { currentPage++; loadMediaLibraryTable(); } );
	}

	if ( bulkStartBtn ) {
		if ( bulkPauseBtn ) {
			bulkPauseBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				isPaused = !isPaused;
				if ( isPaused ) {
					bulkPauseBtn.innerHTML = '<span class="dashicons dashicons-controls-play" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span> Resume';
					const statusEl = document.getElementById( 'tka-bulk-progress-status' );
					if (statusEl) statusEl.textContent = 'Paused. Click Resume to continue...';
				} else {
					bulkPauseBtn.innerHTML = '<span class="dashicons dashicons-controls-pause" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span> Pause';
					const statusEl = document.getElementById( 'tka-bulk-progress-status' );
					if (statusEl) statusEl.textContent = 'Resuming...';
				}
			} );
		}

		bulkStartBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			// Fetch all eligible attachment IDs
			const formData = new FormData();
			formData.append( 'action', 'tka_wp_utils_bulk_get_images' );

			bulkStartBtn.disabled = true;
			bulkStartBtn.textContent = 'Scanning...';
			if ( bulkPauseBtn ) bulkPauseBtn.style.display = 'inline-block';
			isPaused = false;

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
					if ( bulkPauseBtn ) bulkPauseBtn.style.display = 'none';
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
					if ( isPaused ) {
						// Check again shortly
						setTimeout( processNext, 1000 );
						return;
					}

					if ( index >= total ) {
						progressStatus.textContent = 'Bulk Optimization Complete!';
						progressPercentage.textContent = '100%';
						progressBar.style.width = '100%';
						bulkStartBtn.textContent = 'Finished';
						if ( bulkPauseBtn ) bulkPauseBtn.style.display = 'none';
						
						const finalLine = document.createElement( 'div' );
						finalLine.style.color = '#10b981';
						finalLine.textContent = '>> Optimization Complete! Saved ' + formatBytes( totalSavings ) + ' storage in total.';
						logBox.appendChild( finalLine );
						logBox.scrollTop = logBox.scrollHeight;

						// Update main count on success
						totalCountSpan.textContent = '0';
						// Refresh current table page
						loadMediaLibraryTable();
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
							const allTimeSavingsSpan = document.getElementById( 'tka-bulk-all-time-savings' );
							if ( allTimeSavingsSpan ) {
								const initialAllTime = parseInt( allTimeSavingsSpan.getAttribute( 'data-initial' ) || 0, 10 );
								allTimeSavingsSpan.textContent = formatBytes( initialAllTime + totalSavings );
							}
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

								// 4. Update sizes list if not already there
								if ( res.data.affected_sizes && res.data.affected_sizes.length > 0 ) {
									const filenameContainer = rowEl.querySelector('.tka-filename-text').parentElement;
									// remove old sizes div if exists
									const oldSizes = filenameContainer.querySelector('div[style*="margin-top: 6px"]');
									if (oldSizes) oldSizes.remove();

									const sizesHtml = `<div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px;">` + res.data.affected_sizes.map(s => `<span style="font-size: 10px; background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px; color: var(--tka-text-muted);">${s}</span>`).join('') + `</div>`;
									filenameContainer.insertAdjacentHTML('beforeend', sizesHtml);
								}

								// 5. Add subtle micro-animation flash
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
				if ( bulkPauseBtn ) bulkPauseBtn.style.display = 'none';
			} );
		} );
	}
} );


