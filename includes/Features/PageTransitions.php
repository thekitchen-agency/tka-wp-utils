<?php

namespace TKA\WPUtils\Features;

/**
 * Handles cross-document view transitions for page transitions.
 */
class PageTransitions {

	/**
	 * Active option settings array.
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		add_action( 'wp_head', [ $this, 'renderGenerator' ] );
		add_action( 'after_setup_theme', [ $this, 'polyfillThemeSupport' ], PHP_INT_MAX );
		add_action( 'init', [ $this, 'sanitizeViewTransitionsThemeSupport' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'loadViewTransitions' ] );
		add_action( 'admin_head', [ $this, 'printViewTransitionsAdminStyle' ] );
		add_action( 'wp_head', [ $this, 'printTransitionStyles' ] );
	}

	/**
	 * Render metadata generator tag.
	 */
	public function renderGenerator(): void {
		echo '<meta name="generator" content="tka-site-utilities-page-transitions ' . esc_attr( TKA_SITE_UTILITIES_VERSION ) . '">' . "\n";
	}

	/**
	 * Polyfills theme support for 'view-transitions'.
	 */
	public function polyfillThemeSupport(): void {
		global $tka_has_theme_support_with_args, $_wp_theme_features;

		if ( current_theme_supports( 'view-transitions' ) ) {
			if ( isset( $_wp_theme_features['view-transitions'] ) && true !== $_wp_theme_features['view-transitions'] ) {
				$tka_has_theme_support_with_args = true;
			}
			return;
		}

		add_theme_support( 'view-transitions' );
	}

