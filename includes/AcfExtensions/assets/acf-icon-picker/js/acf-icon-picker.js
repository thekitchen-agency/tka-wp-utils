(function($) {
    if (typeof acf === 'undefined') return;

    // A rich selection of standard Google Material Symbols Outlined icons
    const ICONS_LIST = [
        // Common / General
        { name: 'home', categories: ['common'] },
        { name: 'search', categories: ['common'] },
        { name: 'settings', categories: ['common'] },
        { name: 'check', categories: ['common'] },
        { name: 'close', categories: ['common'] },
        { name: 'delete', categories: ['common'] },
        { name: 'edit', categories: ['common'] },
        { name: 'add', categories: ['common'] },
        { name: 'remove', categories: ['common'] },
        { name: 'favorite', categories: ['common'] },
        { name: 'share', categories: ['common'] },
        { name: 'download', categories: ['common'] },
        { name: 'upload', categories: ['common'] },
        { name: 'refresh', categories: ['common'] },
        { name: 'sync', categories: ['common'] },
        { name: 'login', categories: ['common'] },
        { name: 'logout', categories: ['common'] },
        { name: 'menu', categories: ['common', 'navigation'] },
        { name: 'more_vert', categories: ['common'] },
        { name: 'more_horiz', categories: ['common'] },
        { name: 'visibility', categories: ['common', 'media'] },
        { name: 'visibility_off', categories: ['common', 'media'] },
        { name: 'star', categories: ['common'] },
        { name: 'lock', categories: ['common'] },
        { name: 'lock_open', categories: ['common'] },
        { name: 'key', categories: ['common'] },
        { name: 'flag', categories: ['common'] },
        { name: 'bookmark', categories: ['common'] },
        { name: 'lightbulb', categories: ['common'] },
        { name: 'help', categories: ['common'] },
        { name: 'info', categories: ['common'] },
        { name: 'warning', categories: ['common'] },
        { name: 'error', categories: ['common'] },
        { name: 'check_circle', categories: ['common'] },
        { name: 'cancel', categories: ['common'] },
        { name: 'open_in_new', categories: ['common', 'navigation'] },
        { name: 'launch', categories: ['common'] },
        { name: 'filter_list', categories: ['common'] },
        { name: 'sort', categories: ['common'] },
        { name: 'bolt', categories: ['common'] },
        { name: 'rocket', categories: ['common'] },
        { name: 'speed', categories: ['common'] },
        { name: 'verified', categories: ['common'] },

        // Navigation / Arrows
        { name: 'arrow_back', categories: ['navigation'] },
        { name: 'arrow_forward', categories: ['navigation'] },
        { name: 'arrow_upward', categories: ['navigation'] },
        { name: 'arrow_downward', categories: ['navigation'] },
        { name: 'chevron_left', categories: ['navigation'] },
        { name: 'chevron_right', categories: ['navigation'] },
        { name: 'expand_more', categories: ['navigation'] },
        { name: 'expand_less', categories: ['navigation'] },
        { name: 'arrow_drop_down', categories: ['navigation'] },
        { name: 'arrow_drop_up', categories: ['navigation'] },
        { name: 'menu_open', categories: ['navigation'] },
        { name: 'apps', categories: ['navigation'] },
        { name: 'grid_view', categories: ['navigation'] },
        { name: 'list', categories: ['navigation'] },
        { name: 'map', categories: ['navigation'] },
        { name: 'location_on', categories: ['navigation'] },
        { name: 'pin_drop', categories: ['navigation'] },
        { name: 'navigation', categories: ['navigation'] },
        { name: 'explore', categories: ['navigation'] },
        { name: 'my_location', categories: ['navigation'] },
        
        // Media / Design
        { name: 'image', categories: ['media'] },
        { name: 'photo_camera', categories: ['media'] },
        { name: 'videocam', categories: ['media'] },
        { name: 'music_note', categories: ['media'] },
        { name: 'mic', categories: ['media'] },
        { name: 'volume_up', categories: ['media'] },
        { name: 'volume_down', categories: ['media'] },
        { name: 'volume_mute', categories: ['media'] },
        { name: 'play_circle', categories: ['media'] },
        { name: 'pause_circle', categories: ['media'] },
        { name: 'movie', categories: ['media'] },
        { name: 'article', categories: ['media'] },
        { name: 'description', categories: ['media'] },
        { name: 'folder', categories: ['media'] },
        { name: 'file_present', categories: ['media'] },
        { name: 'attachment', categories: ['media'] },
        { name: 'link', categories: ['media'] },
        { name: 'brush', categories: ['media'] },
        { name: 'palette', categories: ['media'] },
        { name: 'design_services', categories: ['media'] },
        { name: 'architecture', categories: ['media'] },
        
        // Communication
        { name: 'mail', categories: ['communication'] },
        { name: 'phone', categories: ['communication'] },
        { name: 'chat', categories: ['communication'] },
        { name: 'comment', categories: ['communication'] },
        { name: 'forum', categories: ['communication'] },
        { name: 'notifications', categories: ['communication'] },
        { name: 'notifications_active', categories: ['communication'] },
        { name: 'email', categories: ['communication'] },
        { name: 'send', categories: ['communication'] },
        { name: 'contact_support', categories: ['communication'] },
        { name: 'contact_page', categories: ['communication'] },
        { name: 'sms', categories: ['communication'] },
        { name: 'headset_mic', categories: ['communication'] },
        
        // Social / Users
        { name: 'person', categories: ['social'] },
        { name: 'people', categories: ['social'] },
        { name: 'group', categories: ['social'] },
        { name: 'person_add', categories: ['social'] },
        { name: 'account_circle', categories: ['social'] },
        { name: 'groups', categories: ['social'] },
        { name: 'emoji_emotions', categories: ['social'] },
        { name: 'sentiment_satisfied', categories: ['social'] },
        { name: 'thumb_up', categories: ['social'] },
        { name: 'thumb_down', categories: ['social'] },
        { name: 'public', categories: ['social'] },
        { name: 'domain', categories: ['social'] },
        { name: 'location_city', categories: ['social'] },
        { name: 'business', categories: ['social'] },
        { name: 'school', categories: ['social'] },
        { name: 'workspace_premium', categories: ['social'] },
        
        // Commerce / Finance
        { name: 'shopping_cart', categories: ['commerce'] },
        { name: 'shopping_bag', categories: ['commerce'] },
        { name: 'storefront', categories: ['commerce'] },
        { name: 'credit_card', categories: ['commerce'] },
        { name: 'payments', categories: ['commerce'] },
        { name: 'account_balance', categories: ['commerce'] },
        { name: 'receipt', categories: ['commerce'] },
        { name: 'calculate', categories: ['commerce'] },
        { name: 'sell', categories: ['commerce'] },
        { name: 'trending_up', categories: ['commerce'] },
        { name: 'trending_down', categories: ['commerce'] },
        { name: 'bar_chart', categories: ['commerce'] },
        { name: 'pie_chart', categories: ['commerce'] },
        { name: 'attach_money', categories: ['commerce'] },
        { name: 'monetization_on', categories: ['commerce'] },
        { name: 'savings', categories: ['commerce'] },
        { name: 'work', categories: ['commerce'] },
        { name: 'business_center', categories: ['commerce'] },
        { name: 'local_shipping', categories: ['commerce'] },
        
        // Travel / Transportation
        { name: 'flight', categories: ['travel'] },
        { name: 'directions_car', categories: ['travel'] },
        { name: 'directions_bus', categories: ['travel'] },
        { name: 'directions_run', categories: ['travel'] },
        { name: 'directions_walk', categories: ['travel'] },
        { name: 'traffic', categories: ['travel'] },
        { name: 'flight_takeoff', categories: ['travel'] },
        { name: 'flight_land', categories: ['travel'] },
        { name: 'hotel', categories: ['travel'] },
        { name: 'restaurant', categories: ['travel'] },
        { name: 'local_cafe', categories: ['travel'] },
        { name: 'local_bar', categories: ['travel'] },
        { name: 'commute', categories: ['travel'] },
        { name: 'subway', categories: ['travel'] },
        { name: 'ev_station', categories: ['travel'] },
        { name: 'pedal_bike', categories: ['travel'] },
        
        // Devices / Tech
        { name: 'computer', categories: ['tech'] },
        { name: 'laptop', categories: ['tech'] },
        { name: 'phone_iphone', categories: ['tech'] },
        { name: 'smartphone', categories: ['tech'] },
        { name: 'tablet', categories: ['tech'] },
        { name: 'tv', categories: ['tech'] },
        { name: 'watch', categories: ['tech'] },
        { name: 'wifi', categories: ['tech'] },
        { name: 'bluetooth', categories: ['tech'] },
        { name: 'battery_charging_full', categories: ['tech'] },
        { name: 'power', categories: ['tech'] },
        { name: 'cloud', categories: ['tech'] },
        { name: 'cloud_download', categories: ['tech'] },
        { name: 'cloud_upload', categories: ['tech'] },
        { name: 'code', categories: ['tech'] },
        { name: 'build', categories: ['tech'] },
        { name: 'construction', categories: ['tech'] },
        { name: 'tune', categories: ['tech'] },
        { name: 'science', categories: ['tech'] },
        
        // Date / Time
        { name: 'schedule', categories: ['time'] },
        { name: 'event', categories: ['time'] },
        { name: 'calendar_today', categories: ['time'] },
        { name: 'alarm', categories: ['time'] },
        { name: 'today', categories: ['time'] },
        { name: 'history', categories: ['time'] },
        { name: 'update', categories: ['time'] },
        { name: 'hourglass_empty', categories: ['time'] },
        { name: 'watch_later', categories: ['time'] }
    ];

    const CATEGORIES = {
        all: 'All',
        common: 'Common',
        navigation: 'Navigation',
        media: 'Media',
        communication: 'Communication',
        social: 'Social',
        commerce: 'Commerce',
        travel: 'Travel',
        tech: 'Tech',
        time: 'Time'
    };

    // Shared modal elements and state
    let $overlay = null;
    let $activeField = null; // Reference to the wrapper jQuery object of the active field instance
    let currentCategory = 'all';
    let searchQuery = '';

    /**
     * Create and initialize the shared global icon picker modal.
     */
    function initModal() {
        if ($overlay) return;

        // Modal overlay HTML template
        const modalHtml = `
            <div class="acf-icon-picker-modal-overlay">
                <div class="acf-icon-picker-modal">
                    <div class="acf-icon-picker-modal-header">
                        <h3>Select Material Symbol</h3>
                        <button type="button" class="acf-icon-picker-modal-close">&times;</button>
                    </div>
                    <div class="acf-icon-picker-modal-search">
                        <span class="material-symbols-outlined search-icon">search</span>
                        <input type="text" class="acf-icon-picker-search-input" placeholder="Search icons...">
                        <button type="button" class="acf-icon-picker-search-clear" style="display:none;">&times;</button>
                    </div>
                    <div class="acf-icon-picker-modal-categories"></div>
                    <div class="acf-icon-picker-modal-body">
                        <div class="acf-icon-picker-grid"></div>
                    </div>
                </div>
            </div>
        `;

        $overlay = $(modalHtml).appendTo('body');

        // Render Category Pills
        const $categoriesWrap = $overlay.find('.acf-icon-picker-modal-categories');
        Object.entries(CATEGORIES).forEach(([key, label]) => {
            $('<button type="button">')
                .addClass('acf-icon-picker-category-pill')
                .attr('data-category', key)
                .text(label)
                .appendTo($categoriesWrap);
        });
        $categoriesWrap.find('[data-category="all"]').addClass('active');

        // Events: Close Modal
        $overlay.on('click', '.acf-icon-picker-modal-close', closeModal);
        $overlay.on('click', function(e) {
            if ($(e.target).hasClass('acf-icon-picker-modal-overlay')) {
                closeModal();
            }
        });

        // Close on ESC keypress
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $overlay.hasClass('active')) {
                closeModal();
            }
        });

        // Events: Category Click
        $overlay.on('click', '.acf-icon-picker-category-pill', function() {
            $overlay.find('.acf-icon-picker-category-pill').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).attr('data-category');
            renderGrid();
        });

        // Events: Search Input
        const $searchInput = $overlay.find('.acf-icon-picker-search-input');
        const $searchClear = $overlay.find('.acf-icon-picker-search-clear');

        $searchInput.on('input', function() {
            searchQuery = $(this).val().toLowerCase().trim();
            if (searchQuery.length > 0) {
                $searchClear.show();
            } else {
                $searchClear.hide();
            }
            renderGrid();
        });

        $searchClear.on('click', function() {
            $searchInput.val('').trigger('input').focus();
        });

        // Events: Icon Picked
        $overlay.on('click', '.acf-icon-picker-grid-item', function() {
            const iconName = $(this).attr('data-name');
            selectIcon(iconName);
        });
    }

    /**
     * Render the grid item list filtered by search and category.
     */
    function renderGrid() {
        if (!$overlay || !$activeField) return;

        const $grid = $overlay.find('.acf-icon-picker-grid');
        $grid.empty();

        const currentValue = $activeField.find('.acf-icon-picker-value').val();

        // Filter the icons
        const filtered = ICONS_LIST.filter(icon => {
            const matchesCategory = currentCategory === 'all' || icon.categories.includes(currentCategory);
            const matchesSearch = searchQuery === '' || 
                                  icon.name.toLowerCase().includes(searchQuery) ||
                                  icon.name.replace(/_/g, ' ').toLowerCase().includes(searchQuery);
            return matchesCategory && matchesSearch;
        });

        if (filtered.length === 0) {
            $grid.append('<div class="acf-icon-picker-no-results" style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 40px 0;">No icons found matching query.</div>');
            return;
        }

        // Render matching icons
        filtered.forEach(icon => {
            const isSelected = icon.name === currentValue;
            const humanName = icon.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

            const itemHtml = `
                <div class="acf-icon-picker-grid-item ${isSelected ? 'selected' : ''}" data-name="${icon.name}" title="${humanName}">
                    <span class="material-symbols-outlined grid-icon">${icon.name}</span>
                    <span class="grid-label">${humanName}</span>
                </div>
            `;
            $grid.append(itemHtml);
        });
    }

    /**
     * Open the modal for a specific field instance.
     */
    function openModal($fieldWrap) {
        initModal();
        $activeField = $fieldWrap;

        // Reset search and categories
        currentCategory = 'all';
        searchQuery = '';
        $overlay.find('.acf-icon-picker-category-pill').removeClass('active');
        $overlay.find('[data-category="all"]').addClass('active');
        $overlay.find('.acf-icon-picker-search-input').val('');
        $overlay.find('.acf-icon-picker-search-clear').hide();

        renderGrid();

        $overlay.addClass('active');
        $('body').css('overflow', 'hidden'); // Lock background scroll

        // Focus search box
        setTimeout(() => {
            $overlay.find('.acf-icon-picker-search-input').focus();
        }, 100);
    }

    /**
     * Close the modal.
     */
    function closeModal() {
        if (!$overlay) return;
        $overlay.removeClass('active');
        $('body').css('overflow', ''); // Restore scroll
        $activeField = null;
    }

    /**
     * Handle icon selection in the modal.
     */
    function selectIcon(iconName) {
        if (!$activeField) return;

        // Update hidden input
        const $input = $activeField.find('.acf-icon-picker-value');
        $input.val(iconName).trigger('change');

        // Update preview card
        const $preview = $activeField.find('.acf-icon-picker-preview');
        $preview.removeClass('empty').addClass('has-value');
        $preview.find('.acf-icon-picker-icon-display').text(iconName);

        // Update clear button visibility
        $activeField.find('.acf-icon-picker-clear-btn').removeClass('hidden');

        closeModal();
    }

    /**
     * Clear the icon selection.
     */
    function clearSelection($fieldWrap) {
        const $input = $fieldWrap.find('.acf-icon-picker-value');
        $input.val('').trigger('change');

        const $preview = $fieldWrap.find('.acf-icon-picker-preview');
        $preview.removeClass('has-value').addClass('empty');
        $preview.find('.acf-icon-picker-icon-display').text('');

        $fieldWrap.find('.acf-icon-picker-clear-btn').addClass('hidden');
    }

    /**
     * Initialize individual ACF Icon Picker field instances.
     */
    function initialize_field(field) {
        // Resolve field instance (ACF 5.7+ passes field object, older versions may pass jQuery element)
        const fieldInstance = (field instanceof jQuery) ? acf.getField(field) : field;
        if (!fieldInstance) return;

        const $el = fieldInstance.$el;
        if (!$el || !$el.length) return;

        // Find field wrapper inside the field element
        const $fieldWrap = $el.find('.acf-icon-picker-wrapper');
        if (!$fieldWrap.length || $fieldWrap.data('initialized')) return;

        $fieldWrap.data('initialized', true);

        // Event: Click on preview or Choose button to open modal
        $fieldWrap.on('click', '.acf-icon-picker-preview, .acf-icon-picker-select-btn', function(e) {
            e.preventDefault();
            openModal($fieldWrap);
        });

        // Event: Click Clear button
        $fieldWrap.on('click', '.acf-icon-picker-clear-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearSelection($fieldWrap);
        });
    }

    // Register ACF hooks
    acf.addAction('ready_field/type=tka_icon_picker', initialize_field);
    acf.addAction('append_field/type=tka_icon_picker', initialize_field);

})(jQuery);
