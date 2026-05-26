<?php

namespace TKA\WPUtils\Features;

/**
 * Handles security validation and registration for SVG uploads in WordPress.
 */
class SvgValidator {

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		add_filter( 'upload_mimes', [ $this, 'allowSvgMimeType' ] );
		add_filter( 'wp_check_filetype_and_ext', [ $this, 'checkFiletypeAndExt' ], 10, 4 );
		add_filter( 'wp_handle_upload_prefilter', [ $this, 'filterUpload' ] );
	}

	/**
	 * Add SVG mime types to the allowed list.
	 */
	public function allowSvgMimeType( array $mimes ): array {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Bypass WordPress extension mismatch filters for SVG files.
	 */
	public function checkFiletypeAndExt( array $data, string $file, string $filename, ?array $mimes = null ): array {
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( 'svg' === strtolower( $ext ) ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		return $data;
	}

	/**
	 * Filter uploaded file before it is saved, validating its contents.
	 */
	public function filterUpload( array $file ): array {
		if ( isset( $file['error'] ) && $file['error'] !== '' ) {
			return $file;
		}

		$ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
		if ( 'svg' === strtolower( $ext ) || 'image/svg+xml' === $file['type'] ) {
			$result = $this->analyzeSvgFile( $file['tmp_name'] );

			if ( ! $result['safe'] ) {
				$threats_string = implode( ' | ', $result['threats'] );
				$file['error']  = sprintf(
					__( 'Security Check Failed: The uploaded SVG file was rejected due to dangerous content. [Threats: %s]', 'tka-wp-utils' ),
					$threats_string
				);
			}
		}

		return $file;
	}

	/**
	 * Analyze SVG XML structure for security threats.
	 *
	 * Returns an array format:
	 * [
	 *   'safe' => bool,
	 *   'threats' => array of strings describing risks found
	 * ]
	 */
	public function analyzeSvgFile( string $filepath ): array {
		if ( ! file_exists( $filepath ) ) {
			return [
				'safe'    => false,
				'threats' => [ __( 'File does not exist.', 'tka-wp-utils' ) ],
			];
		}

		$content = file_get_contents( $filepath );
		if ( false === $content ) {
			return [
				'safe'    => false,
				'threats' => [ __( 'Could not read file content.', 'tka-wp-utils' ) ],
			];
		}

		$threats = [];

		// 1. Raw search for XML Entity Declarations to block XXE & Entity Expansion attacks
		if ( preg_match( '/<!ENTITY/i', $content ) ) {
			$threats[] = __( 'Malicious XML entity declaration (<!ENTITY) detected. Prevented XXE attack.', 'tka-wp-utils' );
		}
		if ( preg_match( '/<!DOCTYPE/i', $content ) ) {
			$threats[] = __( 'Custom Document Type Definition (<!DOCTYPE) detected. Prevented expansion attack.', 'tka-wp-utils' );
		}

		if ( ! empty( $threats ) ) {
			return [
				'safe'    => false,
				'threats' => $threats,
			];
		}

		// 2. Parse XML securely using DOMDocument
		$dom = new \DOMDocument();
		
		// Disable external entity loaders to prevent XXE in older PHP setups
		$old_entity_loader = false;
		if ( \PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
			$old_entity_loader = libxml_disable_entity_loader( true );
		}

		$libxml_internal_errors = libxml_use_internal_errors( true );

		// Load XML string safely
		$loaded = $dom->loadXML( $content, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NONET );

		// Restore entity loader if applicable
		if ( \PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
			libxml_disable_entity_loader( $old_entity_loader );
		}

		if ( ! $loaded ) {
			libxml_use_internal_errors( $libxml_internal_errors );
			return [
				'safe'    => false,
				'threats' => [ __( 'Malformed XML: SVG failed standard XML parsing.', 'tka-wp-utils' ) ],
			];
		}

		// Verify root node is svg
		if ( ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->tagName ) ) {
			libxml_use_internal_errors( $libxml_internal_errors );
			return [
				'safe'    => false,
				'threats' => [ __( 'Invalid Root: Root XML node is not <svg>.', 'tka-wp-utils' ) ],
			];
		}

		// 3. Scan elements for forbidden tags
		$forbidden_tags = [ 'script', 'iframe', 'object', 'embed', 'applet', 'foreignobject' ];
		$all_elements   = $dom->getElementsByTagName( '*' );

		foreach ( $all_elements as $element ) {
			$tag_name = strtolower( $element->tagName );

			if ( in_array( $tag_name, $forbidden_tags, true ) ) {
				$threats[] = sprintf( __( 'Forbidden tag <%s> detected.', 'tka-wp-utils' ), $element->tagName );
			}

			// Scan attributes
			if ( $element->hasAttributes() ) {
				foreach ( $element->attributes as $attr ) {
					$attr_name  = strtolower( $attr->name );
					$attr_value = strtolower( trim( $attr->value ) );

					// Block inline event handlers (onclick, onload, etc.)
					if ( str_starts_with( $attr_name, 'on' ) ) {
						$threats[] = sprintf( __( 'Event listener attribute "%s" detected on <%s>.', 'tka-wp-utils' ), $attr->name, $element->tagName );
					}

					// Block dangerous protocols in URL-like attributes
					if ( in_array( $attr_name, [ 'href', 'xlink:href', 'src', 'action', 'data', 'xlink:arcrole' ], true ) ) {
						if ( str_starts_with( $attr_value, 'javascript:' ) ) {
							$threats[] = sprintf( __( 'Malicious "javascript:" protocol inside "%s" on <%s>.', 'tka-wp-utils' ), $attr->name, $element->tagName );
						} elseif ( str_starts_with( $attr_value, 'data:' ) && ! str_starts_with( $attr_value, 'data:image/' ) ) {
							$threats[] = sprintf( __( 'Suspicious non-image "data:" protocol inside "%s" on <%s>.', 'tka-wp-utils' ), $attr->name, $element->tagName );
						}
					}

					// Block dangerous style values
					if ( 'style' === $attr_name ) {
						if ( str_contains( $attr_value, 'javascript:' ) || str_contains( $attr_value, 'expression(' ) ) {
							$threats[] = sprintf( __( 'Malicious code in style attribute on <%s>.', 'tka-wp-utils' ), $element->tagName );
						}
					}
				}
			}
		}

		// 4. Scan style blocks
		$style_elements = $dom->getElementsByTagName( 'style' );
		foreach ( $style_elements as $style ) {
			$style_content = strtolower( $style->nodeValue );
			if ( str_contains( $style_content, 'javascript:' ) || str_contains( $style_content, 'expression(' ) ) {
				$threats[] = __( 'Malicious code or scripting inside <style> element.', 'tka-wp-utils' );
			}
		}

		libxml_use_internal_errors( $libxml_internal_errors );

		return [
			'safe'    => empty( $threats ),
			'threats' => $threats,
		];
	}
}
