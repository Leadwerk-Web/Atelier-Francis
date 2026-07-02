<?php
/**
 * Build derived import manifest documentation from mapping.json.
 *
 * Usage (from ACM Kopie/): php scripts/build-import-manifest.php
 *
 * @package Leadwerk
 */

declare( strict_types=1 );

$acm_root     = dirname( dirname( __FILE__ ) );
$mapping_path = $acm_root . '/leadwerk_importer/manifest/mapping.json';
$output_path  = $acm_root . '/leadwerk_importer/manifest/import-manifest.json';

if ( ! is_file( $mapping_path ) ) {
	fwrite( STDERR, "mapping.json fehlt.\n" );
	exit( 1 );
}

$mapping = json_decode( (string) file_get_contents( $mapping_path ), true );
if ( ! is_array( $mapping ) ) {
	fwrite( STDERR, "mapping.json ungueltig.\n" );
	exit( 1 );
}

$pages = array();
foreach ( (array) ( $mapping['pages'] ?? array() ) as $page ) {
	$pages[] = array(
		'source_key'  => (string) ( $page['source_key'] ?? '' ),
		'field_name'  => (string) ( $page['field_name'] ?? '' ),
		'source_file' => (string) ( $page['source_file'] ?? '' ),
		'slug_de'     => (string) ( $page['slug'] ?? '' ),
		'slug_en'     => (string) ( $page['slug'] ?? '' ) . '-en',
		'is_front'    => ! empty( $page['is_front_page'] ),
	);
}

$manifest = array(
	'generated_at'   => gmdate( 'c' ),
	'site_title'     => (string) ( $mapping['site_title'] ?? '' ),
	'site_tagline'   => (string) ( $mapping['site_tagline'] ?? '' ),
	'page_count'     => count( $pages ),
	'news_count'     => count( (array) ( $mapping['news_articles'] ?? array() ) ),
	'pages'          => $pages,
);

file_put_contents(
	$output_path,
	json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n"
);

echo "Wrote {$output_path}\n";
