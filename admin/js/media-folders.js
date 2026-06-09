(function($) {
	'use strict';

	// Safeguard check
	if (typeof wp === 'undefined' || !wp.media || typeof tkaMediaFolders === 'undefined') {
		return;
	}

	var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;

	// Extend the AttachmentsBrowser to inject our folders sidebar
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		initialize: function() {
			// Call the parent initialize method
			AttachmentsBrowser.prototype.initialize.apply(this, arguments);
			this.foldersSidebar = null;
		},

		ready: function() {
			// Call the parent ready method
			AttachmentsBrowser.prototype.ready.apply(this, arguments);

			// Inject our premium folders sidebar
			this.injectFoldersSidebar();
		},

		injectFoldersSidebar: function() {
			var self = this;
			var container = this.$el;

			// Add parent indicator class to AttachmentsBrowser container
			container.addClass('tka-has-folders');

			// Restore collapsed state from localStorage
			var isCollapsed = localStorage.getItem('tka_media_folders_collapsed') === 'true';
			if (isCollapsed) {
				container.addClass('tka-folders-collapsed');
			} else {
				container.removeClass('tka-folders-collapsed');
			}

			// Prevent duplicate sidebar injections in the same browser view
			if (container.find('.tka-media-folders-sidebar').length > 0) {
				var $existingSidebar = container.find('.tka-media-folders-sidebar');
				if (isCollapsed) {
					$existingSidebar.addClass('collapsed');
				} else {
					$existingSidebar.removeClass('collapsed');
				}
				this.foldersSidebar = $existingSidebar;
				return;
			}

			// Construct Sidebar HTML
			var sidebarHtml = 
				'<div class="tka-media-folders-sidebar">' +
					'<div class="tka-folders-header">' +
						'<h3><span class="dashicons dashicons-portfolio"></span><span>' + tkaMediaFolders.i18n.allFiles + '</span></h3>' +
						'<button class="tka-folders-collapse-btn" title="Collapse/Expand Folders"><span class="dashicons dashicons-menu"></span></button>' +
					'</div>' +
					'<button type="button" class="tka-folders-new-btn"><span class="dashicons dashicons-plus"></span><span>' + tkaMediaFolders.i18n.newFolder + '</span></button>' +
					'<ul class="tka-folders-tree">' +
						// Static All Files Node
						'<li class="tka-folder-node" data-id="">' +
							'<div class="tka-folder-item active" data-id="">' +
								'<span class="folder-expander"></span>' +
								'<span class="dashicons dashicons-admin-media"></span>' +
								'<span class="folder-name">' + tkaMediaFolders.i18n.allFiles + '</span>' +
							'</div>' +
						'</li>' +
						// Static Unassigned Node
						'<li class="tka-folder-node" data-id="unassigned">' +
							'<div class="tka-folder-item" data-id="unassigned">' +
								'<span class="folder-expander"></span>' +
								'<span class="dashicons dashicons-admin-media"></span>' +
								'<span class="folder-name">' + tkaMediaFolders.i18n.unassigned + '</span>' +
							'</div>' +
						'</li>' +
						// Container for dynamic folder nodes
						'<li class="tka-dynamic-folders-container"></li>' +
					'</ul>' +
				'</div>';

			var $sidebar = $(sidebarHtml);
			container.prepend($sidebar);
			this.foldersSidebar = $sidebar;

			if (isCollapsed) {
				$sidebar.addClass('collapsed');
			}

			// Bind UI interaction listeners
			this.bindSidebarEvents();

			// Fetch and display folder tree
			this.loadFolderTree();

			// Hook into uploader to auto-assign folder on upload
			this.hookUploader();
		},

		bindSidebarEvents: function() {
			var self = this;
			var $sidebar = this.foldersSidebar;
			var container = this.$el;

			// Collapse/Expand Sidebar toggle
			$sidebar.on('click', '.tka-folders-collapse-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$sidebar.toggleClass('collapsed');
				container.toggleClass('tka-folders-collapsed');
				localStorage.setItem('tka_media_folders_collapsed', $sidebar.hasClass('collapsed'));
			});

			// Select folder to filter
			$sidebar.on('click', '.tka-folder-item', function(e) {
				e.preventDefault();
				
				// Skip if click was on action buttons or expander
				if ($(e.target).closest('.folder-actions').length > 0 || $(e.target).closest('.folder-expander').length > 0) {
					return;
				}

				var folderId = $(this).attr('data-id');
				$sidebar.find('.tka-folder-item').removeClass('active');
				$(this).addClass('active');

				// Filter the Backbone grid collection
				self.filterByFolder(folderId);
			});

			// Expand/Collapse folder tree node
			$sidebar.on('click', '.folder-expander', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var $node = $(this).closest('.tka-folder-node');
				$node.toggleClass('expanded');
				
				var $sublist = $node.children('ul');
				var $icon = $(this).find('.dashicons');

				if ($node.hasClass('expanded')) {
					$sublist.slideDown(200);
					$icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
				} else {
					$sublist.slideUp(200);
					$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
				}
			});

			// Click Create New Folder
			$sidebar.on('click', '.tka-folders-new-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var parentId = $sidebar.find('.tka-folder-item.active').attr('data-id') || 0;
				if (parentId === 'unassigned') {
					parentId = 0;
				}

				var name = prompt(tkaMediaFolders.i18n.promptName);
				if (name === null) {
					return;
				}
				name = name.trim();
				if (!name) {
					alert(tkaMediaFolders.i18n.emptyName);
					return;
				}

				self.createFolder(name, parentId);
			});

			// Click Rename Folder
			$sidebar.on('click', '.folder-action-btn.rename', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var folderId = $(this).attr('data-id');
				var $nameEl = $(this).closest('.tka-folder-item').find('.folder-name');
				var oldName = $nameEl.text();

				var name = prompt(tkaMediaFolders.i18n.promptName, oldName);
				if (name === null) {
					return;
				}
				name = name.trim();
				if (!name) {
					alert(tkaMediaFolders.i18n.emptyName);
					return;
				}

				self.renameFolder(folderId, name, $nameEl);
			});

			// Click Delete Folder
			$sidebar.on('click', '.folder-action-btn.delete', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var folderId = $(this).attr('data-id');
				if (confirm(tkaMediaFolders.i18n.confirmDelete)) {
					self.deleteFolder(folderId);
				}
			});

			// HTML5 Dragover target folders
			$sidebar.on('dragover', '.tka-folder-item', function(e) {
				e.preventDefault();
				$(this).addClass('drag-over');
			});

			// HTML5 Dragleave target folders
			$sidebar.on('dragleave', '.tka-folder-item', function(e) {
				$(this).removeClass('drag-over');
			});

			// HTML5 Drop on folders
			$sidebar.on('drop', '.tka-folder-item', function(e) {
				e.preventDefault();
				$(this).removeClass('drag-over');
				
				var folderId = $(this).attr('data-id');
				var rawData = e.originalEvent.dataTransfer.getData('text/plain');
				
				try {
					if (rawData) {
						var dragData = JSON.parse(rawData);
						if (dragData && dragData.ids) {
							self.moveAttachmentsToFolder(dragData.ids, folderId);
						}
					}
				} catch (err) {
					console.error('Failed to parse drag data:', err);
				}
			});
		},

		filterByFolder: function(folderId) {
			if (this.collection) {
				// Setting the custom prop triggers requery in Backbone dynamically
				this.collection.props.set('media_folder', folderId);
			}
		},

		loadFolderTree: function() {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_get_tree',
					nonce: tkaMediaFolders.nonce
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						self.renderTree(response.data);
					}
				}
			});
		},

		renderTree: function(data) {
			var $container = this.foldersSidebar.find('.tka-dynamic-folders-container');
			$container.empty();
			
			var html = this.buildTreeHtml(data);
			$container.append(html);
		},

		buildTreeHtml: function(nodes) {
			if (!nodes || nodes.length === 0) {
				return '';
			}
			var self = this;
			var html = '<ul>';
			
			nodes.forEach(function(node) {
				var hasChildren = node.children && node.children.length > 0;
				var expanderIcon = hasChildren ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-right-alt2';
				
				html += '<li class="tka-folder-node" data-id="' + node.id + '">';
				html += '<div class="tka-folder-item" data-id="' + node.id + '">';
				
				// Expander carat
				if (hasChildren) {
					html += '<span class="folder-expander"><span class="dashicons ' + expanderIcon + '"></span></span>';
				} else {
					html += '<span class="folder-expander"></span>';
				}

				html += '<span class="dashicons dashicons-portfolio"></span>';
				html += '<span class="folder-name">' + node.name + '</span>';
				html += '<span class="folder-count">' + node.count + '</span>';
				
				// Edit/Delete hover action buttons
				html += '<div class="folder-actions">';
				html += '<button class="folder-action-btn rename" data-id="' + node.id + '" title="' + tkaMediaFolders.i18n.renameFolder + '"><span class="dashicons dashicons-edit"></span></button>';
				html += '<button class="folder-action-btn delete" data-id="' + node.id + '" title="' + tkaMediaFolders.i18n.deleteFolder + '"><span class="dashicons dashicons-trash"></span></button>';
				html += '</div>';

				html += '</div>';

				if (hasChildren) {
					html += self.buildTreeHtml(node.children);
				}

				html += '</li>';
			});
			
			html += '</ul>';
			return html;
		},

		createFolder: function(name, parentId) {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_create',
					nonce: tkaMediaFolders.nonce,
					name: name,
					parent: parentId
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						self.loadFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		renameFolder: function(id, name, $nameEl) {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_rename',
					nonce: tkaMediaFolders.nonce,
					id: id,
					name: name
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						$nameEl.text(name);
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		deleteFolder: function(id) {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_delete',
					nonce: tkaMediaFolders.nonce,
					id: id
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						self.loadFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		moveAttachmentsToFolder: function(ids, folderId) {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_move',
					nonce: tkaMediaFolders.nonce,
					attachment_ids: ids,
					folder_id: folderId
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						// Reload folder tree to refresh counts
						self.loadFolderTree();
						
						// If we are currently filtered by a folder and that folder is NOT the one we dropped onto,
						// remove the items dynamically from the current Backbone collection so they disappear.
						var activeFolder = self.foldersSidebar.find('.tka-folder-item.active').attr('data-id');
						if (activeFolder !== '' && activeFolder !== folderId) {
							ids.forEach(function(id) {
								var model = self.collection.get(id);
								if (model) {
									self.collection.remove(model);
								}
							});
						}
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		hookUploader: function() {
			var self = this;
			// Hook into wp.media.frame's uploader success event
			if (wp.media.frame && wp.media.frame.uploader) {
				wp.media.frame.uploader.on('uploader:success', function(attachment) {
					var activeFolder = self.foldersSidebar.find('.tka-folder-item.active').attr('data-id');
					if (activeFolder && activeFolder !== 'unassigned') {
						self.moveAttachmentsToFolder([attachment.id], activeFolder);
					}
				});
			}
		}
	});

	// --- Draggable Attachment Event Delegation ---
	
	// Pre-condition: Make attachment grids draggable when mouse enters
	$(document).on('mouseenter', '.attachments-browser .attachment', function() {
		if (!$(this).attr('draggable')) {
			$(this).attr('draggable', 'true');
		}
	});

	// Handle DragStart event
	$(document).on('dragstart', '.attachments-browser .attachment', function(e) {
		var draggedId = parseInt($(this).attr('data-id'), 10);
		if (!draggedId) {
			return;
		}

		// Add body class to hide global upload overlay
		$('body').addClass('tka-dragging-attachment');

		var selectedIds = [];
		var activeFrame = wp.media.frame;

		if (activeFrame && activeFrame.state()) {
			var selection = activeFrame.state().get('selection');
			if (selection && selection.length > 0) {
				selection.each(function(attachment) {
					selectedIds.push(attachment.id);
				});
			}
		}

		// If dragged item is not in selection, default to just dragging the dragged item
		if (selectedIds.indexOf(draggedId) === -1) {
			selectedIds = [draggedId];
		}

		var dragData = {
			ids: selectedIds
		};

		e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(dragData));
		e.originalEvent.dataTransfer.effectAllowed = 'move';
		
		// Style feedback during dragging
		$(this).css('opacity', '0.4');
	});

	// Clear drag styling on end
	$(document).on('dragend', '.attachments-browser .attachment', function() {
		$('body').removeClass('tka-dragging-attachment');
		$(this).css('opacity', '');
	});

})(jQuery);
