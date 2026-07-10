<?php
/**
 * Open Graph / Twitter link previews for chats and social shares.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default share image (relative source_assets path).
 *
 * @return string
 */
function leadwerk_theme_get_default_share_image_path() {
	return 'Fotos/Ballett_freigestellt.webp';
}

/**
 * Hero image paths keyed by leadwerk_source_key (fallback when ACF has no image).
 *
 * @return array<string,string>
 */
function leadwerk_theme_get_share_image_paths_by_source_key() {
	return array(
		'francis-index-v1'                 => 'Fotos/Ballett_freigestellt.webp',
		'francis-ueber-uns-v1'             => 'Fotos/Über uns/735b2d11-7458-48ba-8074-653701583321.webp',
		'francis-stundenplan-v1'           => 'Fotos/Unser Atelier/IMG_1820.webp',
		'francis-kursuebersicht-v1'        => 'Fotos/Projekte Aufführungen/IMG_2782.webp',
		'francis-aktuelles-v1'             => 'Fotos/Projekte Aufführungen/PHOTO-2024-10-22-20-10-57.webp',
		'francis-eindruecke-v1'            => 'Fotos/Ballett Kinder und Jugend Neu/8464c496-a5b8-46e4-9901-3a4f2394fa6a.webp',
		'francis-kontakt-v1'               => 'Fotos/Unser Atelier/Hero_Kontakt.webp',
		'francis-danke-v1'                 => 'Fotos/Unser Atelier/Hero_Kontakt.webp',
		'francis-kurs-kindertanz-v1'       => 'Fotos/Kreativer Kindertanz Neu/Hero_Kreativerkindertanz.webp',
		'francis-kurs-ballett-kinder-v1'   => 'Fotos/Ballett Kinder und Jugend Neu/Hero_Kinderballett.webp',
		'francis-kurs-ballett-erwachsene-v1' => 'Fotos/Ballett Kinder und Jugend Neu/Ballett Erwachsene Neu/Fotografie_Angelina_Kuehn_Reise_Labyrinth_Show2_2024_032_rez 3.webp',
		'francis-kurs-modern-v1'           => 'Fotos/Ballett Kinder und Jugend Neu/Modern Neu/IMG_9918.webp',
		'francis-kurs-hip-hop-v1'          => 'Fotos/Ballett Kinder und Jugend Neu/Modern Neu/Hip Hop Neu/20110522_B13_04_Unlimited.webp',
		'francis-kurs-pilates-v1'          => 'Fotos/Bilder von Website/Pilates/f8100f36-f2a6-4ada-80e0-83dab21c2572.webp',
		'francis-kurs-choreo-fit-v1'       => 'Fotos/Bilder von Website/Choreo Fit/d82b64f2-97c7-44b3-908a-b75432938616.webp',
		'francis-kurs-latino-v1'           => 'Fotos/Bilder von Website/Latino/44d315d0-0ab7-4488-af85-43ac3926df9d.webp',
		'francis-impressum-v1'             => leadwerk_theme_get_default_share_image_path(),
		'francis-datenschutz-v1'           => leadwerk_theme_get_default_share_image_path(),
		'francis-404-v1'                   => leadwerk_theme_get_default_share_image_path(),
	);
}

/**
 * Resolve a relative asset path to an absolute public URL.
 *
 * @param string $path Relative Fotos/… path.
 * @return string
 */
function leadwerk_theme_resolve_share_image_url( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}

	if ( function_exists( 'leadwerk_theme_resolve_source_asset_url' ) ) {
		$url = leadwerk_theme_resolve_source_asset_url( $path );
		if ( '' !== $url ) {
			return $url;
		}
	}

	if ( preg_match( '#^(?:https?:)?//#i', $path ) ) {
		return $path;
	}

	return home_url( '/' . ltrim( $path, '/' ) );
}

/**
 * Hero attachment ID from flexible ACF page sections.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function leadwerk_theme_get_page_hero_attachment_id( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! function_exists( 'get_field' ) || ! class_exists( 'Leadwerk_Content_Schema' ) ) {
		return 0;
	}

	$group = Leadwerk_Content_Schema::get_group_for_post( $post_id );
	if ( ! $group || empty( $group['field_name'] ) ) {
		return 0;
	}

	$value = get_field( $group['field_name'], $post_id );
	if ( ! is_array( $value ) || empty( $value['hero'] ) || ! is_array( $value['hero'] ) ) {
		return 0;
	}

	$hero = $value['hero'];
	if ( ! empty( $hero['image'] ) ) {
		return (int) $hero['image'];
	}

	return 0;
}

/**
 * Absolute share image URL for the current or given page.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function leadwerk_theme_get_share_image_url( $post_id = 0 ) {
	$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
	$path    = leadwerk_theme_get_default_share_image_path();

	if ( $post_id > 0 ) {
		$attachment_id = leadwerk_theme_get_page_hero_attachment_id( $post_id );
		if ( $attachment_id > 0 ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'large' );
			if ( ! $url ) {
				$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			}
			if ( $url ) {
				return $url;
			}
		}

		$source_key = trim( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
		$map        = leadwerk_theme_get_share_image_paths_by_source_key();
		if ( '' !== $source_key && isset( $map[ $source_key ] ) ) {
			$path = (string) $map[ $source_key ];
		}
	}

	return leadwerk_theme_resolve_share_image_url( $path );
}

/**
 * Share title for Open Graph.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function leadwerk_theme_get_share_title( $post_id = 0 ) {
	$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
	if ( $post_id > 0 ) {
		$document_title = trim( (string) get_post_meta( $post_id, 'leadwerk_document_title', true ) );
		if ( '' !== $document_title ) {
			return $document_title;
		}
	}

	if ( function_exists( 'wp_get_document_title' ) ) {
		return (string) wp_get_document_title();
	}

	return get_bloginfo( 'name', 'display' );
}

/**
 * Share description for Open Graph.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function leadwerk_theme_get_share_description( $post_id = 0 ) {
	$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
	if ( $post_id > 0 ) {
		$meta_description = trim( (string) get_post_meta( $post_id, 'leadwerk_meta_description', true ) );
		if ( '' !== $meta_description ) {
			return $meta_description;
		}
	}

	return (string) get_bloginfo( 'description', 'display' );
}

/**
 * Provide page-specific image to Yoast Open Graph.
 *
 * @param string $image Current image URL.
 * @return string
 */
function leadwerk_theme_filter_yoast_share_image( $image ) {
	$custom = leadwerk_theme_get_share_image_url();
	return '' !== $custom ? $custom : (string) $image;
}
add_filter( 'wpseo_opengraph_image', 'leadwerk_theme_filter_yoast_share_image' );
add_filter( 'wpseo_twitter_image', 'leadwerk_theme_filter_yoast_share_image' );

/**
 * Fallback OG tags when Yoast is not active.
 *
 * @return void
 */
function leadwerk_theme_output_share_meta_fallback() {
	if ( defined( 'WPSEO_VERSION' ) || ! is_singular() ) {
		return;
	}

	$post_id     = get_queried_object_id();
	$title       = leadwerk_theme_get_share_title( $post_id );
	$description = leadwerk_theme_get_share_description( $post_id );
	$image       = leadwerk_theme_get_share_image_url( $post_id );
	$url         = get_permalink( $post_id );

	if ( '' === $image ) {
		return;
	}

	echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', determine_locale() ) ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	echo '<meta property="og:image:alt" content="' . esc_attr( get_bloginfo( 'name', 'display' ) . ' – Ballettschule Ettlingen' ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}
add_action( 'wp_head', 'leadwerk_theme_output_share_meta_fallback', 6 );
