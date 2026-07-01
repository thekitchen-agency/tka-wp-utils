/**
 * TKA Site Utilities Page Transitions Frontend Script
 */

window.tkaInitPageTransitions = ( config ) => {
	if ( ! window.navigation || ! ( 'CSSViewTransitionRule' in window ) ) {
		window.console.warn(
			'Page transitions not loaded as the browser is lacking support.'
		);
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
	 * Detect page type based on body classes
	 */
	const detectPageType = ( bodyEl ) => {
		if ( ! bodyEl ) {
			return 'any';
		}
		if ( bodyEl.classList.contains( 'home' ) || bodyEl.classList.contains( 'front-page' ) ) {
			return 'home';
		}
		if ( bodyEl.classList.contains( 'archive' ) || bodyEl.classList.contains( 'category' ) || bodyEl.classList.contains( 'tag' ) ) {
			return 'archive';
		}
		if ( bodyEl.classList.contains( 'page' ) ) {
			return 'single_page';
		}
		if ( bodyEl.classList.contains( 'single' ) ) {
			return 'single_post';
		}
		return 'any';
	};

	// Track clicked link context to guess target page types during pageswap
	let lastClickedLinkHint = null;
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest( 'a' );
		if ( link ) {
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
			if ( rule.to_type !== 'any' && destTypeHint && rule.to_type !== destTypeHint ) {
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
						const element = bodyElement.querySelector( selector );
						return [ element, name ];
					} )
			: [];

		const postEntries =
			animationConfig.usePostTransitionNames && articleElement
				? Object.entries( config.postTransitionNames || {} ).map(
						( [ selector, name ] ) => {
							const element = articleElement.querySelector( selector );
							return [ element, name ];
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

		await vtPromise;

		for ( const [ element ] of entries ) {
			if ( ! element ) {
				continue;
			}
			element.style.viewTransitionName = '';
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
		return document.querySelector( config.postSelector );
	};

	const getArticleForUrl = ( url ) => {
		if ( ! config.postSelector ) {
			return null;
		}
		const postLinkSelector = appendSelectors(
			config.postSelector,
			'a[href="' + url + '"]'
		);
		const articleLink = document.querySelector( postLinkSelector );
		if ( ! articleLink ) {
			return null;
		}
		return articleLink.closest( config.postSelector );
	};

	/**
	 * pageswap event handler (outgoing page)
	 */
	window.addEventListener( 'pageswap', ( event ) => {
		if ( event.viewTransition ) {
			const srcType = detectPageType( document.body );
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

			console.log( '[TKA Transitions] pageswap details:', {
				srcType,
				srcUrl,
				destUrl,
				animationType,
				transitionClass
			} );

			// Add transition type to event
			event.viewTransition.types.add( animationType );
				
			document.documentElement.classList.add( transitionClass );
			sessionStorage.setItem( 'tka_active_transition_class', transitionClass );

			// Set temporary transition names
			let articleEl = null;
			if ( document.body.classList.contains( 'single' ) || document.body.classList.contains( 'page' ) ) {
				articleEl = getArticle();
			} else if (
				document.body.classList.contains( 'home' ) ||
				document.body.classList.contains( 'blog' ) ||
				document.body.classList.contains( 'archive' )
			) {
				articleEl = getArticleForUrl( destUrl );
			}

			const viewTransitionEntries = getViewTransitionEntries(
				animationType,
				document.body,
				articleEl
			);

			if ( viewTransitionEntries ) {
				setTemporaryViewTransitionNames(
					viewTransitionEntries,
					event.viewTransition.finished
				);
			}
			
			// Clean up transition class when finished
			event.viewTransition.finished.then(() => {
				document.documentElement.classList.remove( transitionClass );
			});
		}
	} );

	/**
	 * pagereveal event handler (incoming page)
	 */
	window.addEventListener( 'pagereveal', ( event ) => {
		if ( event.viewTransition ) {
			const destType = detectPageType( document.body );
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

			event.viewTransition.types.add( animationType );

			// Get the active transition class name (either stored or re-evaluated)
			let transitionClass = sessionStorage.getItem( 'tka_active_transition_class' );
			if ( ! transitionClass ) {
				transitionClass = rule && rule.animation === 'custom' && rule.custom_class 
					? rule.custom_class 
					: 'tka-transition-' + animationType;
			}
			
			console.log( '[TKA Transitions] pagereveal details:', {
				destType,
				destUrl,
				srcType,
				srcUrl,
				animationType,
				transitionClass
			} );
			
			document.documentElement.classList.add( transitionClass );

			let articleEl = null;
			if ( document.body.classList.contains( 'single' ) || document.body.classList.contains( 'page' ) ) {
				articleEl = getArticle();
			} else if (
				document.body.classList.contains( 'home' ) ||
				document.body.classList.contains( 'archive' )
			) {
				const fromUrl = ( window.navigation && window.navigation.activation && window.navigation.activation.from )
					? window.navigation.activation.from.url
					: null;
				articleEl = fromUrl ? getArticleForUrl( fromUrl ) : null;
			}

			const viewTransitionEntries = getViewTransitionEntries(
				animationType,
				document.body,
				articleEl
			);

			if ( viewTransitionEntries ) {
				setTemporaryViewTransitionNames(
					viewTransitionEntries,
					event.viewTransition.ready
				);
			}

			// Clean up classes and sessionStorage
			event.viewTransition.finished.then( () => {
				document.documentElement.classList.remove( transitionClass );
				sessionStorage.removeItem( 'tka_active_transition_class' );
			} );
		}
	} );
};
