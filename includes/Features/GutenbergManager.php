<?php

namespace TKA\WPUtils\Features;

/**
 * Manages Gutenberg block editor suppression, block & embed curation,
 * library/pattern restrictions, and theme.json design system guardrails.
 */
class GutenbergManager {

	/**
	 * Gutenberg disabling mode ('none', 'all', 'post_types', or 'wc_except_cart_checkout').
	 */
	private string $mode;

	/**
	 * Array of post type keys where Gutenberg should be disabled.
	 */
	private array $post_types;

	/**
	 * Whether to dequeue block stylesheets on the frontend.
	 */
	private bool $dequeue_styles;

	/**
	 * Full plugin options array.
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param string|array $mode_or_options Full options array or legacy disabling mode string.
	 * @param array        $post_types      Legacy post types array.
	 * @param bool         $dequeue_styles  Legacy dequeue styles flag.
	 */
	public function __construct( string|array $mode_or_options = 'none', array $post_types = [], bool $dequeue_styles = false ) {
		if ( is_array( $mode_or_options ) ) {
			$this->options        = $mode_or_options;
			$this->mode           = (string) ( $mode_or_options['disable_gutenberg'] ?? 'none' );
			$this->post_types     = (array) ( $mode_or_options['gutenberg_post_types'] ?? [] );
			$this->dequeue_styles = ! empty( $mode_or_options['gutenberg_dequeue_block_styles'] );
		} else {
			$this->options        = [];
			$this->mode           = $mode_or_options;
			$this->post_types     = $post_types;
			$this->dequeue_styles = $dequeue_styles;
		}
	}

	/**
	 * Check whether Gutenberg is globally disabled.
	 */
	private function isGutenbergGloballyDisabled(): bool {
		return ! empty( $this->options['classic_editor'] ) || 'all' === $this->mode;
	}

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		if ( 'all' === $this->mode ) {
			add_filter( 'use_block_editor_for_post', '__return_false', 100 );
			add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		} elseif ( 'post_types' === $this->mode && ! empty( $this->post_types ) ) {
			add_filter( 'use_block_editor_for_post', [ $this, 'filterUseBlockEditorForPost' ], 100, 2 );
			add_filter( 'use_block_editor_for_post_type', [ $this, 'filterUseBlockEditorForPostType' ], 100, 2 );
		} elseif ( 'wc_except_cart_checkout' === $this->mode ) {
			add_filter( 'use_block_editor_for_post', [ $this, 'filterWcExceptCartCheckoutPost' ], 100, 2 );
			add_filter( 'use_block_editor_for_post_type', [ $this, 'filterWcExceptCartCheckoutPostType' ], 100, 2 );
		}

		if ( $this->dequeue_styles ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeueBlockStyles' ], 100 );
		}

		if ( $this->isGutenbergGloballyDisabled() ) {
			return;
		}

		// Block curation & fine-grained oEmbed filtering
		if ( ! empty( $this->options['gutenberg_curation_enabled'] ) ) {
			add_filter( 'allowed_block_types_all', [ $this, 'filterAllowedBlockTypes' ], 10, 2 );
			add_filter( 'oembed_providers', [ $this, 'filterOembedProviders' ], 20 );
		}

		// Pattern & block style cleanup hooks
		if ( ! empty( $this->options['gutenberg_disable_patterns'] ) ) {
			add_action( 'after_setup_theme', [ $this, 'disableThemeBlockPatterns' ], 99 );
		}
		if ( ! empty( $this->options['gutenberg_disable_patterns'] ) || ! empty( $this->options['gutenberg_clean_block_styles'] ) ) {
			add_action( 'init', [ $this, 'cleanupPatternsAndStyles' ], 99 );
		}
		if ( ! empty( $this->options['gutenberg_disable_remote_patterns'] ) ) {
			add_filter( 'should_load_remote_block_patterns', '__return_false' );
		}

		// Block Editor client-side assets (embed variation filtering, directory removal, style cleanup, snackbar safeguard)
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueBlockEditorAssets' ], 1 );

