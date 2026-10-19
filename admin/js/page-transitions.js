/**
 * TKA Site Utilities Page Transitions Frontend Script
 */

window.tkaInitPageTransitions = ( config ) => {
	// Require cross-document view transition support
	const hasVtSupport = ( 'onpageswap' in window && 'onpagereveal' in window ) || ( 'CSSViewTransitionRule' in window );
	if ( ! hasVtSupport ) {
		return;
	}

	const rules = Array.isArray( config.rules )
		? config.rules
		: Object.values( config.rules || {} );

	/**
	 * Helper to match URL paths with wildcard pattern (e.g., /blog/*)
	 */
	const matchUrlPattern = ( url, pattern ) => {
		if ( ! pattern || pattern === '*' ) {
			return true;
		}
		try {
			const path = new URL( url, window.location.origin ).pathname;
			// Escape regex except *
			const escaped = pattern.replace( /[-( )\[\]{} \/\\^$+.?|]/g, '\\$&' );
			const regexStr = '^' + escaped.replace( /\\\*/g, '.*' ) + '$';
			const regex = new RegExp( regexStr );
			return regex.test( path ) || path.startsWith( pattern ) || path === pattern;
		} catch ( e ) {
			return false;
		}
	};

	/**
	 * Detect page type based on body/root element classes
	 */
	const detectPageType = ( el ) => {
		const target = el || document.body || document.documentElement;
		if ( ! target || ! target.classList ) {
			return 'any';
		}
		if ( target.classList.contains( 'home' ) || target.classList.contains( 'front-page' ) ) {
			return 'home';
		}
		if ( target.classList.contains( 'archive' ) || target.classList.contains( 'category' ) || target.classList.contains( 'tag' ) ) {
			return 'archive';
		}
		if ( target.classList.contains( 'page' ) ) {
			return 'single_page';
		}
		if ( target.classList.contains( 'single' ) ) {
			return 'single_post';
		}
		return 'any';
	};

	// Track clicked link context to guess target page types during pageswap
	let lastClickedLinkHint = null;
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest( 'a' );
		if ( link && link.href ) {
			let type = 'any';
			if ( link.closest( '.wp-block-post' ) || link.closest( 'article' ) || link.matches( '.wp-block-post-title a' ) || link.matches( '.entry-title a' ) ) {
				type = 'single_post';
			} else if ( link.closest( '.nav-menu' ) || link.closest( 'header' ) || link.closest( '.wp-block-navigation' ) ) {
				type = 'single_page';
			}
			lastClickedLinkHint = {
				type: type,
				url: link.href
			};
		}
	} );

	/**
	 * Find first matching rule for a transition
	 */
	const findMatchingRule = ( srcType, srcUrl, destUrl, destTypeHint ) => {
		for ( const rule of rules ) {
			// 1. Verify From condition
			if ( rule.from_type !== 'any' && rule.from_type !== srcType ) {
				continue;
			}
			if ( rule.from_type === 'custom_url' && ! matchUrlPattern( srcUrl, rule.from_url ) ) {
				continue;
			}

			// 2. Verify To condition
			if ( rule.to_type !== 'any' && destTypeHint && destTypeHint !== 'any' && rule.to_type !== destTypeHint ) {
				continue;
			}
			if ( rule.to_type === 'custom_url' && ! matchUrlPattern( destUrl, rule.to_url ) ) {
				continue;
			}

			return rule;
		}
		return null;
	};

	/**
	 * Gets all view transition entries relevant for a view transition.
	 */
	const getViewTransitionEntries = (
		animationType,
		bodyElement,
		articleElement
	) => {
		// Fetch animation config or defaults
		const animations = config.animations || {};
		const animationConfig = animations[ animationType ] || { useGlobalTransitionNames: true, usePostTransitionNames: true };

		const globalEntries = animationConfig.useGlobalTransitionNames
			? Object.entries( config.globalTransitionNames || {} )
					.filter( ( [ selector, name ] ) => {
						if (
							typeof animationType === 'string' &&
							( animationType.startsWith( 'slide' ) ||
								animationType.startsWith( 'swipe' ) ||
								animationType.startsWith( 'wipe' ) ) &&
							name === 'main'
						) {
							return false;
						}
						return true;
					} )
					.map( ( [ selector, name ] ) => {
						try {
							const element = bodyElement ? bodyElement.querySelector( selector ) : document.querySelector( selector );
							return [ element, name ];
						} catch ( e ) {
							return [ null, name ];
						}
					} )
			: [];

		const postEntries =
			animationConfig.usePostTransitionNames && articleElement
				? Object.entries( config.postTransitionNames || {} ).map(
						( [ selector, name ] ) => {
							try {
								const element = articleElement.querySelector( selector );
								return [ element, name ];
							} catch ( e ) {
								return [ null, name ];
							}
						}
				  )
				: [];

		return [ ...globalEntries, ...postEntries ];
	};

	/**
	 * Temporarily sets view transition names until the transition completes.
	 */
	const setTemporaryViewTransitionNames = async ( entries, vtPromise ) => {
		for ( const [ element, name ] of entries ) {
			if ( ! element ) {
				continue;
			}
			element.style.viewTransitionName = name;
		}

		try {
			await vtPromise;
		} catch ( e ) {
			// Ignore rejection so cleanup still runs
		} finally {
			for ( const [ element ] of entries ) {
				if ( ! element ) {
					continue;
				}
				element.style.viewTransitionName = '';
			}
		}
	};

	const appendSelectors = ( selectors, append ) => {
		return selectors
			.split( ',' )
			.map( ( subselector ) => subselector.trim() + ' ' + append )
			.join( ',' );
	};

	const getArticle = () => {
		if ( ! config.postSelector ) {
			return null;
		}
		try {
			return document.querySelector( config.postSelector );
		} catch ( e ) {
			return null;
		}
	};

	const getArticleForUrl = ( url ) => {
		if ( ! config.postSelector || ! url ) {
			return null;
		}
		try {
			const safeUrl = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape( url ) : url.replace( /"/g, '\\"' );
			const postLinkSelector = appendSelectors(
				config.postSelector,
				'a[href="' + safeUrl + '"]'
			);
			const articleLink = document.querySelector( postLinkSelector );
			if ( ! articleLink ) {
				return null;
			}
			return articleLink.closest( config.postSelector );
		} catch ( e ) {
			return null;
		}
	};

	/**
	 * pageswap event handler (outgoing page)
	 */
	window.addEventListener( 'pageswap', ( event ) => {
		if ( event.viewTransition ) {
			const bodyEl = document.body || document.documentElement;
			const srcType = detectPageType( bodyEl );
			const srcUrl = window.location.href;
			
			let destUrl = '';
			if ( event.activation && event.activation.entry ) {
				destUrl = event.activation.entry.url;
			} else if ( lastClickedLinkHint ) {
				destUrl = lastClickedLinkHint.url;
			}

			// Guess target type from click context
			let destTypeHint = 'any';
			if ( lastClickedLinkHint && lastClickedLinkHint.url === destUrl ) {
				destTypeHint = lastClickedLinkHint.type;
			}

			// Store source state for pagereveal
			sessionStorage.setItem( 'tka_transition_source', JSON.stringify( {
				type: srcType,
				url: srcUrl
			} ) );

			// Find matching rule or fall back to default animation
			const rule = findMatchingRule( srcType, srcUrl, destUrl, destTypeHint );
			const animationType = rule ? rule.animation : config.defaultAnimation;

			// Set custom transition classes on html element for custom styles support
			const transitionClass = rule && rule.animation === 'custom' && rule.custom_class 
				? rule.custom_class 
				: 'tka-transition-' + animationType;

			// Add transition type to event (if browser supports types)
			if ( event.viewTransition.types ) {
				event.viewTransition.types.add( animationType );
			}
				
			document.documentElement.classList.add( transitionClass );
			sessionStorage.setItem( 'tka_active_transition_class', transitionClass );

			// Set temporary transition names
			let articleEl = null;
			if ( bodyEl && ( bodyEl.classList.contains( 'single' ) || bodyEl.classList.contains( 'page' ) ) ) {
				articleEl = getArticle();
			} else if (
				bodyEl &&
				( bodyEl.classList.contains( 'home' ) ||
					bodyEl.classList.contains( 'blog' ) ||
					bodyEl.classList.contains( 'archive' ) )
			) {
				articleEl = getArticleForUrl( destUrl );
			}

			const viewTransitionEntries = getViewTransitionEntries(
				animationType,
				bodyEl,
				articleEl
			);

			if ( viewTransitionEntries ) {
				setTemporaryViewTransitionNames(
					viewTransitionEntries,
					event.viewTransition.finished
				);
			}
			
			// Clean up transition class when finished (or aborted/skipped)
			event.viewTransition.finished.finally(() => {
				document.documentElement.classList.remove( transitionClass );
			});

			event.viewTransition.ready.catch( ( err ) => {
				console.warn( '[TKA View Transitions] Pageswap ready aborted:', err );
			} );
			event.viewTransition.finished.catch( ( err ) => {
				console.warn( '[TKA View Transitions] Pageswap finished aborted:', err );
			} );
		}
	} );

	/**
	 * pagereveal event handler (incoming page)
	 */
	window.addEventListener( 'pagereveal', ( event ) => {
		if ( event.viewTransition ) {
			const bodyEl = document.body || document.documentElement;
			const destType = detectPageType( bodyEl );
			const destUrl = window.location.href;

			// Retrieve source state from sessionStorage
			let srcType = 'any';
			let srcUrl = '';
			const storedSource = sessionStorage.getItem( 'tka_transition_source' );
			if ( storedSource ) {
				try {
					const sourceInfo = JSON.parse( storedSource );
					srcType = sourceInfo.type;
					srcUrl = sourceInfo.url;
				} catch ( e ) {}
			}

			// Find matching rule
			const rule = findMatchingRule( srcType, srcUrl, destUrl, destType );
			const animationType = rule ? rule.animation : config.defaultAnimation;

			if ( event.viewTransition.types ) {
				event.viewTransition.types.add( animationType );
			}

			// Get the active transition class name (either stored or re-evaluated)
			let transitionClass = sessionStorage.getItem( 'tka_active_transition_class' );
			if ( ! transitionClass ) {
				transitionClass = rule && rule.animation === 'custom' && rule.custom_class 
					? rule.custom_class 
					: 'tka-transition-' + animationType;
			}
			
			document.documentElement.classList.add( transitionClass );

			let articleEl = null;
			if ( bodyEl && ( bodyEl.classList.contains( 'single' ) || bodyEl.classList.contains( 'page' ) ) ) {
				articleEl = getArticle();
			} else if (
				bodyEl &&
				( bodyEl.classList.contains( 'home' ) ||
					bodyEl.classList.contains( 'archive' ) )
			) {
				const fromUrl = ( window.navigation && window.navigation.activation && window.navigation.activation.from )
					? window.navigation.activation.from.url
					: null;
				articleEl = fromUrl ? getArticleForUrl( fromUrl ) : null;
			}

			const viewTransitionEntries = getViewTransitionEntries(
				animationType,
				bodyEl,
				articleEl
			);

			if ( viewTransitionEntries ) {
				setTemporaryViewTransitionNames(
					viewTransitionEntries,
					event.viewTransition.finished
				);
			}

			// Clean up classes and sessionStorage when finished (or aborted/skipped)
			event.viewTransition.finished.finally( () => {
				document.documentElement.classList.remove( transitionClass );
				if ( sessionStorage.getItem( 'tka_active_transition_class' ) === transitionClass ) {
					sessionStorage.removeItem( 'tka_active_transition_class' );
				}
				sessionStorage.removeItem( 'tka_transition_source' );
			} );

			event.viewTransition.ready.catch( ( err ) => {
				console.warn( '[TKA View Transitions] Pagereveal ready aborted:', err );
			} );
			event.viewTransition.finished.catch( ( err ) => {
				console.warn( '[TKA View Transitions] Pagereveal finished aborted:', err );
			} );
		}
	} );

	/**
	 * BFCache restoration handler
	 */
	window.addEventListener( 'pageshow', ( event ) => {
		if ( event.persisted ) {
			const classesToRemove = Array.from( document.documentElement.classList ).filter( ( c ) => c.startsWith( 'tka-transition-' ) );
			classesToRemove.forEach( ( c ) => document.documentElement.classList.remove( c ) );
			sessionStorage.removeItem( 'tka_active_transition_class' );
			sessionStorage.removeItem( 'tka_transition_source' );
		}
	} );
};