	/**
	 * Sanitizes theme support arguments for 'view-transitions' feature.
	 */
	public function sanitizeViewTransitionsThemeSupport(): void {
		global $_wp_theme_features;

		if ( ! isset( $_wp_theme_features['view-transitions'] ) ) {
			return;
		}

		$args = $_wp_theme_features['view-transitions'];

		$defaults = [
			'post-selector'              => '.wp-block-post.post, article.post, body.single main',
			'global-transition-names'    => [
				'header' => 'header',
				'main'   => 'main',
			],
			'post-transition-names'      => [
				'.wp-block-post-title, .entry-title'     => 'post-title',
				'.wp-post-image'                         => 'post-thumbnail',
				'.wp-block-post-content, .entry-content' => 'post-content',
			],
			'default-animation'          => 'fade',
			'default-animation-duration' => 400,
		];

		if ( true === $args ) {
			$args = $defaults;
		} else {
			if ( count( $args ) === 1 && isset( $args[0] ) && is_array( $args[0] ) ) {
				$args = $args[0];
			}
			$args = wp_parse_args( $args, $defaults );

			if ( ! is_array( $args['global-transition-names'] ) ) {
				$args['global-transition-names'] = [];
			}
			if ( ! is_array( $args['post-transition-names'] ) ) {
				$args['post-transition-names'] = [];
			}
		}

		// Apply our settings options over the theme support arguments if override is enabled.
		if ( ! empty( $this->options['page_transitions_override_theme'] ) ) {
			$args['default-animation']          = $this->options['page_transitions_default_animation'] ?? 'fade';
			$args['default-animation-duration'] = absint( $this->options['page_transitions_default_animation_duration'] ?? 400 );

			$selector_map = [
				'page_transitions_header_selector' => 'header',
				'page_transitions_main_selector'   => 'main',
			];
			foreach ( $selector_map as $opt_key => $target_name ) {
				if ( ! empty( $this->options[ $opt_key ] ) ) {
					$existing_key = array_search( $target_name, $args['global-transition-names'], true );
					if ( false !== $existing_key ) {
						unset( $args['global-transition-names'][ $existing_key ] );
					}
					$args['global-transition-names'][ $this->options[ $opt_key ] ] = $target_name;
				}
			}

			$post_selector_map = [
				'page_transitions_post_title_selector'     => 'post-title',
				'page_transitions_post_thumbnail_selector' => 'post-thumbnail',
				'page_transitions_post_content_selector'   => 'post-content',
			];
			foreach ( $post_selector_map as $opt_key => $target_name ) {
				if ( ! empty( $this->options[ $opt_key ] ) ) {
					$existing_key = array_search( $target_name, $args['post-transition-names'], true );
					if ( false !== $existing_key ) {
						unset( $args['post-transition-names'][ $existing_key ] );
					}
					$args['post-transition-names'][ $this->options[ $opt_key ] ] = $target_name;
				}
			}
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_wp_theme_features['view-transitions'] = $args;
	}

	/**
	 * Enqueue and register view transitions configurations on front-end.
	 */
	public function loadViewTransitions(): void {
		if ( ! current_theme_supports( 'view-transitions' ) ) {
			return;
		}

		$theme_support = get_theme_support( 'view-transitions' );

		// Inline style for enabling navigation transitions
		$stylesheet = '@view-transition { navigation: auto; }';
		wp_register_style( 'tka-view-transitions', false, [], TKA_SITE_UTILITIES_VERSION );
		wp_add_inline_style( 'tka-view-transitions', $stylesheet );
		wp_enqueue_style( 'tka-view-transitions' );

		$default_animation = $this->options['page_transitions_default_animation'] ?? 'fade';

		$registry = new PageTransitionAnimationRegistry();
		$this->registerViewTransitionAnimations( $registry );

		$animations_config = [];
		$animations_config['fade'] = [
			'useGlobalTransitionNames' => $registry->useAnimationGlobalTransitionNames( 'fade' ),
			'usePostTransitionNames'   => $registry->useAnimationPostTransitionNames( 'fade' ),
		];

		$variants = [
			'slide', 'slide-from-right', 'slide-from-left', 'slide-from-bottom', 'slide-from-top',
			'swipe', 'swipe-from-right', 'swipe-from-left', 'swipe-from-bottom', 'swipe-from-top',
			'wipe', 'wipe-from-right', 'wipe-from-left', 'wipe-from-bottom', 'wipe-from-top'
		];
		foreach ( $variants as $variant ) {
			$animations_config[ $variant ] = [
				'useGlobalTransitionNames' => $registry->useAnimationGlobalTransitionNames( $variant ),
				'usePostTransitionNames'   => $registry->useAnimationPostTransitionNames( $variant ),
			];
		}

		// Format rules list for frontend matching
		$client_rules = [];
		$rules = $this->options['page_transitions_rules'] ?? [];
		if ( is_array( $rules ) ) {
			foreach ( $rules as $rule ) {
				if ( empty( $rule['animation'] ) ) {
					continue;
				}
				$client_rules[] = [
					'from_type'    => sanitize_key( $rule['from_type'] ?? 'any' ),
					'from_url'     => sanitize_text_field( $rule['from_url'] ?? '' ),
					'to_type'      => sanitize_key( $rule['to_type'] ?? 'any' ),
					'to_url'       => sanitize_text_field( $rule['to_url'] ?? '' ),
					'animation'    => sanitize_key( $rule['animation'] ),
					'custom_class' => sanitize_html_class( $rule['custom_class'] ?? '' ),
				];
			}
		}

		$config = [
			'postSelector'          => $theme_support['post-selector'] ?? '',
			'globalTransitionNames' => $theme_support['global-transition-names'] ?? [],
			'postTransitionNames'   => $theme_support['post-transition-names'] ?? [],
			'defaultAnimation'      => $default_animation,
			'animations'            => $animations_config,
			'rules'                 => $client_rules,
		];

		// Read and output inline page transitions JS file
		$js_path = TKA_SITE_UTILITIES_PATH . 'admin/js/page-transitions.js';
		$src_script = '';
		if ( file_exists( $js_path ) ) {
			$src_script = file_get_contents( $js_path );
		}
		if ( empty( $src_script ) ) {
			return;
		}

		$init_script = sprintf(
			'tkaInitPageTransitions( %s )',
			wp_json_encode( $config, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
		);

		wp_register_script( 'tka-view-transitions', false, [], TKA_SITE_UTILITIES_VERSION, true );
		wp_add_inline_script( 'tka-view-transitions', $src_script );
		wp_add_inline_script( 'tka-view-transitions', $init_script );
		wp_enqueue_script( 'tka-view-transitions' );
	}

	/**
	 * Print page transitions styles scoped to transition classes and custom CSS.
	 */
	public function printTransitionStyles(): void {
		if ( ! is_admin() && ! current_theme_supports( 'view-transitions' ) ) {
			return;
		}

		$registry = new PageTransitionAnimationRegistry();
		$this->registerViewTransitionAnimations( $registry );

		$animations = [
			'slide-from-right', 'slide-from-left', 'slide-from-bottom', 'slide-from-top',
			'swipe-from-right', 'swipe-from-left', 'swipe-from-bottom', 'swipe-from-top',
			'wipe-from-right', 'wipe-from-left', 'wipe-from-bottom', 'wipe-from-top'
		];

		$duration = absint( $this->options['page_transitions_default_animation_duration'] ?? 400 );

		$css = "\n/* TKA Site Utilities Page Transitions Keyframes */\n";
		foreach ( $animations as $anim ) {
			$anim_css = $registry->getAnimationStylesheet( $anim );
			if ( ! empty( $anim_css ) ) {
				$anim_css = $this->injectAnimationDuration( $anim_css, $duration );
				$css .= $this->scopeStylesheet( $anim_css, 'tka-transition-' . $anim ) . "\n";
			}
		}

		$fade_seconds = $duration / 1000;
		$css .= "html.tka-transition-fade::view-transition-group(*) { animation-duration: {$fade_seconds}s; }\n";

		// Prevent header from flashing/fading (keep it persistent and static)
		$css .= "::view-transition-old(header), ::view-transition-new(header) { animation: none; mix-blend-mode: normal; }\n";

		// Output custom CSS
		if ( ! empty( $this->options['page_transitions_custom_css'] ) ) {
			$css .= "\n/* Custom User Page Transitions CSS */\n";
			$css .= $this->options['page_transitions_custom_css'] . "\n";
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<style id="tka-page-transitions-css">' . $css . '</style>' . "\n";
	}

	/**
	 * Output custom view transition styles for WP Admin.
	 */
	public function printViewTransitionsAdminStyle(): void {
		if ( empty( $this->options['page_transitions_enable_admin'] ) ) {
			return;
		}
		
		// In admin area, print transition styles
		$this->printTransitionStyles();
		?>
		<style>
			@view-transition { navigation: auto; }
			#adminmenu > .menu-top { view-transition-name: attr(id type(<custom-ident>), none); }
		</style>
		<?php
	}

	/**
	 * Register built-in transitions in the registry.
	 */
	private function registerViewTransitionAnimations( PageTransitionAnimationRegistry $animation_registry ): void {
		$is_specific_target_name = static function ( string $alias, array $args ): bool {
			return '*' === $args['target-name'] ? false : true;
		};

		$get_hv_offsets_based_on_alias = static function ( string $alias ): array {
			if ( str_ends_with( $alias, 'left' ) ) {
				return [ -1, 0 ];
			}
			if ( str_ends_with( $alias, 'top' ) ) {
				return [ 0, -1 ];
			}
			if ( str_ends_with( $alias, 'bottom' ) ) {
				return [ 0, 1 ];
			}
			if ( str_ends_with( $alias, 'right' ) ) {
				return [ 1, 0 ];
			}
			return [ null, null ];
		};

		$animation_registry->registerAnimation(
			'fade',
			[
				'use_stylesheet'              => false,
				'use_global_transition_names' => true,
				'use_post_transition_names'   => true,
			]
		);
		$animation_registry->registerAnimation(
			'slide',
			[
				'aliases'                     => [
					'slide-from-right',
					'slide-from-bottom',
					'slide-from-left',
					'slide-from-top',
				],
				'use_stylesheet'              => true,
				'use_global_transition_names' => $is_specific_target_name,
				'use_post_transition_names'   => $is_specific_target_name,
				'get_stylesheet_callback'     => static function ( string $css, string $alias, array $args ) use ( $get_hv_offsets_based_on_alias ) {
					list( $horizontal_offset, $vertical_offset ) = $get_hv_offsets_based_on_alias( $alias );
					if ( null !== $horizontal_offset && null !== $vertical_offset ) {
						$args['horizontal-offset'] = $horizontal_offset;
						$args['vertical-offset']   = $vertical_offset;
					}

					$css .= sprintf(
						'::view-transition-old(*), ::view-transition-new(*) { --tka-vt-view-transition-animation-slide-horizontal-offset: %d; --tka-vt-view-transition-animation-slide-vertical-offset: %d; }',
						$args['horizontal-offset'],
						$args['vertical-offset']
					);

					if ( '*' !== $args['target-name'] ) {
						$css = str_replace( '(*)', "({$args['target-name']})", $css );
					}

					return $css;
				},
			],
			[
				'horizontal-offset' => 1,
				'vertical-offset'   => 0,
				'target-name'       => 'root',
			]
		);
		$animation_registry->registerAnimation(
			'swipe',
			[
				'aliases'                     => [
					'swipe-from-right',
					'swipe-from-bottom',
					'swipe-from-left',
					'swipe-from-top',
				],
				'use_stylesheet'              => true,
				'use_global_transition_names' => $is_specific_target_name,
				'use_post_transition_names'   => $is_specific_target_name,
				'get_stylesheet_callback'     => static function ( string $css, string $alias, array $args ) use ( $get_hv_offsets_based_on_alias ) {
					list( $horizontal_offset, $vertical_offset ) = $get_hv_offsets_based_on_alias( $alias );
					if ( null !== $horizontal_offset && null !== $vertical_offset ) {
						$args['horizontal-offset'] = $horizontal_offset;
						$args['vertical-offset']   = $vertical_offset;
					}

					$css .= sprintf(
						'::view-transition-old(*), ::view-transition-new(*) { --tka-vt-view-transition-animation-swipe-horizontal-offset: %d; --tka-vt-view-transition-animation-swipe-vertical-offset: %d; }',
						$args['horizontal-offset'],
						$args['vertical-offset']
					);

					if ( '*' !== $args['target-name'] ) {
						$css = str_replace( '(*)', "({$args['target-name']})", $css );
					}

					return $css;
				},
			],
			[
				'horizontal-offset' => 1,
				'vertical-offset'   => 0,
				'target-name'       => 'root',
			]
		);
		$animation_registry->registerAnimation(
			'wipe',
			[
				'aliases'                     => [
					'wipe-from-right',
					'wipe-from-bottom',
					'wipe-from-left',
					'wipe-from-top',
				],
				'use_stylesheet'              => true,
				'use_global_transition_names' => true,
				'use_post_transition_names'   => true,
				'get_stylesheet_callback'     => static function ( string $css, string $alias, array $args ) {
					if ( str_ends_with( $alias, 'left' ) ) {
						$args['angle'] = 90;
					} elseif ( str_ends_with( $alias, 'top' ) ) {
						$args['angle'] = 180;
					} elseif ( str_ends_with( $alias, 'bottom' ) ) {
						$args['angle'] = 0;
					} elseif ( str_ends_with( $alias, 'right' ) ) {
						$args['angle'] = 270;
					}

					$css .= sprintf(
						'::view-transition-new(root) { --tka-vt-view-transition-animation-wipe-angle: %ddeg; }',
						$args['angle']
					);

					return $css;
				},
			],
			[ 'angle' => 270 ]
		);
	}

	/**
	 * Prepend selector rules with transition classes for scoping.
	 */
	private function scopeStylesheet( string $css, string $class_name ): string {
		return str_replace( '::view-transition-', "html.{$class_name}::view-transition-", $css );
	}

	/**
	 * Injects animation duration to keyframe styles.
	 */
	private function injectAnimationDuration( string $css, int $animation_duration ): string {
		$seconds = $animation_duration / 1000;
		$css .= sprintf(
			'::view-transition-group(*) { --tka-vt-view-transition-animation-duration: %ss; }',
			$seconds
		);
		return $css;
	}
}
