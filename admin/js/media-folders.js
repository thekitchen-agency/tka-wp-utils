(function ($) {
	'use strict';

	// Custom HTML confirm modal to replace browser native confirm()
	function showCustomConfirm(message, onConfirm) {
		$('.tka-confirm-modal-overlay').remove();

		var heading = 'Delete Permanently';
		var bodyText = message || 'Are you sure you want to permanently delete these items?';
		var confirmLabel = 'Delete Permanently';
		var cancelLabel = 'Cancel';

		if (typeof tkaMediaFolders !== 'undefined' && tkaMediaFolders.locale && tkaMediaFolders.locale.indexOf('de') === 0) {
			heading = 'Dauerhaft löschen';
			confirmLabel = 'Dauerhaft löschen';
			cancelLabel = 'Abbrechen';
		}

		var modalHtml = 
			'<div class="tka-confirm-modal-overlay">' +
				'<div class="tka-confirm-modal-box">' +
					'<div class="tka-confirm-modal-header">' +
						'<span class="dashicons dashicons-warning" style="color: #ef4444; font-size: 24px; width: 24px; height: 24px;"></span>' +
						'<h3 style="margin:0; font-size: 18px; font-weight: 600; color: #0f172a;">' + heading + '</h3>' +
					'</div>' +
					'<div class="tka-confirm-modal-body" style="padding: 10px 24px 24px;">' +
						'<p style="margin:0; font-size:14px; line-height:1.5; color:#475569;">' + bodyText + '</p>' +
					'</div>' +
					'<div class="tka-confirm-modal-footer">' +
						'<button class="tka-confirm-modal-btn cancel">' + cancelLabel + '</button>' +
						'<button class="tka-confirm-modal-btn confirm">' + confirmLabel + '</button>' +
					'</div>' +
				'</div>' +
			'</div>';

		var $modal = $(modalHtml);
		$('body').append($modal);

		setTimeout(function () {
			$modal.addClass('active');
		}, 10);

		$modal.find('.tka-confirm-modal-btn.cancel').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$modal.removeClass('active');
			setTimeout(function () {
				$modal.remove();
			}, 200);
		});

		$modal.find('.tka-confirm-modal-btn.confirm').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$modal.removeClass('active');
			setTimeout(function () {
				$modal.remove();
				if (onConfirm) {
					onConfirm();
				}
			}, 200);
		});
	}

	// Safeguard check
	if (typeof wp === 'undefined' || !wp.media || typeof tkaMediaFolders === 'undefined') {
		return;
	}

	// Globally hook wp.Uploader to ensure any uploader instances automatically pass the active folder
	if (wp.Uploader) {
		var origUploaderInit = wp.Uploader.prototype.init;
		wp.Uploader.prototype.init = function () {
			var res = origUploaderInit.apply(this, arguments);
			if (this.uploader) {
				this.uploader.bind('BeforeUpload', function (up, file) {
					var $active = $('.tka-media-folders-sidebar .tka-folder-item.active');
					var activeFolder = $active.length ? $active.attr('data-id') : null;
					var targetFolder = file._tkaTargetFolder || activeFolder;

					if (targetFolder && targetFolder !== 'unassigned' && targetFolder !== '') {
						up.settings.multipart_params = up.settings.multipart_params || {};
						up.settings.multipart_params.tka_folder_id = targetFolder;
						up.settings.multipart_params.media_folder = targetFolder;
					} else if (up.settings.multipart_params) {
						delete up.settings.multipart_params.tka_folder_id;
						delete up.settings.multipart_params.media_folder;
					}
				});
			}
			return res;
		};
	}

	var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;

	// Extend the AttachmentsBrowser to inject our folders sidebar
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		initialize: function () {
			// Call the parent initialize method
			AttachmentsBrowser.prototype.initialize.apply(this, arguments);
			this.foldersSidebar = null;

			// Listen to custom refresh event or collection changes to reload folder tree counts
			window.addEventListener('tka_refresh_folders', _.debounce(_.bind(this.loadFolderTree, this), 100));

			if (this.collection) {
				this.listenTo(this.collection, 'remove destroy', _.debounce(_.bind(this.loadFolderTree, this), 100));
			}
		},

		remove: function () {
			$('body').off('.tka_upload');
			return AttachmentsBrowser.prototype.remove.apply(this, arguments);
		},

		ready: function () {
			// Call the parent ready method
			AttachmentsBrowser.prototype.ready.apply(this, arguments);

			// Inject our premium folders sidebar
			this.injectFoldersSidebar();
		},

		injectFoldersSidebar: function () {
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

			// Uploader integration is bound via Plupload event hooks.
		},

		bindSidebarEvents: function () {
			var self = this;
			var $sidebar = this.foldersSidebar;
			var container = this.$el;

			// Collapse/Expand Sidebar toggle
			$sidebar.on('click', '.tka-folders-collapse-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$sidebar.toggleClass('collapsed');
				container.toggleClass('tka-folders-collapsed');
				localStorage.setItem('tka_media_folders_collapsed', $sidebar.hasClass('collapsed'));
			});

			// Select folder to filter
			$sidebar.on('click', '.tka-folder-item', function (e) {
				e.preventDefault();

				// Skip if click was on action buttons, confirm delete, or expander
				if ($(e.target).closest('.folder-actions').length > 0 || $(e.target).closest('.folder-delete-confirm').length > 0 || $(e.target).closest('.folder-expander').length > 0) {
					return;
				}

				var folderId = $(this).attr('data-id');
				$sidebar.find('.tka-folder-item').removeClass('active');
				$(this).addClass('active');

				// Filter the Backbone grid collection
				self.filterByFolder(folderId);
			});

			// Expand/Collapse folder tree node
			$sidebar.on('click', '.folder-expander', function (e) {
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

			// Prevent mousedown/mouseup from bubbling up and triggering Backbone focus changes on folder action buttons
			$sidebar.on('mousedown mouseup', '.tka-folders-new-btn, .folder-action-btn, .confirm-delete-yes, .confirm-delete-no', function (e) {
				e.stopPropagation();
			});

			// Click Create New Folder
			$sidebar.on('click', '.tka-folders-new-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var parentId = $sidebar.find('.tka-folder-item.active').attr('data-id') || 0;
				if (parentId === 'unassigned') {
					parentId = 0;
				}

				self.showInlineCreateInput(parentId);
			});

			// Click Rename Folder
			$sidebar.on('click', '.folder-action-btn.rename', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var folderId = $(this).attr('data-id');
				var $nameEl = $(this).closest('.tka-folder-item').find('.folder-name');

				self.showInlineRenameInput(folderId, $nameEl);
			});

			// Click Delete Folder (shows inline confirmation)
			$sidebar.on('click', '.folder-action-btn.delete', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var $item = $(this).closest('.tka-folder-item');
				var folderId = $(this).attr('data-id');

				// Hide original actions and count
				$item.find('.folder-actions').hide();
				$item.find('.folder-count').hide();

				// Construct confirmation elements
				var $confirmContainer = $('<div class="folder-delete-confirm" style="display: flex; gap: 4px; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: inherit; padding-left: 5px; border-radius: var(--folders-radius);"></div>');

				var $yesBtn = $('<button class="folder-action-btn confirm-delete-yes" title="Confirm Delete" style="color: hsl(0, 84%, 60%);"><span class="dashicons dashicons-yes"></span></button>');
				var $noBtn = $('<button class="folder-action-btn confirm-delete-no" title="Cancel" style="color: var(--folders-text-muted);"><span class="dashicons dashicons-no"></span></button>');

				// Bind events directly to YES button
				$yesBtn.on('click mousedown mouseup', function (evt) {
					evt.stopPropagation();
					if (evt.type === 'click') {
						evt.preventDefault();
						self.deleteFolder(folderId);
					}
				});

				// Bind events directly to NO button
				$noBtn.on('click mousedown mouseup', function (evt) {
					evt.stopPropagation();
					if (evt.type === 'click') {
						evt.preventDefault();
						$confirmContainer.remove();
						$item.find('.folder-count').show();
					}
				});

				$confirmContainer.append($yesBtn).append($noBtn);
				$item.append($confirmContainer);
			});

			// HTML5 Dragover target folders
			$sidebar.on('dragover', '.tka-folder-item', function (e) {
				e.preventDefault();
				$(this).addClass('drag-over');
			});

			// HTML5 Dragleave target folders
			$sidebar.on('dragleave', '.tka-folder-item', function (e) {
				$(this).removeClass('drag-over');
			});

			// HTML5 Drop on folders
			$sidebar.on('drop', '.tka-folder-item', function (e) {
				e.preventDefault();
				$(this).removeClass('drag-over');

				var folderId = $(this).attr('data-id');

				// Check if files are dropped
				var files = e.originalEvent.dataTransfer.files;
				if (files && files.length > 0) {
					e.stopPropagation();
					var plObj = self.getPluploadInstance();
					if (plObj) {
						Array.prototype.forEach.call(files, function (file) {
							var plFile = plObj.addFile(file);
							if (plFile) {
								plFile._tkaTargetFolder = folderId;
							}
						});
						if (plObj.state !== plupload.STARTED) {
							plObj.start();
						}
					}
					return;
				}

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

		filterByFolder: function (folderId) {
			if (this.collection) {
				// Setting the custom prop triggers requery in Backbone dynamically
				this.collection.props.set('media_folder', folderId);
			}
		},

		loadFolderTree: function () {
			var self = this;
			$.ajax({
				url: tkaMediaFolders.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tka_media_folders_get_tree',
					nonce: tkaMediaFolders.nonce
				},
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						self.renderTree(response.data);
					}
				}
			});
		},

		renderTree: function (data) {
			var $container = this.foldersSidebar.find('.tka-dynamic-folders-container');
			$container.empty();

			var html = this.buildTreeHtml(data);
			$container.append(html);
		},

		buildTreeHtml: function (nodes) {
			if (!nodes || nodes.length === 0) {
				return '';
			}
			var self = this;
			var html = '<ul>';

			nodes.forEach(function (node) {
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

		createFolder: function (name, parentId) {
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
				success: function (response) {
					if (response.success) {
						self.loadFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		renameFolder: function (id, name, $nameEl) {
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
				success: function (response) {
					if (response.success) {
						$nameEl.text(name);
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		deleteFolder: function (id) {
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
				success: function (response) {
					if (response.success) {
						self.loadFolderTree();
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		moveAttachmentsToFolder: function (ids, folderId) {
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
				success: function (response) {
					if (response.success) {
						// Reload folder tree to refresh counts
						self.loadFolderTree();

						// If we are currently filtered by a folder and that folder is NOT the one we dropped onto,
						// remove the items dynamically from the current Backbone collection so they disappear.
						var activeFolder = self.foldersSidebar.find('.tka-folder-item.active').attr('data-id');
						if (activeFolder !== '' && activeFolder !== folderId) {
							ids.forEach(function (id) {
								var model = self.collection.get(id) || self.collection.get(parseInt(id, 10));
								if (model) {
									self.collection.remove(model);
								}
							});

							// Also sync selection in frame controller
							var activeFrame = (self.controller && self.controller.state) ? self.controller : wp.media.frame;
							if (activeFrame && activeFrame.state && activeFrame.state()) {
								var selection = activeFrame.state().get('selection');
								if (selection) {
									ids.forEach(function (id) {
										var selModel = selection.get(id) || selection.get(parseInt(id, 10));
										if (selModel) {
											selection.remove(selModel);
										}
									});
								}
							}
						}
					} else {
						alert(response.data.message);
					}
				}
			});
		},

		getPluploadInstance: function () {
			var uploaderObj = null;

			if (this.controller && this.controller.uploader) {
				uploaderObj = this.controller.uploader;
			} else if (wp.media.frame && wp.media.frame.uploader) {
				uploaderObj = wp.media.frame.uploader;
			}

			if (uploaderObj) {
				if (uploaderObj.uploader && uploaderObj.uploader.uploader) {
					return uploaderObj.uploader.uploader;
				} else if (uploaderObj.uploader) {
					return uploaderObj.uploader;
				}
			}
			return null;
		},

		hookUploader: function () {
			var self = this;
			var plObj = this.getPluploadInstance();

			if (plObj) {
				// Prevent registering Plupload events multiple times on the same uploader object
				if (!plObj._tkaProgressHooked) {
					plObj._tkaProgressHooked = true;

					plObj.bind('BeforeUpload', function (up, file) {
						var activeFolder = self.foldersSidebar ? self.foldersSidebar.find('.tka-folder-item.active').attr('data-id') : null;
						var targetFolder = file._tkaTargetFolder || activeFolder;

						if (targetFolder && targetFolder !== 'unassigned' && targetFolder !== '') {
							up.settings.multipart_params = up.settings.multipart_params || {};
							up.settings.multipart_params.tka_folder_id = targetFolder;
							up.settings.multipart_params.media_folder = targetFolder;
						} else if (up.settings.multipart_params) {
							delete up.settings.multipart_params.tka_folder_id;
							delete up.settings.multipart_params.media_folder;
						}
					});

					plObj.bind('FilesAdded', function (up, files) {
						var activeFolder = self.foldersSidebar ? self.foldersSidebar.find('.tka-folder-item.active').attr('data-id') : null;
						if (activeFolder && activeFolder !== 'unassigned') {
							// Show progress modal
							self.showUploadProgressModal(files.length);
						}
					});

					plObj.bind('UploadProgress', function (up, file) {
						var activeFolder = self.foldersSidebar ? self.foldersSidebar.find('.tka-folder-item.active').attr('data-id') : null;
						if (activeFolder && activeFolder !== 'unassigned') {
							// Calculate overall progress across files
							var uploadedCount = up.files.length - up.total.queued;
							var totalFiles = up.files.length;
							var statusMsg = 'Uploading file ' + Math.min(uploadedCount + 1, totalFiles) + ' of ' + totalFiles + ' (' + file.name + ')...';
							self.updateUploadProgress(up.total.percent, statusMsg);
						}
					});

					plObj.bind('FileUploaded', function (up, file, info) {
						try {
							var response = (typeof info.response === 'string') ? JSON.parse(info.response) : info.response;
							var attachmentData = null;
							if (response) {
								if (response.data && response.data.id) {
									attachmentData = response.data;
								} else if (response.id) {
									attachmentData = response;
								} else if (response.data) {
									attachmentData = response.data;
								}
							}

							if (attachmentData) {
								var activeFolder = self.foldersSidebar ? self.foldersSidebar.find('.tka-folder-item.active').attr('data-id') : null;
								var targetFolder = file._tkaTargetFolder || activeFolder;

								// Fallback: in case server-side did not assign folder, trigger move AJAX
								if (targetFolder && targetFolder !== 'unassigned' && targetFolder !== '' && (!attachmentData.media_folder || String(attachmentData.media_folder) !== String(targetFolder))) {
									self.moveAttachmentsToFolder([attachmentData.id], targetFolder);
								}

								// Immediately add model to the active collection so it displays in the grid
								if (self.collection) {
									var existingModel = self.collection.get(attachmentData.id);
									if (!existingModel) {
										var shouldAdd = (activeFolder === '') || (targetFolder && String(activeFolder) === String(targetFolder));
										if (shouldAdd) {
											var attachmentModel = wp.media.model.Attachment.create(attachmentData);
											self.collection.add(attachmentModel, { at: 0 });
										}
									}
								}

								// Auto-select the attachment in select/gallery frame controllers (e.g. ACF)
								if (self.controller && self.controller.state) {
									var state = self.controller.state();
									if (state) {
										var selection = state.get('selection');
										if (selection) {
											var selModel = wp.media.model.Attachment.create(attachmentData);
											selection.add(selModel);
										}
									}
								}
							}
						} catch (err) {
							console.error('Failed to parse upload response or assign folder:', err);
						}
					});

					plObj.bind('UploadComplete', function (up, files) {
						// Hide progress modal after a delay and refresh folder tree counts
						setTimeout(function () {
							self.hideUploadProgressModal();
							self.loadFolderTree();
						}, 500);
					});

					plObj.bind('Error', function (up, err) {
						console.error('Plupload error:', err);
						alert('Upload error: ' + err.message);
						self.hideUploadProgressModal();
					});
				}
			}
		},

		showInlineCreateInput: function (parentId) {
			var self = this;
			var $sidebar = this.foldersSidebar;
			var $targetUl;

			if (!parentId || parentId === 'unassigned') {
				var $container = $sidebar.find('.tka-dynamic-folders-container');
				$targetUl = $container.children('ul');
				if ($targetUl.length === 0) {
					$targetUl = $('<ul></ul>');
					$container.append($targetUl);
				}
			} else {
				var $parentNode = $sidebar.find('.tka-folder-node[data-id="' + parentId + '"]');
				if ($parentNode.length === 0) {
					return;
				}
				$parentNode.addClass('expanded');
				var $expander = $parentNode.find('.folder-expander');
				$expander.find('.dashicons').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');

				$targetUl = $parentNode.children('ul');
				if ($targetUl.length === 0) {
					$targetUl = $('<ul></ul>');
					$parentNode.append($targetUl);
				}
				$targetUl.slideDown(200);
			}

			if ($targetUl.find('.temp-new-node').length > 0) {
				$targetUl.find('.temp-new-node input').focus();
				return;
			}

			var tempNodeHtml =
				'<li class="tka-folder-node temp-new-node">' +
				'<div class="tka-folder-item">' +
				'<span class="folder-expander"></span>' +
				'<span class="dashicons dashicons-portfolio"></span>' +
				'<input type="text" class="tka-inline-input" placeholder="New Folder..." />' +
				'</div>' +
				'</li>';

			var $tempNode = $(tempNodeHtml);
			$targetUl.append($tempNode);

			var $input = $tempNode.find('input');
			$input.focus();

			var isSaving = false;
			var saveCreate = function() {
				if (isSaving) return;
				isSaving = true;
				var name = $input.val().trim();
				if (name) {
					self.createFolder(name, parentId);
				}
				$tempNode.remove();
			};

			$input.on('keydown', function (e) {
				if (e.which === 13) {
					e.preventDefault();
					saveCreate();
				} else if (e.which === 27) {
					e.preventDefault();
					$tempNode.remove();
				}
			});

			$input.on('blur', function () {
				setTimeout(saveCreate, 150);
			});
		},

		showInlineRenameInput: function (folderId, $nameEl) {
			var self = this;
			var oldName = $nameEl.text();

			var $input = $('<input type="text" class="tka-inline-input rename" value="" />');
			$input.val(oldName);
			$nameEl.hide().after($input);
			$input.focus().select();

			var isSaving = false;
			var saveRename = function() {
				if (isSaving) return;
				isSaving = true;
				var newName = $input.val().trim();
				if (newName && newName !== oldName) {
					self.renameFolder(folderId, newName, $nameEl);
				} else {
					$nameEl.show();
				}
				$input.remove();
			};

			$input.on('keydown', function (e) {
				if (e.which === 13) {
					e.preventDefault();
					saveRename();
				} else if (e.which === 27) {
					e.preventDefault();
					$nameEl.show();
					$input.remove();
				}
			});

			$input.on('blur', function () {
				setTimeout(saveRename, 150);
			});
		},

		showUploadProgressModal: function (totalFiles) {
			var modalHtml =
				'<div class="tka-upload-progress-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 99999; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">' +
				'<div class="tka-progress-card" style="background: #fff; width: 400px; padding: 30px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); text-align: center;">' +
				'<h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600; color: #0f172a;">Uploading Files</h3>' +
				'<p class="tka-progress-status" style="margin: 0 0 20px 0; font-size: 14px; color: #64748b;">Preparing...</p>' +
				'<div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">' +
				'<div class="tka-progress-bar" style="background: var(--folders-primary, #3b82f6); width: 0%; height: 100%; transition: width 0.2s ease;"></div>' +
				'</div>' +
				'<span class="tka-progress-percent" style="font-size: 14px; font-weight: 600; color: #0f172a;">0%</span>' +
				'</div>' +
				'</div>';

			var $modal = $(modalHtml);
			$('body').append($modal);
			this.$progressModal = $modal;
		},

		updateUploadProgress: function (percent, statusText) {
			if (this.$progressModal) {
				this.$progressModal.find('.tka-progress-bar').css('width', percent + '%');
				this.$progressModal.find('.tka-progress-percent').text(percent + '%');
				if (statusText) {
					this.$progressModal.find('.tka-progress-status').text(statusText);
				}
			}
		},

		hideUploadProgressModal: function () {
			if (this.$progressModal) {
				this.$progressModal.remove();
				this.$progressModal = null;
			}
		},


	});

	// --- Draggable Attachment Event Delegation ---

	// Pre-condition: Make attachment items draggable
	$(document).on('mouseenter', '.attachments-browser .attachment', function () {
		if (!$(this).attr('draggable')) {
			$(this).attr('draggable', 'true');
		}
	});

	// Handle DragStart event
	$(document).on('dragstart', '.attachments-browser .attachment', function (e) {
		var draggedId = parseInt($(this).attr('data-id'), 10);
		if (!draggedId) {
			return;
		}

		// Add body class to hide global upload overlay
		$('body').addClass('tka-dragging-attachment');

		var selectedIds = [];
		var activeFrame = wp.media.frame;

		if (activeFrame && activeFrame.state && activeFrame.state()) {
			var selection = activeFrame.state().get('selection');
			if (selection && selection.length > 0) {
				selection.each(function (attachment) {
					var id = attachment.id || (attachment.get && attachment.get('id'));
					if (id && selectedIds.indexOf(id) === -1) {
						selectedIds.push(id);
					}
				});
			}
		}

		// Also check DOM for any selected attachments
		var $browser = $(this).closest('.attachments-browser');
		$browser.find('.attachments .attachment.selected, .attachments .attachment.details').each(function () {
			var id = parseInt($(this).attr('data-id'), 10);
			if (id && selectedIds.indexOf(id) === -1) {
				selectedIds.push(id);
			}
		});

		// If dragged item is not part of the multi-selection, default to just dragging the dragged item
		if (selectedIds.indexOf(draggedId) === -1) {
			selectedIds = [draggedId];
		}

		var dragData = {
			ids: selectedIds
		};

		e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(dragData));
		e.originalEvent.dataTransfer.effectAllowed = 'move';

		// Style feedback during dragging for all dragged items
		if (selectedIds.length > 1) {
			selectedIds.forEach(function (id) {
				$('.attachments-browser .attachment[data-id="' + id + '"]').css('opacity', '0.4');
			});
		} else {
			$(this).css('opacity', '0.4');
		}
	});

	// Clear drag styling on end
	$(document).on('dragend', '.attachments-browser .attachment', function () {
		$('body').removeClass('tka-dragging-attachment');
		$('.attachments-browser .attachment').css('opacity', '');
	});

	// Prevent mousedown/mouseup on delete buttons in the capture phase to stop focus changes
	document.addEventListener('mousedown', function (e) {
		if (e.target && e.target.closest && e.target.closest('.delete-selected-button, .delete-selected-permanently-button, .button-link-delete, .delete-attachment')) {
			e.stopPropagation();
			e.preventDefault();
		}
	}, true);

	document.addEventListener('mouseup', function (e) {
		if (e.target && e.target.closest && e.target.closest('.delete-selected-button, .delete-selected-permanently-button, .button-link-delete, .delete-attachment')) {
			e.stopPropagation();
			e.preventDefault();
		}
	}, true);

	// Intercept click on delete buttons in the capture phase to replace window.confirm
	document.addEventListener('click', function (e) {
		var target = e.target && e.target.closest && e.target.closest('.delete-selected-button, .delete-selected-permanently-button, .button-link-delete, .delete-attachment');
		if (target) {
			e.stopPropagation();
			e.preventDefault();

			var l10n = (wp.media.view && wp.media.view.l10n) ? wp.media.view.l10n : {};
			var mediaTrash = (wp.media.view.settings && wp.media.view.settings.mediaTrash);

			// Check if single attachment delete (modal or sidebar details view)
			var isSingleDelete = target.classList.contains('delete-attachment') || target.classList.contains('button-link-delete');

			if (isSingleDelete) {
				var activeFrame = wp.media.frames.edit || wp.media.frames.browse || wp.media.frame;
				var model = null;
				if (activeFrame) {
					if (activeFrame.model) {
						model = activeFrame.model;
					} else if (activeFrame.state && activeFrame.state()) {
						var selection = activeFrame.state().get('selection');
						if (selection && selection.length > 0) {
							model = selection.single ? selection.single() : selection.at(0);
						}
					}
				}

				if (model) {
					var message = l10n.warnDelete || 'Are you sure you want to permanently delete this item?';
					if (mediaTrash && model.get('status') !== 'trash') {
						message = l10n.warnTrash || 'Are you sure you want to move this item to the trash?';
					}

					showCustomConfirm(message, function () {
						var id = model.id;
						var isTrash = mediaTrash && model.get('status') !== 'trash';
						var p;

						if (isTrash) {
							model.set('status', 'trash');
							p = model.save();
						} else {
							p = model.destroy({ wait: true });
						}

						$.when(p).always(function () {
							if (activeFrame && activeFrame.state && activeFrame.state()) {
								var library = activeFrame.state().get('library');
								if (library) {
									if (typeof library._requery === 'function') {
										library._requery(true);
									}
									var m = library.get(id);
									if (m) {
										library.remove(m);
									}
								}
							}
							if (wp.media.frames.edit) {
								wp.media.frames.edit.close();
							}
							window.dispatchEvent(new CustomEvent('tka_refresh_folders'));
						});
					});
				}
			} else {
				// Bulk delete
				var activeFrame = wp.media.frames.browse || wp.media.frame;
				if (activeFrame && activeFrame.state && activeFrame.state()) {
					var selection = activeFrame.state().get('selection');
					var library = activeFrame.state().get('library');

					if (selection && selection.length > 0) {
						var models = selection.toArray();
						var isTrash = mediaTrash && models[0] && models[0].get('status') !== 'trash';
						var message = l10n.warnBulkDelete || 'Are you sure you want to permanently delete these items?';
						if (isTrash) {
							message = l10n.warnBulkTrash || 'Are you sure you want to move these items to the trash?';
						}

						showCustomConfirm(message, function () {
							var changed = [];
							var removed = [];

							models.forEach(function (m) {
								if (isTrash) {
									m.set('status', 'trash');
									changed.push(m.save());
									removed.push(m);
								} else {
									changed.push(m.destroy({ wait: true }));
									removed.push(m);
								}
							});

							$.when.apply($, changed).always(function () {
								selection.remove(removed);
								if (library) {
									if (typeof library._requery === 'function') {
										library._requery(true);
									}
									removed.forEach(function (m) {
										var libModel = library.get(m.id);
										if (libModel) {
											library.remove(libModel);
										}
									});
								}
								activeFrame.trigger('selection:action:done');
								if (activeFrame.deactivateMode) {
									activeFrame.deactivateMode('select').activateMode('edit');
								}
								window.dispatchEvent(new CustomEvent('tka_refresh_folders'));
							});
						});
					}
				}
			}
		}
	}, true);

})(jQuery);
