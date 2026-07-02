<?php
/**
 * Shared DOM helpers for exact shell rendering.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ══════════════════════════════════════════════════════════════
 * DOM HELPER FUNCTIONS
 * ══════════════════════════════════════════════════════════════ */

function leadwerk_theme_create_document_dom( $html ) {
	$dom = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	return array( $dom, new DOMXPath( $dom ) );
}

function leadwerk_theme_extract_body_sections_from_html( $html ) {
	$sections = array();
	$dom      = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );
	$list  = $xpath->query( '//body/main/section' );
	if ( ! ( $list instanceof DOMNodeList ) || 0 === $list->length ) {
		$list = $xpath->query( '//body/section' );
	}
	if ( $list instanceof DOMNodeList ) {
		foreach ( $list as $node ) {
			if ( $node instanceof DOMNode ) {
				$sections[] = $dom->saveHTML( $node );
			}
		}
	}
	return $sections;
}

/**
 * Create template DOM from HTML fragment.
 *
 * @param string $html HTML fragment.
 * @return array{0: DOMDocument, 1: DOMXPath, 2: DOMElement|null}
 */
function leadwerk_theme_create_template_dom( $html ) {
	$dom = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?><div id="leadwerk-root">' . $html . '</div>' );
	libxml_clear_errors();
	$xpath   = new DOMXPath( $dom );
	$section = leadwerk_theme_dom_first( $xpath, '//*[@id="leadwerk-root"]/*[1]' );
	return array( $dom, $xpath, $section instanceof DOMElement ? $section : null );
}

function leadwerk_theme_dom_query( $context, $query, $scope = null ) {
	if ( $context instanceof DOMXPath ) {
		$list = $context->query( $query, $scope );
	} else {
		$list = ( new DOMXPath( $context->ownerDocument ) )->query( $query, $context );
	}
	$nodes = array();
	if ( $list instanceof DOMNodeList ) {
		foreach ( $list as $node ) {
			if ( $node instanceof DOMNode ) {
				$nodes[] = $node;
			}
		}
	}
	return $nodes;
}

function leadwerk_theme_dom_first( $context, $query, $scope = null ) {
	$nodes = leadwerk_theme_dom_query( $context, $query, $scope );
	return ! empty( $nodes[0] ) ? $nodes[0] : null;
}

function leadwerk_theme_dom_outer_html( $node ) {
	return $node instanceof DOMNode ? $node->ownerDocument->saveHTML( $node ) : '';
}

function leadwerk_theme_dom_clear( $node ) {
	if ( ! $node instanceof DOMNode ) {
		return;
	}
	while ( $node->firstChild ) {
		$node->removeChild( $node->firstChild );
	}
}

function leadwerk_theme_dom_set_inner_html( $node, $html ) {
	if ( ! $node instanceof DOMNode ) {
		return;
	}
	$html = (string) $html;
	leadwerk_theme_dom_clear( $node );
	if ( '' === trim( $html ) ) {
		return;
	}
	$temp = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$temp->loadHTML( '<?xml encoding="utf-8" ?><div id="leadwerk-fragment">' . wp_kses_post( $html ) . '</div>' );
	libxml_clear_errors();
	$fragment_nodes = ( new DOMXPath( $temp ) )->query( '//*[@id="leadwerk-fragment"]/* | //*[@id="leadwerk-fragment"]/text()' );
	if ( ! $fragment_nodes instanceof DOMNodeList ) {
		return;
	}
	foreach ( $fragment_nodes as $child ) {
		$node->appendChild( $node->ownerDocument->importNode( $child, true ) );
	}
}

function leadwerk_theme_dom_set_trusted_inner_html( $node, $html ) {
	if ( ! $node instanceof DOMNode ) {
		return;
	}
	$html = (string) $html;
	leadwerk_theme_dom_clear( $node );
	if ( '' === trim( $html ) ) {
		return;
	}
	$temp = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$temp->loadHTML( '<?xml encoding="utf-8" ?><div id="leadwerk-fragment">' . $html . '</div>' );
	libxml_clear_errors();
	$fragment_nodes = ( new DOMXPath( $temp ) )->query( '//*[@id="leadwerk-fragment"]/* | //*[@id="leadwerk-fragment"]/text()' );
	if ( ! $fragment_nodes instanceof DOMNodeList ) {
		return;
	}
	foreach ( $fragment_nodes as $child ) {
		$node->appendChild( $node->ownerDocument->importNode( $child, true ) );
	}
}

function leadwerk_theme_dom_set_text( $node, $text ) {
	if ( ! $node instanceof DOMNode ) {
		return;
	}
	leadwerk_theme_dom_clear( $node );
	$node->appendChild( $node->ownerDocument->createTextNode( wp_strip_all_tags( (string) $text ) ) );
}

function leadwerk_theme_dom_set_attr( $node, $attr, $value ) {
	if ( ! $node instanceof DOMElement ) {
		return;
	}
	$value = (string) $value;
	if ( '' === trim( $value ) ) {
		$node->removeAttribute( $attr );
		return;
	}
	$node->setAttribute( $attr, $value );
}

