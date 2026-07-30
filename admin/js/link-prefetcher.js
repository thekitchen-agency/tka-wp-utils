(function() {
	'use strict';

	// List of static file extensions to exclude from prefetching
	const excludeExtensions = /\.(png|jpe?g|gif|svg|webp|pdf|zip|tar|gz|mp4|webm|mp3|wav|ogg|xml|txt|json|css|js)($|\?)/i;

	// Set to keep track of already prefetched URLs
	const prefetchedUrls = new Set();

	// Do not prefetch the current page URL (strip hashes)
	prefetchedUrls.add(window.location.href.split('#')[0]);

	/**
	 * Find the closest anchor tag.
	 */
	function getAnchorTarget(target) {
		return target.closest('a');
	}

	/**
	 * Check if a URL is eligible for prefetching.
	 * Returns the normalized URL if eligible, false otherwise.
	 */
	function isPrefetchable(url) {
		if (!url || typeof url !== 'string') {
			return false;
		}

		try {
			const parsedUrl = new URL(url, window.location.href);

			// Only HTTP/HTTPS protocols
			if (parsedUrl.protocol !== 'http:' && parsedUrl.protocol !== 'https:') {
				return false;
			}

			// Only internal links (same origin/hostname)
			if (parsedUrl.hostname !== window.location.hostname) {
				return false;
			}

			// Exclude WordPress admin and login pages
			if (parsedUrl.pathname.includes('/wp-admin') || parsedUrl.pathname.includes('wp-login.php')) {
				return false;
			}

			// Exclude static assets and media files
			if (excludeExtensions.test(parsedUrl.pathname)) {
				return false;
			}

			// Exclude current page or already prefetched URLs
			const cleanUrl = parsedUrl.origin + parsedUrl.pathname + parsedUrl.search;
			if (prefetchedUrls.has(cleanUrl)) {
				return false;
			}

			return cleanUrl;
		} catch (e) {
			return false;
		}
	}

	/**
	 * Perform the prefetch by adding a <link rel="prefetch"> tag to the head.
	 */
	function prefetch(url) {
		const cleanUrl = isPrefetchable(url);
		if (!cleanUrl) {
			return;
		}

		prefetchedUrls.add(cleanUrl);

		const link = document.createElement('link');
		link.rel = 'prefetch';
		link.href = cleanUrl;
		document.head.appendChild(link);
	}

	let hoverTimeout = null;

	// Hover (mouseover) event delegation on body
	document.addEventListener('mouseover', function(e) {
		const anchor = getAnchorTarget(e.target);
		if (!anchor) {
			return;
		}

		// Small delay to ensure the user is actually hovering, not just scanning past
		if (hoverTimeout) {
			clearTimeout(hoverTimeout);
		}
		hoverTimeout = setTimeout(function() {
			prefetch(anchor.href);
		}, 60);
	}, { passive: true });

	// Clear timeout on mouseout to prevent prefetching on fast swipe
	document.addEventListener('mouseout', function(e) {
		if (hoverTimeout) {
			clearTimeout(hoverTimeout);
			hoverTimeout = null;
		}
	}, { passive: true });

	// Touchstart event delegation on body (immediate signal on mobile)
	document.addEventListener('touchstart', function(e) {
		const anchor = getAnchorTarget(e.target);
		if (!anchor) {
			return;
		}
		prefetch(anchor.href);
	}, { passive: true });

})();
