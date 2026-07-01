<?php

namespace TKA\WPUtils\Features;

/**
 * Class representing a page transition animation.
 */
final class PageTransitionAnimation {

	/**
	 * Unique animation slug.
	 */
	private string $slug;

	/**
	 * Unique aliases for the animation.
	 */
	private array $aliases = [];

	/**
	 * Whether the animation uses a stylesheet (boolean or path).
	 */
	private $use_stylesheet = false;

	/**
	 * Whether to apply global view transition names.
	 */
	private $use_global_transition_names = true;

	/**
	 * Whether to apply post-specific view transition names.
	 */
	private $use_post_transition_names = true;

	/**
	 * Callback to customize animation stylesheet inline.
	 */
	private $get_stylesheet_callback = null;

	/**
	 * Default arguments.
	 */
	private array $default_args = [];

	/**
	 * Constructor.
	 */
	public function __construct( string $slug, array $config, array $default_args = [] ) {
		if ( ! $this->isValidSlug( $slug ) ) {
			throw new \InvalidArgumentException( sprintf( 'The animation slug "%s" is invalid.', esc_html( $slug ) ) );
		}

		$this->slug = $slug;
		$this->applyConfig( $config );
		$this->default_args = $default_args;
	}

	/**
	 * Gets slug.
	 */
	public function getSlug(): string {
		return $this->slug;
	}

	/**
	 * Gets aliases.
	 */
	public function getAliases(): array {
		return $this->aliases;
	}

	/**
	 * Gets the animation stylesheet as inline CSS.
	 */
	public function getStylesheet( string $alias = '', array $args = [] ): string {
		$css = '';
		if ( (bool) $this->use_stylesheet ) {
			$stylesheet_path = $this->use_stylesheet;
			if ( ! is_string( $stylesheet_path ) ) {
				$stylesheet_path = TKA_SITE_UTILITIES_PATH . "admin/css/view-transition-animation-{$this->slug}.css";
			}

			if ( file_exists( $stylesheet_path ) ) {
				$css = file_get_contents( $stylesheet_path );
			}
			if ( false === $css || '' === $css ) {
				return '';
			}
		}

		if ( is_callable( $this->get_stylesheet_callback ) ) {
			if ( '' === $alias ) {
				$alias = $this->slug;
			}
			$args = wp_parse_args( $args, $this->default_args );
			return (string) call_user_func_array(
				$this->get_stylesheet_callback,
				[ $css, $alias, $args ]
			);
		}

		return $css;
	}

	/**
	 * Check if global transition names should be applied.
	 */
	public function useGlobalTransitionNames( string $alias = '', array $args = [] ): bool {
		if ( is_bool( $this->use_global_transition_names ) ) {
			return $this->use_global_transition_names;
		}
		if ( '' === $alias ) {
			$alias = $this->slug;
		}
		$args = wp_parse_args( $args, $this->default_args );
		return call_user_func( $this->use_global_transition_names, $alias, $args );
	}

	/**
	 * Check if post transition names should be applied.
	 */
	public function usePostTransitionNames( string $alias = '', array $args = [] ): bool {
		if ( is_bool( $this->use_post_transition_names ) ) {
			return $this->use_post_transition_names;
		}
		if ( '' === $alias ) {
			$alias = $this->slug;
		}
		$args = wp_parse_args( $args, $this->default_args );
		return call_user_func( $this->use_post_transition_names, $alias, $args );
	}

	/**
	 * Apply configuration settings.
	 */
	private function applyConfig( array $config ): void {
		if ( isset( $config['aliases'] ) ) {
			$this->aliases = (array) $config['aliases'];
			foreach ( $this->aliases as $alias ) {
				if ( ! $this->isValidSlug( $alias ) ) {
					throw new \InvalidArgumentException( sprintf( 'The animation alias "%s" is invalid.', esc_html( $alias ) ) );
				}
			}
		}
		if ( isset( $config['use_stylesheet'] ) ) {
			$this->use_stylesheet = is_string( $config['use_stylesheet'] ) ? $config['use_stylesheet'] : (bool) $config['use_stylesheet'];
		}
		if ( isset( $config['use_global_transition_names'] ) ) {
			$this->use_global_transition_names = is_callable( $config['use_global_transition_names'] ) ?
				$config['use_global_transition_names'] :
				(bool) $config['use_global_transition_names'];
		}
		if ( isset( $config['use_post_transition_names'] ) ) {
			$this->use_post_transition_names = is_callable( $config['use_post_transition_names'] ) ?
				$config['use_post_transition_names'] :
				(bool) $config['use_post_transition_names'];
		}
		if ( isset( $config['get_stylesheet_callback'] ) && is_callable( $config['get_stylesheet_callback'] ) ) {
			$this->get_stylesheet_callback = $config['get_stylesheet_callback'];
		}
	}

	/**
	 * Validates animation slug.
	 */
	private function isValidSlug( string $slug ): bool {
		return (bool) preg_match( '/^[a-z][a-z0-9_-]+$/', $slug );
	}
}