function leadwerk_theme_dom_remove( $node ) {
	if ( $node instanceof DOMNode && $node->parentNode ) {
		$node->parentNode->removeChild( $node );
	}
}

function leadwerk_theme_dom_ensure_count( $nodes, $count ) {
	$nodes = array_values( array_filter( $nodes ) );
	$count = max( 0, (int) $count );
	if ( empty( $nodes ) ) {
		return array();
	}
	$template = end( $nodes );
	$parent   = $template instanceof DOMNode ? $template->parentNode : null;
	while ( count( $nodes ) > $count ) {
		$node = array_pop( $nodes );
		leadwerk_theme_dom_remove( $node );
	}
	if ( ! $parent || ! $template ) {
		return $nodes;
	}
	while ( count( $nodes ) < $count ) {
		$clone   = $template->cloneNode( true );
		$parent->appendChild( $clone );
		$nodes[] = $clone;
	}
	return $nodes;
}

function leadwerk_theme_dom_toggle_class( $node, $class, $enabled ) {
	if ( ! $node instanceof DOMElement ) {
		return;
	}
	$classes = preg_split( '/\s+/', trim( (string) $node->getAttribute( 'class' ) ) );
	$classes = array_filter( is_array( $classes ) ? $classes : array() );
	if ( $enabled && ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}
	if ( ! $enabled ) {
		$classes = array_values(
			array_filter(
				$classes,
				static function ( $item ) use ( $class ) {
					return $item !== $class;
				}
			)
		);
	}
	$node->setAttribute( 'class', trim( implode( ' ', $classes ) ) );
}

/* ══════════════════════════════════════════════════════════════
 * 5. MARKUP HELPERS
 * ══════════════════════════════════════════════════════════════ */

function leadwerk_theme_normalize_heading_markup( $html ) {
	$html = (string) $html;
	if ( class_exists( 'Leadwerk_Content_Schema' ) && method_exists( 'Leadwerk_Content_Schema', 'sanitize_heading_html' ) ) {
		return Leadwerk_Content_Schema::sanitize_heading_html( $html );
	}
	$html = wp_kses_post( $html );
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		return '';
	}
	$html = preg_replace( '#</?(?:p|div|section|article|h1|h2|h3|h4|h5|h6)\b[^>]*>#i', '', $html );
	$html = preg_replace( '/(?:<br>\s*){3,}/i', '<br><br>', (string) $html );
	$html = is_string( $html ) ? trim( $html ) : '';
	return '' === trim( wp_strip_all_tags( $html ) ) ? '' : $html;
}

function leadwerk_theme_normalize_paragraph_markup( $html ) {
	$normalized = leadwerk_theme_normalize_heading_markup( $html );
	return '' === trim( wp_strip_all_tags( $normalized ) ) ? '' : $normalized;
}

function leadwerk_theme_force_strong_heading_markup( $html ) {
	$normalized = leadwerk_theme_normalize_heading_markup( $html );
	if ( '' === trim( wp_strip_all_tags( $normalized ) ) ) {
		return '';
	}
	if ( preg_match( '/^\s*<strong\b[^>]*>.*<\/strong>\s*$/is', $normalized ) ) {
		return $normalized;
	}
	return '<strong>' . $normalized . '</strong>';
}

function leadwerk_theme_set_placeholder_markup( $target, $html, $mode = 'container' ) {
	if ( ! $target instanceof DOMNode ) {
		return;
	}
	switch ( (string) $mode ) {
		case 'heading':
			$html = leadwerk_theme_normalize_heading_markup( $html );
			break;
		case 'paragraph':
			$html = leadwerk_theme_normalize_paragraph_markup( $html );
			break;
		case 'container':
		default:
			$html = (string) $html;
			break;
	}
	leadwerk_theme_dom_set_inner_html( $target, $html );
}

/**
 * Resolve a static shell asset path (Fotos/...) via media library or importer bundle.
 *
 * @param string $path Relative or absolute path.
 * @return string
 */
function leadwerk_theme_resolve_francis_theme_asset_url( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}
	if ( preg_match( '#^(?:https?:)?//#i', $path ) || preg_match( '#^(?:mailto|tel|javascript):#i', $path ) ) {
		return $path;
	}

	$normalized = str_replace( '\\', '/', $path );
	if ( preg_match( '#^(?:Fotos|fotos)/#i', $normalized ) && function_exists( 'leadwerk_theme_resolve_source_asset_url' ) ) {
		$resolved = leadwerk_theme_resolve_source_asset_url( $normalized );
		return '' !== $resolved ? $resolved : $path;
	}

	return $path;
}

