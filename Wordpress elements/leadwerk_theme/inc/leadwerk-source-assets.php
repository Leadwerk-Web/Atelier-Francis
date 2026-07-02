<?php
/**
 * Resolve bundled import assets (Fotos/…) from the media library or importer source_assets.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute path to leadwerk_importer/source_assets when the plugin is present.
 *
 * @return string
 */
function leadwerk_theme_get_import_source_assets_dir() {
	static $dir = null;
	if ( null !== $dir ) {
		return $dir;
	}

	$dir = '';
	if ( defined( 'LEADWERK_IMPORTER_PATH' ) ) {
		$candidate = trailingslashit( LEADWERK_IMPORTER_PATH ) . 'source_assets';
		if ( is_dir( $candidate ) ) {
			$dir = $candidate;
			return $dir;
		}
	}

	$sibling = trailingslashit( LEADWERK_THEME_DIR ) . '../leadwerk_importer/source_assets';
	$resolved = realpath( $sibling );
	if ( $resolved && is_dir( $resolved ) ) {
		$dir = $resolved;
	}

	return $dir;
}

/**
 * Public URL for bundled importer source_assets.
 *
 * @return string
 */
function leadwerk_theme_get_import_source_assets_url() {
	static $url = null;
	if ( null !== $url ) {
		return $url;
	}

	$url = '';
	if ( defined( 'LEADWERK_IMPORTER_URL' ) ) {
		$url = trailingslashit( LEADWERK_IMPORTER_URL ) . 'source_assets';
		return $url;
	}

	$dir = leadwerk_theme_get_import_source_assets_dir();
	if ( $dir && defined( 'LEADWERK_THEME_DIR' ) && 0 === strpos( $dir, realpath( LEADWERK_THEME_DIR ) ?: LEADWERK_THEME_DIR ) ) {
		$url = trailingslashit( LEADWERK_THEME_URI ) . '../leadwerk_importer/source_assets';
	} elseif ( $dir ) {
		$content_dir = realpath( WP_CONTENT_DIR );
		$plugins_dir = $content_dir ? realpath( $content_dir . '/plugins' ) : false;
		if ( $plugins_dir && 0 === strpos( $dir, $plugins_dir ) ) {
			$relative = ltrim( str_replace( '\\', '/', substr( $dir, strlen( $plugins_dir ) ) ), '/' );
			$url      = trailingslashit( content_url( 'plugins' ) ) . $relative;
		}
	}

	return is_string( $url ) ? $url : '';
}

/**
 * Normalize a relative source asset path (Fotos/Logo/foo.webp).
 *
 * @param string $path Raw path.
 * @return string
 */
function leadwerk_theme_normalize_source_asset_path( $path ) {
	$path = trim( str_replace( '\\', '/', (string) $path ), '/' );
	$path = str_replace( array( "\xE2\x80\x93", "\xE2\x80\x94" ), '-', $path );
	if ( preg_match( '#^(?:https?:)?//#i', $path ) ) {
		return $path;
	}
	if ( preg_match( '#^(?:Fotos|fotos)/#i', $path ) ) {
		return 'Fotos/' . ltrim( substr( $path, strpos( $path, '/' ) + 1 ), '/' );
	}
	return $path;
}

/**
 * Candidate leadwerk_source_path values (webp/png interchange).
 *
 * @param string $path Relative asset path.
 * @return string[]
 */
function leadwerk_theme_get_source_asset_path_candidates( $path ) {
	$path = leadwerk_theme_normalize_source_asset_path( $path );
	if ( '' === $path || preg_match( '#^(?:https?:)?//#i', $path ) ) {
		return array_filter( array( $path ) );
	}

	$candidates = array( $path );
	$ext        = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( 'png' === $ext ) {
		$candidates[] = preg_replace( '/\.png$/i', '.webp', $path );
	} elseif ( 'webp' === $ext ) {
		$candidates[] = preg_replace( '/\.webp$/i', '.png', $path );
	}

	return array_values( array_unique( array_filter( $candidates ) ) );
}

