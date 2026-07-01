<?php

namespace TKA\WPUtils\Features;

/**
 * Class representing a page transition animation registry.
 */
final class PageTransitionAnimationRegistry {

	/**
	 * Registered animation instances, keyed by slug.
	 */
	private array $registered_animations = [];

	/**
	 * Map of aliases to animation slugs.
	 */
	private array $alias_map = [];

	/**
	 * Registers a view transition animation.
	 */
	public function registerAnimation( string $slug, array $config, array $default_args = [] ): bool {
		if ( isset( $this->alias_map[ $slug ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf( 'The animation slug "%s" conflicts with an existing slug or alias.', esc_html( $slug ) ),
				'1.0.0'
			);
			return false;
		}

		try {
			$animation = new PageTransitionAnimation( $slug, $config, $default_args );
		} catch ( \InvalidArgumentException $e ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( $e->getMessage() ),
				'1.0.0'
			);
			return false;
		}

		// Check alias conflicts.
		$aliases = $animation->getAliases();
		foreach ( $aliases as $alias ) {
			if ( isset( $this->alias_map[ $alias ] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf( 'The animation alias "%s" conflicts with an existing slug or alias.', esc_html( $alias ) ),
					'1.0.0'
				);
				return false;
			}
		}

		$this->registered_animations[ $slug ] = $animation;
		$this->alias_map[ $slug ]             = $slug;
		foreach ( $aliases as $alias ) {
			$this->alias_map[ $alias ] = $slug;
		}

		return true;
	}

	/**
	 * Gets the animation stylesheet for the given alias, as inline CSS.
	 */
	public function getAnimationStylesheet( string $alias, array $args = [] ): string {
		if ( ! isset( $this->alias_map[ $alias ] ) ) {
			return '';
		}

		return $this->registered_animations[ $this->alias_map[ $alias ] ]->getStylesheet( $alias, $args );
	}

	/**
	 * Returns whether to apply the global view transition names for the given animation alias.
	 */
	public function useAnimationGlobalTransitionNames( string $alias, array $args = [] ): bool {
		if ( ! isset( $this->alias_map[ $alias ] ) ) {
			return true;
		}

		return $this->registered_animations[ $this->alias_map[ $alias ] ]->useGlobalTransitionNames( $alias, $args );
	}

	/**
	 * Returns whether to apply the post-specific view transition names for the given animation alias.
	 */
	public function useAnimationPostTransitionNames( string $alias, array $args = [] ): bool {
		if ( ! isset( $this->alias_map[ $alias ] ) ) {
			return true;
		}

		return $this->registered_animations[ $this->alias_map[ $alias ] ]->usePostTransitionNames( $alias, $args );
	}
}
