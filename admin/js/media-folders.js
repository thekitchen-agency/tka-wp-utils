(function($) {
	'use strict';

	// Safeguard check
	if (typeof wp === 'undefined' || !wp.media || typeof tkaMediaFolders === 'undefined') {
		return;
	}

	// Native XHR & Fetch Interceptor to ensure media_folder is ALWAYS present in upload payloads
	(function() {
		var origSend = XMLHttpRequest.prototype.send;
		XMLHttpRequest.prototype.send = function(body) {
			if (body && body instanceof FormData) {
				var $activeItem = $('.tka-media-folders-sidebar .tka-folder-item.active');
				var activeFolder = $activeItem.length ? ($activeItem.attr('data-id') || '') : (localStorage.getItem('tka_media_folders_active_folder') || '');
				if (activeFolder && activeFolder !== 'unassigned') {
					if (!body.has('media_folder')) {
						body.append('media_folder', activeFolder);
					}
				}
			}
			return origSend.apply(this, arguments);
		};

		if (window.fetch) {
			var origFetch = window.fetch;
			window.fetch = function(input, init) {
				if (init && init.body && init.body instanceof FormData) {
					var $activeItem = $('.tka-media-folders-sidebar .tka-folder-item.active');
					var activeFolder = $activeItem.length ? ($activeItem.attr('data-id') || '') : (localStorage.getItem('tka_media_folders_active_folder') || '');
					if (activeFolder && activeFolder !== 'unassigned') {
						if (!init.body.has('media_folder')) {
							init.body.append('media_folder', activeFolder);
						}
					}
				}
				return origFetch.apply(this, arguments);
			};
		}
	})();

	// Force wp.media.model.Query to observe wp.Uploader.queue even when custom props like media_folder are set
	if (wp.media.model && wp.media.model.Query) {
		var originalQueryInit = wp.media.model.Query.prototype.initialize;
		wp.media.model.Query.prototype.initialize = function(models, options) {
			originalQueryInit.apply(this, arguments);
			if (wp.Uploader && wp.Uploader.queue) {
				this.observe(wp.Uploader.queue);
			}
		};
	}

	// Register media_folder validator on Attachments collection filters
	if (wp.media.model && wp.media.model.Attachments && wp.media.model.Attachments.filters) {
		wp.media.model.Attachments.filters.media_folder = function(attachment) {
			var folder = this.props.get('media_folder');
			if (!folder || folder === '') {
				return true;
			}
			// Always allow transient models currently being uploaded
			if (attachment.get('uploading') || attachment.file || !attachment.id) {
				return true;
			}
			if (folder === 'unassigned') {
				var terms = attachment.get('media_folder');
				return !terms || (Array.isArray(terms) && terms.length === 0);
			}
			var folderTerms = attachment.get('media_folder');
			if (Array.isArray(folderTerms)) {
				var folderInt = parseInt(folder, 10);
				return folderTerms.indexOf(folderInt) !== -1 || folderTerms.indexOf(String(folder)) !== -1;
			}
			return true;
		};
	}

	// Global AJAX prefilter to inject media_folder parameter into all WordPress async-upload.php requests
	$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
		if (options.url && options.url.indexOf('async-upload.php') !== -1) {
			var $activeItem = $('.tka-media-folders-sidebar .tka-folder-item.active');
			var activeFolder = $activeItem.length ? ($activeItem.attr('data-id') || '') : (localStorage.getItem('tka_media_folders_active_folder') || '');
			if (activeFolder && activeFolder !== 'unassigned') {
				if (options.data instanceof FormData) {
					if (!options.data.has('media_folder')) {
						options.data.append('media_folder', activeFolder);
					}
				} else if (typeof options.data === 'string') {
					if (options.data.indexOf('media_folder=') === -1) {
						options.data += (options.data ? '&' : '') + 'media_folder=' + encodeURIComponent(activeFolder);
					}
				}
			}
		}
	});

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
					'<div class="tka-folder-upload-notice-container"></div>' +
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

		getActiveFolderId: function() {
			var $activeItem = $('.tka-media-folders-sidebar .tka-folder-item.active');
			if ($activeItem.length) {
				var id = $activeItem.attr('data-id');
				if (typeof id !== 'undefined' && id !== null) {
					return String(id);
				}
			}
			return localStorage.getItem('tka_media_folders_active_folder') || '';
		},

		getActiveFolderName: function() {
			var $activeItem = $('.tka-media-folders-sidebar .tka-folder-item.active');
			return $activeItem.length ? $activeItem.find('.folder-name').text() : '';
		},

		selectFolder: function(folderId) {
			if (!this.foldersSidebar) {
				return;
			}
			localStorage.setItem('tka_media_folders_active_folder', folderId || '');
			var $targetItem = this.foldersSidebar.find('.tka-folder-item[data-id="' + folderId + '"]');
			if ($targetItem.length) {
				this.foldersSidebar.find('.tka-folder-item').removeClass('active');
				$targetItem.addClass('active');
				$targetItem.parents('.tka-folder-node').addClass('expanded');
				$targetItem.parents('ul').show();
			}
			this.filterByFolder(folderId);
		},

		refreshCurrentFolder: function() {
			var self = this;
			self.loadFolderTree();
			var activeFolder = self.getActiveFolderId();
			if (self.collection) {
				self.collection.props.set('media_folder', activeFolder, { silent: true });
				self.collection._hasMore = true;
				delete self.collection._more;
				if (self.collection.mirroring) {
					self.collection.mirroring._hasMore = true;
					delete self.collection.mirroring._more;
					self.collection.mirroring.fetch({ reset: true });
				} else if (typeof self.collection.fetch === 'function') {
					self.collection.fetch({ reset: true });
				}
			}
		},

		showUploadNotice: function(msg) {
			if (!this.foldersSidebar) {
				return;
			}
			var $container = this.foldersSidebar.find('.tka-folder-upload-notice-container');
			var html = '<div class="tka-folder-upload-notice"><span class="spinner is-active"></span><span>' + msg + '</span></div>';
			$container.html(html).stop(true, true).fadeIn(150);
		},

		showUploadSuccessNotice: function(msg) {
			if (!this.foldersSidebar) {
				return;
			}
			var $container = this.foldersSidebar.find('.tka-folder-upload-notice-container');
			var html = '<div class="tka-folder-upload-notice success"><span class="dashicons dashicons-yes-alt"></span><span>' + msg + '</span></div>';
			$container.html(html).stop(true, true).fadeIn(150);
			setTimeout(function() {
				$container.fadeOut(300, function() {
					$container.empty();
				});
			}, 3000);
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
				var files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
				
				// Desktop files dropped directly onto folder item
				if (files && files.length > 0) {
					self.selectFolder(folderId);
					if (wp.media.uploader && wp.media.uploader.uploader && wp.media.uploader.uploader.uploader) {
						var plup = wp.media.uploader.uploader.uploader;
						plup.settings.multipart_params = plup.settings.multipart_params || {};
						plup.settings.multipart_params.media_folder = folderId;
						plup.addFile(Array.from(files));
					}
					return;
				}

				// Internal media items drag and drop
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
				this.collection._hasMore = true;
				delete this.collection._more;
				if (this.collection.mirroring) {
					this.collection.mirroring._hasMore = true;
					delete this.collection.mirroring._more;
					this.collection.mirroring.fetch({ reset: true });
				} else if (typeof this.collection.fetch === 'function') {
					this.collection.fetch({ reset: true });
				}
				if (wp.Uploader && wp.Uploader.queue) {
					this.collection.observe(wp.Uploader.queue);
				}
			}
		},

		loadFolderTree: function(callback) {
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
						var activeId = self.getActiveFolderId() || localStorage.getItem('tka_media_folders_active_folder') || '';
						self.renderTree(response.data);
						if (activeId !== '') {
							var $activeItem = self.foldersSidebar.find('.tka-folder-item[data-id="' + activeId + '"]');
							if ($activeItem.length) {
								self.foldersSidebar.find('.tka-folder-item').removeClass('active');
								$activeItem.addClass('active');
								$activeItem.parents('.tka-folder-node').addClass('expanded');
								$activeItem.parents('ul').show();
							}
						}
						if (typeof callback === 'function') {
							callback(response.data);
						}
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
					if (response.success && response.data && response.data.id) {
						var newFolderId = String(response.data.id);
						self.loadFolderTree(function() {
							self.selectFolder(newFolderId);
						});
					} else {
						alert(response.data ? response.data.message : 'Failed to create folder.');
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
						var activeFolder = self.getActiveFolderId();
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

			var attachPlupload = function(plup) {
				if (!plup || plup._tkaHooked) {
					return;
				}
				plup._tkaHooked = true;

				plup.bind('BeforeUpload', function(up, file) {
					var activeFolder = self.getActiveFolderId();
					if (activeFolder && activeFolder !== 'unassigned') {
						up.settings.multipart_params = up.settings.multipart_params || {};
						up.settings.multipart_params.media_folder = activeFolder;
					} else if (up.settings.multipart_params) {
						delete up.settings.multipart_params.media_folder;
					}

					var folderName = self.getActiveFolderName();
					var targetText = folderName ? ' to "' + folderName + '"' : '';
					self.showUploadNotice('Uploading file(s)' + targetText + '...');
				});

				plup.bind('FileUploaded', function(up, file, response) {
					var activeFolder = self.getActiveFolderId();
					if (activeFolder && activeFolder !== 'unassigned') {
						try {
							var res = typeof response.response === 'string' ? JSON.parse(response.response) : response.response;
							if (res && res.success && res.data && res.data.id) {
								self.moveAttachmentsToFolder([res.data.id], activeFolder);
							}
						} catch (e) {
							console.error('Error parsing upload response:', e);
						}
					}
				});

				plup.bind('UploadProgress', function(up, file) {
					var folderName = self.getActiveFolderName();
					var targetText = folderName ? ' to "' + folderName + '"' : '';
					self.showUploadNotice('Uploading ' + up.total.percent + '%' + targetText);
				});

				plup.bind('UploadComplete', function(up, files) {
					self.showUploadSuccessNotice('✓ Upload completed');
					self.refreshCurrentFolder();
				});
			};

			// Check wp.media.uploader
			if (wp.media.uploader && wp.media.uploader.uploader && wp.media.uploader.uploader.uploader) {
				attachPlupload(wp.media.uploader.uploader.uploader);
			} else if (wp.Uploader && wp.Uploader.prototype) {
				var oldInit = wp.Uploader.prototype.init;
				wp.Uploader.prototype.init = function() {
					if (oldInit) {
						oldInit.apply(this, arguments);
					}
					if (this.uploader) {
						attachPlupload(this.uploader);
					}
				};
			}

			// Backbone central wp.Uploader.queue listeners
			if (wp.Uploader && wp.Uploader.queue) {
				wp.Uploader.queue.on('add change:percent', function() {
					var count = wp.Uploader.queue.length;
					if (count > 0) {
						var folderName = self.getActiveFolderName();
						var targetText = folderName ? ' to "' + folderName + '"' : '';
						var totalPercent = 0;
						wp.Uploader.queue.each(function(m) {
							totalPercent += (m.get('percent') || 0);
						});
						var avgPercent = count > 0 ? Math.round(totalPercent / count) : 0;
						self.showUploadNotice('Uploading ' + count + ' file(s)' + targetText + ' (' + avgPercent + '%)');
					}
				});

				wp.Uploader.queue.on('change:attachment', function(model) {
					var activeFolder = self.getActiveFolderId();
					if (activeFolder && activeFolder !== 'unassigned') {
						var att = model ? model.get('attachment') : null;
						if (att && att.id && !att._tkaMoved) {
							att._tkaMoved = true;
							if (typeof att.set === 'function') {
								att.set('media_folder', [parseInt(activeFolder, 10)]);
							}
							self.moveAttachmentsToFolder([att.id], activeFolder);
						}
					}
				});

				wp.Uploader.queue.on('reset remove', function() {
					if (wp.Uploader.queue.length === 0) {
						self.showUploadSuccessNotice('✓ Upload completed');
						self.refreshCurrentFolder();
					}
				});
			}

			// Backbone framework uploader listeners
			if (wp.media.frame && wp.media.frame.uploader) {
				wp.media.frame.uploader.on('uploader:start', function() {
					var folderName = self.getActiveFolderName();
					var targetText = folderName ? ' to "' + folderName + '"' : '';
					self.showUploadNotice('Uploading file(s)' + targetText + '...');
				});

				wp.media.frame.uploader.on('uploader:success', function(attachment) {
					var activeFolder = self.getActiveFolderId();
					if (activeFolder && activeFolder !== 'unassigned') {
						if (attachment && typeof attachment.set === 'function') {
							attachment.set('media_folder', [parseInt(activeFolder, 10)]);
						}
						self.moveAttachmentsToFolder([attachment.id], activeFolder);
					}
				});

				wp.media.frame.uploader.on('uploader:end', function() {
					self.showUploadSuccessNotice('✓ Upload completed');
					self.refreshCurrentFolder();
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