/**
 * Lookup an imported attachment by leadwerk_source_path meta.
 *
 * @param string $normalized_path Normalized source path.
 * @return int
 */
function leadwerk_theme_get_attachment_id_by_source_path( $normalized_path ) {
	static $cache = array();

	$normalized_path = leadwerk_theme_normalize_source_asset_path( $normalized_path );
	if ( '' === $normalized_path || preg_match( '#^(?:https?:)?//#i', $normalized_path ) ) {
		return 0;
	}
	if ( isset( $cache[ $normalized_path ] ) ) {
		return (int) $cache[ $normalized_path ];
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => 'leadwerk_source_path',
					'value' => $normalized_path,
				),
			),
		)
	);
	$ids = $query->get_posts();
	$id  = ! empty( $ids ) ? (int) $ids[0] : 0;
	$cache[ $normalized_path ] = $id;

	return $id;
}

/**
 * Resolve Fotos/… to a public URL (media library first, then importer bundle).
 *
 * @param string $path Relative or absolute path.
 * @return string
 */
function leadwerk_theme_resolve_source_asset_url( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}
	if ( preg_match( '#^(?:https?:)?//#i', $path ) || preg_match( '#^(?:mailto|tel|javascript):#i', $path ) ) {
		return $path;
	}

	foreach ( leadwerk_theme_get_source_asset_path_candidates( $path ) as $candidate ) {
		$attachment_id = leadwerk_theme_get_attachment_id_by_source_path( $candidate );
		if ( $attachment_id ) {
			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				return $url;
			}
		}
	}

	$assets_dir = leadwerk_theme_get_import_source_assets_dir();
	$assets_url = leadwerk_theme_get_import_source_assets_url();
	if ( $assets_dir && $assets_url ) {
		foreach ( leadwerk_theme_get_source_asset_path_candidates( $path ) as $candidate ) {
			$full = $assets_dir . '/' . str_replace( '/', DIRECTORY_SEPARATOR, $candidate );
			if ( is_file( $full ) ) {
				return trailingslashit( $assets_url ) . $candidate;
			}
		}
	}

	return '';
}

/**
 * Resolve canonical shell HTML path (importer source_assets only).
 *
 * @param string $file_name Shell file name.
 * @return string
 */
function leadwerk_theme_resolve_source_shell_path( $file_name ) {
	$file_name = basename( (string) $file_name );
	if ( '' === $file_name ) {
		return '';
	}

	$assets_dir = leadwerk_theme_get_import_source_assets_dir();
	if ( $assets_dir ) {
		$candidate = $assets_dir . '/' . $file_name;
		if ( is_file( $candidate ) ) {
			return $candidate;
		}
	}

	return '';
}

/**
 * Rewrite Fotos/ and cursor asset URLs inside CSS text.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function leadwerk_theme_rewrite_source_asset_urls_in_css( $css ) {
	$css = (string) $css;
	if ( '' === $css ) {
		return '';
	}

	$css = preg_replace_callback(
		"#url\\((['\"]?)(Fotos/[^'\"\\)]+)\\1\\)#i",
		static function ( $matches ) {
			$url = leadwerk_theme_resolve_source_asset_url( $matches[2] );
			return $url ? "url('" . esc_url( $url ) . "')" : $matches[0];
		},
		$css
	);

	return (string) preg_replace_callback(
		"#url\\((['\"]?)(cursor-[^'\"\\)]+)\\1\\)#i",
		static function ( $matches ) {
			$file = $matches[2];
			$full = trailingslashit( LEADWERK_THEME_DIR ) . $file;
			if ( ! is_file( $full ) ) {
				$assets_dir = leadwerk_theme_get_import_source_assets_dir();
				if ( $assets_dir && is_file( $assets_dir . '/' . $file ) ) {
					$url = trailingslashit( leadwerk_theme_get_import_source_assets_url() ) . $file;
					return "url('" . esc_url( $url ) . "')";
				}
			}
			return "url('" . esc_url( trailingslashit( LEADWERK_THEME_URI ) . $file ) . "')";
		},
		$css
	);
}
