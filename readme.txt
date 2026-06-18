=== TKA WP Utils ===
Contributors: thekitchen-agency
Tags: classic editor, svg upload, admin columns, menu organizer, hardening, image optimization, woocommerce, woocommerce optimization
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.10.0
Requires PHP: 8.3
License: MIT
License URI: https://opensource.org/licenses/MIT

A powerful suite of utility tools to secure, white-label, customize, and optimize your WordPress experience.

== Description ==

TKA WP Utils is an all-in-one utility plugin designed to help developers and agency owners customize, secure, and white-label the WordPress administration environment for clients. From editor control and SVG validation to menu organization and advanced columns customization, this plugin is built to make client site delivery flawless and professional.

### Key Features

*   **Classic Experience Restored**: Easily revert post/page editing back to the classic rich text (TinyMCE) editor and restore the traditional widgets dashboard page.
*   **Granular Gutenberg Editor Control**: Globally disable the Gutenberg Block Editor or selectively activate it on specific post types.
*   **Strict SVG Upload Security**: Safely upload SVG vector graphics. The plugin runs deep XML structural analysis upon upload to block XML External Entity (XXE) and Cross-Site Scripting (XSS) injection vectors. Includes a visual Security Sandbox to test SVGs before upload.
*   **Obfuscate Author URLs & REST User Slugs**: Secure your site against brute-force user enumeration by hiding user slugs in the REST API and public author archives.
*   **Obfuscate Email Addresses**: Auto-scans published post, page, and widget contents to convert email addresses into randomized decimal/hexadecimal HTML entities, shielding them from spam harvesters.
*   **Complete White-Labeling**: Rebrand the login screen with a custom logo and login page CSS. Replace the top admin toolbar logo and remove footer brandings.
*   **Dedicated Admin Menu Organizer Subpage**: Rearrange sidebar menus with a drag-and-drop builder on a standalone subpage. Configure separate visibility rules for the website owner (installer) and client administrators.
*   **Drag & Drop Content Ordering**: Enables manual drag-and-drop row sorting in post list tables.
*   **One-Click Duplication**: Clone any post, page, or custom post type into a new draft in one click.
*   **Redesigned Admin Columns Customizer**: Customize column layouts for post type lists. Features drag-and-drop sortable configurations, database meta key selectors, click-through relational links for Related Posts and Custom Taxonomy Terms, and matching top-bar filters.
*   **ACF Flexible Content Copy/Paste Engine**: Copy and paste layout blocks inside Flexible Content fields, supporting both single block duplication and bulk multiselect operations across different posts and pages. Supports deep, recursive mapping for standard inputs, WYSIWYG, Images, Files, Galleries, Repeaters, Groups, and relational fields (Post Object, Taxonomy, Relationship).
*   **Dedicated Bulk Retroactive Image Optimizer**: Features a sequential batch retroactive image optimizer subpage that safely processes JPEGs and PNGs to prevent FPM gateway timeouts.
*   **Interactive Media Library Status Table**: A premium, real-time reactive table displaying JPEG, PNG, and WebP assets with color-coded format badges (slate JPEG, indigo PNG, success green WebP), status indicators, and direct database-backed size savings metadata display with smooth success highlight animations.
*   **WooCommerce Speed & Bloat Settings**: Speed up WooCommerce stores by dequeuing WooCommerce scripts/styles on non-shop pages, disabling or selectively loading AJAX cart fragments, suppressing Gutenberg blocks stylesheets, removing the heavy password strength meter script, and cleaning up marketing menu/dashboard widgets.
*   **Gravity Forms Integrations**: Clean up markup by converting `<input type="submit">` tags into modern `<button type="submit">` tags, block default Gravity Forms CSS stylesheets, and prevent double form submissions by displaying custom loading text (e.g. "Sending...") during form posts.
*   **Cross-Document Page Transitions**: Smooth visual transitions between page loads using the native View Transitions API. Includes pre-defined animations (slide, swipe, wipe), a dynamic custom rules manager to map transitions between specific page types/URLs, and a custom CSS editor for tailor-made transition styles.
*   **Virtual Media Folders**: Organize your WordPress Media Library with virtual hierarchical folders and a drag-and-drop file organization sidebar. Fully integrated with standard media grid and selection modals.
*   **WPML Performance Optimization**: Improve server response time and reduce database overhead on multilingual sites. Adds options to bypass "Adjust IDs for multilingual functionality" runtime lookups and suppress canonical redirects during REST/AJAX background operations.
*   **Advanced Resource Optimizations**: Adjust or disable the WordPress Heartbeat API, programmatically limit post revisions, extend autosave intervals, and dequeue core Gutenberg block styles globally on the frontend to optimize page weight and server resources.
*   **SMTP & Mail Settings**: Configure custom SMTP credentials for reliable email delivery. Includes an automatic developer mode override that seamlessly routes outbound emails to a local Mailpit instance during local development.

