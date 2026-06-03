/**
 * TKA WP Utils - ACF Layout Modal Selection JavaScript Engine
 */

(function($) {
	'use strict';

	// Enable layout modal active body class immediately on script load
	$(function() {
		console.log('TKA WP Utils: Script executed immediately on script load.');
		$('body').addClass('tka-layout-modal-active');
	});

	// Dynamically guess Dashicon icon for custom layout slugs
	function getLayoutIcon(slug) {
		const name = slug.toLowerCase();
		if (name.includes('text') || name.includes('paragraph') || name.includes('wysiwyg') || name.includes('rich') || name.includes('editor')) return 'dashicons-editor-paragraph';
		if (name.includes('quote') || name.includes('testimonial')) return 'dashicons-editor-quote';
		if (name.includes('gallery') || name.includes('image') || name.includes('photo') || name.includes('portfolio') || name.includes('media')) return 'dashicons-images-alt';
		if (name.includes('slider') || name.includes('carousel') || name.includes('slide')) return 'dashicons-slides';
		if (name.includes('video') || name.includes('player') || name.includes('youtube') || name.includes('vimeo')) return 'dashicons-format-video';
		if (name.includes('hero') || name.includes('cover') || name.includes('banner')) return 'dashicons-cover-image';
		if (name.includes('location') || name.includes('map') || name.includes('contact') || name.includes('address')) return 'dashicons-location-alt';
		if (name.includes('teaser') || name.includes('promo') || name.includes('card') || name.includes('marketing') || name.includes('cta') || name.includes('callout')) return 'dashicons-megaphone';
		if (name.includes('post') || name.includes('feed') || name.includes('item') || name.includes('blog') || name.includes('archive') || name.includes('news')) return 'dashicons-admin-post';
		if (name.includes('form') || name.includes('input') || name.includes('field') || name.includes('gravity')) return 'dashicons-feedback';
		if (name.includes('price') || name.includes('pricing') || name.includes('product') || name.includes('cart') || name.includes('shop') || name.includes('store')) return 'dashicons-cart';
		if (name.includes('setting') || name.includes('config') || name.includes('option') || name.includes('setup') || name.includes('util') || name.includes('helper')) return 'dashicons-admin-generic';
		return 'dashicons-block-default'; // Fallback icon
	}

	// Modern gradients mapping for visual cards if screenshots are missing
	const layoutGradients = [
		'linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%)', // Indigo to Cyan
		'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)', // Indigo to Purple
		'linear-gradient(135deg, #0284c7 0%, #06b6d4 100%)', // Light Blue to Teal
		'linear-gradient(135deg, #10b981 0%, #059669 100%)', // Emerald to Green
		'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)', // Pink to Purple
		'linear-gradient(135deg, #f59e0b 0%, #ef4444 100%)'  // Amber to Red
	];

	// Map layout name to a stable gradient index based on its hash
	function getLayoutGradient(slug) {
		let hash = 0;
		for (let i = 0; i < slug.length; i++) {
			hash = slug.charCodeAt(i) + ((hash << 5) - hash);
		}
		const index = Math.abs(hash) % layoutGradients.length;
		return layoutGradients[index];
	}

	// Classify layout slug into standard categories
	function getLayoutCategory(slug) {
		const name = slug.toLowerCase();
		
		if (name.includes('text') || name.includes('quote') || name.includes('paragraph') || name.includes('wysiwyg') || name.includes('heading') || name.includes('rich') || name.includes('editor')) {
			return 'content';
		}
		if (name.includes('gallery') || name.includes('slider') || name.includes('image') || name.includes('video') || name.includes('hero') || name.includes('carousel') || name.includes('media')) {
			return 'media';
		}
		if (name.includes('teaser') || name.includes('banner') || name.includes('promo') || name.includes('pricing') || name.includes('card') || name.includes('cta') || name.includes('callout')) {
			return 'marketing';
		}
		if (name.includes('location') || name.includes('map') || name.includes('post') || name.includes('item') || name.includes('feed') || name.includes('form') || name.includes('grid') || name.includes('advanced') || name.includes('util')) {
			return 'advanced';
		}
		return 'content'; // Fallback category
	}

	// Dynamic Modal Picker Class
	class AcfLayoutModalPicker {
		constructor() {
			this.dialog = null;
			this.layouts = [];
			this.activeCategory = 'all';
			this.searchQuery = '';
			this.init();
		}

		init() {
			// Handled natively by acf.models.Tooltip.prototype.show override
			console.log('TKA WP Utils: AcfLayoutModalPicker instance init complete.');
		}

		createModalMarkup() {
			// Remove existing modal if any
			$('#tka-acf-layout-modal').remove();

			const i18n = tkaAcfLayoutModalSettings.i18n;
			const markup = `
				<dialog id="tka-acf-layout-modal" class="tka-acf-layout-modal" closedby="any" aria-labelledby="tka-acf-modal-title">
					<div class="tka-acf-modal-container">
						<header class="tka-acf-modal-header">
							<h2 id="tka-acf-modal-title">${i18n.selectLayout}</h2>
							<div class="tka-acf-modal-search-wrap">
								<span class="dashicons dashicons-search"></span>
								<input type="text" id="tka-acf-modal-search" placeholder="${i18n.searchPlaceholder} (Press '/' to focus)" autocomplete="off">
								<button type="button" id="tka-acf-modal-clear-search" style="display: none;">&times;</button>
							</div>
							<button type="button" class="tka-acf-modal-close" aria-label="Close modal">&times;</button>
						</header>
						
						<nav class="tka-acf-modal-cats">
							<button type="button" class="tka-cat-btn active" data-cat="all">All Blocks</button>
							<button type="button" class="tka-cat-btn" data-cat="content">Content & Text</button>
							<button type="button" class="tka-cat-btn" data-cat="media">Media & Visuals</button>
							<button type="button" class="tka-cat-btn" data-cat="marketing">Marketing</button>
							<button type="button" class="tka-cat-btn" data-cat="advanced">Advanced</button>
						</nav>
						
						<div class="tka-acf-modal-body">
							<div class="tka-acf-modal-grid"></div>
						</div>
						
						<footer class="tka-acf-modal-footer">
							<p class="tka-acf-modal-stats">Showing <span id="tka-acf-modal-count">0</span> blocks</p>
							<span class="tka-acf-modal-keyboard-tip">Tip: Press <kbd>Esc</kbd> to close</span>
						</footer>
					</div>
				</dialog>
			`;

			$('body').append(markup);
			this.dialog = document.getElementById('tka-acf-layout-modal');

			// Register Modal Dom Listeners
			this.registerDomListeners();
		}

		registerDomListeners() {
			const $dialog = $(this.dialog);

			// Search input filter
			$dialog.on('input', '#tka-acf-modal-search', (e) => {
				this.searchQuery = $(e.target).val().toLowerCase();
				this.filterCards();

				// Toggle search clear button visibility
				if (this.searchQuery.length > 0) {
					$('#tka-acf-modal-clear-search').show();
				} else {
					$('#tka-acf-modal-clear-search').hide();
				}
			});

			// Clear search button
			$dialog.on('click', '#tka-acf-modal-clear-search', () => {
				$('#tka-acf-modal-search').val('').trigger('input').focus();
			});

			// Hotkey '/' to focus search input
			$dialog.on('keydown', (e) => {
				if (e.key === '/' && document.activeElement !== document.getElementById('tka-acf-modal-search')) {
					e.preventDefault();
					$('#tka-acf-modal-search').focus().select();
				}
			});

			// Category filter button click
			$dialog.on('click', '.tka-cat-btn', (e) => {
				const $btn = $(e.currentTarget);
				$('.tka-cat-btn').removeClass('active');
				$btn.addClass('active');

				this.activeCategory = $btn.data('cat');
				this.filterCards();
			});

			// Block card item click
			$dialog.on('click', '.tka-layout-card', (e) => {
				console.log('TKA WP Utils: Block card clicked.');
				const index = $(e.currentTarget).data('index');
				const layout = this.layouts[index];
				console.log('TKA WP Utils: Selected layout object:', layout);
				
				if (layout) {
					// Set layout selection state to avoid closing native tooltip twice
					this.selectingLayout = true;
					
					// Trigger programmatically standard click event on the native ACF dropdown anchor
					console.log('TKA WP Utils: Triggering native click event on linkEl...');
					if (layout.linkEl && layout.linkEl[0]) {
						layout.linkEl[0].click();
					} else {
						console.log('TKA WP Utils: WARNING: linkEl DOM element not found!');
					}

					// Close modal
					console.log('TKA WP Utils: Closing modal...');
					this.dialog.close();
					
					this.selectingLayout = false;
				}
			});

			// Close button click
			$dialog.on('click', '.tka-acf-modal-close', () => {
				this.dialog.close();
			});

			// Accessibility: light-dismiss fallback (click backdrop)
			this.dialog.addEventListener('click', (event) => {
				if (event.target !== this.dialog) return;
				
				const rect = this.dialog.getBoundingClientRect();
				const isDialogContent = (
					rect.top <= event.clientY &&
					event.clientY <= rect.top + rect.height &&
					rect.left <= event.clientX &&
					event.clientX <= rect.left + rect.width
				);
				
				if (!isDialogContent) {
					this.dialog.close();
				}
			});

			// Handle modal close event to hide the native tooltip cleanly
			this.dialog.addEventListener('close', () => {
				console.log('TKA WP Utils: Modal dialog close event triggered.');
				if (!this.selectingLayout) {
					if (window.tkaActiveAcfTooltip && typeof window.tkaActiveAcfTooltip.hide === 'function') {
						console.log('TKA WP Utils: Hiding the native ACF tooltip...');
						window.tkaActiveAcfTooltip.hide();
					}
				}
				window.tkaActiveAcfTooltip = null;
			});
		}

		renderCards() {
			const $grid = $(this.dialog).find('.tka-acf-modal-grid');
			$grid.empty();

			const themeUrl = tkaAcfLayoutModalSettings.themeUrl;

			this.layouts.forEach((layout, index) => {
				// Construct primary screenshot source URL
				const imgSrc = `${themeUrl}/public/images/builder/${layout.slug}.png`;
				const altImgSrc = `${themeUrl}/public/images/builder/block_${layout.slug.replace('block_', '')}.png`;

				const card = `
					<div class="tka-layout-card" data-index="${index}" data-slug="${layout.slug}" data-cat="${layout.category}">
						<div class="tka-layout-card-img">
							<!-- Primary image loads. If it triggers 404, we try to load the alt image. If that fails, we fallback to CSS gradient -->
							<img src="${imgSrc}" alt="${layout.label}" 
								onerror="
									this.onerror=function(){
										this.style.display='none'; 
										jQuery(this).parent().find('.tka-layout-card-placeholder').css('display', 'flex');
									};
									this.src='${altImgSrc}';
								"
							/>
							
							<!-- Gorgeous dynamic fallback visual placeholder -->
							<div class="tka-layout-card-placeholder" style="display: none; background: ${layout.gradient};">
								<div class="tka-layout-card-placeholder-icon">
									<span class="dashicons ${layout.icon}"></span>
								</div>
							</div>
						</div>
						<div class="tka-layout-card-details">
							<h3 class="tka-layout-card-title">${layout.label}</h3>
							<span class="tka-layout-card-slug">${layout.slug}</span>
							<p class="tka-layout-card-desc">${layout.description}</p>
						</div>
					</div>
				`;
				$grid.append(card);
			});
		}

		filterCards() {
			let visibleCount = 0;
			const $grid = $(this.dialog).find('.tka-acf-modal-grid');
			
			// Remove empty state if present
			$('.tka-acf-modal-empty').remove();

			$(this.dialog).find('.tka-layout-card').each((index, el) => {
				const $card = $(el);
				const slug = $card.data('slug').toLowerCase();
				const title = $card.find('.tka-layout-card-title').text().toLowerCase();
				const desc = $card.find('.tka-layout-card-desc').text().toLowerCase();
				const cat = $card.data('cat');

				const matchesCat = (this.activeCategory === 'all' || cat === this.activeCategory);
				const matchesSearch = (slug.includes(this.searchQuery) || title.includes(this.searchQuery) || desc.includes(this.searchQuery));

				if (matchesCat && matchesSearch) {
					$card.show();
					visibleCount++;
				} else {
					$card.hide();
				}
			});

			// Update visual stats
			$('#tka-acf-modal-count').text(visibleCount);

			// Render empty state if no layouts are visible
			if (visibleCount === 0) {
				const i18n = tkaAcfLayoutModalSettings.i18n;
				const emptyMarkup = `
					<div class="tka-acf-modal-empty">
						<span class="dashicons dashicons-search"></span>
						<h3>${i18n.noLayoutsFound}</h3>
						<p>Try searching for a different block name or category.</p>
					</div>
				`;
				$grid.append(emptyMarkup);
			}
		}

		openModal() {
			this.createModalMarkup();
			this.renderCards();
			this.filterCards();
			
			// Open the modal natively
			this.dialog.showModal();
			
			// Focus search input
			setTimeout(() => {
				$('#tka-acf-modal-search').focus();
			}, 50);
		}
	}

	// Modular initialization helper to prevent double-binding
	function initializePicker() {
		if (window.tkaAcfLayoutModalPickerInstance) return;

		console.log('TKA WP Utils: Registering custom Tooltip prototype hooks...');
		window.tkaAcfLayoutModalPickerInstance = new AcfLayoutModalPicker();

		// Override Tooltip setup method to bypass subclass prototype shadowing
		if (acf.models && acf.models.Tooltip) {
			console.log('TKA WP Utils: Overriding acf.models.Tooltip.prototype.setup...');
			const originalSetup = acf.models.Tooltip.prototype.setup;

			acf.models.Tooltip.prototype.setup = function(e) {
				// Execute the original setup to build $el and set up basic data
				const result = originalSetup.apply(this, arguments);

				// Dynamically override 'show' directly on the instance to guarantee interception
				const self = this;
				const originalInstanceShow = this.show;

				this.show = function() {
					// Render/show the tooltip in the DOM (runs either standard or shadowed subclass show)
					const showResult = originalInstanceShow.apply(this, arguments);

					// Intercept only the Flexible Content popup, ignoring the 'More Layout Actions' list
					if (this.$el.hasClass('acf-fc-popup') && !this.$el.hasClass('acf-more-layout-actions')) {
						console.log('TKA WP Utils: Intercepted Flexible Content Layout selection popup via instance show!');
						
						// Save tooltip reference to close it if the modal is dismissed manually
						window.tkaActiveAcfTooltip = this;
						
						const picker = window.tkaAcfLayoutModalPickerInstance;
						if (picker) {
							picker.layouts = [];
							this.$el.find('li a').each(function(index, el) {
								const $link = $(el);
								const slug = $link.data('layout');
								const label = $link.text().trim();
								
								// Get dynamic metadata passed from PHP or fallback dynamically
								const metadata = (typeof tkaAcfLayoutModalSettings !== 'undefined' && tkaAcfLayoutModalSettings.metadata) ? tkaAcfLayoutModalSettings.metadata : {};
								const customMeta = metadata[slug] || {};
								
								const category = customMeta.category || getLayoutCategory(slug);
								const description = customMeta.description || ("Flexible content block layout for displaying " + label.toLowerCase() + " section.");
								const icon = customMeta.icon || getLayoutIcon(slug);
								
								picker.layouts.push({
									slug: slug,
									label: label,
									category: category,
									description: description,
									icon: icon,
									gradient: getLayoutGradient(slug),
									linkEl: $link
								});
							});

							console.log('TKA WP Utils: Harvested layouts count:', picker.layouts.length);

							if (picker.layouts.length > 0) {
								picker.openModal();
							}
						}
					}

					return showResult;
				};

				return result;
			};
		} else {
			console.log('TKA WP Utils: acf.models.Tooltip definition not found.');
		}
	}

	// Instantiate the Visual layout picker
	$(document).ready(function() {
		console.log('TKA WP Utils: JS script file evaluated on document ready.');
		
		function checkAndBoot() {
			if (typeof acf !== 'undefined') {
				if (acf.didAction && acf.didAction('ready')) {
					console.log('TKA WP Utils: ACF already ready. Initializing directly...');
					initializePicker();
				} else {
					console.log('TKA WP Utils: ACF not yet ready. Hooking ready action...');
					acf.addAction('ready', function() {
						console.log('TKA WP Utils: ACF ready action fired. Initializing...');
						initializePicker();
					});
				}
				return true;
			}
			return false;
		}

		if (!checkAndBoot()) {
			console.log('TKA WP Utils: ACF object not found on load. Polling...');
			var acfCheckCount = 0;
			var acfCheckInterval = setInterval(function() {
				acfCheckCount++;
				if (checkAndBoot()) {
					clearInterval(acfCheckInterval);
				} else if (acfCheckCount > 50) {
					clearInterval(acfCheckInterval);
					console.log('TKA WP Utils: ACF object not found after 5 seconds of polling.');
				}
			}, 100);
		}
	});

})(jQuery);
