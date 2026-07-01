/**
 * TKA Site Utilities - ACF Layout Visibility Toggle Engine
 */
(function ($) {
	if (typeof acf === 'undefined') {
		return;
	}

	// -------------------------------------------------------------
	// FIELD GROUP EDITOR LOGIC (Custom Fields screen)
	// -------------------------------------------------------------
	function initFieldGroupEditor() {
		if (typeof tkaAcfLayoutToggleSettings === 'undefined' || tkaAcfLayoutToggleSettings.enableToggle != 1) {
			return;
		}
		const $layouts = $('.acf-field-setting-fc_layout');
		
		$layouts.each(function () {
			const $layout = $(this);
			const $actions = $layout.find('.acf-fl-actions').first();

			if ($actions.length && !$actions.find('.tka-field-group-layout-visibility-btn').length) {
				const $disabledInput = $layout.find('.tka-layout-disabled-input').first();
				if (!$disabledInput.length) {
					return;
				}

				const isDisabled = $disabledInput.val() === '1';

				// Apply initial class
				if (isDisabled) {
					$layout.addClass('tka-layout-globally-disabled');
				} else {
					$layout.removeClass('tka-layout-globally-disabled');
				}

				// Create Visibility Button
				const iconClass = isDisabled ? 'is-disabled' : '';
				const titleText = isDisabled ? 'Enable Layout' : 'Disable Layout';

				const $toggleBtn = $('<li><button type="button" class="acf-btn acf-btn-tertiary acf-btn-sm tka-field-group-layout-visibility-btn" title="' + titleText + '"><span class="tka-visibility-icon ' + iconClass + '"></span></button></li>');
				
				// Insert right before other buttons
				$actions.prepend($toggleBtn);
			}
		});
	}

	// Delegate click event for the visibility button in field group editor
	$(document).on('click', '.tka-field-group-layout-visibility-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const $btn = $(this);
		const $layout = $btn.closest('.acf-field-setting-fc_layout');
		const $disabledInput = $layout.find('.tka-layout-disabled-input').first();

		if ($disabledInput.length) {
			const currentlyDisabled = $disabledInput.val() === '1';
			const newDisabled = !currentlyDisabled;

			$disabledInput.val(newDisabled ? '1' : '0');
			$disabledInput.trigger('change');

			const $icon = $btn.find('.tka-visibility-icon');

			if (newDisabled) {
				$layout.addClass('tka-layout-globally-disabled');
				$icon.addClass('is-disabled');
				$btn.attr('title', 'Enable Layout');
			} else {
				$layout.removeClass('tka-layout-globally-disabled');
				$icon.removeClass('is-disabled');
				$btn.attr('title', 'Disable Layout');
			}
		}
	});

	// -------------------------------------------------------------
	// POST/PAGE EDITOR LOGIC (Content Editing screen)
	// -------------------------------------------------------------
	function initPostEditor(field) {
		if (!field) return;
		const $field = field.$el ? field.$el : $(field);
		
		// Apply class if rename hijack is enabled
		if (typeof tkaAcfLayoutToggleSettings !== 'undefined' && tkaAcfLayoutToggleSettings.enableRename == 1) {
			$field.addClass('tka-layout-rename-enabled');
		}

		if (typeof tkaAcfLayoutToggleSettings === 'undefined' || tkaAcfLayoutToggleSettings.enableToggle != 1) {
			return;
		}
		
		// Get globally disabled layouts passed via wrapper data attribute
		const disabledLayoutsAttr = $field.data('tka-disabled-layouts');
		if (disabledLayoutsAttr) {
			const disabledLayouts = disabledLayoutsAttr.split(',');

			// 1. Hide options from default popup menu template
			const $popupTemplate = $field.find('.tmpl-popup').first();
			if ($popupTemplate.length) {
				let html = $popupTemplate.html();
				const $tempDiv = $('<div>').html(html);
				
				disabledLayouts.forEach(function (layoutName) {
					$tempDiv.find('a[data-layout="' + layoutName + '"]').closest('li').remove();
				});
				
				$popupTemplate.html($tempDiv.html());
			}

			// 2. Style existing layout rows that are globally disabled
			const $layouts = $field.find('.acf-fc-layout, .layout');
			$layouts.each(function () {
				const $layout = $(this);
				const layoutName = $layout.data('layout');
				if (disabledLayouts.indexOf(layoutName) > -1) {
					$layout.addClass('tka-layout-row-globally-disabled');
				}
			});
		}
	}

	// Hook into ACF Flexible Content Initialization
	acf.add_action('ready_field/type=flexible_content', function (field) {
		initPostEditor(field);
	});

	// Re-run initialization on newly appended layout elements
	acf.add_action('append', function ($el) {
		if ($el.hasClass('acf-fc-layout') || $el.hasClass('layout')) {
			const $fieldEl = $el.closest('.acf-field-flexible-content');
			if ($fieldEl.length) {
				const field = acf.getField($fieldEl);
				if (field) {
					initPostEditor(field);
				}
			}
		}
	});

	// MutationObserver fallback to catch asynchronous layouts enqueued dynamically (e.g. via Gutenberg/AJAX)
	if (typeof MutationObserver !== 'undefined') {
		const observer = new MutationObserver(function (mutations) {
			let shouldInitFieldGroup = false;
			let shouldInitPostEditor = false;

			mutations.forEach(function (mutation) {
				if (mutation.addedNodes && mutation.addedNodes.length > 0) {
					for (let i = 0; i < mutation.addedNodes.length; i++) {
						const node = mutation.addedNodes[i];
						if (node.nodeType === 1) { // Element node
							const $node = $(node);
							// Check if setting layouts were added
							if ($node.hasClass('acf-field-setting-fc_layout') || $node.find('.acf-field-setting-fc_layout').length > 0) {
								shouldInitFieldGroup = true;
							}
							// Check if flexible layout rows were added
							if ($node.hasClass('acf-fc-layout') || $node.hasClass('layout') || $node.find('.acf-fc-layout, .layout').length > 0) {
								shouldInitPostEditor = true;
							}
						}
					}
				}
			});

			if (shouldInitFieldGroup) {
				initFieldGroupEditor();
			}

			if (shouldInitPostEditor) {
				acf.getFields({ type: 'flexible_content' }).forEach(function (field) {
					initPostEditor(field);
				});
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	// Trigger initial runs
	$(document).ready(function () {
		// Field Group Editor initial check
		initFieldGroupEditor();

		// Post Editor initial check (delayed fallback for Gutenberg/AJAX fields)
		setTimeout(function () {
			acf.getFields({ type: 'flexible_content' }).forEach(function (field) {
				initPostEditor(field);
			});
		}, 1000);
	});

	// Hijack layout title click to open the native rename dialog
	$(document).on('click', '.acf-field-flexible-content .layout .acf-fc-layout-title', function (e) {
		if (typeof tkaAcfLayoutToggleSettings === 'undefined' || tkaAcfLayoutToggleSettings.enableRename != 1) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		const $title = $(this);
		const $layout = $title.closest('.layout');
		const $fieldEl = $layout.closest('.acf-field-flexible-content');
		
		const field = acf.getField($fieldEl);
		if (field && typeof field.onClickRenameLayout === 'function') {
			field.onClickRenameLayout(e, $layout);
		}
	});

})(jQuery);
