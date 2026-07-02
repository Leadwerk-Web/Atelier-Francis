<?php
/**
 * Sync Atelier Francis root static assets into Leadwerk importer source_assets.
 *
 * Canonical copies live only under leadwerk_importer/source_assets/.
 * Theme keeps css/js for wp_enqueue; Fotos and HTML shells are not duplicated in the theme.
 *
 * Usage (from ACM Kopie/): php scripts/sync-html-sources.php
 *
 * @package Leadwerk
 */

declare( strict_types=1 );

$repo_root   = dirname( dirname( dirname( __FILE__ ) ) );
$acm_root    = dirname( dirname( __FILE__ ) );
$source_root = $repo_root;
$dest_assets = $acm_root . '/leadwerk_importer/source_assets';
$theme_dir   = $acm_root . '/leadwerk_theme';

$html_files = array(
	'index.html',
	'ueber-uns.html',
	'stundenplan.html',
	'kursuebersicht.html',
	'kurs-kindertanz.html',
	'kurs-ballett-kinder.html',
	'kurs-ballett-erwachsene.html',
	'kurs-modern.html',
	'kurs-hip-hop.html',
	'kurs-pilates.html',
	'kurs-choreo-fit.html',
	'kurs-latino.html',
	'aktuelles.html',
	'eindruecke.html',
	'kontakt.html',
	'danke.html',
	'404.html',
	'impressum.html',
	'datenschutz.html',
);

$asset_files = array(
	'styles.css',
	'script.js',
	'cursor-default.svg',
	'cursor-slider.svg',
	'cursor-slider-large.svg',
);

function leadwerk_sync_copy_file( string $from, string $to ): void {
	$dir = dirname( $to );
	if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
		throw new RuntimeException( 'Cannot create directory: ' . $dir );
	}
	if ( ! copy( $from, $to ) ) {
		throw new RuntimeException( 'Copy failed: ' . $from . ' -> ' . $to );
	}
}

function leadwerk_sync_copy_tree( string $from, string $to ): void {
	if ( ! is_dir( $from ) ) {
		throw new RuntimeException( 'Missing source directory: ' . $from );
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $from, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ( $iterator as $item ) {
		$target = $to . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
		if ( $item->isDir() ) {
			if ( ! is_dir( $target ) && ! mkdir( $target, 0755, true ) && ! is_dir( $target ) ) {
				throw new RuntimeException( 'Cannot create directory: ' . $target );
			}
		} else {
			leadwerk_sync_copy_file( $item->getPathname(), $target );
		}
	}
}

function leadwerk_sync_rmdir( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	foreach ( scandir( $dir ) ?: array() as $entry ) {
		if ( in_array( $entry, array( '.', '..' ), true ) ) {
			continue;
		}
		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) ) {
			leadwerk_sync_rmdir( $path );
		} else {
			unlink( $path );
		}
	}
	rmdir( $dir );
}

echo "Sync Atelier Francis -> Leadwerk importer (single source)\n";
echo "Source root: {$source_root}\n";

if ( ! is_dir( $dest_assets ) && ! mkdir( $dest_assets, 0755, true ) && ! is_dir( $dest_assets ) ) {
	throw new RuntimeException( 'Cannot create: ' . $dest_assets );
}

foreach ( $html_files as $file ) {
	$from = $source_root . '/' . $file;
	if ( ! is_file( $from ) ) {
		throw new RuntimeException( 'Missing HTML: ' . $from );
	}
	leadwerk_sync_copy_file( $from, $dest_assets . '/' . $file );
	echo "HTML: {$file}\n";
}

foreach ( $asset_files as $file ) {
	$from = $source_root . '/' . $file;
	if ( ! is_file( $from ) ) {
		throw new RuntimeException( 'Missing asset: ' . $from );
	}
	leadwerk_sync_copy_file( $from, $dest_assets . '/' . $file );
	if ( 'styles.css' === $file ) {
		leadwerk_sync_copy_file( $from, $theme_dir . '/css/styles.css' );
	} elseif ( 'script.js' === $file ) {
		leadwerk_sync_copy_file( $from, $theme_dir . '/js/script.js' );
	}
	echo "Asset: {$file}\n";
}

$fotos_from   = $source_root . '/Fotos';
$fotos_assets = $dest_assets . '/Fotos';
$fotos_theme  = $theme_dir . '/Fotos';

if ( is_dir( $dest_assets . '/Fotos' ) ) {
	leadwerk_sync_rmdir( $dest_assets . '/Fotos' );
}
if ( is_dir( $fotos_theme ) ) {
	leadwerk_sync_rmdir( $fotos_theme );
}

leadwerk_sync_copy_tree( $fotos_from, $fotos_assets );
echo "Copied Fotos/ -> source_assets only\n";

$theme_shells = $theme_dir . '/source_shells';
if ( is_dir( $theme_shells ) ) {
	leadwerk_sync_rmdir( $theme_shells );
	echo "Removed duplicate theme source_shells/\n";
}

foreach ( glob( $theme_dir . '/cursor-*.svg' ) ?: array() as $cursor_file ) {
	unlink( $cursor_file );
	echo 'Removed duplicate theme cursor: ' . basename( $cursor_file ) . "\n";
}

echo "Done.\n";
