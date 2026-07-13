/**
 * TKA Site Utilities - ACF Layout Visibility Toggle & Location Rules Engine
 */
(function ($) {
	if (typeof acf === 'undefined') {
		return;
	}

	// -------------------------------------------------------------
	// FIELD GROUP EDITOR LOGIC (Custom Fields screen)
	// -------------------------------------------------------------
	function initFieldGroupEditor() {
		// Move location rules wrappers to the bottom of the layout meta container so they stay inside the layout block settings area
		$('.tka-layout-location-rules').each(function () {
			const $rules = $(this);
			const $meta = $rules.closest('.acf-fc-meta');
			// Check if still nested inside meta elements
			if ($rules.closest('.acf-fc-meta-max').length) {
				$meta.append($rules);
			}
		});

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

	// Helper to reindex location rules inputs inside the layout builder
	function reindexRules($container) {
		const baseName = $container.data('base-name');
		
		$container.find('.tka-rules-group').each(function (groupIdx) {
			const $group = $(this);
			$group.attr('data-group-index', groupIdx);
			
			// Adjust OR divider
			let $orLabel = $group.find('.tka-rule-group-or').first();
			if (groupIdx === 0) {
				$orLabel.remove();
			} else if ($orLabel.length === 0) {
				$group.prepend('<div class="tka-rule-group-or">or</div>');
			}
			
			$group.find('.tka-rule-row').each(function (ruleIdx) {
				const $row = $(this);
				$row.attr('data-rule-index', ruleIdx);
				
				const prefix = baseName + '[' + groupIdx + '][' + ruleIdx + ']';
				
				$row.find('select, input').each(function () {
					const $input = $(this);
					const nameAttr = $input.attr('name');
					if (nameAttr) {
						if (nameAttr.indexOf('[param]') !== -1) {
							$input.attr('name', prefix + '[param]');
						} else if (nameAttr.indexOf('[operator]') !== -1) {
							$input.attr('name', prefix + '[operator]');
						} else if (nameAttr.indexOf('[value]') !== -1) {
							$input.attr('name', prefix + '[value]');
						}
					}
				});
			});
		});
	}

	// Toggle value select fields based on parameter type selection
	$(document).on('change', '.tka-rule-param-select', function () {
		const $select = $(this);
		const $row = $select.closest('.tka-rule-row');
		const param = $select.val();
		
		const $valCell = $row.find('td.value');
		$valCell.find('.tka-val-select').hide().prop('disabled', true);
		
		const $target = $valCell.find('.tka-val-' + param);
		if ($target.length) {
			$target.show().prop('disabled', false);
		}
	});

	// Add new rule group (OR condition)
	$(document).on('click', '.tka-add-rule-group', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $container = $btn.closest('.tka-layout-location-rules');
		const $groupsWrap = $container.find('.tka-rules-groups-container').first();
		
		const nextGroupIdx = $groupsWrap.find('.tka-rules-group').length;
		
		// Create new group wrapper
		const $newGroup = $('<div class="tka-rules-group" data-group-index="' + nextGroupIdx + '"><table class="tka-rules-table"><tbody></tbody></table></div>');
		$groupsWrap.append($newGroup);
		
		// Add first row to this group
		const rowTpl = $container.find('.tka-tpl-rule-row').first().html();
		const cleanRow = rowTpl.replace(/{group_idx}/g, nextGroupIdx).replace(/{rule_idx}/g, 0);
		
		$newGroup.find('tbody').append(cleanRow);
		
		// Trigger param select change to set up disabled/hidden values
		$newGroup.find('.tka-rule-param-select').trigger('change');
		
		reindexRules($container);
	});

	// Add row to group (AND condition)
	$(document).on('click', '.tka-add-rule', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $row = $btn.closest('.tka-rule-row');
		const $tbody = $row.closest('tbody');
		const $group = $row.closest('.tka-rules-group');
		const $container = $row.closest('.tka-layout-location-rules');
		
		const groupIdx = $group.attr('data-group-index');
		const nextRuleIdx = $tbody.find('.tka-rule-row').length;
		
		const rowTpl = $container.find('.tka-tpl-rule-row').first().html();
		const cleanRow = rowTpl.replace(/{group_idx}/g, groupIdx).replace(/{rule_idx}/g, nextRuleIdx);
		
		const $newRow = $(cleanRow);
		$row.after($newRow);
		
		$newRow.find('.tka-rule-param-select').trigger('change');
		
		reindexRules($container);
	});

	// Remove rule row
	$(document).on('click', '.tka-remove-rule', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $row = $btn.closest('.tka-rule-row');
		const $tbody = $row.closest('tbody');
		const $group = $row.closest('.tka-rules-group');
		const $container = $row.closest('.tka-layout-location-rules');
		
		// Remove current row
		$row.remove();
		
		// If group is empty, remove the group
		if ($tbody.find('.tka-rule-row').length === 0) {
			$group.remove();
		}
		
		reindexRules($container);
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

		// Initialize list of layouts to hide
		let layoutsToHide = [];

		// 1. Harvest globally disabled layouts
		if (typeof tkaAcfLayoutToggleSettings !== 'undefined' && tkaAcfLayoutToggleSettings.enableToggle == 1) {
			const disabledLayoutsAttr = $field.data('tka-disabled-layouts');
			if (disabledLayoutsAttr) {
				const disabledLayouts = disabledLayoutsAttr.split(',');
				layoutsToHide = layoutsToHide.concat(disabledLayouts);

				// Style existing layout rows that are globally disabled
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

		// 2. Harvest layout location rule restrictions
		const restrictedLayoutsAttr = $field.data('tka-restricted-layouts');
		if (restrictedLayoutsAttr) {
			const restrictedLayouts = restrictedLayoutsAttr.split(',');
			layoutsToHide = layoutsToHide.concat(restrictedLayouts);
		}

		// 3. Remove restricted/disabled options from the pop-up template
		if (layoutsToHide.length > 0) {
			const $popupTemplate = $field.find('.tmpl-popup').first();
			if ($popupTemplate.length) {
				let html = $popupTemplate.html();
				const $tempDiv = $('<div>').html(html);
				
				layoutsToHide.forEach(function (layoutName) {
					$tempDiv.find('a[data-layout="' + layoutName + '"]').closest('li').remove();
				});
				
				$popupTemplate.html($tempDiv.html());
			}
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