		// Theme.json & Block Editor Settings Guardrails
		if ( $this->hasDesignOrLibraryGuardrails() ) {
			add_filter( 'wp_theme_json_data_theme', [ $this, 'filterThemeJsonData' ], 20 );
			add_filter( 'block_editor_settings_all', [ $this, 'filterBlockEditorSettings' ], 10, 2 );
		}
	}

	/**
	 * Determine if any theme.json or editor library guardrails are active.
	 */
	private function hasDesignOrLibraryGuardrails(): bool {
		$keys = [
			'gutenberg_disable_openverse',
			'gutenberg_disable_block_directory',
			'gutenberg_disable_custom_colors',
			'gutenberg_enforce_brand_palette',
			'gutenberg_disable_custom_font_sizes',
			'gutenberg_disable_custom_spacing',
		];
		foreach ( $keys as $key ) {
			if ( ! empty( $this->options[ $key ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Dequeue Gutenberg block stylesheets on frontend.
	 */
	public function dequeueBlockStyles(): void {
		if ( ! is_admin() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-blocks-style' );
			wp_dequeue_style( 'wc-block-style' );
		}
	}

	/**
	 * Suppress Gutenberg for specific posts by evaluating their post type.
	 */
	public function filterUseBlockEditorForPost( bool $use_block_editor, $post ): bool {
		if ( ! $post ) {
			return $use_block_editor;
		}

		$post_type = get_post_type( $post );
		if ( in_array( $post_type, $this->post_types, true ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Suppress Gutenberg for specific registered post types.
	 */
	public function filterUseBlockEditorForPostType( bool $use_block_editor, string $post_type ): bool {
		if ( in_array( $post_type, $this->post_types, true ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Suppress Gutenberg everywhere except on WooCommerce Cart and Checkout pages.
	 */
	public function filterWcExceptCartCheckoutPost( bool $use_block_editor, $post ): bool {
		if ( ! $post ) {
			return $use_block_editor;
		}

		$post_type = get_post_type( $post );
		if ( 'page' !== $post_type ) {
			return false;
		}

		$post_id = is_object( $post ) ? $post->ID : (int) $post;
		if ( function_exists( 'wc_get_page_id' ) ) {
			$cart_id     = (int) wc_get_page_id( 'cart' );
			$checkout_id = (int) wc_get_page_id( 'checkout' );

			if ( $post_id === $cart_id || $post_id === $checkout_id ) {
				return $use_block_editor;
			}
		}

		return false;
	}

	/**
	 * Suppress Gutenberg for all post types except 'page'.
	 */
	public function filterWcExceptCartCheckoutPostType( bool $use_block_editor, string $post_type ): bool {
		if ( 'page' === $post_type ) {
			return $use_block_editor;
		}

		return false;
	}

	/**
	 * Filter allowed Gutenberg blocks in the editor.
	 */
	public function filterAllowedBlockTypes( $allowed_block_types, $editor_context ) {
		if ( ! empty( $this->options['gutenberg_admin_bypass'] ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		$raw_allowed = isset( $this->options['gutenberg_allowed_blocks'] ) && is_array( $this->options['gutenberg_allowed_blocks'] )
			? $this->options['gutenberg_allowed_blocks']
			: self::getRecommendedPresetBlocks();

		if ( empty( $raw_allowed ) ) {
			return false;
		}

		$clean_allowed = [];
		$has_any_embed = false;

		foreach ( $raw_allowed as $b_name ) {
			if ( str_starts_with( $b_name, 'core-embed-' ) ) {
				$has_any_embed = true;
				continue;
			}
			if ( 'core/embed' === $b_name ) {
				$has_any_embed = true;
				continue;
			}
			$clean_allowed[] = $b_name;
		}

		if ( $has_any_embed ) {
			$clean_allowed[] = 'core/embed';
		}

		return ! empty( $clean_allowed ) ? array_values( array_unique( $clean_allowed ) ) : false;
	}

	/**
	 * Filter WordPress oEmbed providers based on allowed embed slugs.
	 */
	public function filterOembedProviders( array $providers ): array {
		if ( ! empty( $this->options['gutenberg_admin_bypass'] ) && current_user_can( 'manage_options' ) ) {
			return $providers;
		}

		$allowed = $this->getResolvedAllowedEmbeds();
		if ( empty( $allowed ) ) {
			return [];
		}

		$keyword_map = [
			'crowdsignal'   => [ 'crowdsignal', 'polldaddy', 'poll.fm' ],
			'pocket-casts'  => [ 'pocketcasts', 'pca.st' ],
			'speaker-deck'  => [ 'speakerdeck' ],
			'amazon-kindle' => [ 'amazon', 'amzn', 'a.co' ],
			'wolfram-cloud' => [ 'wolfram' ],
			'twitter'       => [ 'twitter', 'x.com' ],
			'flickr'        => [ 'flickr', 'flic.kr' ],
			'dailymotion'   => [ 'dailymotion', 'dai.ly' ],
			'bluesky'       => [ 'bsky.app', 'bluesky' ],
			'ted'           => [ 'ted.com' ],
			'screencast'    => [ 'screencast' ],
			'slideshare'    => [ 'slideshare' ],
		];

		$filtered = [];
		foreach ( $providers as $regex => $info ) {
			foreach ( $allowed as $slug ) {
				$keywords = $keyword_map[ $slug ] ?? [ $slug ];
				foreach ( $keywords as $kw ) {
					if ( stripos( $regex, $kw ) !== false || ( is_array( $info ) && isset( $info[0] ) && stripos( $info[0], $kw ) !== false ) ) {
						$filtered[ $regex ] = $info;
						break 2;
					}
				}
			}
		}

		return $filtered;
	}

	/**
	 * Resolve allowed embed slugs from options or checked core-embed-* items.
	 */
	private function getResolvedAllowedEmbeds(): array {
		if ( isset( $this->options['gutenberg_allowed_blocks'] ) && is_array( $this->options['gutenberg_allowed_blocks'] ) ) {
			$detected = [];
			foreach ( $this->options['gutenberg_allowed_blocks'] as $b ) {
				if ( str_starts_with( $b, 'core-embed-' ) ) {
					$detected[] = substr( $b, strlen( 'core-embed-' ) );
				}
			}
			if ( ! empty( $detected ) ) {
				return array_values( array_unique( $detected ) );
			}
		}

		if ( isset( $this->options['gutenberg_allowed_embeds'] ) && is_array( $this->options['gutenberg_allowed_embeds'] ) ) {
			return array_values( $this->options['gutenberg_allowed_embeds'] );
		}

		return self::getDefaultAllowedEmbeds();
	}

	/**
	 * Remove core-block-patterns theme support.
	 */
	public function disableThemeBlockPatterns(): void {
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Unregister registered block patterns and unsupported core block styles.
	 */
	public function cleanupPatternsAndStyles(): void {
		if ( ! empty( $this->options['gutenberg_disable_patterns'] ) && class_exists( 'WP_Block_Patterns_Registry' ) ) {
			$patterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
			foreach ( $patterns as $pattern ) {
				if ( isset( $pattern['name'] ) ) {
					unregister_block_pattern( $pattern['name'] );
				}
			}
		}

		if ( ! empty( $this->options['gutenberg_clean_block_styles'] ) && class_exists( 'WP_Block_Styles_Registry' ) ) {
			$styles_registry = \WP_Block_Styles_Registry::get_instance();
			$styles_to_clean = [
				'core/image'     => [ 'rounded' ],
				'core/separator' => [ 'dots', 'wide-line' ],
				'core/quote'     => [ 'plain' ],
			];
			foreach ( $styles_to_clean as $block => $styles ) {
				foreach ( $styles as $style ) {
					if ( $styles_registry->is_registered( $block, $style ) ) {
						unregister_block_style( $block, $style );
					}
				}
			}
		}
	}

	/**
	 * Enqueue block editor scripts for embed variation curation, style cleanup,
	 * block directory removal, and UploadProgressSnackbar crash safeguard.
	 */
	public function enqueueBlockEditorAssets(): void {
		if ( ! empty( $this->options['gutenberg_disable_block_directory'] ) ) {
			wp_dequeue_script( 'wp-block-directory' );
			wp_dequeue_style( 'wp-block-directory' );
			remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
		}

		$is_curation_enabled = ! empty( $this->options['gutenberg_curation_enabled'] ) ? 1 : 0;
		if ( $is_curation_enabled && ! empty( $this->options['gutenberg_admin_bypass'] ) && current_user_can( 'manage_options' ) ) {
			$is_curation_enabled = 0;
		}
		$clean_styles        = ! empty( $this->options['gutenberg_clean_block_styles'] ) ? 1 : 0;
		$register_lead_style = ! empty( $this->options['gutenberg_register_lead_style'] ) ? 1 : 0;

		if ( ! $is_curation_enabled && ! $clean_styles && ! $register_lead_style ) {
			return;
		}

		// Defensive safeguard for core Gutenberg UploadProgressSnackbar sub-registry bug
		$safeguard = '
			(function() {
				if (!window.wp || !window.wp.data || !window.wp.privateApis) return;
				try {
					var apis = window.wp.privateApis.__dangerousOptInToUnstableAPIsOnlyForCoreModules(
						"I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
						"@wordpress/editor"
					);
					if (apis && apis.unlock && window.wp.uploadMedia && window.wp.uploadMedia.store) {
						var s = window.wp.data.select(window.wp.uploadMedia.store);
						var u = apis.unlock(s);
						if (u && typeof u.getFailureCount !== "function") {
							u.getFailureCount = function() { return 0; };
						}
					}
				} catch (e) {}
			})();
		';
		wp_add_inline_script( 'wp-editor', $safeguard, 'before' );

		$allowed_embeds = $this->getResolvedAllowedEmbeds();
		$curator_script = sprintf(
			'(function() {
				var isCurationEnabled = %d;
				var allowedEmbeds = %s;
				var cleanStyles = %d;
				var registerLeadStyle = %d;
				var leadStyleLabel = %s;

				function filterEmbedVariations() {
					if (!isCurationEnabled || !window.wp || !window.wp.data) return false;
					try {
						var select = window.wp.data.select("core/blocks");
						if (!select || !select.getBlockVariations) return false;
						var variations = select.getBlockVariations("core/embed") || [];
						if (!variations.length) return false;

						var unallowed = [];
						for (var i = 0; i < variations.length; i++) {
							var v = variations[i];
							if (v && v.name && allowedEmbeds.indexOf(v.name) === -1) {
								unallowed.push(v.name);
							}
						}

						if (unallowed.length > 0) {
							var dispatch = window.wp.data.dispatch("core/blocks");
							if (dispatch && typeof dispatch.removeBlockVariations === "function") {
								dispatch.removeBlockVariations("core/embed", unallowed);
							}
							if (window.wp.blocks && typeof window.wp.blocks.unregisterBlockVariation === "function") {
								for (var j = 0; j < unallowed.length; j++) {
									window.wp.blocks.unregisterBlockVariation("core/embed", unallowed[j]);
								}
							}
							return true;
						}
					} catch (e) {}
					return false;
				}

				function handleStyles() {
					if (!window.wp || !window.wp.blocks) return;
					try {
						if (cleanStyles) {
							window.wp.blocks.unregisterBlockStyle("core/image", "rounded");
							window.wp.blocks.unregisterBlockStyle("core/separator", "dots");
							window.wp.blocks.unregisterBlockStyle("core/separator", "wide-line");
							window.wp.blocks.unregisterBlockStyle("core/quote", "plain");
						}
						if (registerLeadStyle) {
							window.wp.blocks.registerBlockStyle("core/paragraph", {
								name: "lead",
								label: leadStyleLabel
							});
						}
					} catch (e) {}
				}

				function runAll() {
					filterEmbedVariations();
					handleStyles();
				}

				if (isCurationEnabled && window.wp && window.wp.data && typeof window.wp.data.subscribe === "function") {
					window.wp.data.subscribe(function() {
						var select = window.wp.data.select("core/blocks");
						if (select) {
							var vars = select.getBlockVariations("core/embed");
							if (vars && vars.length > 0) {
								for (var k = 0; k < vars.length; k++) {
									if (vars[k] && vars[k].name && allowedEmbeds.indexOf(vars[k].name) === -1) {
										filterEmbedVariations();
										break;
									}
								}
							}
						}
					});
				}

				if (window.wp && window.wp.domReady) {
					window.wp.domReady(function() {
						runAll();
						if (window._wpLoadBlockEditor && typeof window._wpLoadBlockEditor.then === "function") {
							window._wpLoadBlockEditor.then(runAll);
						}
						setTimeout(runAll, 50);
						setTimeout(runAll, 200);
						setTimeout(runAll, 600);
						setTimeout(runAll, 1200);
					});
				} else {
					setTimeout(runAll, 100);
					setTimeout(runAll, 500);
				}
			})();',
			$is_curation_enabled,
			wp_json_encode( $allowed_embeds ),
			$clean_styles,
			$register_lead_style,
			wp_json_encode( __( 'Lead Paragraph', 'tka-site-utilities' ) )
		);

		$deps = [ 'wp-blocks', 'wp-dom-ready', 'wp-data' ];
		if ( wp_script_is( 'wp-edit-post', 'registered' ) ) {
			$deps[] = 'wp-edit-post';
		}
		wp_register_script( 'tka-gutenberg-curator', '', $deps, TKA_SITE_UTILITIES_VERSION, true );
		wp_enqueue_script( 'tka-gutenberg-curator' );
		wp_add_inline_script( 'tka-gutenberg-curator', $curator_script );
	}

	/**
	 * Apply color palette, font size scale, and spacing/border guardrails via theme.json.
	 */
	public function filterThemeJsonData( $theme_json ) {
		$theme_data = [
			'version'  => 3,
			'settings' => [],
			'styles'   => [],
		];

		// 1. Color Palette & Custom Color Guardrails
		$disable_custom_colors = ! empty( $this->options['gutenberg_disable_custom_colors'] );
		$enforce_brand_palette = ! empty( $this->options['gutenberg_enforce_brand_palette'] );

		if ( $disable_custom_colors || $enforce_brand_palette ) {
			$color_settings = [];
			if ( $disable_custom_colors ) {
				$color_settings['custom']           = false;
				$color_settings['customGradient']   = false;
				$color_settings['defaultGradients'] = false;
				$color_settings['gradients']        = [];
			}
			if ( $enforce_brand_palette ) {
				$palette = ! empty( $this->options['gutenberg_brand_palette'] ) && is_array( $this->options['gutenberg_brand_palette'] )
					? $this->options['gutenberg_brand_palette']
					: self::getDefaultBrandPalette();

				/**
				 * Filter the brand color palette enforced in Gutenberg theme.json.
				 *
				 * @param array $palette Array of ['name' => string, 'slug' => string, 'color' => string].
				 */
				$palette = apply_filters( 'tka_site_utilities_gutenberg_color_palette', $palette );

				$color_settings['defaultPalette'] = false;
				$color_settings['palette']        = array_values( $palette );
			}
			$theme_data['settings']['color'] = $color_settings;
		}

		// 2. Custom Font Size Lock & Heading Scale Guardrails
		if ( ! empty( $this->options['gutenberg_disable_custom_font_sizes'] ) ) {
			$typography_settings = [
				'customFontSize' => false,
				'fontSizes'      => [
					[ 'name' => __( 'Small', 'tka-site-utilities' ), 'slug' => 'small', 'size' => '0.875rem' ],
					[ 'name' => __( 'Normal', 'tka-site-utilities' ), 'slug' => 'normal', 'size' => '1.0625rem' ],
					[ 'name' => __( 'Medium / Lead', 'tka-site-utilities' ), 'slug' => 'medium', 'size' => '1.3125rem' ],
					[ 'name' => __( 'Large (H3)', 'tka-site-utilities' ), 'slug' => 'large', 'size' => '1.625rem' ],
					[ 'name' => __( 'Extra Large (H2)', 'tka-site-utilities' ), 'slug' => 'x-large', 'size' => '2.25rem' ],
					[ 'name' => __( '2X Large (H1)', 'tka-site-utilities' ), 'slug' => 'xx-large', 'size' => '3.25rem' ],
				],
			];

			/**
			 * Filter the Gutenberg theme.json typography settings & scale.
			 *
			 * @param array $typography_settings Typography settings array.
			 */
			$theme_data['settings']['typography'] = apply_filters( 'tka_site_utilities_gutenberg_typography', $typography_settings );

			$theme_data['styles']['elements'] = [
				'h1' => [ 'typography' => [ 'fontSize' => 'var:preset|font-size|xx-large', 'fontWeight' => '700', 'lineHeight' => '1.12' ] ],
				'h2' => [ 'typography' => [ 'fontSize' => 'var:preset|font-size|x-large', 'fontWeight' => '700', 'lineHeight' => '1.2' ] ],
				'h3' => [ 'typography' => [ 'fontSize' => 'var:preset|font-size|large', 'fontWeight' => '700', 'lineHeight' => '1.28' ] ],
				'h4' => [ 'typography' => [ 'fontSize' => '1.3125rem', 'fontWeight' => '700', 'lineHeight' => '1.35' ] ],
				'h5' => [ 'typography' => [ 'fontSize' => '1.125rem', 'fontWeight' => '700', 'lineHeight' => '1.4' ] ],
				'h6' => [ 'typography' => [ 'fontSize' => 'var:preset|font-size|small', 'fontWeight' => '700', 'lineHeight' => '1.4' ] ],
			];
		}

		// 3. Spacing & Border Guardrails
		if ( ! empty( $this->options['gutenberg_disable_custom_spacing'] ) ) {
			$theme_data['settings']['spacing'] = [
				'margin'            => false,
				'padding'           => false,
				'blockGap'          => false,
				'customSpacingSize' => false,
				'units'             => [ 'px', 'rem' ],
			];
			$theme_data['settings']['border']  = [
				'color'  => false,
				'radius' => false,
				'style'  => false,
				'width'  => false,
			];
		}

		if ( empty( $theme_data['settings'] ) && empty( $theme_data['styles'] ) ) {
			return $theme_json;
		}

		if ( empty( $theme_data['styles'] ) ) {
			unset( $theme_data['styles'] );
		}

		return $theme_json->update_with( $theme_data );
	}

	/**
	 * Fallback block editor settings filter for Openverse, custom colors, font sizes, and block directory.
	 */
	public function filterBlockEditorSettings( array $editor_settings, $editor_context ): array {
		if ( ! empty( $this->options['gutenberg_disable_openverse'] ) ) {
			$editor_settings['enableOpenverseMediaCategory'] = false;
		}

		if ( ! empty( $this->options['gutenberg_disable_custom_colors'] ) ) {
			$editor_settings['disableCustomColors']    = true;
			$editor_settings['disableCustomGradients'] = true;
		}

		if ( ! empty( $this->options['gutenberg_disable_custom_font_sizes'] ) ) {
			$editor_settings['disableCustomFontSizes'] = true;
		}

		if ( ! empty( $this->options['gutenberg_disable_block_directory'] ) ) {
			$editor_settings['__experimentalBlockDirectory'] = false;
		}

		return $editor_settings;
	}

	/**
	 * Default brand color palette swatches when "Enforce Brand Palette" is enabled.
	 */
	public static function getDefaultBrandPalette(): array {
		return [
			[
				'name'  => __( 'White (Base)', 'tka-site-utilities' ),
				'slug'  => 'base',
				'color' => '#FFFFFF',
			],
			[
				'name'  => __( 'Dark Contrast', 'tka-site-utilities' ),
				'slug'  => 'contrast',
				'color' => '#1E293B',
			],
			[
				'name'  => __( 'Primary', 'tka-site-utilities' ),
				'slug'  => 'primary',
				'color' => '#4F46E5',
			],
			[
				'name'  => __( 'Secondary', 'tka-site-utilities' ),
				'slug'  => 'secondary',
				'color' => '#06B6D4',
			],
			[
				'name'  => __( 'Muted Surface', 'tka-site-utilities' ),
				'slug'  => 'surface-muted',
				'color' => '#F8FAFC',
			],
		];
	}

	/**
	 * Available Gutenberg embed providers with labels, descriptions, and group badges.
	 */
	public static function getAvailableEmbedProviders(): array {
		return [
			// Video
			'youtube'       => [ 'title' => 'YouTube', 'group' => 'Video', 'desc' => __( 'Embed YouTube videos, Shorts, and playlists.', 'tka-site-utilities' ) ],
			'vimeo'         => [ 'title' => 'Vimeo', 'group' => 'Video', 'desc' => __( 'Embed HD videos and streams from Vimeo.', 'tka-site-utilities' ) ],
			'tiktok'        => [ 'title' => 'TikTok', 'group' => 'Video', 'desc' => __( 'Embed short-form videos from TikTok.', 'tka-site-utilities' ) ],
			'dailymotion'   => [ 'title' => 'Dailymotion', 'group' => 'Video', 'desc' => __( 'Embed videos from Dailymotion.', 'tka-site-utilities' ) ],
			'videopress'    => [ 'title' => 'VideoPress', 'group' => 'Video', 'desc' => __( 'Embed WordPress VideoPress videos.', 'tka-site-utilities' ) ],
			'ted'           => [ 'title' => 'TED Talks', 'group' => 'Video', 'desc' => __( 'Embed TED conference talks and videos.', 'tka-site-utilities' ) ],
			'animoto'       => [ 'title' => 'Animoto', 'group' => 'Video', 'desc' => __( 'Embed Animoto video presentations.', 'tka-site-utilities' ) ],
			'screencast'    => [ 'title' => 'Screencast', 'group' => 'Video', 'desc' => __( 'Embed Screencast.com videos.', 'tka-site-utilities' ) ],
			'collegehumor'  => [ 'title' => 'CollegeHumor', 'group' => 'Video', 'desc' => __( 'Legacy CollegeHumor video embed.', 'tka-site-utilities' ) ],

			// Audio
			'spotify'       => [ 'title' => 'Spotify', 'group' => 'Audio', 'desc' => __( 'Embed Spotify tracks, albums, and podcasts.', 'tka-site-utilities' ) ],
			'soundcloud'    => [ 'title' => 'SoundCloud', 'group' => 'Audio', 'desc' => __( 'Embed SoundCloud audio tracks and playlists.', 'tka-site-utilities' ) ],
			'pocket-casts'  => [ 'title' => 'Pocket Casts', 'group' => 'Audio', 'desc' => __( 'Embed podcast episodes from Pocket Casts.', 'tka-site-utilities' ) ],
			'mixcloud'      => [ 'title' => 'Mixcloud', 'group' => 'Audio', 'desc' => __( 'Embed Mixcloud DJ mixes and radio shows.', 'tka-site-utilities' ) ],
			'reverbnation'  => [ 'title' => 'ReverbNation', 'group' => 'Audio', 'desc' => __( 'Embed music tracks from ReverbNation.', 'tka-site-utilities' ) ],

			// Social
			'twitter'       => [ 'title' => 'Twitter / X', 'group' => 'Social', 'desc' => __( 'Embed posts and threads from X (Twitter).', 'tka-site-utilities' ) ],
			'bluesky'       => [ 'title' => 'Bluesky', 'group' => 'Social', 'desc' => __( 'Embed posts and threads from Bluesky.', 'tka-site-utilities' ) ],
			'reddit'        => [ 'title' => 'Reddit', 'group' => 'Social', 'desc' => __( 'Embed Reddit posts and discussions.', 'tka-site-utilities' ) ],
			'pinterest'     => [ 'title' => 'Pinterest', 'group' => 'Social', 'desc' => __( 'Embed Pinterest pins and boards.', 'tka-site-utilities' ) ],
			'tumblr'        => [ 'title' => 'Tumblr', 'group' => 'Social', 'desc' => __( 'Embed Tumblr posts and media.', 'tka-site-utilities' ) ],
			'facebook'      => [ 'title' => 'Facebook', 'group' => 'Social', 'desc' => __( 'Facebook posts (requires Meta oEmbed token).', 'tka-site-utilities' ) ],
			'instagram'     => [ 'title' => 'Instagram', 'group' => 'Social', 'desc' => __( 'Instagram posts/Reels (requires Meta oEmbed token).', 'tka-site-utilities' ) ],

			// Documents
			'issuu'         => [ 'title' => 'Issuu', 'group' => 'Documents', 'desc' => __( 'Embed interactive digital magazines and PDFs from Issuu.', 'tka-site-utilities' ) ],
			'crowdsignal'   => [ 'title' => 'Crowdsignal', 'group' => 'Documents', 'desc' => __( 'Embed Crowdsignal (Polldaddy) polls and surveys.', 'tka-site-utilities' ) ],
			'scribd'        => [ 'title' => 'Scribd', 'group' => 'Documents', 'desc' => __( 'Embed documents and books from Scribd.', 'tka-site-utilities' ) ],
			'speaker-deck'  => [ 'title' => 'Speaker Deck', 'group' => 'Documents', 'desc' => __( 'Embed slide decks from Speaker Deck.', 'tka-site-utilities' ) ],
			'slideshare'    => [ 'title' => 'SlideShare', 'group' => 'Documents', 'desc' => __( 'Embed SlideShare presentations.', 'tka-site-utilities' ) ],
			'amazon-kindle' => [ 'title' => 'Amazon Kindle', 'group' => 'Documents', 'desc' => __( 'Embed Amazon Kindle book previews.', 'tka-site-utilities' ) ],
			'wolfram-cloud' => [ 'title' => 'Wolfram Cloud', 'group' => 'Documents', 'desc' => __( 'Embed interactive Wolfram Cloud notebooks.', 'tka-site-utilities' ) ],

			// Images
			'flickr'        => [ 'title' => 'Flickr', 'group' => 'Images', 'desc' => __( 'Embed photos and albums from Flickr.', 'tka-site-utilities' ) ],
			'imgur'         => [ 'title' => 'Imgur', 'group' => 'Images', 'desc' => __( 'Embed Imgur images and galleries.', 'tka-site-utilities' ) ],
			'smugmug'       => [ 'title' => 'SmugMug', 'group' => 'Images', 'desc' => __( 'Embed SmugMug photo galleries.', 'tka-site-utilities' ) ],
			'cloudup'       => [ 'title' => 'Cloudup', 'group' => 'Images', 'desc' => __( 'Embed shared media streams from Cloudup.', 'tka-site-utilities' ) ],

			// Web
			'wordpress'     => [ 'title' => 'WordPress Embed', 'group' => 'Web', 'desc' => __( 'Embed external WordPress post cards.', 'tka-site-utilities' ) ],
			'wordpress-tv'  => [ 'title' => 'WordPress.tv', 'group' => 'Web', 'desc' => __( 'Embed WordCamp talks and videos from WordPress.tv.', 'tka-site-utilities' ) ],
			'kickstarter'   => [ 'title' => 'Kickstarter', 'group' => 'Web', 'desc' => __( 'Embed Kickstarter project cards.', 'tka-site-utilities' ) ],
		];
	}

	/**
	 * Recommended default allowed embed providers.
	 */
	public static function getDefaultAllowedEmbeds(): array {
		return [ 'youtube', 'vimeo', 'spotify' ];
	}

	/**
	 * Recommended Editorial / Headless-Safe preset blocks (includes any registered custom blocks).
	 */
	public static function getRecommendedPresetBlocks(): array {
		$blocks = [
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			'core/pullquote',
			'core/table',
			'core/freeform',
			'core/code',
			'core/preformatted',
			'core/image',
			'core/gallery',
			'core/audio',
			'core/video',
			'core/media-text',
			'core/file',
			'core/separator',
			'core/spacer',
			'core/columns',
			'core/column',
			'core/group',
			'core/buttons',
			'core/button',
			'core/embed',
			'core-embed-youtube',
			'core-embed-vimeo',
			'core-embed-spotify',
			'core/html',
			'core/shortcode',
			'core/details',
		];

		if ( class_exists( 'WP_Block_Type_Registry' ) ) {
			$registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
			foreach ( $registered as $name => $block ) {
				if ( ! str_starts_with( $name, 'core/' ) ) {
					$blocks[] = $name;
				}
			}
		}

		return array_values( array_unique( $blocks ) );
	}

	/**
	 * Minimal Text preset blocks.
	 */
	public static function getMinimalPresetBlocks(): array {
		return [
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			'core/image',
			'core/separator',
		];
	}

	/**
	 * Categorize all registered blocks + individual embed providers for the admin UI.
	 */
	public static function getCategorizedBlocks(): array {
		$registered = class_exists( 'WP_Block_Type_Registry' )
			? \WP_Block_Type_Registry::get_instance()->get_all_registered()
			: [];

		$categories = [
			'editorial' => [
				'title'  => __( 'Text & Editorial', 'tka-site-utilities' ),
				'desc'   => __( 'Core text blocks for articles, headings, lists, and quotes.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'media'     => [
				'title'  => __( 'Media', 'tka-site-utilities' ),
				'desc'   => __( 'Images, galleries, audio, video, and file attachments.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'design'    => [
				'title'  => __( 'Design & Layout', 'tka-site-utilities' ),
				'desc'   => __( 'Columns, groups, buttons, spacers, and separators.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'embeds'    => [
				'title'  => __( 'Embeds (Fine-Grained)', 'tka-site-utilities' ),
				'desc'   => __( 'Choose exactly which external oEmbed providers are available in the editor.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'custom'    => [
				'title'  => __( 'Custom & Plugin Blocks', 'tka-site-utilities' ),
				'desc'   => __( 'ACF blocks, Gravity Forms, WooCommerce, and custom-registered blocks.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'advanced'  => [
				'title'  => __( 'Widgets & Advanced', 'tka-site-utilities' ),
				'desc'   => __( 'Custom HTML, shortcodes, accordions/details, and widget blocks.', 'tka-site-utilities' ),
				'blocks' => [],
			],
			'theme_fse' => [
				'title'  => __( 'Theme & Full-Site-Editing (FSE)', 'tka-site-utilities' ),
				'desc'   => __( 'Site-building blocks (typically disabled for articles or headless setups).', 'tka-site-utilities' ),
				'blocks' => [],
			],
		];

		$embed_providers = self::getAvailableEmbedProviders();
		foreach ( $embed_providers as $p_slug => $p_data ) {
			$key                                  = 'core-embed-' . $p_slug;
			$categories['embeds']['blocks'][ $key ] = [
				'name'        => $key,
				'title'       => $p_data['title'],
				'description' => $p_data['desc'],
				'category'    => 'embeds',
				'is_embed'    => true,
				'embed_slug'  => $p_slug,
				'group'       => $p_data['group'] ?? 'Embed',
			];
		}

		$categories['embeds']['blocks']['core/embed'] = [
			'name'        => 'core/embed',
			'title'       => __( 'Generic URL Embed', 'tka-site-utilities' ),
			'description' => __( 'Generic URL input block for any allowed embed provider.', 'tka-site-utilities' ),
			'category'    => 'embeds',
			'group'       => 'Base',
		];

		foreach ( $registered as $name => $block ) {
			if ( 'core/embed' === $name ) {
				continue;
			}

			$title     = ! empty( $block->title ) ? $block->title : $name;
			$desc      = ! empty( $block->description ) ? $block->description : '';
			$block_cat = ! empty( $block->category ) ? $block->category : '';

			$item = [
				'name'        => $name,
				'title'       => $title,
				'description' => $desc,
				'category'    => $block_cat,
			];

			if ( ! str_starts_with( $name, 'core/' ) ) {
				$categories['custom']['blocks'][ $name ] = $item;
			} elseif (
				'theme' === $block_cat
				|| str_starts_with( $name, 'core/query' )
				|| str_starts_with( $name, 'core/post-' )
				|| str_starts_with( $name, 'core/site-' )
				|| str_starts_with( $name, 'core/template-' )
				|| str_starts_with( $name, 'core/navigation' )
			) {
				$categories['theme_fse']['blocks'][ $name ] = $item;
			} elseif ( 'media' === $block_cat ) {
				$categories['media']['blocks'][ $name ] = $item;
			} elseif ( 'design' === $block_cat ) {
				$categories['design']['blocks'][ $name ] = $item;
			} elseif ( in_array( $name, [ 'core/html', 'core/shortcode', 'core/details', 'core/search', 'core/social-links', 'core/tag-cloud' ], true ) ) {
				$categories['advanced']['blocks'][ $name ] = $item;
			} elseif ( 'text' === $block_cat ) {
				$categories['editorial']['blocks'][ $name ] = $item;
			} else {
				$categories['advanced']['blocks'][ $name ] = $item;
			}
		}

		return $categories;
	}
}