== Installation ==

1. Upload the `tka-wp-utils` folder to your `/wp-content/plugins/` directory, or upload the ZIP file directly via the WordPress admin panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **TKA WP Utils** admin panel in your sidebar to configure and enable settings.

== Frequently Asked Questions ==

= Does this plugin affect performance? =
No. Every tool is built with performance in mind. Asset dequeuing blocks emojis, embeds, guest REST endpoints, and unused frontend dashicons to reduce the network load on your site.

= What taxonomies and relations does the columns manager support? =
The columns customizer supports standard custom field metadata, ACF Post Object/Relationship fields, and Custom Taxonomy Term fields. When selected as related posts or terms, values are resolved into direct click-through edit links and filter dropdowns are injected into the list table.

= How are WebP images delivered, and is it compatible with Apache and Nginx? =
Our plugin uses a database-first approach: it converts physical files to WebP and updates the attachment references directly in the database. This delivers static WebP URLs natively. Unlike other plugins, it does NOT require complex .htaccess (Apache) or nginx.conf (Nginx) rewrite rules, ensuring 100% compatibility across all server setups with zero performance overhead.

= What happens if I deactivate the plugin? =
Since images are permanently converted to WebP in the database, deactivating the plugin leaves WebP files active in your media library. If you want to keep original JPEGs/PNGs for safe backup, make sure the "Keep Original Images" setting is toggled on before running bulk optimization.

== Changelog ==

= 1.10.0 =
*   Added dynamic ACF Custom Field Extensions architecture. You can now drop PHP files into the `/includes/AcfExtensions/` directory and they will be automatically discovered, registered, and presented as toggleable checkboxes in the plugin's ACF settings tab.
*   Migrated the Gravity Forms Custom Field Fallback logic out of the core into the new dynamic extensions architecture (`acf-gravity-forms-fallback.php`).
*   Added Auto-Inject Video Poster Field toggle. Automatically injects a native ACF image field to the WordPress media editor for all video attachments, allowing you to easily assign fallback poster cover images to MP4 uploads.

= 1.9.0 =
*   Added SMTP & Mail Settings tab to securely configure outbound email credentials (Host, Port, User, Password, Encryption).
*   Integrated Mailpit Development Override, automatically routing all outbound emails to a local Mailpit instance (port 1025) when `WP_ENV` is set to `development`.
*   Implemented `SmtpManager` to seamlessly hook into `phpmailer_init` for advanced SMTP routing.

= 1.8.0 =
*   Added Multi-User Superadmin access control, allowing designated administrators to access the plugin settings while hiding it entirely from regular administrators.
*   Major upgrade to the Bulk Retroactive Image Optimizer: Added pagination, tabbed views (Optimized / Not Optimized), page size selector, and all-time total storage savings calculations.
*   Implemented Pause/Resume functionality for the Bulk Optimizer sequential engine to safely stop heavy processing.
*   Exposed the Bulk Optimizer as a dedicated submenu under the Media menu so all administrators can access it.
*   Added a dynamic "Widget Sniffer" to reliably detect and disable stubborn dashboard widgets (like Gravity Forms) by monitoring real dashboard loads.
*   Added option to disable the WordPress Command Palette (Cmd/Ctrl+K) for non-superadmins.
*   Improved robustness of admin bar cleanup and help tab hiding by utilizing later hooks and CSS fallbacks.

