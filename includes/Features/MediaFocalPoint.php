<?php

namespace TKA\WPUtils\Features;

/**
 * Interactive Media Focal Point Selector & CSS Object-Position Provider.
 */
class MediaFocalPoint
{
    public const META_KEY_X = '_focal_point_x';
    public const META_KEY_Y = '_focal_point_y';
    public const META_KEY_COMPOSITE = '_focal_point';

    /**
     * Register hooks.
     */
    public function hook(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_filter('attachment_fields_to_edit', [$this, 'renderAttachmentField'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'saveAttachmentField'], 10, 2);
        add_filter('wp_prepare_attachment_for_js', [$this, 'prepareAttachmentForJs'], 10, 3);
        add_filter('wp_get_attachment_image_attributes', [$this, 'filterImageAttributes'], 10, 3);
        add_filter('acf/format_value/type=image', [$this, 'formatAcfImageValue'], 20, 3);
        add_action('rest_api_init', [$this, 'registerRestFields']);
    }

    /**
     * Enqueue picker scripts and styles in WP Admin.
     */
    public function enqueueAdminAssets(string $hook): void
    {
        $version = defined('TKA_SITE_UTILITIES_VERSION') ? TKA_SITE_UTILITIES_VERSION : '1.0.0';
        $url = defined('TKA_SITE_UTILITIES_URL') ? TKA_SITE_UTILITIES_URL : plugin_dir_url(__DIR__ . '/../../tka-site-utilities.php');

        wp_enqueue_style(
            'tka-media-focal-point-admin',
            $url . 'admin/css/focal-point.css',
            [],
            $version
        );

        wp_enqueue_script(
            'tka-media-focal-point-admin',
            $url . 'admin/js/focal-point.js',
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Render the interactive focal point UI in attachment fields.
     */
    public function renderAttachmentField(array $form_fields, \WP_Post $post): array
    {
        $mime = get_post_mime_type($post->ID) ?: '';

        // Only display for raster images (not SVGs, audio, or video)
        if (!str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            return $form_fields;
        }

        $focal = self::getFocalPoint($post->ID);
        $x = $focal['x'];
        $y = $focal['y'];

        $imageUrl = wp_get_attachment_image_url($post->ID, 'medium') ?: wp_get_attachment_url($post->ID);
        if (!$imageUrl) {
            return $form_fields;
        }

        ob_start();
        ?>
        <div class="focal-point-field-wrapper" data-attachment-id="<?php echo esc_attr($post->ID); ?>">
            <div class="focal-point-picker" tabindex="0" role="slider" aria-label="<?php esc_attr_e('Image Focal Point', 'tka-site-utilities'); ?>" aria-valuenow="<?php echo esc_attr("{$x}, {$y}"); ?>">
                <img src="<?php echo esc_url($imageUrl); ?>" alt="" class="focal-point-image" />
                <div class="focal-point-reticle" style="left: <?php echo esc_attr($x); ?>%; top: <?php echo esc_attr($y); ?>%;">
                    <span class="focal-point-crosshair-h"></span>
                </div>
            </div>

            <!-- Hidden inputs synced to WordPress Backbone/AJAX save handler -->
            <input type="hidden" class="focal-point-input-x" name="attachments[<?php echo esc_attr($post->ID); ?>][focal_point_x]" value="<?php echo esc_attr($x); ?>" />
            <input type="hidden" class="focal-point-input-y" name="attachments[<?php echo esc_attr($post->ID); ?>][focal_point_y]" value="<?php echo esc_attr($y); ?>" />

            <div class="focal-point-controls">
                <span class="focal-point-coords-badge">
                    <span>X: <strong class="coord-x"><?php echo esc_html($x); ?>%</strong></span>
                    <span>·</span>
                    <span>Y: <strong class="coord-y"><?php echo esc_html($y); ?>%</strong></span>
                </span>

                <div class="focal-point-presets">
                    <button type="button" class="focal-point-btn" data-preset-x="50" data-preset-y="20" title="<?php esc_attr_e('Face / Top Center', 'tka-site-utilities'); ?>">
                        <?php esc_html_e('Face', 'tka-site-utilities'); ?>
                    </button>
                    <button type="button" class="focal-point-btn" data-preset-x="50" data-preset-y="50" title="<?php esc_attr_e('Center', 'tka-site-utilities'); ?>">
                        <?php esc_html_e('Center', 'tka-site-utilities'); ?>
                    </button>
                    <button type="button" class="focal-point-btn is-reset" data-preset-x="50" data-preset-y="50" title="<?php esc_attr_e('Reset to Default', 'tka-site-utilities'); ?>">
                        <?php esc_html_e('Reset', 'tka-site-utilities'); ?>
                    </button>
                </div>
            </div>
            <span class="focal-point-help-text">
                <?php esc_html_e('Click or drag on the preview to choose the primary visual focus for cropping and cover layouts.', 'tka-site-utilities'); ?>
            </span>
        </div>
        <?php
        $html = ob_get_clean();

        $form_fields['focal_point'] = [
            'label' => __('Focal Point', 'tka-site-utilities'),
            'input' => 'html',
            'html'  => $html,
            'helps' => '',
        ];

        return $form_fields;
    }

    /**
     * Save focal point coordinates to post meta.
     */
    public function saveAttachmentField(array $post, array $attachment): array
    {
        $id = $post['ID'] ?? null;
        if (!$id) {
            return $post;
        }

        if (isset($attachment['focal_point_x'])) {
            $x = self::sanitizeCoordinate($attachment['focal_point_x']);
            update_post_meta($id, self::META_KEY_X, $x);
        }

        if (isset($attachment['focal_point_y'])) {
            $y = self::sanitizeCoordinate($attachment['focal_point_y']);
            update_post_meta($id, self::META_KEY_Y, $y);
        }

        if (isset($x) || isset($y)) {
            $currentFocal = self::getFocalPoint($id);
            update_post_meta($id, self::META_KEY_COMPOSITE, "{$currentFocal['x']}% {$currentFocal['y']}%");
        }

        return $post;
    }

    /**
     * Inject focal point into JS attachment response for Backbone media modal.
     */
    public function prepareAttachmentForJs(array $response, \WP_Post $post, $meta = null): array
    {
        $focal = self::getFocalPoint($post->ID);
        $response['focal_point'] = $focal;
        return $response;
    }

    /**
     * Filter native wp_get_attachment_image attributes to append object-position.
     */
    public function filterImageAttributes(array $attr, $attachment, $size): array
    {
        $id = is_object($attachment) ? $attachment->ID : (int) $attachment;
        if (!$id) {
            return $attr;
        }

        $focal = self::getFocalPoint($id);
        if ($focal['has_focal_point']) {
            if (!isset($attr['data-focal-x'])) {
                $attr['data-focal-x'] = $focal['x'];
            }
            if (!isset($attr['data-focal-y'])) {
                $attr['data-focal-y'] = $focal['y'];
            }

            $existingStyle = $attr['style'] ?? '';
            if (!str_contains($existingStyle, 'object-position')) {
                $focalStyle = "object-position: {$focal['x']}% {$focal['y']}%; --focal-x: {$focal['x']}%; --focal-y: {$focal['y']}%;";
                $attr['style'] = rtrim(trim($existingStyle), ';') . ($existingStyle ? '; ' : '') . $focalStyle;
            }
        }

        return $attr;
    }

    /**
     * Enhance ACF image field return value with focal point data.
     */
    public function formatAcfImageValue($value, $post_id, $field)
    {
        if (is_array($value) && !empty($value['ID'])) {
            $value['focal_point'] = self::getFocalPoint((int) $value['ID']);
        }
        return $value;
    }

    /**
     * Register REST API field for attachments.
     */
    public function registerRestFields(): void
    {
        register_rest_field('attachment', 'focal_point', [
            'get_callback' => function ($post) {
                return self::getFocalPoint((int) $post['id']);
            },
            'update_callback' => function ($value, $post) {
                if (is_array($value)) {
                    if (isset($value['x'])) {
                        update_post_meta($post->ID, self::META_KEY_X, self::sanitizeCoordinate($value['x']));
                    }
                    if (isset($value['y'])) {
                        update_post_meta($post->ID, self::META_KEY_Y, self::sanitizeCoordinate($value['y']));
                    }
                }
                return true;
            },
            'schema' => [
                'description' => 'Focal point coordinates for responsive image cropping',
                'type'        => 'object',
            ],
        ]);
    }

    /**
     * Sanitize coordinate percentage (0 - 100).
     */
    public static function sanitizeCoordinate($val): float
    {
        $num = floatval(preg_replace('/[^0-9.]/', '', (string) $val));
        return max(0.0, min(100.0, round($num, 1)));
    }

    /**
     * Retrieve focal point for an attachment ID.
     *
     * @param int $attachment_id
     * @return array{x: float, y: float, css: string, style: string, has_focal_point: bool}
     */
    public static function getFocalPoint(int $attachment_id): array
    {
        $rawX = get_post_meta($attachment_id, self::META_KEY_X, true);
        $rawY = get_post_meta($attachment_id, self::META_KEY_Y, true);

        $hasMeta = ($rawX !== '' && $rawX !== false) || ($rawY !== '' && $rawY !== false);
        $x = $rawX !== '' && $rawX !== false ? floatval($rawX) : 50.0;
        $y = $rawY !== '' && $rawY !== false ? floatval($rawY) : 50.0;

        return [
            'x'               => $x,
            'y'               => $y,
            'css'             => "{$x}% {$y}%",
            'style'           => "object-position: {$x}% {$y}%; --focal-x: {$x}%; --focal-y: {$y}%;",
            'has_focal_point' => $hasMeta || ($x !== 50.0 || $y !== 50.0),
        ];
    }
}
