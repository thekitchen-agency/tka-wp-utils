/**
 * TKA WP Utils - ACF Flexible Layout Copy & Paste Engine
 */
(function($) {
	if (typeof acf === 'undefined') {
		return;
	}

	const enableMultiselect = parseInt(tkaAcfSettings.enableMultiselect, 10) === 1;
	const i18n = tkaAcfSettings.i18n;

	// Hook into ACF Flexible Content Initialization
	acf.add_action('ready_field/type=flexible_content', function(field) {
		initFlexibleField(field);
	});

	// Re-run initialization on newly appended layout elements
	acf.add_action('append', function($el) {
		if ($el.hasClass('acf-fc-layout') || $el.hasClass('layout')) {
			const $fieldEl = $el.closest('.acf-field-flexible-content');
			if ($fieldEl.length) {
				const field = acf.getField($fieldEl);
				if (field) {
					initFlexibleField(field);
				}
			}
		}
	});

	// Setup MutationObserver to dynamically initialize layouts when they are loaded asynchronously (e.g. via AJAX / Gutenberg Block Editor)
	if (typeof MutationObserver !== 'undefined') {
		const observer = new MutationObserver(function(mutations) {
			let shouldInit = false;
			mutations.forEach(function(mutation) {
				if (mutation.addedNodes && mutation.addedNodes.length > 0) {
					for (let i = 0; i < mutation.addedNodes.length; i++) {
						const node = mutation.addedNodes[i];
						if (node.nodeType === 1) { // Element node
							const $node = $(node);
							if ($node.hasClass('acf-fc-layout') || $node.hasClass('layout') || $node.find('.acf-fc-layout, .layout').length > 0) {
								shouldInit = true;
								break;
							}
						}
					}
				}
			});
			if (shouldInit) {
				acf.getFields({ type: 'flexible_content' }).forEach(function(field) {
					initFlexibleField(field);
				});
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	// Delayed initialization fallback for slow loading Gutenberg AJAX blocks
	$(document).ready(function() {
		setTimeout(function() {
			acf.getFields({ type: 'flexible_content' }).forEach(function(field) {
				initFlexibleField(field);
			});
		}, 1000);
	});

	/**
	 * Initialize custom copy/paste actions on a Flexible Content Field.
	 */
	function initFlexibleField(field) {
		if (!field) {
			return;
		}
		const $field = field.$el ? field.$el : (field.jquery ? field : $(field));
		if (!$field || !$field.length) {
			return;
		}
		const $layouts = $field.find('.acf-fc-layout, .layout');

		// 1. Inject Copy buttons to each layout row
		$layouts.each(function() {
			const $layout = $(this);
			const $controls = $layout.find('.acf-fc-layout-controls').first();

			if ($controls.length && !$controls.find('.tka-acf-copy-btn').length) {
				const $copyBtn = $('<a class="acf-icon dashicons dashicons-admin-page tka-acf-copy-btn" style="cursor: pointer; font-size: 14px; line-height: 20px; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px;" title="' + i18n.copy + '"></a>');
				$controls.prepend($copyBtn);
			}

			// 2. Inject select checkboxes for multiselect bulk options
			if (enableMultiselect) {
				const $handle = $layout.find('.acf-fc-layout-handle').first();
				if ($handle.length && !$handle.find('.tka-acf-layout-select').length) {
					const $checkbox = $('<input type="checkbox" class="tka-acf-layout-select">');
					$handle.prepend($checkbox);
				}
			}
		});

		// 3. Inject Action bar items inside acf-actions (Add Layout bar)
		const $actions = $field.find('.acf-actions').first();
		if ($actions.length && !$actions.find('.tka-acf-flex-actions').length) {
			const $actionsGroup = $('<div class="tka-acf-flex-actions"></div>');

			// Paste Button
			const $pasteBtn = $('<a class="acf-button button button-secondary tka-acf-paste-btn" style="display: none;"><span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -3px; font-size: 16px;"></span> ' + i18n.paste + '</a>');
			$actionsGroup.append($pasteBtn);

			// Copy Selected Button
			if (enableMultiselect) {
				const $copySelectedBtn = $('<a class="acf-button button button-secondary tka-acf-copy-selected-btn" style="display: none;"><span class="dashicons dashicons-admin-page" style="vertical-align: middle; margin-top: -3px; font-size: 16px;"></span> ' + i18n.copySelected + ' (<span class="count">0</span>)</a>');
				$actionsGroup.append($copySelectedBtn);
			}

			$actions.append($actionsGroup);
		}

		updatePasteButtonsVisibility();
	}

	/**
	 * Show or hide Paste buttons depending on localStorage contents.
	 */
	function updatePasteButtonsVisibility() {
		const copied = localStorage.getItem('tka_acf_copied_layouts');
		const $pasteBtns = $('.tka-acf-paste-btn');

		if (copied) {
			try {
				const parsed = JSON.parse(copied);
				if (Array.isArray(parsed) && parsed.length > 0) {
					$pasteBtns.show();
					return;
				}
			} catch (e) {}
		}
		$pasteBtns.hide();
	}

	/**
	 * Serialize a Layout Row into a JSON object.
	 */
	function serializeLayout($layout) {
		const $layoutInput = $layout.find('input[name$="[acf_fc_layout]"]').first();
		if (!$layoutInput.length) {
			return null;
		}

		const layoutName = $layoutInput.attr('name');
		const layoutSlug = $layoutInput.val();
		const prefix = layoutName.replace('[acf_fc_layout]', '');
		const fieldValues = [];

		// Query all nested input elements
		$layout.find('input, select, textarea').each(function() {
			const name = $(this).attr('name');
			if (!name || name.endsWith('[acf_fc_layout]')) {
				return;
			}

			if (name.startsWith(prefix)) {
				const relativeName = name.substring(prefix.length);
				const type = $(this).attr('type');
				const value = $(this).val();

				// Skip unchecked radios/checkboxes
				if (type === 'radio' || type === 'checkbox') {
					if (!$(this).prop('checked')) {
						return;
					}
				}

				fieldValues.push({
					relativeName: relativeName,
					value: value,
					type: type,
					checked: $(this).prop('checked')
				});
			}
		});

		return {
			layoutSlug: layoutSlug,
			fields: fieldValues
		};
	}

	/**
	 * Populate values recursively into a newly created layout row.
	 */
	function populateLayout($newRow, fields) {
		const $layoutInput = $newRow.find('input[name$="[acf_fc_layout]"]').first();
		if (!$layoutInput.length) {
			return;
		}

		const layoutName = $layoutInput.attr('name');
		const prefix = layoutName.replace('[acf_fc_layout]', '');

		fields.forEach(function(field) {
			const absoluteName = prefix + field.relativeName;
			// Escape jQuery selector meta characters in name
			const escapedName = absoluteName.replace(/(:|\.|\[|\]|,|=|@)/g, "\\$1");
			const $input = $newRow.find('[name="' + escapedName + '"]');

			if ($input.length) {
				const $acfFieldEl = $input.closest('.acf-field');
				if ($acfFieldEl.length) {
					const acfField = acf.getField($acfFieldEl);
					if (acfField && typeof acfField.val === 'function') {
						acfField.val(field.value);
					} else {
						if (field.type === 'radio' || field.type === 'checkbox') {
							$input.prop('checked', field.checked);
						} else {
							$input.val(field.value);
						}
					}
				} else {
					if (field.type === 'radio' || field.type === 'checkbox') {
						$input.prop('checked', field.checked);
					} else {
						$input.val(field.value);
					}
				}
				$input.trigger('change');
			}
		});
	}

	/**
	 * Recursively paste layouts queue sequentially to support complex DOM loading.
	 */
	function pasteLayoutsQueue(field, layouts, index) {
		if (index >= layouts.length) {
			updatePasteButtonsVisibility();
			return;
		}

		const copiedLayout = layouts[index];
		const $beforeRows = field.$el.find('.acf-fc-layout, .layout');

		// Programmatically add the layout
		field.add({
			layout: copiedLayout.layoutSlug
		});

		// Set a safe timeout to let ACF instantiate the layouts HTML and nested JS fields
		setTimeout(function() {
			const $afterRows = field.$el.find('.acf-fc-layout, .layout');
			let $newRow = null;

			$afterRows.each(function() {
				if ($beforeRows.index(this) === -1) {
					$newRow = $(this);
				}
			});

			if ($newRow && $newRow.length) {
				populateLayout($newRow, copiedLayout.fields);
			}

			// Process next item in the queue
			pasteLayoutsQueue(field, layouts, index + 1);
		}, 150);
	}

	// ==========================================
	// DOM EVENT BINDINGS
	// ==========================================

	// 1. Single Copy Click Handler
	$(document).on('click', '.tka-acf-copy-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();

		const $btn = $(this);
		const $layout = $btn.closest('.acf-fc-layout, .layout');
		const layoutData = serializeLayout($layout);

		if (layoutData) {
			localStorage.setItem('tka_acf_copied_layouts', JSON.stringify([layoutData]));
			
			// Quick feedback
			const originalClass = $btn.attr('class');
			$btn.removeClass('dashicons-admin-page').addClass('dashicons-yes-alt').attr('title', i18n.copied);
			setTimeout(function() {
				$btn.attr('class', originalClass).attr('title', i18n.copy);
			}, 1500);

			updatePasteButtonsVisibility();
		}
	});

	// 1b. Prevent checkbox clicks from triggering layout collapse/expand in ACF handle
	$(document).on('click', '.tka-acf-layout-select', function(e) {
		e.stopPropagation();
	});

	// 2. Multiselect Checkbox Handler
	$(document).on('change', '.tka-acf-layout-select', function() {
		const $checkbox = $(this);
		const $layout = $checkbox.closest('.acf-fc-layout, .layout');
		const $field = $checkbox.closest('.acf-field-flexible-content');

		if ($checkbox.prop('checked')) {
			$layout.addClass('tka-layout-selected');
		} else {
			$layout.removeClass('tka-layout-selected');
		}

		// Update "Copy Selected" buttons
		const checkedCount = $field.find('.tka-acf-layout-select:checked').length;
		const $copySelectedBtn = $field.find('.tka-acf-copy-selected-btn');

		if (checkedCount > 0) {
			$copySelectedBtn.find('.count').text(checkedCount);
			$copySelectedBtn.show();
		} else {
			$copySelectedBtn.hide();
		}
	});

	// 3. Bulk "Copy Selected" Click Handler
	$(document).on('click', '.tka-acf-copy-selected-btn', function(e) {
		e.preventDefault();
		const $btn = $(this);
		const $field = $btn.closest('.acf-field-flexible-content');
		const $checked = $field.find('.tka-acf-layout-select:checked');
		const serializedArray = [];

		$checked.each(function() {
			const $layout = $(this).closest('.acf-fc-layout, .layout');
			const data = serializeLayout($layout);
			if (data) {
				serializedArray.push(data);
			}
		});

		if (serializedArray.length > 0) {
			localStorage.setItem('tka_acf_copied_layouts', JSON.stringify(serializedArray));
			alert(i18n.layoutsCopied);

			// Uncheck and clear classes
			$checked.prop('checked', false).trigger('change');
			updatePasteButtonsVisibility();
		}
	});

	// 4. "Paste Layouts" Click Handler
	$(document).on('click', '.tka-acf-paste-btn', function(e) {
		e.preventDefault();
		const $btn = $(this);
		const $fieldEl = $btn.closest('.acf-field-flexible-content');
		const field = acf.getField($fieldEl);

		if (!field) {
			return;
		}

		const copied = localStorage.getItem('tka_acf_copied_layouts');
		if (!copied) {
			alert(i18n.nothingCopied);
			return;
		}

		try {
			const layouts = JSON.parse(copied);
			if (Array.isArray(layouts) && layouts.length > 0) {
				pasteLayoutsQueue(field, layouts, 0);
			}
		} catch (err) {
			alert(i18n.nothingCopied);
		}
	});

})(jQuery);
