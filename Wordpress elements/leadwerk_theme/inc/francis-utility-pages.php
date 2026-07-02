<?php
/**
 * Shared markup for 404 and other Francis utility views.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Common page URLs for utility templates.
 *
 * @return array{home: string, contact: string, courses: string, about: string, trial: string}
 */
function leadwerk_theme_get_francis_utility_urls() {
	$lang = function_exists( 'leadwerk_theme_get_current_lang' ) ? leadwerk_theme_get_current_lang() : 'de';

	return array(
		'home'    => function_exists( 'leadwerk_theme_get_page_url' )
			? leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) )
			: home_url( '/' ),
		'contact' => function_exists( 'leadwerk_theme_get_page_url' )
			? leadwerk_theme_get_page_url( 'francis-kontakt-v1', $lang, home_url( '/kontakt/' ) )
			: home_url( '/kontakt/' ),
		'courses' => function_exists( 'leadwerk_theme_get_page_url' )
			? leadwerk_theme_get_page_url( 'francis-kursuebersicht-v1', $lang, home_url( '/kursuebersicht/' ) )
			: home_url( '/kursuebersicht/' ),
		'about'   => function_exists( 'leadwerk_theme_get_page_url' )
			? leadwerk_theme_get_page_url( 'francis-ueber-uns-v1', $lang, home_url( '/ueber-uns/' ) )
			: home_url( '/ueber-uns/' ),
		'trial'   => function_exists( 'leadwerk_theme_get_page_url' )
			? leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . '#contact'
			: home_url( '/#contact' ),
	);
}

/**
 * Render a compact Francis subpage hero.
 *
 * @param string $label      Eyebrow label.
 * @param string $title      Hero title (may contain HTML).
 * @param string $image_path Source asset path for background image.
 * @return string
 */
function leadwerk_theme_render_francis_subpage_hero( $label, $title, $image_path ) {
	$image_url = function_exists( 'leadwerk_theme_resolve_source_asset_url' )
		? leadwerk_theme_resolve_source_asset_url( $image_path )
		: '';

	ob_start();
	?>
	<section class="hero hero--subpage">
		<div class="hero__bg">
			<?php if ( '' !== $image_url ) : ?>
				<img class="hero__video" src="<?php echo esc_url( $image_url ); ?>" alt="" aria-hidden="true">
			<?php endif; ?>
		</div>
		<div class="hero__overlay hero__overlay--center"></div>
		<div class="hero__content hero__content--center">
			<?php if ( '' !== trim( (string) $label ) ) : ?>
				<span class="hero__label reveal reveal--delay-1"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<h1 class="hero__title reveal reveal--delay-2"><?php echo wp_kses_post( $title ); ?></h1>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render the Francis 404 content section.
 *
 * @return string
 */
function leadwerk_theme_render_francis_404_content() {
	$urls = leadwerk_theme_get_francis_utility_urls();

	ob_start();
	?>
	<section class="page-section page-section--warm utility-page utility-page--404">
		<div class="page-section__inner utility-page__inner">
			<span class="section-label reveal"><?php esc_html_e( 'Seite nicht gefunden', 'leadwerk-theme' ); ?></span>
			<h2 class="utility-page__title section-title reveal">
				<span class="utility-page__title-main"><?php esc_html_e( 'Diese Seite', 'leadwerk-theme' ); ?></span>
				<span class="utility-page__title-accent"><?php esc_html_e( 'gibt es leider nicht.', 'leadwerk-theme' ); ?></span>
			</h2>
			<p class="utility-page__lead reveal"><?php esc_html_e( 'Der Link ist ungültig oder die Seite wurde verschoben. Kehre zur Startseite zurück oder entdecke unsere Kurse und Kontaktmöglichkeiten.', 'leadwerk-theme' ); ?></p>
			<div class="utility-page__actions contact-actions reveal">
				<a class="btn btn--gold" href="<?php echo esc_url( $urls['home'] ); ?>"><?php esc_html_e( 'Zur Startseite', 'leadwerk-theme' ); ?></a>
				<a class="btn btn--outline" href="<?php echo esc_url( $urls['courses'] ); ?>"><?php esc_html_e( 'Kurse entdecken', 'leadwerk-theme' ); ?></a>
			</div>
			<div class="utility-page__links reveal">
				<a href="<?php echo esc_url( $urls['about'] ); ?>"><?php esc_html_e( 'Über uns', 'leadwerk-theme' ); ?></a>
				<a href="<?php echo esc_url( $urls['contact'] ); ?>"><?php esc_html_e( 'Kontakt', 'leadwerk-theme' ); ?></a>
				<a href="<?php echo esc_url( $urls['trial'] ); ?>"><?php esc_html_e( 'Probestunde', 'leadwerk-theme' ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
