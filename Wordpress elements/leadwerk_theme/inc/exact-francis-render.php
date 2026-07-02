<?php
/**
 * Exact shell renderer for Atelier Francis pages.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LEADWERK_THEME_DIR . '/inc/exact-render-dom.php';
require_once LEADWERK_THEME_DIR . '/inc/exact-francis-bindings.php';

function leadwerk_theme_render_exact_page_group( $group, $value, $post_id = 0 ) {
	$resolved = function_exists( 'leadwerk_theme_resolve_structured_group_value' )
		? leadwerk_theme_resolve_structured_group_value( $group, $value, $post_id )
		: array( 'value' => $value, 'override_html' => '' );

	$value = $resolved['value'] ?? $value;
	if ( ! empty( $resolved['override_html'] ) ) {
		return (string) $resolved['override_html'];
	}

	if ( empty( $group['layouts'] ) ) {
		return leadwerk_theme_render_exact_legal_group( $group, $value, $post_id );
	}

	$source_key        = (string) get_post_meta( $post_id, 'leadwerk_source_key', true );
	$template_sections = leadwerk_theme_get_source_template_sections( $source_key );
	$sections          = is_array( $value ) ? array_values( $value ) : array();
	$output            = '';
	$index             = 0;

	foreach ( (array) $group['layouts'] as $layout_key => $layout_schema ) {
		$section_value = isset( $sections[ $index ] ) && is_array( $sections[ $index ] ) ? $sections[ $index ] : array();
		$template_html = isset( $template_sections[ $index ] ) ? $template_sections[ $index ] : '';
		$output       .= leadwerk_theme_render_exact_layout_section( $layout_key, $layout_schema, $section_value, $template_html );
		++$index;
	}

	if ( '' === trim( wp_strip_all_tags( $output ) ) ) {
		return leadwerk_theme_render_exact_runtime_notice(
			'Exact shell rendering produced no visible content for "' . (string) ( $group['label'] ?? 'page' ) . '".',
			$post_id
		);
	}

	return $output;
}

function leadwerk_theme_render_exact_runtime_notice( $message, $post_id = 0 ) {
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}
	return '<div class="runtime-notice runtime-notice--exact" style="margin:24px auto;max-width:1180px;padding:16px 18px;border:1px solid #fdba74;border-radius:16px;background:#fff7ed;color:#9a3412;">' . esc_html( $message ) . '</div>';
}

function leadwerk_theme_render_exact_legal_group( $group, $value, $post_id = 0 ) {
	$source_key = (string) get_post_meta( $post_id, 'leadwerk_source_key', true );
	$sections   = leadwerk_theme_get_source_template_sections( $source_key );
	if ( empty( $sections[0] ) || ! is_array( $value ) ) {
		return leadwerk_theme_render_exact_runtime_notice( 'Legal shell missing.', $post_id );
	}

	list( $dom, $xpath, $section_node ) = leadwerk_theme_create_template_dom( $sections[0] );
	if ( ! $section_node ) {
		return '';
	}

	leadwerk_theme_normalize_template_urls( $xpath, $section_node );
	leadwerk_francis_bind_heading( $xpath, $section_node, './/*[contains(@class,"legal-title")][1]', $value['headline'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section_node, './/*[contains(@class,"legal-body")][1]', $value['content'] ?? '' );

	return leadwerk_theme_dom_outer_html( $section_node );
}

function leadwerk_theme_render_exact_layout_section( $layout_key, $layout_schema, $section, $template_html ) {
	if ( '' === trim( $template_html ) ) {
		return '';
	}

	list( $dom, $xpath, $section_node ) = leadwerk_theme_create_template_dom( $template_html );
	if ( ! $section_node ) {
		return '';
	}

	leadwerk_theme_normalize_template_urls( $xpath, $section_node );
	$template = (string) ( $layout_schema['template'] ?? $layout_key );
	leadwerk_theme_bind_francis_layout_section( $xpath, $section_node, $template, is_array( $section ) ? $section : array() );

	$html = leadwerk_theme_dom_outer_html( $section_node );
	return function_exists( 'leadwerk_theme_prepare_section_html' )
		? leadwerk_theme_prepare_section_html( $html )
		: $html;
}

function leadwerk_theme_get_source_template_map() {
	return array(
		'francis-index-v1'                 => 'index.html',
		'francis-ueber-uns-v1'             => 'ueber-uns.html',
		'francis-stundenplan-v1'           => 'stundenplan.html',
		'francis-kursuebersicht-v1'        => 'kursuebersicht.html',
		'francis-kurs-kindertanz-v1'       => 'kurs-kindertanz.html',
		'francis-kurs-ballett-kinder-v1'   => 'kurs-ballett-kinder.html',
		'francis-kurs-ballett-erwachsene-v1'=> 'kurs-ballett-erwachsene.html',
		'francis-kurs-modern-v1'           => 'kurs-modern.html',
		'francis-kurs-hip-hop-v1'          => 'kurs-hip-hop.html',
		'francis-kurs-pilates-v1'          => 'kurs-pilates.html',
		'francis-kurs-choreo-fit-v1'       => 'kurs-choreo-fit.html',
		'francis-kurs-latino-v1'           => 'kurs-latino.html',
		'francis-aktuelles-v1'             => 'aktuelles.html',
		'francis-eindruecke-v1'            => 'eindruecke.html',
		'francis-kontakt-v1'               => 'kontakt.html',
		'francis-danke-v1'                 => 'danke.html',
		'francis-impressum-v1'             => 'impressum.html',
		'francis-datenschutz-v1'           => 'datenschutz.html',
	);
}

function leadwerk_theme_get_source_template_body_class_map() {
	return array(
		'francis-index-v1' => 'page-home',
		'francis-danke-v1' => 'page-danke',
		'francis-impressum-v1' => 'page-impressum',
		'francis-datenschutz-v1' => 'page-datenschutz',
	);
}

function leadwerk_theme_get_source_template_body_class( $source_key ) {
	$map = leadwerk_theme_get_source_template_body_class_map();
	$source_key = (string) $source_key;
	return $map[ $source_key ] ?? '';
}

function leadwerk_theme_get_source_template_sections( $source_key ) {
	static $cache = array();
	$source_key = (string) $source_key;
	if ( isset( $cache[ $source_key ] ) ) {
		return $cache[ $source_key ];
	}
	$file_map  = leadwerk_theme_get_source_template_map();
	$file_name = $file_map[ $source_key ] ?? '';
	if ( '' === $file_name ) {
		$cache[ $source_key ] = array();
		return $cache[ $source_key ];
	}
	$file_path = function_exists( 'leadwerk_theme_resolve_source_shell_path' )
		? leadwerk_theme_resolve_source_shell_path( $file_name )
		: '';
	if ( '' === $file_path ) {
		$cache[ $source_key ] = array();
		return $cache[ $source_key ];
	}
	$html = file_get_contents( $file_path );
	$cache[ $source_key ] = false === $html ? array() : leadwerk_theme_extract_body_sections_from_html( (string) $html );
	return $cache[ $source_key ];
}

function leadwerk_theme_get_source_template_html( $source_key ) {
	static $cache = array();
	$source_key = (string) $source_key;
	if ( isset( $cache[ $source_key ] ) ) {
		return $cache[ $source_key ];
	}
	$file_map  = leadwerk_theme_get_source_template_map();
	$file_name = $file_map[ $source_key ] ?? '';
	if ( '' === $file_name ) {
		$cache[ $source_key ] = '';
		return '';
	}
	$file_path = function_exists( 'leadwerk_theme_resolve_source_shell_path' )
		? leadwerk_theme_resolve_source_shell_path( $file_name )
		: '';
	if ( '' === $file_path ) {
		$cache[ $source_key ] = '';
		return '';
	}
	$html = file_get_contents( $file_path );
	$cache[ $source_key ] = false === $html ? '' : (string) $html;
	return $cache[ $source_key ];
}

function leadwerk_theme_get_exact_shell_source_key( $source_key = '' ) {
	$source_key = (string) $source_key;
	$file_map   = leadwerk_theme_get_source_template_map();
	if ( isset( $file_map[ $source_key ] ) ) {
		return $source_key;
	}
	return 'francis-index-v1';
}

function leadwerk_theme_get_francis_nav_map() {
	return array(
		array( 'key' => 'francis-ueber-uns-v1', 'label' => 'Über uns' ),
		array( 'key' => 'francis-stundenplan-v1', 'label' => 'Stundenplan' ),
		array( 'key' => 'francis-kursuebersicht-v1', 'label' => 'Kursübersicht', 'children' => array(
			array( 'key' => 'francis-kursuebersicht-v1', 'label' => 'Übersicht' ),
			array( 'key' => 'francis-kurs-kindertanz-v1', 'label' => 'Kreativer Kindertanz' ),
			array( 'key' => 'francis-kurs-ballett-kinder-v1', 'label' => 'Ballett Kinder & Jugendliche' ),
			array( 'key' => 'francis-kurs-ballett-erwachsene-v1', 'label' => 'Ballett Erwachsene' ),
			array( 'key' => 'francis-kurs-modern-v1', 'label' => 'Modern Contemporary' ),
			array( 'key' => 'francis-kurs-hip-hop-v1', 'label' => 'Hip-Hop' ),
			array( 'key' => 'francis-kurs-pilates-v1', 'label' => 'Pilates' ),
			array( 'key' => 'francis-kurs-choreo-fit-v1', 'label' => 'Choreo Fit' ),
			array( 'key' => 'francis-kurs-latino-v1', 'label' => 'Latino Style' ),
		) ),
		array( 'key' => 'francis-aktuelles-v1', 'label' => 'Aktuelles' ),
		array( 'key' => 'francis-eindruecke-v1', 'label' => 'Eindrücke' ),
		array( 'key' => 'francis-kontakt-v1', 'label' => 'Kontakt' ),
	);
}

function leadwerk_theme_get_francis_course_footer_links() {
	return array(
		array( 'key' => 'francis-kurs-kindertanz-v1', 'label' => 'Kreativer Kindertanz' ),
		array( 'key' => 'francis-kurs-ballett-kinder-v1', 'label' => 'Ballett Kinder & Jugendliche' ),
		array( 'key' => 'francis-kurs-ballett-erwachsene-v1', 'label' => 'Ballett Erwachsene' ),
		array( 'key' => 'francis-kurs-modern-v1', 'label' => 'Modern Contemporary' ),
		array( 'key' => 'francis-kurs-hip-hop-v1', 'label' => 'Hip-Hop' ),
		array( 'key' => 'francis-kurs-pilates-v1', 'label' => 'Pilates' ),
		array( 'key' => 'francis-kurs-choreo-fit-v1', 'label' => 'Choreo Fit' ),
		array( 'key' => 'francis-kurs-latino-v1', 'label' => 'Latino Style' ),
	);
}

function leadwerk_theme_francis_resolve_href_from_html( $href, $lang ) {
	$href = trim( html_entity_decode( (string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$fragment = '';
	if ( false !== strpos( $href, '#' ) ) {
		list( $path, $frag ) = explode( '#', $href, 2 );
		$href     = $path;
		$fragment = '#' . $frag;
	}
	$map = array(
		'' => 'francis-index-v1',
		'index.html' => 'francis-index-v1',
		'ueber-uns.html' => 'francis-ueber-uns-v1',
		'stundenplan.html' => 'francis-stundenplan-v1',
		'kursuebersicht.html' => 'francis-kursuebersicht-v1',
		'kurs-kindertanz.html' => 'francis-kurs-kindertanz-v1',
		'kurs-ballett-kinder.html' => 'francis-kurs-ballett-kinder-v1',
		'kurs-ballett-erwachsene.html' => 'francis-kurs-ballett-erwachsene-v1',
		'kurs-modern.html' => 'francis-kurs-modern-v1',
		'kurs-hip-hop.html' => 'francis-kurs-hip-hop-v1',
		'kurs-pilates.html' => 'francis-kurs-pilates-v1',
		'kurs-choreo-fit.html' => 'francis-kurs-choreo-fit-v1',
		'kurs-latino.html' => 'francis-kurs-latino-v1',
		'aktuelles.html' => 'francis-aktuelles-v1',
		'eindruecke.html' => 'francis-eindruecke-v1',
		'kontakt.html' => 'francis-kontakt-v1',
		'danke.html' => 'francis-danke-v1',
		'impressum.html' => 'francis-impressum-v1',
		'datenschutz.html' => 'francis-datenschutz-v1',
	);
	$path = ltrim( preg_replace( '#^(?:https?:)?//[^/]+/#i', '', str_replace( '\\', '/', $href ) ), '/' );
	if ( isset( $map[ $path ] ) ) {
		return leadwerk_theme_get_page_url( $map[ $path ], $lang, home_url( '/' ) ) . $fragment;
	}
	if ( '' === $path && '' !== $fragment ) {
		return leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . $fragment;
	}
	if ( preg_match( '#^https?://#i', $href ) ) {
		return $href;
	}
	return $href;
}

function leadwerk_theme_normalize_template_urls( $xpath, $scope = null ) {
	foreach ( leadwerk_theme_dom_query( $xpath, './/*[@src]', $scope ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$src = leadwerk_theme_resolve_francis_theme_asset_url( (string) $node->getAttribute( 'src' ) );
		if ( '' !== $src ) {
			leadwerk_theme_dom_set_attr( $node, 'src', $src );
		}
	}
	foreach ( array( 'poster', 'data-img', 'data-src' ) as $attribute ) {
		foreach ( leadwerk_theme_dom_query( $xpath, './/*[@' . $attribute . ']', $scope ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$value = leadwerk_theme_resolve_francis_theme_asset_url( (string) $node->getAttribute( $attribute ) );
			if ( '' !== $value ) {
				leadwerk_theme_dom_set_attr( $node, $attribute, $value );
			}
		}
	}
	foreach ( leadwerk_theme_dom_query( $xpath, './/a[@href]', $scope ) as $link ) {
		if ( ! $link instanceof DOMElement ) {
			continue;
		}
		$href = (string) $link->getAttribute( 'href' );
		if ( preg_match( '#^(?:mailto:|tel:|javascript:)#i', $href ) ) {
			continue;
		}
		$lang = function_exists( 'leadwerk_theme_get_current_lang' ) ? leadwerk_theme_get_current_lang() : 'de';
		$resolved = leadwerk_theme_francis_resolve_href_from_html( $href, $lang );
		if ( '' !== $resolved ) {
			leadwerk_theme_dom_set_attr( $link, 'href', $resolved );
		}
	}
}

function leadwerk_theme_render_exact_site_header() {
	$source_key = leadwerk_theme_get_exact_shell_source_key( leadwerk_theme_get_current_source_key() );
	$html       = leadwerk_theme_get_source_template_html( $source_key );
	if ( '' === trim( $html ) ) {
		return '';
	}

	list( $dom, $xpath ) = leadwerk_theme_create_document_dom( $html );
	$nav = leadwerk_theme_dom_first( $xpath, '//body/nav[contains(@class,"nav")][1]' );
	if ( ! $nav instanceof DOMElement ) {
		return '';
	}

	$lang     = leadwerk_theme_get_current_lang();
	$home_url = leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . '#hero';

	leadwerk_theme_normalize_template_urls( $xpath, $nav );

	foreach ( leadwerk_theme_dom_query( $xpath, './/a[contains(@class,"nav__logo")]', $nav ) as $logo ) {
		leadwerk_theme_dom_set_attr( $logo, 'href', $home_url );
	}

	$main_links = leadwerk_theme_dom_query( $xpath, './/ul[contains(@class,"nav__links")]/li/a[not(ancestor::ul[contains(@class,"nav__sub")])][not(contains(@class,"nav__cta"))]', $nav );
	$nav_map    = leadwerk_theme_get_francis_nav_map();
	$idx        = 0;
	foreach ( $main_links as $link ) {
		if ( ! isset( $nav_map[ $idx ] ) ) {
			break;
		}
		$page = $nav_map[ $idx ];
		leadwerk_theme_dom_set_attr( $link, 'href', leadwerk_theme_get_page_url( $page['key'], $lang ) );
		leadwerk_theme_dom_set_text( $link, leadwerk_theme_get_page_title( $page['key'], $lang, $page['label'] ) );
		++$idx;
	}

	$sub_links = leadwerk_theme_dom_query( $xpath, './/ul[contains(@class,"nav__sub")]//a', $nav );
	$children  = isset( $nav_map[2]['children'] ) ? $nav_map[2]['children'] : array();
	foreach ( $sub_links as $i => $link ) {
		if ( ! isset( $children[ $i ] ) ) {
			break;
		}
		leadwerk_theme_dom_set_attr( $link, 'href', leadwerk_theme_get_page_url( $children[ $i ]['key'], $lang ) );
		leadwerk_theme_dom_set_text( $link, leadwerk_theme_get_page_title( $children[ $i ]['key'], $lang, $children[ $i ]['label'] ) );
	}

	$cta = leadwerk_theme_dom_first( $xpath, './/a[contains(@class,"nav__cta")]', $nav );
	if ( $cta instanceof DOMElement ) {
		leadwerk_theme_dom_set_attr( $cta, 'href', leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . '#contact' );
	}

	if ( function_exists( 'leadwerk_theme_nav_starts_scrolled' ) && leadwerk_theme_nav_starts_scrolled() ) {
		$nav_classes = preg_split( '/\s+/', trim( (string) $nav->getAttribute( 'class' ) ) );
		$nav_classes = is_array( $nav_classes ) ? $nav_classes : array();
		if ( ! in_array( 'nav--scrolled', $nav_classes, true ) ) {
			$nav_classes[] = 'nav--scrolled';
		}
		$nav->setAttribute( 'class', implode( ' ', array_filter( $nav_classes ) ) );
	}

	return leadwerk_theme_dom_outer_html( $nav );
}

function leadwerk_theme_render_exact_site_footer() {
	$source_key = leadwerk_theme_get_exact_shell_source_key( leadwerk_theme_get_current_source_key() );
	$html       = leadwerk_theme_get_source_template_html( $source_key );
	if ( '' === trim( $html ) ) {
		return '';
	}

	list( $dom, $xpath ) = leadwerk_theme_create_document_dom( $html );
	$footer = leadwerk_theme_dom_first( $xpath, '//body/footer[contains(@class,"footer")][1]' );
	if ( ! $footer instanceof DOMElement ) {
		return '';
	}

	$lang = leadwerk_theme_get_current_lang();
	leadwerk_theme_normalize_template_urls( $xpath, $footer );

	$nav_links = leadwerk_theme_dom_query( $xpath, './/div[contains(@class,"footer__nav")]//a', $footer );
	$nav_pages = array(
		'francis-ueber-uns-v1', 'francis-stundenplan-v1', 'francis-kursuebersicht-v1',
		'francis-aktuelles-v1', 'francis-eindruecke-v1', 'francis-kontakt-v1', 'francis-index-v1',
	);
	foreach ( $nav_links as $idx => $link ) {
		if ( ! isset( $nav_pages[ $idx ] ) ) {
			break;
		}
		$key = $nav_pages[ $idx ];
		$url = 'francis-index-v1' === $key
			? leadwerk_theme_get_page_url( $key, $lang, home_url( '/' ) ) . '#contact'
			: leadwerk_theme_get_page_url( $key, $lang );
		leadwerk_theme_dom_set_attr( $link, 'href', $url );
	}

	$course_links = leadwerk_theme_dom_query( $xpath, './/div[contains(@class,"footer__courses")]//a', $footer );
	$courses      = leadwerk_theme_get_francis_course_footer_links();
	foreach ( $course_links as $idx => $link ) {
		if ( ! isset( $courses[ $idx ] ) ) {
			break;
		}
		leadwerk_theme_dom_set_attr( $link, 'href', leadwerk_theme_get_page_url( $courses[ $idx ]['key'], $lang ) );
	}

	$legal_links = leadwerk_theme_dom_query( $xpath, './/div[contains(@class,"footer__legal")]//a', $footer );
	$legal_keys  = array( 'francis-impressum-v1', 'francis-datenschutz-v1' );
	foreach ( $legal_links as $idx => $link ) {
		if ( ! isset( $legal_keys[ $idx ] ) ) {
			break;
		}
		leadwerk_theme_dom_set_attr( $link, 'href', leadwerk_theme_get_page_url( $legal_keys[ $idx ], $lang ) );
	}

	$address = nl2br( esc_html( leadwerk_theme_get_option_value( 'company_address', "Kronenstraße 22\n76275 Ettlingen" ) ) );
	$phone   = leadwerk_theme_get_option_value( 'company_phone', '0721 84 42 83' );
	$email   = leadwerk_theme_get_option_value( 'company_email', 'info@atelierfrancis.de' );
	$addr_node = leadwerk_theme_dom_first( $xpath, './/div[contains(@class,"footer__contact")]//address', $footer );
	if ( $addr_node instanceof DOMElement ) {
		leadwerk_theme_dom_set_trusted_inner_html(
			$addr_node,
			$address . '<br><br><a href="tel:+49721844283">' . esc_html( $phone ) . '</a><br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
		);
	}

	$tagline = leadwerk_theme_get_string( 'footer_tagline', 'Ballett · Tanz · Bewegung', $lang );
	$tag_node = leadwerk_theme_dom_first( $xpath, './/*[contains(@class,"footer__tagline")][1]', $footer );
	if ( $tag_node instanceof DOMElement && '' !== trim( $tagline ) ) {
		leadwerk_theme_dom_set_text( $tag_node, $tagline );
	}

	return leadwerk_theme_dom_outer_html( $footer );
}
