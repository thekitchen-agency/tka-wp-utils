# Changelog

All notable changes to the **TKA Site Utilities** WordPress plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.18.2] - 2026-10-08

### Changed
- **Media Library Styling**:
  - Set `.media-toolbar` and `.attachments-browser .media-toolbar` CSS height to `68px !important` across all media library modal and grid interfaces.

## [1.18.1] - 2026-10-08

### Fixed
- **Virtual Media Folders & Media Grid Layout**:
  - Fixed images being hidden under the right attachment details sidebar by preserving WordPress Core's right sidebar boundary (`right: 300px`) and properly offsetting left margins.
  - Fixed "+ Add Folder" button displaying as a truncated circular button in the minimized/collapsed sidebar; now correctly hidden in collapsed state.
  - Prevented native browser image drag (`-webkit-user-drag: none`) from interfering with HTML5 media folder drag-and-drop.
  - Added robust dragover/drop event delegation and instant visual navigation when moving media files between folders.

## [1.18.0] - 2026-10-08

### Added & Enhanced
- **ACF Media & Video Content Support**:
  - Enhanced `AcfManager` video support to reliably permit `.mp4`, `.webm`, and `.mov` uploads and selection in ACF Gallery and Image fields.
  - Automatically bypasses ACF and WordPress false-positive upload errors when uploading video files through the media modal.
  - Added fallback support for AJAX media library queries (`ajax_query_attachments_args`) ensuring videos are selectable even when field key lookups miss.
  - Relaxed parameter type constraints on ACF validation filter callbacks (`acf/validate_value`, `acf/validate_rest_value`, `acf/validate_attachment`) to ensure complete PHP 8 compatibility without fatal TypeErrors.

## [1.17.0] - 2026-10-08

### Added
- **Interactive Media Focal Point Selector**:
  - Adds an interactive visual reticle picker to the WordPress Media Library and ACF image fields.
  - Allows editors to specify exact focal point coordinates (X% and Y%) with quick preset buttons (Face, Center).
  - Automatically calculates and provides CSS `object-position` rules for responsive cover cropping.
  - Integrates seamlessly with ACF Image format values (`$image['focal_point']`), REST API attachment schemas, and `wp_get_attachment_image_attributes`.
  - Added global helper functions: `get_attachment_focal_point(int $attachment_id)` and `get_attachment_focal_point_style(int $attachment_id)`.
  - Added toggle setting under **Settings -> Media** (`Enable Media Focal Point`).

## [1.16.1] - 2026-09-26

### Fixed
- **Virtual Media Folders**:
  - Resolved batch upload folder assignment ensuring all files in a multi-file upload batch are assigned directly to the selected folder on the server.
  - Enabled multi-item drag and drop organization into and out of folders in standard and bulk select views.
  - Fixed media deletion issues after navigating to "All Files", supporting proper library re-queries, media trash, and real-time sidebar count updates.
- **Admin Columns**: Resolved ACF gallery and image thumbnail preview rendering in custom columns.
- **SMTP**: Fixed SMTP From Email and From Name header and envelope filters.

## [1.16.0] - 2026-07-30

### Added
- **ACF Dynamic Table Field**: Introduced custom ACF table field type featuring locked rows/columns, prepopulated data, multi-level headers, and custom styling.
- **ACF SVG Picker Enhancements**: Enhanced grid layout, search padding, custom card sizes, dark background preview, and hover animations.
- **Virtual Media Folders Custom Tables**: Dedicated database tables for virtual folders avoiding term pollution and speeding up media queries.
- **Custom Confirmation Modal**: Replaced browser-native confirm dialogs with an accessible, branded modal.
- **Single Regeneration Actions**: Direct WebP/image regeneration buttons in media list and edit screens with settings-hash validation.

## [1.15.1] - 2026-07-15

### Added
- SVG Picker Field type for ACF.

## [1.15.0] - 2026-07-10

### Added
- ACF Flexible Content Layout Location Rules extension for restricting block layouts by post type, page template, parent, and user role.
