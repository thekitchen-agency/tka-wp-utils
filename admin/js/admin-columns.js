jQuery(document).ready(function($) {
	// Select selectors
	const $selector = $('#tka-column-post-type-selector');
	const $panels = $('.tka-columns-post-type-panel');

	// Restore selected post type from localStorage
	const savedPostType = localStorage.getItem('tka_active_post_type');
	if (savedPostType && $selector.find('option[value="' + savedPostType + '"]').length) {
		$selector.val(savedPostType);
	}

	// Handle post-type panel switching
	$selector.on('change', function() {
		const selectedPostType = $(this).val();
		localStorage.setItem('tka_active_post_type', selectedPostType);
		$panels.hide();
		$('#tka-columns-panel-' + selectedPostType).fadeIn(200);
	});

	// Trigger initial change to show active panel
	$selector.trigger('change');

	// Initialize jQuery UI Sortable for drag-and-drop ordering
	function initSortable() {
		$('.tka-columns-rows-list').sortable({
			handle: '.tka-column-drag-handle',
			placeholder: 'tka-column-row-placeholder',
			opacity: 0.85,
			axis: 'y',
			cursor: 'move',
			tolerance: 'pointer',
			containment: 'parent',
			forcePlaceholderSize: true
		});
	}
	initSortable();

	// Handle meta key select change
	$(document).on('change', '.tka-meta-key-select', function() {
		const $select = $(this);
		const $wrap = $select.closest('.tka-meta-key-selector-wrap');
		const $input = $wrap.find('.tka-meta-key-input');
		
		if ($select.val() === '__custom__') {
			$input.slideDown(150).val('').focus();
		} else {
			$input.slideUp(100).val($select.val());
		}
	});

	// Handle adding a custom column row
	$(document).on('click', '.tka-add-column-row-btn', function() {
		const postType = $(this).data('posttype');
		const $panel = $('#tka-columns-panel-' + postType);
		const $list = $panel.find('.tka-columns-rows-list');
		const $placeholder = $panel.find('.tka-columns-empty-placeholder');
		const $headers = $panel.find('.tka-columns-headers-row');

		// Create a unique index using timestamp + random
		const index = Date.now() + Math.floor(Math.random() * 1000);

		// Get localized keys and strings
		let metaOptions = `<option value="">${tkaWpUtilsColumns.i18n.selectField}</option>`;
		if (tkaWpUtilsColumns && tkaWpUtilsColumns.metaKeys) {
			tkaWpUtilsColumns.metaKeys.forEach(function(key) {
				metaOptions += `<option value="${key}">${key}</option>`;
			});
		}
		metaOptions += `<option value="__custom__">${tkaWpUtilsColumns.i18n.enterCustomKey}</option>`;

		const html = `
			<div class="tka-column-row-item" style="opacity: 0; transform: translateY(-10px); transition: all 0.3s ease;">
				<!-- Drag Handle -->
				<div class="tka-column-drag-handle" title="${tkaWpUtilsColumns.i18n.customKeyPlaceholder}">
					<span class="dashicons dashicons-menu"></span>
				</div>

				<!-- Layout Inputs Grid -->
				<div class="tka-column-inputs-grid">
					<!-- Label Input -->
					<div>
						<input type="text" name="tka_wp_utils_columns[${postType}][${index}][label]" value="" placeholder="Column Header Label (e.g. Price)" class="tka-col-input-field">
					</div>

					<!-- Meta Key Selector Dropdown + Text Input -->
					<div>
						<div class="tka-meta-key-selector-wrap">
							<select class="tka-meta-key-select">
								${metaOptions}
							</select>
							<input type="text" class="tka-meta-key-input" name="tka_wp_utils_columns[${postType}][${index}][meta_key]" value="" placeholder="${tkaWpUtilsColumns.i18n.customKeyPlaceholder}" style="font-family: monospace; display: none;">
						</div>
					</div>

					<!-- Field Type Select -->
					<div>
						<select name="tka_wp_utils_columns[${postType}][${index}][field_type]" class="tka-field-type-select">
							<option value="text">${tkaWpUtilsColumns.i18n.plainText}</option>
							<option value="post_relation">${tkaWpUtilsColumns.i18n.relatedPost}</option>
							<option value="term_relation">${tkaWpUtilsColumns.i18n.relatedTerm}</option>
						</select>
					</div>
				</div>

				<!-- Delete Button -->
				<button type="button" class="button tka-column-remove-btn" title="Delete column rule">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		`;

		const $row = $(html).appendTo($list);

		// Hide empty placeholder & show headers
		$placeholder.hide();
		$headers.show();

		// Refresh sortable list
		$list.sortable('refresh');

		// Animate the row fade-in
		setTimeout(function() {
			$row.css({ opacity: 1, transform: 'translateY(0)' });
		}, 50);
	});

	// Handle removing a custom column row
	$(document).on('click', '.tka-column-remove-btn', function() {
		const $row = $(this).closest('.tka-column-row-item');
		const $list = $row.closest('.tka-columns-rows-list');
		const $panel = $list.closest('.tka-columns-post-type-panel');
		const $placeholder = $panel.find('.tka-columns-empty-placeholder');
		const $headers = $panel.find('.tka-columns-headers-row');

		// Animate row fade-out
		$row.css({ opacity: 0, transform: 'translateY(-10px)' });

		setTimeout(function() {
			$row.remove();

			// Show placeholder & hide headers if no rows left
			if ($list.children('.tka-column-row-item').length === 0) {
				$headers.hide();
				$placeholder.fadeIn(200);
			} else {
				$list.sortable('refresh');
			}
		}, 300);
	});
});
