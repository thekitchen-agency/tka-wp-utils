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
*   **Hardening Cleanups**: Block guest REST endpoints, feeds, emojis, embeds, XML-RPC, trackbacks, pingbacks, front-end dashicons, and WP generator version metadata enqueues.

### 🎨 2. Agency White-Labeling
*   ** wp-login.php Rebranding**: Rebrand the default login screen with a custom logo and login page CSS (backgrounds, buttons, brand identity overrides).
*   **Admin Bar Replacing**: Replace the default WordPress logo in the top toolbar with a custom small logo (transparent square brand icons work best).
*   **Footer Cleanups**: Remove standard WordPress branding strings and core version metadata blocks from the dashboard footer area.

### 🗂️ 3. Navigation & Dashboard Control
*   **Granular Gutenberg Editor Control**: Globally disable the Gutenberg Block Editor, allow it everywhere, or selectively activate/deactivate it per public post type.
*   **Classic Experience Restored**: Revert post/page creation back to the Classic rich text (TinyMCE) editor and restore the traditional widgets dashboard.
*   **Dedicated Admin Menu Organizer Subpage**: Moved to a standalone submenu page (`tka-wp-utils-menu-organizer`) with a split-view drag-and-drop builder to easily rearrange, hide, and sort sidebar menus for owner (installer) and client administrators separately.

### ⚙️ 4. Content Flow & Columns Manager
*   **Drag-and-Drop Sorting**: Reorder post, page, or custom post type listings manually in admin list tables.
*   **One-Click Duplication**: Instantly duplicate any content item to a new Draft via list table row actions.
*   **Redesigned Admin Columns Customizer**: An advanced card-based manager featuring:
    *   **Drag-and-Drop Order Reordering**: Instantly slide columns to adjust display sequences.
    *   **Database Key Selector**: Automatically populates distinct metadata keys from the database, with a fallback text input for custom entries.
    *   **Relational Linking**: Define if a column contains a *Related Post* or a *Taxonomy Term*. The customizer resolves raw IDs/objects into direct edit page links.
    *   **Toolbar Filters**: Injects matching dropdown filters at the top of the post list tables to filter entries by selected relations.

### 🔌 5. ACF Integration & Copy/Paste Engine
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

### 🖼️ 6. Image Optimization & WebP Engine
*   **Automatic WebP Conversion**: Converts newly uploaded JPEG and PNG images into modern WebP format, generating WebP sub-sizes automatically to ensure lightning-fast site loading.
*   **Original Image Retention & Compression**: Gives developers the flexibility to delete original uploads or keep compressed JPEG/PNG originals. Intercepts files on upload and compresses them in-place using customizable quality standards.
*   **Custom Compression Quality Slider**: Sleek range slider enqueued into the dashboard settings to let developers choose the target compression ratio (e.g. 75% or 82%).
*   **Dedicated Bulk Optimizer Subpage**: Features an advanced sequential batch retroactive image optimizer page (`tka-wp-utils-bulk-optimizer`) that processes existing media assets safely to prevent gateway timeouts.
*   **Interactive Media Library Status Table**: Displays a real-time responsive dashboard listing all JPEGs, PNGs, and WebPs in the Media Library. Shows format badges, optimized status pills, and direct database-backed size savings metadata (`_tka_image_savings`). Rows transition dynamically in real-time with visual CSS success flash highlight animations upon process completion.

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

---

## Directory Structure

```text
tka-wp-utils/
├── admin/
│   ├── css/
│   │   ├── acf-copy-paste.css    # Layout highlights & custom checkboxes
│   │   ├── acf-layout-modal.css  # Visual layout selector modal styles
│   │   └── admin-style.css       # Slate/Indigo Dashboard styles
│   └── js/
│       ├── acf-copy-paste.js     # ACF layout copy-paste & Select2 engine
│       ├── acf-layout-modal.js   # Visual layout selector modal & comment parser script
│       ├── admin-columns.js      # Column Customizer and sorting scripts
│       ├── admin-order.js        # Drag-and-drop content ordering scripts
│       └── admin-script.js       # Admin Sandbox and Menu Organizer handling
├── includes/
│   ├── Admin/
│   │   └── Settings.php          # Settings coordinators, registration & markup
│   ├── Core/
│   │   └── Plugin.php            # Main Singleton COORDINATOR
│   ├── Features/                 # 13 core feature classes
│   │   ├── AcfManager.php        # ACF controls & asset loader
│   │   ├── AdminColumns.php
│   │   ├── AdminInterface.php
│   │   ├── ClassicEditor.php
│   │   ├── ClassicWidgets.php
│   │   ├── ContentDuplicate.php
│   │   ├── ContentOrder.php
│   │   ├── GravityFormsManager.php # Gravity Forms enhancements
│   │   ├── GutenbergManager.php
│   │   ├── ImageOptimizer.php
│   │   ├── SecurityManager.php
│   │   ├── SvgValidator.php
│   │   ├── VariousCleaner.php
│   │   └── WooCommerceManager.php
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
