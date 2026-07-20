/**
 * ACF SVG Picker Javascript Engine (with Multi-Select Support)
 */
(function($) {
    'use strict';

    class AcfSvgPickerModal {
        constructor() {
            this.dialog = null;
            this.svgs = [];
            this.selected = []; // Tracks selected filenames/slugs in multi-select mode
            this.searchQuery = '';
            this.activeWrapper = null;
            this.isMultiple = false;
        }

        createModalMarkup() {
            $('#tka-acf-svg-modal').remove();

            const applyBtnHtml = this.isMultiple 
                ? '<button type="button" id="tka-acf-modal-apply" class="button button-primary">Apply Selection</button>' 
                : '';

            const markup = `
                <dialog id="tka-acf-svg-modal" class="tka-acf-svg-modal" closedby="any" aria-labelledby="tka-acf-svg-modal-title">
                    <div class="tka-acf-modal-container">
                        <header class="tka-acf-modal-header">
                            <h2 id="tka-acf-svg-modal-title">Select SVG Icon(s)</h2>
                            <div class="tka-acf-modal-search-wrap">
                                <span class="dashicons dashicons-search"></span>
                                <input type="text" id="tka-acf-svg-modal-search" placeholder="Search SVG icons... (Press '/' to focus)" autocomplete="off">
                                <button type="button" id="tka-acf-modal-clear-search" style="display: none;">&times;</button>
                            </div>
                            <button type="button" class="tka-acf-modal-close" aria-label="Close modal">&times;</button>
                        </header>
                        
                        <div class="tka-acf-modal-body">
                            <div class="tka-acf-modal-grid"></div>
                        </div>
                        
                        <footer class="tka-acf-modal-footer">
                            <p class="tka-acf-modal-stats">Showing <span id="tka-acf-modal-count">0</span> SVGs</p>
                            <div class="tka-acf-modal-footer-actions">
                                ${applyBtnHtml}
                                <span class="tka-acf-modal-keyboard-tip">Tip: Press <kbd>Esc</kbd> to close</span>
                            </div>
                        </footer>
                    </div>
                </dialog>
            `;

            $('body').append(markup);
            this.dialog = document.getElementById('tka-acf-svg-modal');

            this.registerDomListeners();
        }

        registerDomListeners() {
            const $dialog = $(this.dialog);

            // Search input filter
            $dialog.on('input', '#tka-acf-svg-modal-search', (e) => {
                this.searchQuery = $(e.target).val().toLowerCase();
                this.filterCards();

                if (this.searchQuery.length > 0) {
                    $('#tka-acf-modal-clear-search').show();
                } else {
                    $('#tka-acf-modal-clear-search').hide();
                }
            });

            // Clear search button
            $dialog.on('click', '#tka-acf-modal-clear-search', () => {
                $('#tka-acf-svg-modal-search').val('').trigger('input').focus();
            });

            // Hotkey '/' to focus search input
            $dialog.on('keydown', (e) => {
                if (e.key === '/' && document.activeElement !== document.getElementById('tka-acf-svg-modal-search')) {
                    e.preventDefault();
                    $('#tka-acf-svg-modal-search').focus().select();
                }
            });

            // Block card item click
            $dialog.on('click', '.tka-svg-card', (e) => {
                const $card = $(e.currentTarget);
                const index = $card.data('index');
                const svg = this.svgs[index];
                
                if (!svg) return;

                if (this.isMultiple) {
                    // Toggle multiple selection
                    const val = svg.filename;
                    const idx = this.selected.indexOf(val);

                    if (idx > -1) {
                        this.selected.splice(idx, 1);
                        $card.removeClass('is-selected');
                    } else {
                        this.selected.push(val);
                        $card.addClass('is-selected');
                    }
                } else {
                    // Single Selection: Save value and close immediately
                    if (this.activeWrapper) {
                        const $wrapper = this.activeWrapper;
                        const $input = $wrapper.find('.tka-svg-picker-value').first();
                        const $preview = $wrapper.find('.tka-svg-picker-preview').first();
                        const $display = $preview.find('.tka-svg-picker-display').first();
                        const $clearBtn = $wrapper.find('.tka-svg-picker-clear-btn').first();

                        $input.val(svg.filename).trigger('change');
                        $display.html(svg.raw).show();
                        $preview.removeClass('empty').addClass('has-value');
                        $clearBtn.removeClass('hidden');

                        this.dialog.close();
                    }
                }
            });

            // Apply button click (only visible in multiple mode)
            $dialog.on('click', '#tka-acf-modal-apply', () => {
                if (this.activeWrapper && this.isMultiple) {
                    const $wrapper = this.activeWrapper;
                    const $grid = $wrapper.find('.tka-svg-picker-multiple-grid').first();
                    const $placeholder = $wrapper.find('.tka-svg-multiple-empty-placeholder').first();
                    const $clearBtn = $wrapper.find('.tka-svg-picker-clear-btn').first();
                    const $baseInput = $wrapper.find('.tka-svg-picker-value').first();
                    
                    // Clear existing selection HTML
                    $grid.empty();

                    const inputName = $baseInput.attr('name');
                    const normalizedInputName = inputName.replace(/\[\]$/, ''); // Strip existing array suffix if any

                    this.selected.forEach((val) => {
                        // Find matching svg preview content
                        const svg = this.svgs.find(s => s.filename === val);
                        if (svg) {
                            const itemHtml = `
                                <div class="tka-svg-multiple-preview-item" data-value="${val}">
                                    <div class="tka-svg-multiple-preview-display">${svg.raw}</div>
                                    <span class="tka-svg-multiple-preview-title">${svg.slug}</span>
                                    <span class="tka-svg-multiple-preview-remove">&times;</span>
                                    <input type="hidden" name="${normalizedInputName}[]" value="${val}" />
                                </div>
                            `;
                            $grid.append(itemHtml);
                        }
                    });

                    // Toggle placeholders and actions
                    if (this.selected.length > 0) {
                        $baseInput.prop('disabled', true); // Disable base input so it doesn't submit empty values
                        $placeholder.hide();
                        $clearBtn.removeClass('hidden');
                    } else {
                        $baseInput.prop('disabled', false); // Enable base input to submit empty value
                        $placeholder.show();
                        $clearBtn.addClass('hidden');
                    }

                    // Trigger change trigger for ACF validation
                    $grid.find('input').first().trigger('change');

                    this.dialog.close();
                }
            });

            // Close button click
            $dialog.on('click', '.tka-acf-modal-close', () => {
                this.dialog.close();
            });

            // Backdrop click close
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
        }

        renderCards() {
            const $grid = $(this.dialog).find('.tka-acf-modal-grid');
            $grid.empty();

            this.svgs.forEach((svg, index) => {
                const isSelected = this.isMultiple && (this.selected.indexOf(svg.filename) > -1);
                const selectedClass = isSelected ? 'is-selected' : '';

                const card = `
                    <div class="tka-svg-card ${selectedClass}" data-index="${index}" data-slug="${svg.slug}" data-filename="${svg.filename}">
                        <div class="tka-svg-card-display">
                            ${svg.raw}
                        </div>
                        <span class="tka-svg-card-title">${svg.slug}</span>
                        <div class="tka-svg-card-checkmark"><span class="dashicons dashicons-yes"></span></div>
                    </div>
                `;
                $grid.append(card);
            });
        }

        filterCards() {
            let visibleCount = 0;
            const $grid = $(this.dialog).find('.tka-acf-modal-grid');
            
            $('.tka-acf-modal-empty').remove();

            $(this.dialog).find('.tka-svg-card').each((index, el) => {
                const $card = $(el);
                const slug = $card.data('slug').toLowerCase();
                const filename = $card.data('filename').toLowerCase();

                const matchesSearch = (slug.includes(this.searchQuery) || filename.includes(this.searchQuery));

                if (matchesSearch) {
                    $card.show();
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });

            $('#tka-acf-modal-count').text(visibleCount);

            if (visibleCount === 0) {
                const emptyMarkup = `
                    <div class="tka-acf-modal-empty">
                        <span class="dashicons dashicons-search"></span>
                        <h3>No SVGs found</h3>
                        <p>Try searching for a different name.</p>
                    </div>
                `;
                $grid.append(emptyMarkup);
            }
        }

        openModal(wrapper) {
            this.activeWrapper = wrapper;
            this.isMultiple = wrapper.data('multiple') == '1';
            
            // Get SVGs data parsed from data-svgs attribute
            const rawSvgs = wrapper.attr('data-svgs');
            try {
                this.svgs = JSON.parse(rawSvgs) || [];
            } catch (e) {
                this.svgs = [];
            }

            // Harvest already selected SVGs in multi-select mode
            this.selected = [];
            if (this.isMultiple) {
                wrapper.find('.tka-svg-multiple-preview-item').each((i, el) => {
                    const val = $(el).data('value');
                    if (val) {
                        this.selected.push(val);
                    }
                });
            }

            this.createModalMarkup();
            this.renderCards();
            this.filterCards();
            
            this.dialog.showModal();
            
            setTimeout(() => {
                $('#tka-acf-svg-modal-search').focus();
            }, 50);
        }
    }

    // Instantiation
    $(document).ready(function() {
        const pickerModal = new AcfSvgPickerModal();

        // Choose SVG button click handler
        $(document).on('click', '.tka-svg-picker-select-btn', function(e) {
            e.preventDefault();
            const wrapper = $(this).closest('.tka-svg-picker-wrapper');
            pickerModal.openModal(wrapper);
        });

        // Clear SVG button click handler
        $(document).on('click', '.tka-svg-picker-clear-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $wrapper = $btn.closest('.tka-svg-picker-wrapper');
            const isMultiple = $wrapper.data('multiple') == '1';

            if (isMultiple) {
                const $grid = $wrapper.find('.tka-svg-picker-multiple-grid').first();
                const $placeholder = $wrapper.find('.tka-svg-multiple-empty-placeholder').first();
                const $baseInput = $wrapper.find('.tka-svg-picker-value').first();
                
                $grid.empty();
                $placeholder.show();
                $btn.addClass('hidden');
                $baseInput.prop('disabled', false).val('').trigger('change');
            } else {
                const $input = $wrapper.find('.tka-svg-picker-value').first();
                const $preview = $wrapper.find('.tka-svg-picker-preview').first();
                const $display = $preview.find('.tka-svg-picker-display').first();

                $input.val('').trigger('change');
                $display.empty().hide();
                $preview.removeClass('has-value').addClass('empty');
                $btn.addClass('hidden');
            }
        });

        // Individual remove item handler in multiselect list
        $(document).on('click', '.tka-svg-multiple-preview-remove', function(e) {
            e.preventDefault();
            const $removeBtn = $(this);
            const $item = $removeBtn.closest('.tka-svg-multiple-preview-item');
            const $wrapper = $removeBtn.closest('.tka-svg-picker-wrapper');
            const $grid = $wrapper.find('.tka-svg-picker-multiple-grid').first();
            const $placeholder = $wrapper.find('.tka-svg-multiple-empty-placeholder').first();
            const $clearBtn = $wrapper.find('.tka-svg-picker-clear-btn').first();

            $item.remove();

            // Toggle placeholders and actions
            const remainingCount = $grid.find('.tka-svg-multiple-preview-item').length;
            if (remainingCount === 0) {
                const $baseInput = $wrapper.find('.tka-svg-picker-value').first();
                $baseInput.prop('disabled', false).val('').trigger('change');
                $placeholder.show();
                $clearBtn.addClass('hidden');
            }
        });
    });

})(jQuery);
