# TKA WP Utils

[![WordPress Plugin Directory](https://img.shields.io/badge/WordPress.org-Plugin-blue.svg?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/tka-wp-utils/)
[![WordPress Version Requirement](https://img.shields.io/badge/WordPress-6.0%2B-indigo.svg?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP Version Requirement](https://img.shields.io/badge/PHP-8.3-teal.svg?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive suite of utility tools designed for developers and agencies to secure, white-label, customize, and optimize the WordPress administration dashboard.

---

## Key Modules & Features

### 🛡️ 1. Security & Hardening
*   **Strict SVG XML Sanitization**: Securely upload SVG vector graphics. The plugin runs structural XML analysis upon upload to block XML External Entity (XXE) and Cross-Site Scripting (XSS) injection vectors. Includes a visual Sandbox Playground for developers to inspect SVGs before upload.
*   **Obfuscate Author URLs & REST User Slugs**: Hide author slugs in public archives and guest REST API endpoints (`/wp-json/wp/v2/users`) to protect against user enumeration brute-force attacks.
*   **Email Obfuscation**: Scans post, page, and widget contents to convert email addresses into randomized decimal/hexadecimal HTML entities, shielding them from automatic scraping bots.
*   **Hardening Cleanups**: Block guest REST endpoints, feeds, emojis, embeds, XML-RPC, trackbacks, pingbacks, front-end dashicons, disable virtual cron (WP-Cron) with system-level setup guides, and WP generator version metadata enqueues.

### 🎨 2. Agency White-Labeling
*   ** wp-login.php Rebranding**: Rebrand the default login screen with a custom logo and login page CSS (backgrounds, buttons, brand identity overrides).
*   **Admin Bar Replacing**: Replace the default WordPress logo in the top toolbar with a custom small logo (transparent square brand icons work best).
*   **Footer Cleanups**: Remove standard WordPress branding strings and core version metadata blocks from the dashboard footer area.

### 🗂️ 3. Navigation & Dashboard Control
*   **Granular Gutenberg Editor Control**: Globally disable the Gutenberg Block Editor, allow it everywhere, or selectively activate/deactivate it per public post type.
*   **Classic Experience Restored**: Revert post/page creation back to the Classic rich text (TinyMCE) editor and restore the traditional widgets dashboard.
*   **Dedicated Admin Menu Organizer Subpage**: Moved to a standalone submenu page (`tka-wp-utils-menu-organizer`) with a split-view drag-and-drop builder to easily rearrange, hide, and sort sidebar menus.
*   **Multi-User Superadmin Access Control**: Grant full plugin configuration access to designated administrators, while hiding the plugin entirely from regular administrators.
*   **Dynamic Widget Sniffer**: Reliably intercepts and hides stubborn third-party dashboard widgets (like Gravity Forms) by monitoring and recording widgets during actual dashboard visits.

### 🔄 4. Content Ordering
*   **Drag & Drop Sorting**: Enables intuitive, manual drag-and-drop sorting natively within the WordPress list tables.
*   **Targeted Activation**: Explicitly select exactly which Custom Post Types and Taxonomies (like Categories, Tags, and ACF Taxonomies) should have drag-and-drop sorting enabled.
*   **Granular Control**: Does not blindly override the entire website—only the checked Post Types and Taxonomies will be sortable.

### ⚙️ 5. Content Flow & Columns Manager
*   **One-Click Duplication**: Instantly duplicate any content item to a new Draft via list table row actions.
*   **Redesigned Admin Columns Customizer**: An advanced card-based manager featuring:
    *   **Drag-and-Drop Order Reordering**: Instantly slide columns to adjust display sequences.
    *   **Database Key Selector**: Automatically populates distinct metadata keys from the database, with a fallback text input for custom entries.
    *   **Relational Linking**: Define if a column contains a *Related Post* or a *Taxonomy Term*. The customizer resolves raw IDs/objects into direct edit page links.
    *   **Toolbar Filters**: Injects matching dropdown filters at the top of the post list tables to filter entries by selected relations.

### 🔌 6. ACF Integration & Copy/Paste Engine
*   **ACF Flexible Content Visual Layout Selection Modal**: Replaces the standard narrow ACF layout selector dropdown list with a gorgeous, searchable, and category-filtered grid modal showing block descriptions, custom icons, and theme screenshot previews.
*   **Dynamic Theme-Agnostic Discovery**: Automatically scans active theme block template directories (supporting both PHP and Blade templates) and dynamically extracts custom block metadata (Title, Category, Icon, Description) from template file comment headers natively using `get_file_data()`.
*   **Automated Fallback Generation**: Features automatic title-based and keyword-based metadata fallback generators for any new custom block layout, allowing zero-configuration setup for any third-party block theme.
*   **ACF Flexible Content Copy/Paste**: Duplicate layouts inside WordPress edit screens, storing serialized layout structures inside browser `localStorage` to copy-and-paste across different posts and pages.
*   **Deep Recursive Mapping**: Fully serializes and repopulates nested field elements including standard text inputs, WYSIWYG visual editors, checkboxes, and radio lists.
*   **Nested Repeater & Group Fields Support**: Automatically tracks nested arrays and subfield hierarchies inside Repeaters and Groups. Appends required repeater rows dynamically and sequentially before population.
*   **Complex Media Uploader Integrity**: Preserves and restores visual preview containers, cancellation hooks, and dynamic thumbnail links for Image, File, and Gallery ACF uploader fields, re-indexing name attributes securely.
*   **Relational & Select2 Options Reconstitution**: Recreates dynamic selected option elements for AJAX-loaded Select2 select boxes (including Post Object, Taxonomy, and Relationship fields) to ensure selections render correctly in the visual interface.
*   **Visually Styled Highlights**: Features HSL tailored border glow highlighting (`.tka-layout-selected`) and visual check states during multiselect bulk operations.
*   **Sidebar Visibility & Shortcode Hardening**: Restricts sidebar Custom Fields editor access to the developer/installer only, and strictly disables front-end shortcode execution for robust security hardening.
*   **Local JSON Shared Storage**: Integrates with ACF to automatically direct ACF local JSON configurations into the theme-independent `/wp-content/acf-json/` directory.
*   **Dynamic Custom Field Extensions Engine**: Drop any PHP file into the `/includes/AcfExtensions/` directory with a valid header comment, and the plugin will automatically discover and present it as a toggleable checkbox in the ACF settings tab. Includes a built-in "Gravity Forms Field Fallback" extension out-of-the-box.
*   **Auto-Inject Video Poster Field**: Optionally registers a "Video Poster Image" (`video_poster_image`) ACF field directly onto all video attachments in the native Media Library, making it incredibly simple to assign fallback cover images to MP4 uploads.

### 🖼️ 6. Image Optimization & WebP Engine
*   **Automatic WebP Conversion**: Converts newly uploaded JPEG and PNG images into modern WebP format, generating WebP sub-sizes automatically to ensure lightning-fast site loading.
*   **Original Image Retention & Compression**: Gives developers the flexibility to delete original uploads or keep compressed JPEG/PNG originals. Intercepts files on upload and compresses them in-place using customizable quality standards.
*   **Custom Compression Quality Slider**: Sleek range slider enqueued into the dashboard settings to let developers choose the target compression ratio (e.g. 75% or 82%).
*   **Dedicated Bulk Optimizer Subpage**: Features an advanced sequential batch retroactive image optimizer page (`tka-wp-utils-bulk-optimizer`) that processes existing media assets safely to prevent gateway timeouts. Includes pagination, tabbed views, page size selectors, and pause/resume capabilities. Also available as a shortcut under the Media menu for regular administrators.
*   **Interactive Media Library Status Table**: Displays a real-time responsive dashboard listing all JPEGs, PNGs, and WebPs in the Media Library. Shows format badges, optimized status pills, and direct database-backed all-time total size savings metadata (`_tka_image_savings`). Rows transition dynamically in real-time with visual CSS success flash highlight animations upon process completion.

### 🛒 7. WooCommerce Settings & Helpers
*   **Scripts & Styles Optimization**: Dequeue heavy WooCommerce scripts and styles on pages that aren't shop pages, cart, checkout, or account pages.
*   **AJAX Cart Fragments Control**: Disable or selectively defer the resource-intensive `/?wc-ajax=get_refreshed_fragments` AJAX requests. Choose between globally disabling them, keeping them active, or disabling them on non-shop pages only.
*   **Block Styles Suppression**: Disable WooCommerce Gutenberg blocks styling sheets (`wc-blocks-style.css`) enqueued on the frontend.
*   **Password Strength Meter Removal**: Dequeue `wc-password-strength-meter` and heavy `zxcvbn.min.js` scripts to speed up checkout and accounts creation pages.
*   **Admin UI Cleanup**: Hide marketing hub submenus, dashboard status widgets, and remove marketplace extension recommendations and nags.
*   **Direct "Buy Now" Checkout Button**: Direct checkout button on single product pages, bypassing the cart page (`/checkout-link/?products=ID:QTY`).
*   **SKU Slug URL Redirects**: Automatically redirects requests that match a product SKU in the URL (e.g. `/your-sku/`) to that product page.
*   **Redirection Parameters Cleanup**: Removes URL parameters on add-to-cart by returning to referer.
*   **AJAX View Cart Suppressor**: Hides AJAX-injected secondary "View Cart" links on the shop archive page.
*   **Quantity Selector Overrides**: Plus/Minus interactive buttons around numeric inputs, or full conversion to select dropdowns (max limit 20).

### 📝 8. Gravity Forms Integrations & Enhancements
*   **Markup Cleanup**: Convert standard submit input tags to semantic HTML5 `<button type="submit">` tags for better flex/grid layout control.
*   **Stylesheet Suppressor**: Disable default Gravity Forms CSS styles completely to make custom styling with frameworks (Tailwind, bootstrap) easier.
*   **Submit Loading Feedback**: Set up custom loading/sending text feedback upon button clicks to prevent double clicks and double submissions.

### 🛠️ 9. Premium Maintenance Mode
*   **Seamless Site Suspension**: Take the site offline temporarily for scheduled maintenance while serving a beautiful glassmorphic dark-themed screen.
*   **HTTP 503 SEO Integrity**: Responds with a proper `503 Service Unavailable` status and `Retry-After` HTTP headers to protect search engine indexes.
*   **Bypasses**: Bypasses logged-in administrators (users with `manage_options`), the login/registration pages, REST requests, and XML-RPC.
*   **Customization**: Configurable page titles, description messages, custom logo, and full-screen background image uploads.

### 🔄 10. Cross-Document Page Transitions
*   **Native View Transitions API**: Leverage modern cross-document view transitions for smooth page loads with zero layout shifts.
*   **Pre-defined Animations**: Slide, swipe, and wipe animations with CSS keyframe custom timings and standard easing functions.
*   **Dynamic Rules Engine**: Select transition rules dynamically using a From/To page type mapping (e.g. Front Page to Blog Page) or custom URI patterns.
*   **Custom CSS Stylesheet Editor**: Live stylesheet block rendered directly in the `<head>` of the page for customizing target transition animations (e.g., configuring `view-transition-name` on elements).

### 📁 11. Media Library Enhancements
*   **Replace Media File**: Seamlessly overwrite images and PDFs with a new upload directly from the Media Library while keeping the exact same URL and attachment ID. Includes automatic browser cache-busting.
*   **Smart WebP Exception**: When replacing a `.webp` image, the plugin allows `.jpg` or `.png` uploads, silently converting them to WebP in the background and replacing the original file automatically.
*   **Virtual Media Folders**: Organize files in the Media Library using a nested virtual folders system without altering physical filesystem paths.
*   **Drag-and-Drop Sidebar Interface**: Drag attachments into folders and drag folders to reorganize or nest them inside the media library grid browser and selection modals.
*   **AJAX-Driven Operations**: Fully non-blocking AJAX actions for folder creation, renaming, deletion, and attachment reassignment.

### 🚀 12. WPML Performance Optimization
*   **Theme ID Adjustments Override**: Disable default "Adjust IDs for multilingual functionality" runtime translations programmatically. This reduces database SELECT operations on page loads.
*   **Canonical Redirect Suppression**: Suppresses URL canonical redirection during background REST API queries and AJAX transactions.
*   **Query Suppression**: Automatic query parameters injection (`suppress_filters => true`) on background operations to bypass language filters, speeding up retrieval operations (like Retroactive Image Optimizer and Media Folder counts).

### ⚡ 13. Advanced Resource Optimizations
*   **Heartbeat API Control**: Optimize server performance by rate-limiting or completely disabling the WordPress Heartbeat API background AJAX operations (Disable Everywhere, Disable on Dashboard, or Allow only on Post Edit Screen). Customize request interval frequencies (15s to 120s).
*   **Post Revisions & Autosave Control**: Reduce database growth and bloat by programmatically setting a cap on post revisions (Unlimited, Disabled, or 1-10 Revisions). Adjust the autosave interval frequency (60s to 300s) early in the plugin lifecycle to override WordPress defaults.
*   **Gutenberg Stylesheet Dequeuer**: Dequeue the heavy core block editor stylesheets (`wp-block-library.css` and `wp-block-library-theme.css`) globally on the frontend when Gutenberg is disabled or block elements are not used.

### 🌐 14. .htaccess Control & Hardening
*   **Root .htaccess Customizations**: Enable directory browsing block, deny direct access to `wp-config.php`, `user.ini`, and `.htaccess` files, XML-RPC block, author scans block, CORS headers, Gzip compression, and browser caching (mod_headers and mod_expires rules).
*   **Subdirectory Protection**: Automatically places/removes a secondary `.htaccess` file inside `wp-content/uploads/` to deny execution of `.php` and similar executable scripts.
*   **Cache-Clearing Integration**: Purges popular caching plugins (WP Super Cache, W3 Total Cache, WP Rocket, SiteGround Optimizer) when saving configuration settings or running bulk image optimizations.

### 📧 15. SMTP & Email Delivery
*   **Custom SMTP Configuration**: Configure reliable outbound email delivery by supplying custom SMTP credentials (Host, Port, Username, Password, Encryption).
*   **Mailpit Developer Mode**: Automatically intercepts and routes all outgoing emails to a local Mailpit instance (port 1025) whenever the WordPress environment is running in development mode, preventing accidental client emails during local testing.

### 🗄️ 16. Database Maintenance
*   **Database Cleanup Suite**: Clean up post revisions, auto-drafts, trashed posts, spam/trashed comments, orphaned post/comment metadata, and expired transients with real-time database counters.
*   **Engine Optimization**: Perform native MySQL `OPTIMIZE TABLE` commands across all WordPress tables.
*   **High-Performance Indexing**: Toggle a custom compound `idx_tka_meta_key_value` PostMeta index to dramatically speed up complex metadata queries on large sites.
*   **Native Search & Replace**: A powerful Search and Replace GUI right in your dashboard with an optimized horizontal layout.
*   **WP-CLI Powered Engine**: Safely leverages WP-CLI `search-replace` in the background for reliable database operations without memory limit crashes.
*   **Dry Run Support**: Test your replacements before committing them to the database.
*   **Clean HTML Logging**: Outputs precise `--report-changed-only` tables natively parsed from raw WP-CLI output to show you exactly which tables were modified, stripping out noise and empty tables.

### 🔀 17. Routing & Redirects
*   **Ultra-Lightweight URL Redirects**: A native, blazing-fast redirect manager designed to replace heavy third-party redirection plugins.
*   **Parse Request Engine**: Redirects are hooked at the `parse_request` level, firing before WordPress loads any theme files or heavy database queries, and ensuring perfect compatibility across both Apache and Nginx servers.
*   **Client-Friendly Repeater UI**: Accessible as a dedicated top-level menu for all Administrators, featuring a visual drag-and-drop-style repeater UI (styled identically to the TKA settings) to easily add and remove redirects.
*   **Wildcard Support**: Add asterisks (`*`) to instantly map entire directories or parameter structures to new destinations.

---

## Directory Structure

```text
tka-wp-utils/
├── admin/
│   ├── css/
│   │   ├── acf-copy-paste.css    # Layout highlights & custom checkboxes
│   │   ├── acf-layout-modal.css  # Visual layout selector modal styles
│   │   ├── admin-style.css       # Slate/Indigo Dashboard styles
│   │   ├── media-folders.css     # Virtual media folders layout styles
│   │   ├── view-transition-animation-slide.css  # Slide transition keyframes
│   │   ├── view-transition-animation-swipe.css  # Swipe transition keyframes
│   │   └── view-transition-animation-wipe.css   # Wipe transition keyframes
│   └── js/
│       ├── acf-copy-paste.js     # ACF layout copy-paste & Select2 engine
│       ├── acf-layout-modal.js   # Visual layout selector modal & comment parser script
│       ├── admin-columns.js      # Column Customizer and sorting scripts
│       ├── admin-order.js        # Drag-and-drop content ordering scripts
│       ├── admin-script.js       # Admin Sandbox and Menu Organizer handling
│       ├── media-folders.js      # Media folders sidebar drag-and-drop controller
│       └── page-transitions.js   # Client-side router and transition rules evaluator
├── includes/
│   ├── Admin/
│   │   └── Settings.php          # Settings coordinators, registration & markup
│   ├── Core/
│   │   └── Plugin.php            # Main Singleton COORDINATOR
│   ├── Features/                 # 17 core feature classes
│   │   ├── AcfManager.php        # ACF controls & asset loader
│   │   ├── AdminColumns.php
│   │   ├── AdminInterface.php
│   │   ├── ClassicEditor.php
│   │   ├── ClassicWidgets.php
│   │   ├── ContentDuplicate.php
│   │   ├── ContentOrder.php
│   │   ├── GravityFormsManager.php # Gravity Forms enhancements
│   │   ├── GutenbergManager.php
│   │   ├── HeartbeatRevisionManager.php     # Heartbeat API & Post Revisions optimizer class
│   │   ├── HtaccessManager.php              # Root & uploads .htaccess manager class
│   │   ├── ImageOptimizer.php
│   │   ├── MediaFolders.php                     # Virtual media folders manager class
│   │   ├── PageTransitionAnimation.php          # Value object representing a transition animation
│   │   ├── PageTransitionAnimationRegistry.php  # Registry of predefined/custom animations
│   │   ├── PageTransitions.php                  # Page transitions manager class
│   │   ├── SecurityManager.php
│   │   ├── SmtpManager.php                      # Custom SMTP & Mailpit override class
│   │   ├── SvgValidator.php
│   │   ├── MaintenanceMode.php
│   │   ├── VariousCleaner.php
│   │   ├── WooCommerceManager.php
│   │   └── WpmlOptimizer.php                    # WPML performance optimization feature class
│   └── pluggables.php            # Pluggable function overrides (WooCommerce templates)
├── LICENSE                       # MIT License
├── README.md                     # GitHub Developer Guide
├── readme.txt                    # Official WordPress.org Readme
└── tka-wp-utils.php              # Plugin Entry point & PSR-4 autoloader
```

---

## Installation

1. Clone or download the repository into your `/wp-content/plugins/tka-wp-utils` directory:
   ```bash
   git clone git@github.com:thekitchen-agency/tka-wp-utils.git
   ```
2. Activate the plugin via the **Plugins** dashboard in WordPress.
3. Access configuration tools through the **TKA WP Utils** menu in the admin sidebar.

## Local Development & Setup

This plugin uses standard WordPress enqueues and native client scripts (jQuery, jQuery UI Sortable).
*   **Requirements**: PHP 8.3+, WordPress 6.0+
*   **Autoloading**: Utilizes a PSR-4 compliant autoloader registered inside `tka-wp-utils.php`. Ensure namespace naming structures correspond to folders inside `includes/`.

---

## Architectural Notice: Native WebP Delivery vs. Rewrite Rules

This plugin uses a **database-first static delivery approach** for WebP conversion rather than dynamic server rewrites:

### Why We Do It This Way
* **Perfect Cross-Server Compatibility**: Unlike plugins that inject custom `.htaccess` (Apache) or `nginx.conf` (Nginx) rules, our optimizer writes the `.webp` mime type directly to the WordPress attachment records. This guarantees that images deliver flawlessly across Nginx, Apache, or Litespeed servers with **zero custom configurations**.
* **Zero Performance Overhead**: Browsers load the static `.webp` files directly. The server does not need to intercept each static request, parse browser headers, or perform filesystem negotiations.
* **Modern Standard Ready**: With modern WebP browser support over 97%, legacy non-WebP fallback systems are no longer necessary.

### Developer Consequences & Reversibility
* **Permanent Database Changes**: When JPEGs/PNGs are optimized, their database references (`guid`, `post_mime_type`, `_wp_attached_file`, and metadata) are updated to the `.webp` version. 
* **Uninstallation Note**: If this plugin is deactivated, the images in the database remain `.webp`. They are not automatically reverted to JPEGs/PNGs.
* **Keep Originals Toggle**: To safeguard original files, ensure the "Keep Original Images" setting is toggled on. The original uncompressed files will remain on disk under their respective directories.

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
