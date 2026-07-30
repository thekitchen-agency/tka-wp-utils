(function($) {
	if (typeof acf === 'undefined') {
		return;
	}

	var Field = acf.Field.extend({
		type: 'tka_dynamic_table',

		events: {
			'click .btn-add-row': 'onAddRow',
			'click .btn-remove-row': 'onRemoveRow',
			'click .btn-move-row-up': 'onMoveRowUp',
			'click .btn-move-row-down': 'onMoveRowDown',
			'click .btn-add-col': 'onAddColumn',
			'click .btn-add-category': 'onAddTopCategory',
			'click .btn-add-sub-col': 'onAddSubColumn',
			'click .btn-remove-col': 'onRemoveColumn',
			'click .btn-remove-sub-col': 'onRemoveSubColumn',
			'click .btn-toggle-column-settings': 'onToggleColumnSettings',
			'change .input-colspan': 'onColspanChange',
			'change .select-col-type': 'onColTypeChange',
			'click .btn-select-file': 'onSelectFile',
			'click .btn-clear-file': 'onClearFile'
		},

		$wrapper: function() {
			return this.$('.acf-dynamic-table-wrapper');
		},

		$table: function() {
			return this.$('.acf-dynamic-table');
		},

		getPrefix: function() {
			var $wrap = this.$wrapper();
			var $anyInput = this.$('input.acf-dynamic-table-input, input.acf-dynamic-table-caption-input').first();

			if ($anyInput.length) {
				var currentName = $anyInput.attr('name') || '';
				var match = currentName.match(/^(.*?)\[(?:header|body|caption)\]/);
				if (match && match[1] && match[1].indexOf('acfcloneindex') === -1) {
					$wrap.attr('data-field-name', match[1]);
					return match[1];
				}
			}

			var fallback = $wrap.attr('data-field-name') || $wrap.data('field-name') || '';
			return fallback;
		},

		getSettings: function() {
			var $wrap = this.$wrapper();
			return {
				lockColumns: parseInt($wrap.attr('data-lock-columns') || $wrap.data('lock-columns'), 10) === 1,
				fixedColumns: parseInt($wrap.attr('data-fixed-columns') || $wrap.data('fixed-columns'), 10) || 1,
				minColumns: parseInt($wrap.attr('data-min-columns') || $wrap.data('min-columns'), 10) || 1,
				maxColumns: parseInt($wrap.attr('data-max-columns') || $wrap.data('max-columns'), 10) || 0,
				lockRows: parseInt($wrap.attr('data-lock-rows') || $wrap.data('lock-rows'), 10) === 1,
				fixedRows: parseInt($wrap.attr('data-fixed-rows') || $wrap.data('fixed-rows'), 10) || 1,
				minRows: parseInt($wrap.attr('data-min-rows') || $wrap.data('min-rows'), 10) || 1,
				maxRows: parseInt($wrap.attr('data-max-rows') || $wrap.data('max-rows'), 10) || 0,
				useHeader: parseInt($wrap.attr('data-use-header') || $wrap.data('use-header'), 10) === 1,
				headerType: $wrap.attr('data-header-type') || $wrap.data('header-type') || 'single'
			};
		},

		onToggleColumnSettings: function(e) {
			e.preventDefault();
			var $wrap = this.$wrapper();
			var $btn = $(e.currentTarget);
			$wrap.toggleClass('show-column-settings');

			if ($wrap.hasClass('show-column-settings')) {
				$btn.addClass('active').html('<span class="dashicons dashicons-admin-generic"></span> Close Cell Types');
			} else {
				$btn.removeClass('active').html('<span class="dashicons dashicons-admin-generic"></span> Column Cell Types');
			}
		},

		getLeafColumnTypes: function() {
			var $table = this.$table();
			var settings = this.getSettings();
			var leafTypes = [];

			if (settings.headerType === 'multi') {
				var $topThs = $table.find('thead tr.tr-header-top th.th-top-header');
				var $subThs = $table.find('thead tr.tr-header-sub th.th-sub-header');
				var subIdx = 0;

				$topThs.each(function() {
					var $topTh = $(this);
					var rs = parseInt($topTh.attr('rowspan') || 1, 10);
					var type = $topTh.attr('data-col-type') || $topTh.find('select.select-col-type').val() || 'text';

					if (rs > 1) {
						leafTypes.push(type);
					} else {
						var cs = parseInt($topTh.attr('colspan') || $topTh.find('.input-colspan').val() || 1, 10);
						for (var c = 0; c < cs; c++) {
							var $subTh = $subThs.eq(subIdx);
							var subType = $subTh.length ? ($subTh.attr('data-col-type') || $subTh.find('select.select-col-type').val() || 'text') : 'text';
							leafTypes.push(subType);
							subIdx++;
						}
					}
				});
			} else {
				$table.find('thead tr:first th.th-data-col').each(function() {
					var $th = $(this);
					var type = $th.attr('data-col-type') || $th.find('select.select-col-type').val() || 'text';
					leafTypes.push(type);
				});
			}

			return leafTypes;
		},

		getColCount: function() {
			var $table = this.$table();
			var settings = this.getSettings();

			if (settings.headerType === 'multi') {
				var topSum = 0;
				$table.find('thead tr.tr-header-top th.th-top-header').each(function() {
					var $th = $(this);
					var cs = parseInt($th.attr('colspan') || $th.find('input.input-colspan').val() || 1, 10);
					topSum += cs;
				});
				if (topSum > 0) return topSum;
			}

			var singleCount = $table.find('thead tr:first th.th-data-col').length;
			if (singleCount > 0) return singleCount;

			var $firstRowCells = $table.find('tbody tr:first td.td-data-cell');
			if ($firstRowCells.length > 0) {
				return $firstRowCells.length;
			}

			return settings.fixedColumns || 1;
		},

		initialize: function() {
			this.updateState();
		},

		updateState: function() {
			var $table = this.$table();
			var settings = this.getSettings();

			var $bodyRows = $table.find('tbody tr');
			var rowCount = $bodyRows.length;

			// Update Add Row button
			var $btnAddRow = this.$('.btn-add-row');
			if (settings.lockRows) {
				$btnAddRow.hide();
			} else {
				$btnAddRow.show();
				if (settings.maxRows > 0 && rowCount >= settings.maxRows) {
					$btnAddRow.prop('disabled', true);
				} else {
					$btnAddRow.prop('disabled', false);
				}
			}

			// ALWAYS show Add Column / Add Category buttons
			var $btnAddCol = this.$('.btn-add-col');
			var $btnAddCat = this.$('.btn-add-category');

			if (settings.headerType === 'multi') {
				$btnAddCol.hide();
				$btnAddCat.show();
			} else {
				$btnAddCol.show();
				$btnAddCat.hide();
			}

			// Update Remove Row buttons
			$bodyRows.each(function(rIdx) {
				var $row = $(this);
				var $btnRemove = $row.find('.btn-remove-row');
				var $btnUp = $row.find('.btn-move-row-up');
				var $btnDown = $row.find('.btn-move-row-down');

				if (settings.lockRows) {
					$btnRemove.hide();
				} else {
					$btnRemove.show();
					if (rowCount <= settings.minRows) {
						$btnRemove.prop('disabled', true);
					} else {
						$btnRemove.prop('disabled', false);
					}
				}

				$btnUp.prop('disabled', rIdx === 0);
				$btnDown.prop('disabled', rIdx === rowCount - 1);
			});

			this.reindexNames();
		},

		reindexNames: function() {
			var fieldName = this.getPrefix();
			if (!fieldName) return;

			var $table = this.$table();
			var settings = this.getSettings();
			var targetCols = this.getColCount();
			var leafTypes = this.getLeafColumnTypes();

			// Reindex Caption
			this.$('input.acf-dynamic-table-caption-input').attr('name', fieldName + '[caption]');

			// Reindex Header
			if (settings.headerType === 'multi') {
				// Top Header Row
				$table.find('thead tr.tr-header-top th.th-top-header').each(function(colIdx) {
					var $th = $(this);
					$th.addClass('col-idx-' + colIdx);
					$th.find('input.input-label').attr('name', fieldName + '[header][top][' + colIdx + '][label]');
					$th.find('input.input-colspan').attr('name', fieldName + '[header][top][' + colIdx + '][colspan]');
					$th.find('input.select-rowspan, select.select-rowspan').attr('name', fieldName + '[header][top][' + colIdx + '][rowspan]');
					$th.find('.select-col-type').attr('name', fieldName + '[header][top][' + colIdx + '][type]');
				});
				// Sub Header Row
				$table.find('thead tr.tr-header-sub th.th-sub-header').each(function(colIdx) {
					var $th = $(this);
					$th.addClass('sub-col-idx-' + colIdx);
					$th.find('input.input-label').attr('name', fieldName + '[header][sub][' + colIdx + '][label]');
					$th.find('select.select-col-type').attr('name', fieldName + '[header][sub][' + colIdx + '][type]');
				});
			} else {
				// Single Header Row
				$table.find('thead tr th.th-data-col').each(function(colIdx) {
					var $th = $(this);
					$th.addClass('col-idx-' + colIdx);
					$th.find('input.acf-dynamic-table-input').attr('name', fieldName + '[header][' + colIdx + '][label]');
					$th.find('select.select-col-type').attr('name', fieldName + '[header][' + colIdx + '][type]');
				});
			}

			// Reindex & Sync Cell Types for Body Rows
			$table.find('tbody tr').each(function(rowIdx) {
				var $tr = $(this);
				var $cells = $tr.find('td.td-data-cell');

				if ($cells.length < targetCols) {
					for (var c = $cells.length; c < targetCols; c++) {
						var isFileCol = (leafTypes[c] === 'file');
						var newTd = '<td class="td-data-cell col-idx-' + c + '">';
						if (isFileCol) {
							newTd += '<div class="acf-dynamic-table-file-cell empty">';
							newTd += '<input type="hidden" class="acf-dynamic-table-input file-url-input" value="" />';
							newTd += '<button type="button" class="button button-small btn-select-file" title="Select File"><span class="dashicons dashicons-paperclip"></span> <span class="file-name-label">Add File</span></button>';
							newTd += '<button type="button" class="acf-dynamic-table-btn btn-clear-file" title="Clear File"><span class="dashicons dashicons-no-alt"></span></button>';
							newTd += '</div>';
						} else {
							newTd += '<input type="text" class="acf-dynamic-table-input" value="" />';
						}
						newTd += '</td>';
						$tr.append(newTd);
					}
					$cells = $tr.find('td.td-data-cell');
				} else if ($cells.length > targetCols) {
					$cells.slice(targetCols).remove();
					$cells = $tr.find('td.td-data-cell');
				}

				$cells.each(function(colIdx) {
					var $td = $(this);
					$td.attr('class', 'td-data-cell col-idx-' + colIdx);

					var isFileCol = (leafTypes[colIdx] === 'file');
					var hasFileWrapper = $td.find('.acf-dynamic-table-file-cell').length > 0;

					if (isFileCol && !hasFileWrapper) {
						var val = ($td.find('input.acf-dynamic-table-input').val() || '').trim();
						var hasFile = (val !== '');
						var filename = hasFile ? (val.split('/').pop() || 'File') : 'Add File';
						var fileCellHtml = '<div class="acf-dynamic-table-file-cell ' + (hasFile ? 'has-file' : 'empty') + '">';
						fileCellHtml += '<input type="hidden" class="acf-dynamic-table-input file-url-input" value="' + val + '" />';
						fileCellHtml += '<button type="button" class="button button-small btn-select-file" title="' + (hasFile ? val : 'Select File') + '"><span class="dashicons dashicons-paperclip"></span> <span class="file-name-label">' + filename + '</span></button>';
						fileCellHtml += '<button type="button" class="acf-dynamic-table-btn btn-clear-file" title="Clear File"><span class="dashicons dashicons-no-alt"></span></button>';
						fileCellHtml += '</div>';
						$td.html(fileCellHtml);
					} else if (!isFileCol && hasFileWrapper) {
						var textVal = $td.find('input.file-url-input, input.acf-dynamic-table-input').val() || '';
						$td.html('<input type="text" class="acf-dynamic-table-input" value="' + textVal + '" />');
					}

					$td.find('input.acf-dynamic-table-input').attr('name', fieldName + '[body][' + rowIdx + '][' + colIdx + ']');
				});
			});
		},

		onColspanChange: function(e) {
			var $input = $(e.currentTarget);
			var val = parseInt($input.val(), 10) || 1;
			var $th = $input.closest('th');
			$th.attr('colspan', val);

			if (val > 1) {
				$th.attr('rowspan', 1);
				$th.find('.select-rowspan').val(1);
			}

			this.syncSubHeaders();
			this.updateState();
		},

		onColTypeChange: function(e) {
			var $select = $(e.currentTarget);
			var val = $select.val() || 'text';
			$select.closest('th').attr('data-col-type', val);
			this.updateState();
		},

		onAddSubColumn: function(e) {
			e.preventDefault();
			var $btn = $(e.currentTarget);
			var $th = $btn.closest('th.th-top-header');

			if (!$th.length) {
				$th = this.$table().find('thead tr.tr-header-top th.th-top-header').last();
			}

			if (!$th.length) return;

			var rs = parseInt($th.attr('rowspan') || 1, 10);
			if (rs > 1) {
				$th.attr('rowspan', 1);
				$th.attr('colspan', 2);
				$th.find('.select-rowspan').val(1);

				var fieldName = this.getPrefix();
				var cIdx = $th.index();

				var metaHtml = '<label>Sub-cols: <input type="number" min="1" max="20" class="acf-dynamic-table-span-input input-colspan" name="' + fieldName + '[header][top][' + cIdx + '][colspan]" value="2" title="Number of sub-columns" /></label>';
				metaHtml += '<button type="button" class="acf-dynamic-table-btn btn-add-sub-col" title="Add Sub-column"><span class="dashicons dashicons-plus-alt2"></span> + Sub</button>';
				metaHtml += '<input type="hidden" class="select-rowspan" name="' + fieldName + '[header][top][' + cIdx + '][rowspan]" value="1" />';
				metaHtml += '<input type="hidden" class="select-col-type" name="' + fieldName + '[header][top][' + cIdx + '][type]" value="text" />';

				$th.find('.acf-dynamic-table-header-row-meta').html(metaHtml);
			} else {
				var $spanInput = $th.find('input.input-colspan');
				if ($spanInput.length) {
					var currentSpan = parseInt($spanInput.val(), 10) || 1;
					$spanInput.val(currentSpan + 1).trigger('change');
				}
			}

			this.syncSubHeaders();
			this.updateState();
		},

		onRemoveSubColumn: function(e) {
			e.preventDefault();
			var $btn = $(e.currentTarget);
			var $subTh = $btn.closest('th.th-sub-header');
			if (!$subTh.length) return;

			var subIdx = $subTh.parent().find('th.th-sub-header').index($subTh);
			var $table = this.$table();
			var $topThs = $table.find('thead tr.tr-header-top th.th-top-header');

			var currSubIdx = 0;
			$topThs.each(function() {
				var $topTh = $(this);
				var rs = parseInt($topTh.attr('rowspan') || 1, 10);
				if (rs === 1) {
					var cs = parseInt($topTh.attr('colspan') || $topTh.find('.input-colspan').val() || 1, 10);
					if (subIdx >= currSubIdx && subIdx < currSubIdx + cs) {
						var newCs = cs - 1;
						if (newCs < 1) newCs = 1;
						$topTh.attr('colspan', newCs);
						$topTh.find('.input-colspan').val(newCs);
						return false;
					}
					currSubIdx += cs;
				}
			});

			$subTh.remove();
			this.syncSubHeaders();
			this.updateState();
		},

		syncSubHeaders: function() {
			var $table = this.$table();
			var settings = this.getSettings();
			if (settings.headerType !== 'multi') return;

			var fieldName = this.getPrefix();
			var $subTr = $table.find('thead tr.tr-header-sub');
			var $topThs = $table.find('thead tr.tr-header-top th.th-top-header');

			var neededSubCols = 0;
			$topThs.each(function() {
				var rs = parseInt($(this).attr('rowspan') || 1, 10);
				if (rs === 1) {
					var cs = parseInt($(this).attr('colspan') || $(this).find('.input-colspan').val() || 1, 10);
					neededSubCols += cs;
				}
			});

			var $existingSubCols = $subTr.find('th.th-sub-header');
			var currentCount = $existingSubCols.length;

			if (currentCount < neededSubCols) {
				for (var s = currentCount; s < neededSubCols; s++) {
					var subHeaderHtml = '<th class="th-data-col th-sub-header sub-col-idx-' + s + '" data-col-type="text">';
					subHeaderHtml += '<div class="acf-dynamic-table-header-cell">';
					subHeaderHtml += '<div class="acf-dynamic-table-header-row-main">';
					subHeaderHtml += '<input type="text" class="acf-dynamic-table-input input-label" name="' + fieldName + '[header][sub][' + s + '][label]" value="Sub ' + (s + 1) + '" placeholder="Sub-column title" />';
					subHeaderHtml += '<button type="button" class="acf-dynamic-table-btn btn-remove-sub-col" title="Remove Sub-column"><span class="dashicons dashicons-no-alt"></span></button>';
					subHeaderHtml += '</div>';
					subHeaderHtml += '<div class="acf-dynamic-table-header-row-meta">';
					subHeaderHtml += '<select class="acf-dynamic-table-type-select select-col-type" name="' + fieldName + '[header][sub][' + s + '][type]">';
					subHeaderHtml += '<option value="text">Text</option>';
					subHeaderHtml += '<option value="file">File Selector</option>';
					subHeaderHtml += '</select>';
					subHeaderHtml += '</div>';
					subHeaderHtml += '</div>';
					subHeaderHtml += '</th>';
					$subTr.append(subHeaderHtml);
				}
			} else if (currentCount > neededSubCols) {
				$existingSubCols.slice(neededSubCols).remove();
			}
		},

		onAddTopCategory: function(e) {
			e.preventDefault();
			var $table = this.$table();
			var fieldName = this.getPrefix();

			var $topTr = $table.find('thead tr.tr-header-top');
			var topCount = $topTr.find('th.th-top-header').length;

			var catHtml = '<th class="th-data-col th-top-header col-idx-' + topCount + '" data-col-type="text" colspan="1" rowspan="2">';
			catHtml += '<div class="acf-dynamic-table-header-cell">';
			catHtml += '<div class="acf-dynamic-table-header-row-main">';
			catHtml += '<input type="text" class="acf-dynamic-table-input input-label" name="' + fieldName + '[header][top][' + topCount + '][label]" value="Category ' + (topCount + 1) + '" placeholder="Category title" />';
			catHtml += '<button type="button" class="acf-dynamic-table-btn btn-remove-col" title="Remove Category"><span class="dashicons dashicons-no-alt"></span></button>';
			catHtml += '</div>';
			catHtml += '<div class="acf-dynamic-table-header-row-meta">';
			catHtml += '<select class="acf-dynamic-table-type-select select-col-type" name="' + fieldName + '[header][top][' + topCount + '][type]"><option value="text">Text</option><option value="file">File Selector</option></select>';
			catHtml += '<button type="button" class="acf-dynamic-table-btn btn-add-sub-col" title="Add Sub-column"><span class="dashicons dashicons-plus-alt2"></span> + Sub</button>';
			catHtml += '<input type="hidden" class="input-colspan" name="' + fieldName + '[header][top][' + topCount + '][colspan]" value="1" />';
			catHtml += '<input type="hidden" class="select-rowspan" name="' + fieldName + '[header][top][' + topCount + '][rowspan]" value="2" />';
			catHtml += '</div>';
			catHtml += '</div>';
			catHtml += '</th>';

			$topTr.append(catHtml);

			this.syncSubHeaders();
			this.updateState();
		},

		onSelectFile: function(e) {
			e.preventDefault();
			var $btn = $(e.currentTarget);
			var $cell = $btn.closest('.acf-dynamic-table-file-cell');
			var $input = $cell.find('input.file-url-input');

			if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
				alert('WordPress Media Library JS is not loaded.');
				return;
			}

			var frame = wp.media({
				title: 'Select File from Media Library',
				button: { text: 'Select File' },
				multiple: false
			});

			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				if (attachment && attachment.url) {
					var url = attachment.url;
					var filename = attachment.filename || url.split('/').pop() || 'File';
					$input.val(url).trigger('change');
					$cell.find('.file-name-label').text(filename);
					$cell.removeClass('empty').addClass('has-file');
					$btn.attr('title', url);
				}
			});

			frame.open();
		},

		onClearFile: function(e) {
			e.preventDefault();
			var $btn = $(e.currentTarget);
			var $cell = $btn.closest('.acf-dynamic-table-file-cell');
			$cell.find('input.file-url-input').val('').trigger('change');
			$cell.find('.file-name-label').text('Add File');
			$cell.removeClass('has-file').addClass('empty');
			$cell.find('.btn-select-file').attr('title', 'Select File');
		},

		onAddRow: function(e) {
			e.preventDefault();
			var settings = this.getSettings();
			if (settings.lockRows) return;

			var $table = this.$table();
			var $tbody = $table.find('tbody');
			var colCount = this.getColCount();
			var leafTypes = this.getLeafColumnTypes();

			var newRowIdx = $tbody.find('tr').length;
			var fieldName = this.getPrefix();

			var html = '<tr>';
			html += '<td class="td-row-actions">';
			html += '<div class="acf-dynamic-table-row-controls">';
			html += '<span class="acf-dynamic-table-row-index">' + (newRowIdx + 1) + '</span> ';
			html += '<button type="button" class="acf-dynamic-table-btn btn-move-row-up" title="Move Up"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
			html += '<button type="button" class="acf-dynamic-table-btn btn-move-row-down" title="Move Down"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
			html += '<button type="button" class="acf-dynamic-table-btn btn-remove-row" title="Remove Row"><span class="dashicons dashicons-no-alt"></span></button>';
			html += '</div>';
			html += '</td>';

			for (var c = 0; c < colCount; c++) {
				var isFileCol = (leafTypes[c] === 'file');
				html += '<td class="td-data-cell col-idx-' + c + '">';
				if (isFileCol) {
					html += '<div class="acf-dynamic-table-file-cell empty">';
					html += '<input type="hidden" class="acf-dynamic-table-input file-url-input" name="' + fieldName + '[body][' + newRowIdx + '][' + c + ']" value="" />';
					html += '<button type="button" class="button button-small btn-select-file" title="Select File"><span class="dashicons dashicons-paperclip"></span> <span class="file-name-label">Add File</span></button>';
					html += '<button type="button" class="acf-dynamic-table-btn btn-clear-file" title="Clear File"><span class="dashicons dashicons-no-alt"></span></button>';
					html += '</div>';
				} else {
					html += '<input type="text" class="acf-dynamic-table-input" name="' + fieldName + '[body][' + newRowIdx + '][' + c + ']" value="" />';
				}
				html += '</td>';
			}
			html += '</tr>';

			$tbody.append(html);
			this.updateState();
		},

		onRemoveRow: function(e) {
			e.preventDefault();
			var settings = this.getSettings();
			if (settings.lockRows) return;

			var $btn = $(e.currentTarget);
			var $tr = $btn.closest('tr');
			$tr.remove();
			this.updateState();
		},

		onMoveRowUp: function(e) {
			e.preventDefault();
			var $tr = $(e.currentTarget).closest('tr');
			var $prev = $tr.prev('tr');
			if ($prev.length) {
				$tr.insertBefore($prev);
				this.updateState();
			}
		},

		onMoveRowDown: function(e) {
			e.preventDefault();
			var $tr = $(e.currentTarget).closest('tr');
			var $next = $tr.next('tr');
			if ($next.length) {
				$tr.insertAfter($next);
				this.updateState();
			}
		},

		onAddColumn: function(e) {
			e.preventDefault();
			var settings = this.getSettings();

			var $table = this.$table();
			var fieldName = this.getPrefix();

			if (settings.headerType === 'multi') {
				this.onAddTopCategory(e);
			} else {
				var $theadTr = $table.find('thead tr');
				var colCount = $theadTr.find('th.th-data-col').length;

				if ($theadTr.length) {
					var headerHtml = '<th class="th-data-col col-idx-' + colCount + '" data-col-type="text">';
					headerHtml += '<div class="acf-dynamic-table-header-cell">';
					headerHtml += '<input type="text" class="acf-dynamic-table-input" name="' + fieldName + '[header][' + colCount + '][label]" value="Column ' + (colCount + 1) + '" />';
					headerHtml += '<div class="acf-dynamic-table-header-row-meta">';
					headerHtml += '<select class="acf-dynamic-table-type-select select-col-type" name="' + fieldName + '[header][' + colCount + '][type]"><option value="text">Text</option><option value="file">File Selector</option></select>';
					headerHtml += '<button type="button" class="acf-dynamic-table-btn btn-remove-col" title="Remove Column"><span class="dashicons dashicons-no-alt"></span></button>';
					headerHtml += '</div>';
					headerHtml += '</div>';
					headerHtml += '</th>';
					$theadTr.append(headerHtml);
				}
				this.updateState();
			}
		},

		onRemoveColumn: function(e) {
			e.preventDefault();
			var $btn = $(e.currentTarget);
			var $th = $btn.closest('th');
			$th.remove();
			this.syncSubHeaders();
			this.updateState();
		}
	});

	acf.registerFieldType(Field);
})(jQuery);