= 1.7.2 =
*   Added .htaccess Control & Hardening settings panel to configure root and uploads .htaccess rules.
*   Implemented root .htaccess protection for wp-config.php, user.ini, and .htaccess files, directory indexes block, XML-RPC block, author scans block, CORS headers, Gzip compression, and browser caching (mod_headers and mod_expires).
*   Implemented secondary uploads directory .htaccess to block direct execution of PHP files.
*   Developed robust fallback getHomePath() method to support Bedrock, WP-CLI, and subdirectory environments.
*   Integrated automatic cache-clearing for WP Super Cache, W3 Total Cache, WP Rocket, and SiteGround Optimizer on settings updates and image optimizations.

= 1.7.1 =
*   Fixed page transition script TypeErrors on push/replace navigations and page reloads.
*   Fixed CSS scoping selectors for view transitions by removing incorrect space selectors, enabling custom slide/swipe/wipe animations.
*   Fixed page transitions on static pages by adding singular 'page' class matching and ensuring global transition element names are always registered.
*   Scoped slide and swipe animations to the root view transition group to keep the header static while page content slides.

= 1.7.0 =
*   Added advanced performance optimization options in Various Settings and Gutenberg Control tabs.
*   Implemented Heartbeat API Control (Default, Disable Everywhere, Disable on Dashboard, Allow only on Post Edit Screen).
*   Implemented custom Heartbeat Frequency interval settings (15s to 120s).
*   Implemented Post Revisions Limit filter (Unlimited, Disable Revisions, 1-10 Revisions).
*   Implemented Custom Autosave Interval (60s to 300s), defined early to override WordPress core defaults.
*   Implemented Dequeue Core Gutenberg Block Styles globally on the frontend.
*   Updated admin dashboard pages to display brand header icons using WordPress Dashicons.

= 1.6.2 =
*   Added WPML Performance Optimization settings tab (available if WPML is active).
*   Implemented option to programmatically bypass WPML runtime theme ID adjustments.
*   Implemented option to suppress WPML canonical URL redirects during background AJAX transactions and REST API requests.
*   Optimized existing queries (Bulk Retroactive Image Optimizer and Media Folders) by adding suppress_filters to ignore language filter overhead.

= 1.6.1 =
*   Added Cross-Document Page Transitions module utilizing the native Web View Transitions API.
*   Implemented pre-defined transition animations (slide, swipe, wipe) with custom timing functions and standard CSS.
*   Added dynamic Page Transitions admin dashboard settings tab with toggle switch, override theme support, custom selector scoping, and custom CSS stylesheet editor.
*   Added a Custom Transition Rules Table allowing builders to map From/To transitions by Page Type or Custom URL path.
*   Integrated sessionStorage-based client-side routing state context passing.
*   Added Virtual Media Folders feature allowing hierarchical media library organization with a drag-and-drop category builder.

= 1.5.1 =
*   Fixed Advanced Custom Fields (ACF) layouts copy/paste functionality by implementing a synchronous queue tied to ACF's append hook.
*   Added compatibility check to disable conflicting plugin layout features when Advanced Custom Fields Extended (ACFE) is active.
*   Fixed CPT duplication where fields were copied as empty by switching from add_post_meta to direct database inserts ($wpdb->insert) and clearing cache.
*   Removed conflicting theme-level duplicate post actions from the functions.php file.

= 1.5.0 =
*   Added Premium Maintenance Mode feature allowing custom titles, messages, logo, and background images.
*   Fixed settings saving logic resetting options to null when submitting individual subpages/contexts.
*   Fixed post duplication functionality to ensure custom field metadata, taxonomy terms, password protection, and filtered content are cloned properly.
*   Enhanced ACF Copy & Paste Engine to manually initialize uninstantiated fields and fall back to direct DOM values, fixing empty blocks when pasting.
*   Added comprehensive unit test suite covering Maintenance Mode, settings sanitization, and duplication logic.

= 1.4.1 =
*   Added Virtual Cron (WP-Cron) disable toggle under the Various Settings tab.
*   Added dynamic system cron configuration instruction note with the site's exact cron command.

