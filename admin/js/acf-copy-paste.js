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
					$checkbox.on('click mousedown mouseup', function(e) {
						e.stopPropagation();
					});
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
	 * Check if a subfield is a direct child field of a container.
	 */
	function isDirectChildField($subfield, $container) {
		return !$subfield.parentsUntil($container).filter('.acf-field').length;
	}

	/**
	 * Recursively serialize an ACF field (supporting repeaters, groups, and standard fields).
	 */
	function serializeField($fieldEl) {
		const field = acf.getField($fieldEl);
		if (!field) return null;

		const key = field.get('key');
		const type = field.get('type');

		if (type === 'repeater') {
			const rows = [];
			field.$rows().each(function() {
				const $row = $(this);
				const rowData = {};
				$row.find('.acf-field').each(function() {
					const $subfieldEl = $(this);
					// Process only direct children of this row (not nested deeper inside other subfields in the same row)
					if (isDirectChildField($subfieldEl, $row)) {
						const subSerialized = serializeField($subfieldEl);
						if (subSerialized) {
							rowData[subSerialized.key] = subSerialized;
						}
					}
				});
				rows.push(rowData);
			});
			return { key: key, type: type, value: rows };
		} else if (type === 'group') {
			const groupData = {};
			$fieldEl.find('.acf-field').each(function() {
				const $subfieldEl = $(this);
				// Process only direct children of this group
				if (isDirectChildField($subfieldEl, $fieldEl)) {
					const subSerialized = serializeField($subfieldEl);
					if (subSerialized) {
						groupData[subSerialized.key] = subSerialized;
					}
				}
			});
			return { key: key, type: type, value: groupData };
		} else {
			// Standard or complex field
			let val = field.val();

			// Special handling for WYSIWYG: ensure TinyMCE is synced to textarea
			if (type === 'wysiwyg' && typeof tinyMCE !== 'undefined') {
				const textareaId = $fieldEl.find('textarea').first().attr('id');
				if (textareaId) {
					const editor = tinyMCE.get(textareaId);
					if (editor) {
						val = editor.getContent();
					}
				}
			}

			// Special handling for media fields (Image, File, Gallery): capture entire input preview HTML
			let mediaHtml = null;
			if (type === 'image' || type === 'file' || type === 'gallery') {
				const $inputContainer = $fieldEl.find('.acf-input').first();
				if ($inputContainer.length) {
					mediaHtml = $inputContainer.html();
				}
			}

			return {
				key: key,
				type: type,
				value: val,
				mediaHtml: mediaHtml
			};
		}
	}

	/**
	 * Recursively populate an ACF field (supporting repeaters, groups, and standard fields).
	 */
	function populateField($fieldEl, serializedData, prefix) {
		if (!serializedData) return;
		const field = acf.getField($fieldEl);
		if (!field) return;

		const type = serializedData.type;
		const val = serializedData.value;

		if (type === 'repeater') {
			// Clear existing rows (if any)
			field.$rows().each(function() {
				$(this).remove();
			});

			// Add rows and populate recursively
			if (Array.isArray(val)) {
				val.forEach(function(rowData) {
					const $newRow = field.add(); // Programmatically append new row
					if ($newRow && $newRow.length) {
						$newRow.find('.acf-field').each(function() {
							const $subfieldEl = $(this);
							if (isDirectChildField($subfieldEl, $newRow)) {
								const subKey = $subfieldEl.data('key');
								if (rowData[subKey]) {
									populateField($subfieldEl, rowData[subKey], prefix);
								}
							}
						});
					}
				});
			}
		} else if (type === 'group') {
			$fieldEl.find('.acf-field').each(function() {
				const $subfieldEl = $(this);
				if (isDirectChildField($subfieldEl, $fieldEl)) {
					const subKey = $subfieldEl.data('key');
					if (val && val[subKey]) {
						populateField($subfieldEl, val[subKey], prefix);
					}
				}
			});
		} else {
			// Standard or complex field

			// 1. Restore visual media structures first
			if ((type === 'image' || type === 'file' || type === 'gallery') && serializedData.mediaHtml) {
				const $inputContainer = $fieldEl.find('.acf-input').first();
				if ($inputContainer.length) {
					$inputContainer.html(serializedData.mediaHtml);

					// Rename inputs to use the new layout row's prefix
					$inputContainer.find('input, select, textarea').each(function() {
						const oldName = $(this).attr('name');
						if (oldName) {
							const newName = oldName.replace(/^acf\[field_[a-zA-Z0-9_]+\]\[(?:row-)?\d+\]/, prefix);
							$(this).attr('name', newName);
						}
					});

					$fieldEl.find('.acf-gallery').first().removeClass('-empty');
				}
			}

			// 2. Set DOM value directly
			const $input = $fieldEl.find('input, select, textarea').first();
			if ($input.length) {
				if ($input.attr('type') === 'radio' || $input.attr('type') === 'checkbox') {
					$fieldEl.find('input[value="' + val + '"]').prop('checked', true);
				} else {
					$input.val(val);
				}
			}

			// 3. Try setting via ACF JS API
			if (typeof field.val === 'function') {
				try {
					field.val(val);
				} catch (e) {
					console.warn('ACF JS API val() failed:', e);
				}
			}

			// 4. Special handling for WYSIWYG editors
			if (type === 'wysiwyg' && typeof tinymce !== 'undefined') {
				const textareaId = $fieldEl.find('textarea').first().attr('id');
				if (textareaId) {
					const editor = tinymce.get(textareaId);
					if (editor) {
						try {
							editor.setContent(val);
						} catch (e) {}
					}
				}
			}

			$fieldEl.trigger('change');
		}
	}

	/**
	 * Serialize a Layout Row into a JSON object.
	 */
	function serializeLayout($layout) {
		const $layoutInput = $layout.find('input[name$="[acf_fc_layout]"]').first();
		if (!$layoutInput.length) {
			return null;
		}

		const layoutSlug = $layoutInput.val();
		const fieldValues = [];

		// Serialize only top-level fields inside the layout
		$layout.find('.acf-field').each(function() {
			const $fieldEl = $(this);
			if (isDirectChildField($fieldEl, $layout)) {
				const serialized = serializeField($fieldEl);
				if (serialized) {
					fieldValues.push(serialized);
				}
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

		if (fields && Array.isArray(fields)) {
			fields.forEach(function(serializedField) {
				const $fieldEl = $newRow.find('.acf-field[data-key="' + serializedField.key + '"]').first();
				if ($fieldEl.length) {
					populateField($fieldEl, serializedField, prefix);
				}
			});
		}
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

	// 1b. Prevent checkbox interactions (click, drag/sort) from triggering layout collapse/expand or sortable drag in ACF handle
	if (typeof document !== 'undefined') {
		['click', 'mousedown', 'mouseup'].forEach(function(eventName) {
			document.addEventListener(eventName, function(e) {
				if (e.target && (e.target.classList.contains('tka-acf-layout-select') || e.target.closest('.tka-acf-layout-select'))) {
					e.stopPropagation();
				}
			}, true); // Use capture phase to intercept event before it reaches ACF or jQuery UI Sortable
		});
	}

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
