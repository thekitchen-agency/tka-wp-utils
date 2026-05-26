# TKA WP Utils

[![WordPress Plugin Directory](https://img.shields.io/badge/WordPress.org-Plugin-blue.svg?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/tka-wp-utils/)
[![WordPress Version Requirement](https://img.shields.io/badge/WordPress-6.0%2B-indigo.svg?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP Version Requirement](https://img.shields.io/badge/PHP-8.3-teal.svg?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive, premium suite of utility tools designed for developers and agencies to secure, white-label, customize, and optimize the WordPress administration dashboard.

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
*   **Admin Menu Organizer**: Rearrange, hide, or sort dashboard sidebar menus. Features a split-view drag-and-drop builder to save separate visible layouts for the installer (owner) and client administrators.

### ⚙️ 4. Content Flow & Columns Manager
*   **Drag-and-Drop Sorting**: Reorder post, page, or custom post type listings manually in admin list tables.
*   **One-Click Duplication**: Instantly duplicate any content item to a new Draft via list table row actions.
*   **Redesigned Admin Columns Customizer**: An advanced card-based manager featuring:
    *   **Drag-and-Drop Order Reordering**: Instantly slide columns to adjust display sequences.
    *   **Database Key Selector**: Automatically populates distinct metadata keys from the database, with a fallback text input for custom entries.
    *   **Relational Linking**: Define if a column contains a *Related Post* or a *Taxonomy Term*. The customizer resolves raw IDs/objects into direct edit page links.
    *   **Toolbar Filters**: Injects matching dropdown filters at the top of the post list tables to filter entries by selected relations.

---

## Directory Structure

```text
tka-wp-utils/
├── admin/
│   ├── css/
│   │   └── admin-style.css       # Premium Slate/Indigo Dashboard styles
│   └── js/
│       ├── admin-columns.js      # Column Customizer and sorting scripts
│       ├── admin-order.js        # Drag-and-drop content ordering scripts
│       └── admin-script.js       # Admin Sandbox and Menu Organizer handling
├── includes/
│   ├── Admin/
│   │   └── Settings.php          # Settings coordinators, registration & markup
│   ├── Core/
│   │   └── Plugin.php            # Main Singleton COORDINATOR
│   └── Features/                 # 10 core feature classes
│       ├── AdminColumns.php
│       ├── AdminInterface.php
│       ├── ClassicEditor.php
│       ├── ClassicWidgets.php
│       ├── ContentDuplicate.php
│       ├── ContentOrder.php
│       ├── GutenbergManager.php
│       ├── SecurityManager.php
│       ├── SvgValidator.php
│       └── VariousCleaner.php
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

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