= 1.4.0 =
*   Added Gravity Forms integration tab (available if Gravity Forms is active) to disable default CSS, convert submit inputs to modern buttons, and customize submit button loading/sending feedback text.
*   Added WooCommerce Helpers & Extras setting card under WooCommerce tab.
*   Implemented "Buy Now" button direct-to-checkout option on single product pages.
*   Implemented automatic SKU redirects for URLs matching active product SKUs.
*   Implemented referer redirection for "add-to-cart" actions to remove URL query strings.
*   Implemented AJAX "View Cart" hide control for shop archive pages.
*   Implemented interactive Plus/Minus quantity buttons for product pages.
*   Implemented WooCommerce quantity input to select dropdown converter (up to 20 options).
*   Added support for WooCommerce Cart & Checkout Gutenberg exclusions.
*   Fixed settings serialization loss for WooCommerce and Gravity Forms settings keys in the Admin Settings panel.

= 1.3.0 =
*   Added WooCommerce Speed & Bloat settings tab (available if WooCommerce is active).
*   Implemented option to disable WooCommerce scripts and styles on non-WooCommerce pages.
*   Implemented cart fragments AJAX control (keep active, disable globally, or disable on non-shop pages).
*   Implemented options to disable WooCommerce block styles and customer password strength meter scripts.
*   Implemented WooCommerce Admin UI cleaning to remove marketing links and dashboard status/reviews widgets.

= 1.2.0 =
*   Reorganized Settings Dashboard to move advanced utilities to dedicated submenu subpages.
*   Moved Admin Menu Organizer to its own standalone submenu subpage.
*   Moved retroactive Bulk Image Optimizer sequential engine to its own standalone submenu subpage.
*   Added Interactive Media Library Optimization Status Table on the Bulk Optimizer page.
*   Implemented persistent database storage footprint tracking saving bytes saved to `_tka_image_savings` post meta.
*   Rendered high-fidelity type badges (slate JPEG, indigo PNG, success green WebP), status pills, and direct KB/MB savings columns.
*   Implemented real-time sequential client transitions updating badges, status, and savings dynamically with a smooth CSS success highlight flash animation upon completion.

= 1.1.1 =
*   Added ACF Flexible Content Visual Layout Selection Modal.
*   Replaced the native layout dropdown with a stunning, searchable modal overlay supporting visual previews and category tabs.
*   Implemented automated keyword-based and title-based fallback metadata generators (icons, descriptions, categories) in Javascript for new blocks.
*   Implemented dynamic PHP custom block metadata registry via filter `tka_acf_layout_modal_metadata`.
*   Implemented theme-level template header discovery scanning template directories for `.php` and `.blade.php` files to automatically extract block metadata from template comments natively via `get_file_data()`.
*   Fixed closed modal stickiness to the admin viewport footer.
*   Ensured clean dismissal of the background native ACF tooltip popup on modal close events (Escape key, backdrop click, close button) to prevent post editing session state leakages.

= 1.1.0 =
*   Added ACF Flexible Content Copy & Paste Engine.
*   Added multiselect bulk duplication controls with selection glow borders.
*   Implemented recursive field-tree mapping for complex ACF types (Repeaters, Groups, and Media fields).
*   Resolved Select2/AJAX option recreation and change event propagation for relational fields (Post Object, Taxonomy, Relationship).
*   Added ACF Integration Settings tab.
*   Added Custom Fields sidebar menu visibility controls (restricts access to developer only).
*   Added ACF shortcode strict disable option for security hardening.
*   Added theme-independent Local JSON option storing configs in `/wp-content/acf-json/`.

= 1.0.0 =
*   Initial Release.
*   Classic Editor and Classic Widgets toggles.
*   Safe SVG strict XML validator and interactive sandbox playground.
*   Obfuscate author links, email addresses, and REST user endpoints.
*   Hardening tweaks (Disable emojis, comment tools, guest REST APIs, feeds, embeds, version strings).
*   White-labeling suite (Custom login logo, CSS, toolbar replacements).
*   Dual-layout Admin Menu Organizer (Owner vs. Client lists).
*   Drag-and-drop sorting and post duplication hooks.
*   Fully redesigned Admin Columns Customizer with database selectors, drag-and-drop sortable rules, click-through post/taxonomy links, and top-bar query filters.