function leadwerk_theme_get_exact_image_url( $image_id, $fallback = '' ) {
	$image_id = absint( $image_id );
	if ( $image_id ) {
		$url = wp_get_attachment_image_url( $image_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	$fallback = (string) $fallback;
	if ( '' !== $fallback ) {
		return leadwerk_theme_resolve_francis_theme_asset_url( $fallback );
	}
	return '';
}

/**
 * Mediathek-Anhang-ID oder Roh-URL (Legacy/Import).
 *
 * @param mixed $value Anhang-ID (int/string) oder URL-String.
 * @return string Aufgelöste URL oder getrimmter String.
 */
function leadwerk_theme_resolve_media_url( $value ) {
	if ( is_int( $value ) || ( is_string( $value ) && '' !== $value && is_numeric( trim( $value ) ) ) ) {
		$id = (int) $value;
		if ( $id > 0 ) {
			$url = wp_get_attachment_url( $id );
			return $url ? $url : '';
		}
	}
	return trim( (string) $value );
}

function leadwerk_theme_bind_exact_image( $xpath, $context, $query, $image_id, $alt = '' ) {
	$image = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( ! $image instanceof DOMElement ) {
		return;
	}
	$fallback = (string) $image->getAttribute( 'src' );
	$url      = leadwerk_theme_get_exact_image_url( (int) $image_id, $fallback );
	leadwerk_theme_dom_set_attr( $image, 'src', $url );
	if ( '' !== trim( (string) $alt ) ) {
		leadwerk_theme_dom_set_attr( $image, 'alt', $alt );
	}
}

function leadwerk_theme_resolve_exact_href( $page_key, $url = '' ) {
	$page_key = trim( (string) $page_key );
	$url      = trim( (string) $url );
	if ( '' !== $page_key ) {
		return leadwerk_theme_get_page_url( $page_key, leadwerk_theme_get_current_lang(), '' !== $url ? $url : '#' );
	}
	return '' !== $url ? $url : '#';
}

function leadwerk_theme_bind_exact_button( $xpath, $context, $query, $label, $page_key = '', $url = '' ) {
	$button = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( ! $button instanceof DOMElement ) {
		return;
	}
	if ( '' === trim( (string) $label ) ) {
		leadwerk_theme_dom_remove( $button );
		return;
	}
	leadwerk_theme_dom_set_attr( $button, 'href', leadwerk_theme_resolve_exact_href( $page_key, $url ) );
	leadwerk_theme_dom_set_text( $button, $label );
}

/**
 * Set href (and optional label) on an anchor that keeps a trailing <svg> (e.g. link-arrow).
 *
 * @param DOMXPath $xpath   XPath.
 * @param DOMNode  $context Context node.
 * @param string   $query   Anchor query.
 * @param string   $label   Link text (optional if href is set).
 * @param string   $page_key Internal page key.
 * @param string   $url     URL fallback.
 * @param bool|string|null $download true = Dateiname aus URL-Pfad; non-empty string = Vorschlagsname; null = kein download-Attribut.
 */
function leadwerk_theme_bind_exact_anchor_keep_svg( $xpath, $context, $query, $label, $page_key = '', $url = '', $download = null ) {
	$a = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( ! $a instanceof DOMElement ) {
		return;
	}
	$label    = trim( wp_strip_all_tags( (string) $label ) );
	$page_key = trim( (string) $page_key );
	$url      = trim( (string) $url );
	$href     = leadwerk_theme_resolve_exact_href( $page_key, $url );
	if ( '' === $label && ( '' === $href || '#' === $href ) ) {
		leadwerk_theme_dom_remove( $a );
		return;
	}
	if ( '' !== $href && '#' !== $href ) {
		leadwerk_theme_dom_set_attr( $a, 'href', $href );
	}
	if ( null !== $download && '' !== $href && '#' !== $href ) {
		$fname = '';
		if ( true === $download ) {
			$path = (string) parse_url( $href, PHP_URL_PATH );
			$fname = $path ? sanitize_file_name( basename( $path ) ) : '';
			if ( '' === $fname ) {
				$fname = 'document.pdf';
			}
		} elseif ( is_string( $download ) && '' !== trim( $download ) ) {
			$fname = sanitize_file_name( $download );
			if ( '' === $fname ) {
				$fname = 'document.pdf';
			}
		}
		if ( '' !== $fname ) {
			$a->setAttribute( 'download', $fname );
		}
		if ( $a->hasAttribute( 'target' ) ) {
			$a->removeAttribute( 'target' );
		}
	}
	if ( '' === $label ) {
		return;
	}
	$svg = leadwerk_theme_dom_first( $xpath, './/svg[1]', $a );
	$rm  = array();
	foreach ( iterator_to_array( $a->childNodes, false ) as $child ) {
		if ( $child === $svg ) {
			break;
		}
		if ( $child instanceof DOMText ) {
			$rm[] = $child;
		} elseif ( $child instanceof DOMElement && 'svg' !== strtolower( $child->tagName ) ) {
			$rm[] = $child;
		}
	}
	foreach ( $rm as $n ) {
		if ( $n->parentNode === $a ) {
			$a->removeChild( $n );
		}
	}
	$doc = $a->ownerDocument;
	if ( $svg instanceof DOMElement && $doc ) {
		$a->insertBefore( $doc->createTextNode( $label . ' ' ), $svg );
	} else {
		leadwerk_theme_dom_set_text( $a, $label );
	}
}
