<?php
/**
 * Verify Atelier Francis Leadwerk deployment readiness.
 *
 * Usage (from ACM Kopie/): php scripts/verify-leadwerk-deployment.php [--strict-drift]
 *
 * @package Leadwerk
 */

declare( strict_types=1 );

$acm_root    = dirname( dirname( __FILE__ ) );
$repo_root   = dirname( $acm_root );
$strict      = in_array( '--strict-drift', $argv ?? array(), true );
$errors      = array();
$warnings    = array();

$mapping_path = $acm_root . '/leadwerk_importer/manifest/mapping.json';
$shells_dir   = $acm_root . '/leadwerk_importer/source_assets';
$assets_dir   = $shells_dir;
$schema_file  = $acm_root . '/leadwerk-fields/includes/class-leadwerk-content-schema.php';

if ( ! is_file( $mapping_path ) ) {
	$errors[] = 'mapping.json fehlt.';
} else {
	$mapping = json_decode( (string) file_get_contents( $mapping_path ), true );
	if ( ! is_array( $mapping ) || empty( $mapping['pages'] ) ) {
		$errors[] = 'mapping.json ist ungueltig oder leer.';
	} else {
		foreach ( (array) $mapping['pages'] as $page ) {
			$field = (string) ( $page['field_name'] ?? '' );
			$file  = (string) ( $page['source_file'] ?? '' );
			$key   = (string) ( $page['source_key'] ?? '' );
			if ( '' === $field || '' === $file || '' === $key ) {
				$errors[] = 'Unvollstaendiger Page-Eintrag in mapping.json.';
				continue;
			}
			if ( ! is_file( $shells_dir . '/' . $file ) ) {
				$errors[] = "HTML fehlt: {$file} in source_assets";
			}
		}
	}
}

if ( is_file( $schema_file ) ) {
	require_once $acm_root . '/leadwerk-fields/includes/trait-leadwerk-francis-schema.php';
	require_once $schema_file;
	if ( class_exists( 'Leadwerk_Content_Schema' ) && is_file( $mapping_path ) ) {
		$mapping = json_decode( (string) file_get_contents( $mapping_path ), true );
		foreach ( (array) ( $mapping['pages'] ?? array() ) as $page ) {
			$group = Leadwerk_Content_Schema::get_group( (string) ( $page['field_name'] ?? '' ) );
			if ( ! $group ) {
				$errors[] = 'Schema-Gruppe fehlt: ' . (string) ( $page['field_name'] ?? '' );
				continue;
			}
			if ( ! empty( $group['layouts'] ) && is_file( $shells_dir . '/' . (string) $page['source_file'] ) ) {
				$html = (string) file_get_contents( $shells_dir . '/' . (string) $page['source_file'] );
				$sections = 0;
				if ( class_exists( 'DOMDocument' ) ) {
					$dom = new DOMDocument( '1.0', 'UTF-8' );
					libxml_use_internal_errors( true );
					$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
					libxml_clear_errors();
					$xpath = new DOMXPath( $dom );
					$list  = $xpath->query( '//body/main/section' );
					if ( ! ( $list instanceof DOMNodeList ) || 0 === $list->length ) {
						$list = $xpath->query( '//body/section' );
					}
					$sections = ( $list instanceof DOMNodeList ) ? $list->length : 0;
				}
				$layouts  = count( (array) $group['layouts'] );
				if ( $sections > 0 && $sections !== $layouts ) {
					$msg = sprintf(
						'Section/Layout-Mismatch %s: %d HTML-Sektionen vs %d Schema-Layouts',
						(string) $page['source_file'],
						$sections,
						$layouts
					);
					if ( $strict ) {
						$errors[] = $msg;
					} else {
						$warnings[] = $msg;
					}
				}
			}
		}
	}
}

if ( ! is_dir( $assets_dir ) ) {
	$errors[] = 'source_assets/ fehlt — php scripts/sync-html-sources.php ausfuehren.';
}
if ( ! is_file( $acm_root . '/leadwerk_theme/css/styles.css' ) ) {
	$errors[] = 'Theme styles.css fehlt.';
}
if ( ! is_file( $acm_root . '/leadwerk_theme/js/script.js' ) ) {
	$errors[] = 'Theme script.js fehlt.';
}

if ( is_dir( $assets_dir . '/Fotos' ) ) {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $assets_dir . '/Fotos' ) );
	foreach ( $iterator as $file ) {
		if ( $file->isFile() && preg_match( '/[^\x00-\x7F]/', $file->getPathname() ) ) {
			$warnings[] = 'Umlaut-Pfad in Fotos/: ' . $file->getPathname();
			break;
		}
	}
}

echo "Leadwerk Deployment Verify\n";
echo "Errors: " . count( $errors ) . "\n";
echo "Warnings: " . count( $warnings ) . "\n";
foreach ( $errors as $e ) {
	echo "ERROR: {$e}\n";
}
foreach ( $warnings as $w ) {
	echo "WARN: {$w}\n";
}

exit( empty( $errors ) ? 0 : 1 );
