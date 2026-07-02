<?php
/**
 * 404 template.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$utility_file = LEADWERK_THEME_DIR . '/inc/francis-utility-pages.php';
if ( is_file( $utility_file ) ) {
	require_once $utility_file;
}

get_header();
?>
<main class="leadwerk-page leadwerk-page--404">
	<?php
	if ( function_exists( 'leadwerk_theme_render_francis_subpage_hero' ) ) {
		echo leadwerk_theme_render_francis_subpage_hero(
			'Atelier Francis',
			'404',
			'Fotos/Ballett_freigestellt.webp'
		);
	}
	if ( function_exists( 'leadwerk_theme_render_francis_404_content' ) ) {
		echo leadwerk_theme_render_francis_404_content();
	}
	?>
</main>
<?php
get_footer();
