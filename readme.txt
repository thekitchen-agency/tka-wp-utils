=== TKA WP Utils ===
Contributors: thekitchen-agency
Tags: classic editor, svg upload, admin columns, menu organizer, hardening
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
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
*   **Admin Menu Organizer**: Rearrange sidebar menus with a drag-and-drop builder. Configure separate visibility rules for the website owner (installer) and client administrators.
*   **Drag & Drop Content Ordering**: Enables manual drag-and-drop row sorting in post list tables.
*   **One-Click Duplication**: Clone any post, page, or custom post type into a new draft in one click.
*   **Redesigned Admin Columns Customizer**: Customize column layouts for post type lists. Features drag-and-drop sortable configurations, database meta key selectors, click-through relational links for Related Posts and Custom Taxonomy Terms, and matching top-bar filters.

== Installation ==

1. Upload the `tka-wp-utils` folder to your `/wp-content/plugins/` directory, or upload the ZIP file directly via the WordPress admin panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **TKA WP Utils** admin panel in your sidebar to configure and enable settings.

== Frequently Asked Questions ==

= Does this plugin affect performance? =
No. Every tool is built with performance in mind. Asset dequeuing blocks emojis, embeds, guest REST endpoints, and unused frontend dashicons to reduce the network load on your site.

= What taxonomies and relations does the columns manager support? =
The columns customizer supports standard custom field metadata, ACF Post Object/Relationship fields, and Custom Taxonomy Term fields. When selected as related posts or terms, values are resolved into direct click-through edit links and filter dropdowns are injected into the list table.

== Changelog ==

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
