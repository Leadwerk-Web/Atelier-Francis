<?php
/**
 * Atelier Francis Leadwerk theme integration.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEADWERK_THEME_VERSION', '2.0.0' );
define( 'LEADWERK_THEME_DIR', get_template_directory() );
define( 'LEADWERK_THEME_URI', get_template_directory_uri() );
define( 'LEADWERK_THEME_SIMPLE_HEADER_META', 'leadwerk_simple_header' );
define( 'LEADWERK_THEME_SIMPLE_PAGE_TEMPLATE', 'template-francis-simple-page.php' );

$leadwerk_source_assets_file = LEADWERK_THEME_DIR . '/inc/leadwerk-source-assets.php';
if ( is_file( $leadwerk_source_assets_file ) ) {
	require_once $leadwerk_source_assets_file;
}

$leadwerk_exact_render_file = LEADWERK_THEME_DIR . '/inc/exact-francis-render.php';
if ( is_file( $leadwerk_exact_render_file ) ) {
	require_once $leadwerk_exact_render_file;
}

/**
 * Theme setup.
 *
 * @return void
 */
function leadwerk_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'post-thumbnails', array( 'post', 'page' ) );
	remove_theme_support( 'block-templates' );
	remove_theme_support( 'block-template-parts' );
}
add_action( 'after_setup_theme', 'leadwerk_theme_setup' );
add_filter( 'leadwerk_render_floating_switcher', '__return_false' );

/**
 * Enqueue Francis theme assets.
 *
 * @return void
 */
function leadwerk_theme_enqueue_assets() {
	$current_lang = leadwerk_theme_get_current_lang();
	$default_lang = class_exists( 'Leadwerk_Translation_API' ) ? Leadwerk_Translation_API::get_default_language() : 'de';
	$is_default   = $current_lang === $default_lang;

	wp_enqueue_style(
		'leadwerk-theme-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@300;400;500&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'leadwerk-theme-core', get_stylesheet_uri(), array( 'leadwerk-theme-fonts' ), LEADWERK_THEME_VERSION );

	$css_path = LEADWERK_THEME_DIR . '/css/styles.css';
	$css      = is_file( $css_path ) ? (string) file_get_contents( $css_path ) : '';
	if ( function_exists( 'leadwerk_theme_rewrite_source_asset_urls_in_css' ) ) {
		$css = leadwerk_theme_rewrite_source_asset_urls_in_css( $css );
	}
	wp_register_style( 'leadwerk-francis-styles', false, array( 'leadwerk-theme-core' ), LEADWERK_THEME_VERSION );
	wp_enqueue_style( 'leadwerk-francis-styles' );
	if ( '' !== $css ) {
		wp_add_inline_style( 'leadwerk-francis-styles', $css );
	}

	wp_enqueue_script( 'leadwerk-francis-script', LEADWERK_THEME_URI . '/js/script.js', array(), LEADWERK_THEME_VERSION, true );
	wp_localize_script(
		'leadwerk-francis-script',
		'leadwerkThemeData',
		array(
			'locale'              => $current_lang,
			'defaultLang'         => $default_lang,
			'wpformsTranslations' => $is_default ? array() : leadwerk_theme_get_wpforms_translations( $current_lang ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'leadwerk_theme_enqueue_assets' );

/**
 * Truncate a human-readable SEO title for Yoast pixel/width hints (character-based heuristic).
 *
 * @param string $title      Raw title.
 * @param int    $max_chars  Maximum characters before ellipsis.
 * @return string
 */
function leadwerk_theme_truncate_seo_title_for_yoast( $title, $max_chars = 58 ) {
	$title = trim( (string) $title );
	if ( '' === $title ) {
		return '';
	}
	if ( $max_chars < 8 ) {
		$max_chars = 8;
	}
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $title ) > $max_chars ) {
		return rtrim( mb_substr( $title, 0, $max_chars - 1 ) ) . '…';
	}
	if ( strlen( $title ) > $max_chars ) {
		return rtrim( substr( $title, 0, $max_chars - 1 ) ) . '…';
	}

	return $title;
}

/**
 * Build rendered page HTML for Yoast analysis on field-driven pages.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function leadwerk_theme_get_yoast_analysis_content( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! class_exists( 'Leadwerk_Content_Schema' ) || ! function_exists( 'get_field' ) ) {
		return '';
	}

	$group = Leadwerk_Content_Schema::get_group_for_post( $post_id );
	if ( ! $group || empty( $group['field_name'] ) ) {
		return '';
	}

	$field_name = $group['field_name'];
	$value      = get_field( $field_name, $post_id );

	$content = '';
	if ( function_exists( 'leadwerk_theme_render_exact_page_group' ) ) {
		$content = leadwerk_theme_render_exact_page_group( $group, $value, $post_id );
	} else {
		$content = leadwerk_theme_render_current_page_content( $post_id );
	}

	if ( '' === trim( wp_strip_all_tags( $content ) ) && false === strpos( $content, '<img' ) ) {
		return '';
	}

	$content = (string) preg_replace( '#<script[^>]*>.*?</script>#is', '', $content );
	$content = (string) preg_replace( '#<style[^>]*>.*?</style>#is', '', $content );

	$clean_content = wp_kses_post( $content );
	$clean_content = (string) str_replace( array( "\r", "\n", "\t" ), ' ', $clean_content );
	$clean_content = (string) preg_replace( '/\s+/', ' ', $clean_content );

	return trim( $clean_content );
}

/**
 * Rebuild Yoast SEO Indexable for one post (admin list dots, admin bar) after meta-only changes.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function leadwerk_theme_rebuild_yoast_post_indexable( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! function_exists( 'YoastSEO' ) ) {
		return;
	}
	if ( ! class_exists( '\Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher', false ) ) {
		return;
	}

	try {
		$yoast = YoastSEO();
		if ( ! is_object( $yoast ) || ! isset( $yoast->classes ) || ! is_object( $yoast->classes ) || ! method_exists( $yoast->classes, 'get' ) ) {
			return;
		}

		$watcher = $yoast->classes->get( \Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher::class );
		if ( is_object( $watcher ) && method_exists( $watcher, 'build_indexable' ) ) {
			$watcher->build_indexable( $post_id );
		}
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return;
	}
}

/**
 * After saving a Leadwerk-managed page, refresh Yoast indexables (ACF-only saves may skip wp_insert_post).
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post.
 * @return void
 */
function leadwerk_theme_leadwerk_page_yoast_indexable_touch( $post_id, $post, $update ) {
	unset( $update );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( '' === (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) ) {
		return;
	}

	leadwerk_theme_rebuild_yoast_post_indexable( $post_id );
}

add_action( 'save_post', 'leadwerk_theme_leadwerk_page_yoast_indexable_touch', 99, 3 );

/**
 * Feed rendered Leadwerk page content into Yoast's content analysis.
 *
 * Yoast analyses the editor content by default. Our ACM pages render from
 * Leadwerk/ACF fields and exact source shells, so the editor can appear empty
 * even when the public page contains headings, links, images and copy.
 *
 * @param string $hook_suffix Current admin hook.
 * @return void
 */
function leadwerk_theme_enqueue_admin_yoast_analysis( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! class_exists( 'WPSEO_Options' ) || ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = (int) $_GET['post'];
	} elseif ( isset( $_POST['post_ID'] ) ) {
		$post_id = (int) $_POST['post_ID'];
	}

	if ( $post_id <= 0 ) {
		return;
	}

	$analysis_content = leadwerk_theme_get_yoast_analysis_content( $post_id );
	if ( '' === $analysis_content ) {
		return;
	}

	$max_bytes = (int) apply_filters( 'leadwerk_yoast_analysis_inline_max_bytes', 350000 );
	if ( $max_bytes > 0 && strlen( $analysis_content ) > $max_bytes ) {
		$analysis_content = substr( $analysis_content, 0, $max_bytes );
	}

	$payload = array(
		'postId'          => $post_id,
		'renderedContent' => $analysis_content,
	);
	$json    = wp_json_encode(
		$payload,
		JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	if ( false === $json ) {
		$payload['renderedContent'] = substr( wp_strip_all_tags( $analysis_content ), 0, 60000 );
		$json                       = wp_json_encode(
			$payload,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
	}
	if ( false === $json ) {
		return;
	}

	wp_enqueue_script(
		'leadwerk-admin-yoast-analysis',
		LEADWERK_THEME_URI . '/js/admin-yoast-analysis.js',
		array(),
		LEADWERK_THEME_VERSION,
		true
	);

	wp_add_inline_script(
		'leadwerk-admin-yoast-analysis',
		'window.leadwerkYoastAnalysis = ' . $json . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'leadwerk_theme_enqueue_admin_yoast_analysis', 100 );

/**
 * Start output buffering early so we can replace Complianz banner strings.
 *
 * Complianz stores its banner text as wp_options (not gettext .mo strings),
 * so switch_to_locale / load_plugin_textdomain has no effect on the banner.
 * We capture the full page HTML and do a targeted string replacement for
 * the cmplz-cookiebanner-container block.
 *
 * @return void
 */
function leadwerk_theme_cmplz_ob_start() {
if ( is_admin() ) {
return;
}

$lang         = leadwerk_theme_get_current_lang();
$default_lang = class_exists( 'Leadwerk_Translation_API' ) ? Leadwerk_Translation_API::get_default_language() : 'de';

if ( $lang === $default_lang ) {
return;
}

ob_start( 'leadwerk_theme_cmplz_replace_banner_strings' );
}

/**
 * Output buffer callback: replace German Complianz banner strings with
 * the current language translations.
 *
 * @param string $html Full page HTML.
 * @return string
 */
function leadwerk_theme_cmplz_replace_banner_strings( $html ) {
/* Only process if the banner container is present. */
if ( false === strpos( $html, 'cmplz-cookiebanner-container' ) ) {
return $html;
}

$lang       = leadwerk_theme_get_current_lang();
$de_strings = leadwerk_theme_get_theme_strings( 'de' );
$tr_strings = leadwerk_theme_get_theme_strings( $lang );

$search  = array();
$replace = array();
foreach ( $de_strings as $key => $de_value ) {
if ( 0 !== strpos( $key, 'cmplz_' ) ) {
continue;
}

$tr_value = isset( $tr_strings[ $key ] ) ? (string) $tr_strings[ $key ] : '';
if ( '' === $tr_value || $tr_value === $de_value ) {
continue;
}

$search[]  = $de_value;
$replace[] = $tr_value;
}

if ( empty( $search ) ) {
return $html;
}

return str_replace( $search, $replace, $html );
}
add_action( 'template_redirect', 'leadwerk_theme_cmplz_ob_start', 1 );

/**
 * Also hook cmplz_cookie_banner_text if the filter exists in the installed
 * Complianz version.
 *
 * @param string $html Banner HTML.
 * @return string
 */
function leadwerk_theme_cmplz_filter_banner_text( $html ) {
$lang         = leadwerk_theme_get_current_lang();
$default_lang = class_exists( 'Leadwerk_Translation_API' ) ? Leadwerk_Translation_API::get_default_language() : 'de';

if ( $lang === $default_lang ) {
return $html;
}

return leadwerk_theme_cmplz_replace_banner_strings( $html );
}
add_filter( 'cmplz_cookie_banner_text', 'leadwerk_theme_cmplz_filter_banner_text', 10, 1 );

/**
 * Inject a small JS patch in the footer that overrides the German strings
 * inside the global complianz config object (placeholdertext, aria_label)
 * for non-default-language pages.
 *
 * @return void
 */
function leadwerk_theme_cmplz_js_locale_override() {
if ( is_admin() ) {
return;
}

$lang         = leadwerk_theme_get_current_lang();
$default_lang = class_exists( 'Leadwerk_Translation_API' ) ? Leadwerk_Translation_API::get_default_language() : 'de';

if ( $lang === $default_lang ) {
return;
}

$js_overrides = array(
'placeholdertext' => 'en' === $lang
? leadwerk_theme_get_string( 'cmplz_placeholder_accept', 'Click here to accept {category} cookies and enable this content', $lang )
: '',
'aria_label'      => 'en' === $lang
? leadwerk_theme_get_string( 'cmplz_placeholder_accept', 'Click here to accept {category} cookies and enable this content', $lang )
: '',
);

$js_overrides = array_filter( $js_overrides );
if ( empty( $js_overrides ) ) {
return;
}
?>
<script id="leadwerk-cmplz-locale-override">
(function(){
if(typeof complianz==='undefined')return;
var t=<?php echo wp_json_encode( $js_overrides ); ?>;
for(var k in t){if(t.hasOwnProperty(k))complianz[k]=t[k];}
})();
</script>
<?php
}
add_action( 'wp_footer', 'leadwerk_theme_cmplz_js_locale_override', 101 );

/**
 * Register dynamic theme blocks.
 *
 * @return void
 */
function leadwerk_theme_register_blocks() {
$blocks = array(
'leadwerk-acm-page'   => 'leadwerk_theme_render_page_block',
'leadwerk-acm-header' => 'leadwerk_theme_render_header_block',
'leadwerk-acm-footer' => 'leadwerk_theme_render_footer_block',
);

foreach ( $blocks as $name => $callback ) {
register_block_type(
'acf/' . $name,
array(
'render_callback' => $callback,
)
);
}
}
add_action( 'init', 'leadwerk_theme_register_blocks' );

/**
 * Add static body classes and language marker.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function leadwerk_theme_body_classes( $classes ) {
	if ( function_exists( 'leadwerk_theme_is_simple_header_context' ) && leadwerk_theme_is_simple_header_context() ) {
		$classes[] = 'page-simple-header';
	}
if ( is_404() ) {
$classes[] = 'page-404';
$classes[] = 'header-scrolled';
$classes[] = 'lang-' . leadwerk_theme_get_current_lang();
}
if ( is_singular( 'page' ) ) {
$post_id    = get_queried_object_id();
$body_class = trim( (string) get_post_meta( $post_id, 'leadwerk_body_class', true ) );
$source_key = trim( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
$lang       = leadwerk_theme_get_current_lang();

if ( '' === $body_class && '' !== $source_key && function_exists( 'leadwerk_theme_get_source_template_body_class' ) ) {
$body_class = (string) leadwerk_theme_get_source_template_body_class( $source_key );
}

if ( '' !== $body_class ) {
$classes = array_merge( $classes, preg_split( '/\s+/', $body_class ) );
}

if ( 'francis-index-v1' === $source_key || 'acm-index-v1' === $source_key || 'acm-home-v1' === $source_key ) {
$classes[] = 'home';
}
if ( 'francis-404-v1' === $source_key || 'acm-404-v1' === $source_key ) {
$classes[] = 'page-404';
$classes[] = 'header-scrolled';
}

$classes[] = 'lang-' . $lang;
}

$classes = array_values(
array_filter(
array_unique( array_filter( $classes ) ),
static function ( $class ) {
return false === strpos( (string) $class, 'leadwerk' );
}
)
);

return $classes;
}
add_filter( 'body_class', 'leadwerk_theme_body_classes' );
/**
 * Output fallback favicon if site icon is not configured.
 *
 * @return void
 */
function leadwerk_theme_favicon() {
	if ( get_option( 'site_icon' ) ) {
		return;
	}

	echo '<link rel="icon" type="image/png" href="' . esc_url( LEADWERK_THEME_URI . '/favicon-32x32.png' ) . '" sizes="32x32">' . "\n";
	echo '<link rel="icon" type="image/png" href="' . esc_url( LEADWERK_THEME_URI . '/favicon-192x192.png' ) . '" sizes="192x192">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( LEADWERK_THEME_URI . '/apple-touch-icon.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'leadwerk_theme_favicon', 1 );

/**
 * Output canonical, hreflang and meta description tags.
 *
 * @return void
 */
function leadwerk_theme_head_meta() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$post_id          = get_queried_object_id();
	$meta_description = trim( (string) get_post_meta( $post_id, 'leadwerk_meta_description', true ) );
	$robots           = trim( (string) get_post_meta( $post_id, 'leadwerk_meta_robots', true ) );

	if ( '' !== $meta_description ) {
		echo '<meta name="description" content="' . esc_attr( $meta_description ) . '">' . "\n";
	}

	if ( '' !== $robots ) {
		echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
	}

	echo '<link rel="canonical" href="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";

	if ( class_exists( 'Leadwerk_Translation_API' ) ) {
		$translations = Leadwerk_Translation_API::get_translations( $post_id );
		$x_default    = ! empty( $translations['de'] ) ? get_permalink( $translations['de'] ) : get_permalink( $post_id );
		if ( ! empty( $translations['de'] ) ) {
			echo '<link rel="alternate" hreflang="de" href="' . esc_url( get_permalink( $translations['de'] ) ) . '">' . "\n";
		}
		if ( ! empty( $translations['en'] ) ) {
			echo '<link rel="alternate" hreflang="en" href="' . esc_url( get_permalink( $translations['en'] ) ) . '">' . "\n";
		}
		if ( $x_default ) {
			echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $x_default ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'leadwerk_theme_head_meta', 5 );

/**
 * Render the dynamic page block.
 *
 * @return string
 */
function leadwerk_theme_render_page_block() {
	$post_id = get_the_ID();
	if ( ! $post_id || ! class_exists( 'Leadwerk_Content_Schema' ) || ! function_exists( 'get_field' ) ) {
		return '';
	}

	$group = Leadwerk_Content_Schema::get_group_for_post( $post_id );
	if ( ! $group || empty( $group['field_name'] ) ) {
		return '';
	}

	$field_name = $group['field_name'];
	$value      = get_field( $field_name, $post_id );
	if ( function_exists( 'leadwerk_theme_render_exact_page_group' ) ) {
		$exact_html = leadwerk_theme_render_exact_page_group( $group, $value, $post_id );
		if ( false !== strpos( $exact_html, 'leadwerk-structured-' ) ) {
			return function_exists( 'leadwerk_theme_render_exact_runtime_notice' )
				? leadwerk_theme_render_exact_runtime_notice(
					'Structured fallback markers were detected for post #' . $post_id . '. Exact shell rendering must be fixed before this page is used publicly.',
					$post_id
				)
				: '';
		}
		if ( '' !== trim( wp_strip_all_tags( $exact_html ) ) || false !== strpos( $exact_html, '<section' ) || false !== strpos( $exact_html, 'runtime-notice' ) ) {
			return $exact_html;
		}
	}

	return function_exists( 'leadwerk_theme_render_exact_runtime_notice' )
		? leadwerk_theme_render_exact_runtime_notice(
			'Exact shell rendering is required for post #' . $post_id . ', but no mapped shell output was produced.',
			$post_id
		)
		: '';
}

/**
 * Render current page content in classic theme templates.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function leadwerk_theme_render_current_page_content( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( ! $post_id ) {
		return '';
	}

	$group = class_exists( 'Leadwerk_Content_Schema' )
		? Leadwerk_Content_Schema::get_group_for_post( $post_id )
		: null;

	if ( $group && ! empty( $group['field_name'] ) && function_exists( 'get_field' ) ) {
		$field_name = $group['field_name'];
		$value      = get_field( $field_name, $post_id );

		if ( function_exists( 'leadwerk_theme_render_exact_page_group' ) ) {
			$exact_html = leadwerk_theme_render_exact_page_group( $group, $value, $post_id );
			if ( false !== strpos( $exact_html, 'leadwerk-structured-' ) ) {
				return function_exists( 'leadwerk_theme_render_exact_runtime_notice' )
					? leadwerk_theme_render_exact_runtime_notice(
						'Structured fallback markers were detected for post #' . $post_id . '. Exact shell rendering is not clean yet.',
						$post_id
					)
					: '';
			}
			if ( false !== strpos( $exact_html, '<section' ) || false !== strpos( $exact_html, '<div class="runtime-notice"' ) ) {
				return $exact_html;
			}

			return function_exists( 'leadwerk_theme_render_exact_runtime_notice' )
				? leadwerk_theme_render_exact_runtime_notice(
					'Exact shell missing or source key is unmapped for post #' . $post_id . '.',
					$post_id
				)
				: '';
		}

		return function_exists( 'leadwerk_theme_render_exact_runtime_notice' )
			? leadwerk_theme_render_exact_runtime_notice(
				'Exact renderer is unavailable for post #' . $post_id . '.',
				$post_id
			)
			: '';
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( function_exists( 'leadwerk_theme_is_simple_content_page' ) && leadwerk_theme_is_simple_content_page( $post_id ) ) {
		return leadwerk_theme_render_simple_content_page( $post_id );
	}

	return apply_filters( 'the_content', $post->post_content );
}

/**
 * Whether the selected page template is the editable simple content template.
 *
 * @param int $post_id Optional post ID.
 * @return bool
 */
function leadwerk_theme_uses_simple_page_template( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
		return false;
	}

	return LEADWERK_THEME_SIMPLE_PAGE_TEMPLATE === (string) get_page_template_slug( $post_id );
}

/**
 * Plain WordPress pages render in the same quiet shell as Impressum/Datenschutz.
 *
 * @param int $post_id Optional post ID.
 * @return bool
 */
function leadwerk_theme_is_simple_content_page( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
		return false;
	}

	if ( leadwerk_theme_uses_simple_page_template( $post_id ) ) {
		return true;
	}

	return '' === trim( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
}

/**
 * Force the rendered ACM header into its scrolled visual state.
 *
 * @param string $html Header markup.
 * @return string
 */
function leadwerk_theme_force_scrolled_header_markup( $html ) {
	return preg_replace_callback(
		'/<header\b([^>]*)>/i',
		static function ( $matches ) {
			$attrs = (string) ( $matches[1] ?? '' );
			if ( preg_match( '/\sclass=(["\'])(.*?)\1/i', $attrs, $class_match ) ) {
				$classes = preg_split( '/\s+/', trim( (string) $class_match[2] ) );
				$classes = is_array( $classes ) ? $classes : array();
				if ( ! in_array( 'header-scrolled', $classes, true ) ) {
					$classes[] = 'header-scrolled';
				}
				$attrs = preg_replace( '/\sclass=(["\'])(.*?)\1/i', ' class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '"', $attrs, 1 );
			} else {
				$attrs .= ' class="header-scrolled"';
			}
			if ( false === stripos( $attrs, 'data-force-scrolled-header' ) ) {
				$attrs .= ' data-force-scrolled-header="true"';
			}
			return '<header' . $attrs . '>';
		},
		(string) $html,
		1
	);
}

/**
 * Force Francis nav into its scrolled visual state (dark bar, inverted logo).
 *
 * @param string $html Nav markup.
 * @return string
 */
function leadwerk_theme_force_scrolled_nav_markup( $html ) {
	return preg_replace_callback(
		'/<nav\b([^>]*)>/i',
		static function ( $matches ) {
			$attrs = (string) ( $matches[1] ?? '' );
			if ( preg_match( '/\sclass=(["\'])(.*?)\1/i', $attrs, $class_match ) ) {
				$classes = preg_split( '/\s+/', trim( (string) $class_match[2] ) );
				$classes = is_array( $classes ) ? $classes : array();
				if ( ! in_array( 'nav--scrolled', $classes, true ) ) {
					$classes[] = 'nav--scrolled';
				}
				$attrs = preg_replace( '/\sclass=(["\'])(.*?)\1/i', ' class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '"', $attrs, 1 );
			} else {
				$attrs .= ' class="nav nav--scrolled"';
			}
			if ( false === stripos( $attrs, 'data-force-scrolled-nav' ) ) {
				$attrs .= ' data-force-scrolled-nav="true"';
			}
			return '<nav' . $attrs . '>';
		},
		(string) $html,
		1
	);
}

/**
 * Whether the nav should start in scrolled state (no dark hero behind it).
 *
 * @return bool
 */
function leadwerk_theme_nav_starts_scrolled() {
	if ( is_404() ) {
		return true;
	}
	if ( function_exists( 'leadwerk_theme_is_simple_header_context' ) && leadwerk_theme_is_simple_header_context() ) {
		return true;
	}
	return false;
}

/**
 * Render editor-managed page content inside the legal/simple ACM layout.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function leadwerk_theme_render_simple_content_page( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$title   = trim( get_the_title( $post ) );
	$content = apply_filters( 'the_content', $post->post_content );

	ob_start();
	?>
	<main class="leadwerk-simple-page">
		<section class="content-section content-section--white legal-content pt-32 pb-24">
			<div class="max-w-3xl mx-auto px-6">
				<?php if ( '' !== $title ) : ?>
					<h1 class="legal-title font-serif text-4xl text-stone-900 mb-8"><?php echo esc_html( $title ); ?></h1>
				<?php endif; ?>
				<div class="legal-body text-stone-600 leading-relaxed prose prose-stone max-w-none">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already filtered through the_content. ?>
				</div>
			</div>
		</section>
	</main>
	<?php

	return ob_get_clean();
}

/**
 * Render the dynamic header block.
 *
 * @return string
 */
function leadwerk_theme_render_header_block() {
	if ( function_exists( 'leadwerk_theme_render_exact_site_header' ) ) {
		$exact_header = leadwerk_theme_render_exact_site_header();
		if ( '' !== trim( $exact_header ) ) {
			if ( leadwerk_theme_nav_starts_scrolled() ) {
				return leadwerk_theme_force_scrolled_nav_markup( $exact_header );
			}
			return $exact_header;
		}
	}

	$lang            = leadwerk_theme_get_current_lang();
	$strings         = leadwerk_theme_get_theme_strings( $lang );
	$home_url        = leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . '#hero';
	$cta_url         = leadwerk_theme_get_page_url( 'francis-index-v1', $lang, home_url( '/' ) ) . '#contact';
	$logo_url        = leadwerk_theme_get_option_image_url( 'header_logo', 'Fotos/Logo/Logo.webp' );
	$logo_alt        = leadwerk_theme_get_string( 'header_logo_alt', 'Atelier Francis', $lang );
	$open_menu_label = $strings['header_open_menu_label'] ?? ( 'en' === $lang ? 'Open menu' : 'Menü öffnen' );
	$cta_label       = $strings['header_contact_cta_label'] ?? 'Probestunde';
	$nav_map         = function_exists( 'leadwerk_theme_get_francis_nav_map' ) ? leadwerk_theme_get_francis_nav_map() : array();
	$current_key     = leadwerk_theme_get_current_source_key();
	$nav_class       = 'nav';
	if ( leadwerk_theme_nav_starts_scrolled() ) {
		$nav_class .= ' nav--scrolled';
	}

	ob_start();
	?>
	<nav class="<?php echo esc_attr( $nav_class ); ?>" id="nav">
		<div class="nav__inner">
			<a href="<?php echo esc_url( $home_url ); ?>" class="nav__logo">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" class="nav__logo-img">
			</a>
			<button class="nav__toggle" id="navToggle" type="button" aria-label="<?php echo esc_attr( $open_menu_label ); ?>">
				<span></span><span></span><span></span>
			</button>
			<ul class="nav__links" id="navLinks">
				<?php foreach ( $nav_map as $page ) : ?>
					<?php
					$is_active = ( $current_key === ( $page['key'] ?? '' ) );
					if ( ! $is_active && ! empty( $page['children'] ) ) {
						foreach ( (array) $page['children'] as $child ) {
							if ( $current_key === ( $child['key'] ?? '' ) ) {
								$is_active = true;
								break;
							}
						}
					}
					?>
					<?php if ( ! empty( $page['children'] ) ) : ?>
					<li class="nav__item nav__item--has-sub">
						<a href="<?php echo esc_url( leadwerk_theme_get_page_url( $page['key'], $lang ) ); ?>" class="nav__link-parent<?php echo $is_active ? ' is-active' : ''; ?>"><?php echo esc_html( leadwerk_theme_get_page_title( $page['key'], $lang, $page['label'] ) ); ?></a>
						<ul class="nav__sub" role="list">
							<?php foreach ( (array) $page['children'] as $child ) : ?>
							<li><a href="<?php echo esc_url( leadwerk_theme_get_page_url( $child['key'], $lang ) ); ?>"<?php echo $current_key === ( $child['key'] ?? '' ) ? ' class="is-active"' : ''; ?>><?php echo esc_html( leadwerk_theme_get_page_title( $child['key'], $lang, $child['label'] ) ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</li>
					<?php else : ?>
					<li><a href="<?php echo esc_url( leadwerk_theme_get_page_url( $page['key'], $lang ) ); ?>"<?php echo $is_active ? ' class="is-active"' : ''; ?>><?php echo esc_html( leadwerk_theme_get_page_title( $page['key'], $lang, $page['label'] ) ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( $cta_url ); ?>" class="nav__cta"><?php echo esc_html( $cta_label ); ?></a></li>
			</ul>
		</div>
	</nav>
	<?php

	return ob_get_clean();
}

/**
 * Footer AGB link: URL and anchor text for the active language.
 *
 * Priority: optional WP page ID (translation pair) → optional leadwerk_source_key → static footer_agb_url.
 *
 * @param string $lang Language code (de|en).
 * @return array{href:string,label:string}
 */
function leadwerk_theme_get_footer_agb_link( $lang ) {
	$lang       = sanitize_key( (string) $lang );
	$static     = trim( (string) leadwerk_theme_get_option_value( 'footer_agb_url', '' ) );
	$page_id    = absint( leadwerk_theme_get_option_value( 'footer_agb_page_id', '0' ) );
	$source_key = sanitize_key( (string) leadwerk_theme_get_option_value( 'footer_agb_source_key', '' ) );
	$fallback   = leadwerk_theme_get_string( 'footer_agb_link_label', 'AGB', $lang );

	if ( $page_id > 0 && class_exists( 'Leadwerk_Translation_API' ) ) {
		$target = (int) Leadwerk_Translation_API::get_translation( $page_id, $lang );
		if ( $target > 0 ) {
			$url = Leadwerk_Translation_API::get_public_post_url( $target, $static );
			if ( '' === $url ) {
				$plink = get_permalink( $target );
				$url   = $plink ? (string) $plink : $static;
			}
			$post = get_post( $target );
			$label = ( $post instanceof WP_Post && '' !== trim( (string) $post->post_title ) )
				? (string) $post->post_title
				: $fallback;
			return array( 'href' => $url, 'label' => $label );
		}
	}

	if ( '' !== $source_key && class_exists( 'Leadwerk_Translation_API' ) ) {
		$url = leadwerk_theme_get_page_url( $source_key, $lang, $static );
		if ( '' !== $url && '#' !== $url ) {
			return array(
				'href'  => $url,
				'label' => leadwerk_theme_get_page_title( $source_key, $lang, $fallback ),
			);
		}
	}

	if ( '' !== $static ) {
		return array( 'href' => $static, 'label' => $fallback );
	}

	return array( 'href' => '', 'label' => $fallback );
}

/**
 * Render the dynamic footer block.
 *
 * @return string
 */
function leadwerk_theme_render_footer_block() {
	if ( function_exists( 'leadwerk_theme_render_exact_site_footer' ) ) {
		$exact_footer = leadwerk_theme_render_exact_site_footer();
		if ( '' !== trim( $exact_footer ) ) {
			return $exact_footer;
		}
	}

	$lang             = leadwerk_theme_get_current_lang();
	$strings          = leadwerk_theme_get_theme_strings( $lang );
	$footer_tagline   = leadwerk_theme_get_string( 'footer_tagline', 'Ballett · Tanz · Bewegung', $lang );
	$footer_copyright = leadwerk_theme_get_string( 'footer_copyright', '© ' . gmdate( 'Y' ) . ' Atelier Francis, Francis Meylemans & Andrea Schlaile GbR', $lang );
	$address          = nl2br( esc_html( leadwerk_theme_get_option_value( 'company_address', "Kronenstraße 22\n76275 Ettlingen" ) ) );
	$phone            = leadwerk_theme_get_option_value( 'company_phone', '0721 84 42 83' );
	$email            = leadwerk_theme_get_option_value( 'company_email', 'info@atelierfrancis.de' );
	$logo_url         = leadwerk_theme_get_option_image_url( 'footer_logo', 'Fotos/Logo/Logo.webp' );
	$logo_alt         = leadwerk_theme_get_string( 'footer_logo_alt', 'Atelier Francis', $lang );
	$nav_pages        = array(
		'francis-ueber-uns-v1',
		'francis-stundenplan-v1',
		'francis-kursuebersicht-v1',
		'francis-aktuelles-v1',
		'francis-eindruecke-v1',
		'francis-kontakt-v1',
		'francis-index-v1',
	);
	$courses = function_exists( 'leadwerk_theme_get_francis_course_footer_links' )
		? leadwerk_theme_get_francis_course_footer_links()
		: array();

	ob_start();
	?>
	<footer class="footer" id="contact">
		<div class="footer__inner">
			<div class="footer__brand">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" class="footer__logo">
				<p class="footer__tagline"><?php echo esc_html( $footer_tagline ); ?></p>
			</div>
			<div class="footer__nav">
				<h4 class="footer__heading"><?php echo esc_html( $strings['footer_nav_heading'] ?? 'Navigation' ); ?></h4>
				<ul>
					<?php foreach ( $nav_pages as $idx => $key ) : ?>
					<li><a href="<?php echo esc_url( 'francis-index-v1' === $key ? leadwerk_theme_get_page_url( $key, $lang, home_url( '/' ) ) . '#contact' : leadwerk_theme_get_page_url( $key, $lang ) ); ?>"><?php echo esc_html( leadwerk_theme_get_page_title( $key, $lang, 6 === $idx ? 'Probestunde' : '' ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="footer__courses">
				<h4 class="footer__heading"><?php echo esc_html( $strings['footer_courses_heading'] ?? 'Kurse' ); ?></h4>
				<ul>
					<?php foreach ( $courses as $course ) : ?>
					<li><a href="<?php echo esc_url( leadwerk_theme_get_page_url( $course['key'], $lang ) ); ?>"><?php echo esc_html( leadwerk_theme_get_page_title( $course['key'], $lang, $course['label'] ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="footer__contact">
				<h4 class="footer__heading"><?php echo esc_html( $strings['footer_contact_heading'] ?? 'Kontakt' ); ?></h4>
				<address>
					<?php echo $address; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?><br><br>
					<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><br>
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
				</address>
			</div>
			<div class="footer__legal">
				<h4 class="footer__heading"><?php echo esc_html( $strings['footer_legal_heading'] ?? 'Rechtliches' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( leadwerk_theme_get_page_url( 'francis-impressum-v1', $lang ) ); ?>"><?php echo esc_html( leadwerk_theme_get_page_title( 'francis-impressum-v1', $lang, 'Impressum' ) ); ?></a></li>
					<li><a href="<?php echo esc_url( leadwerk_theme_get_page_url( 'francis-datenschutz-v1', $lang ) ); ?>"><?php echo esc_html( leadwerk_theme_get_page_title( 'francis-datenschutz-v1', $lang, 'Datenschutz' ) ); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="footer__bottom">
			<p><?php echo esc_html( $footer_copyright ); ?></p>
		</div>
	</footer>
	<?php

	return ob_get_clean();
}

/**
 * Prepare a stored HTML section before output.
 *
 * @param string $html HTML.
 * @return string
 */
function leadwerk_theme_prepare_section_html( $html ) {
	if ( false !== strpos( $html, 'contact-form' ) || false !== strpos( $html, 'reach-form' ) ) {
		$form_markup = leadwerk_theme_get_contact_form_markup();
		$html        = preg_replace( '#<form[^>]*class="[^"]*contact-form[^"]*"[^>]*>.*?</form>#si', $form_markup, $html, 1 );
		$html        = preg_replace( '#<form[^>]*class="[^"]*reach-form[^"]*"[^>]*>.*?</form>#si', $form_markup, $html, 1 );
	}

	return $html;
}

/**
 * Return contact form markup or fallback.
 *
 * @return string
 */
function leadwerk_theme_get_contact_form_markup() {
	$lang        = leadwerk_theme_get_current_lang();
	$form_config = trim(
		(string) (
			class_exists( 'Leadwerk_Translation_API' )
				? Leadwerk_Translation_API::get_localized_option( 'wpforms_form_id', $lang, '' )
				: leadwerk_theme_get_option_value( 'en' === $lang ? 'wpforms_form_id_en' : 'wpforms_form_id_de', '' )
		)
	);
	$strings     = leadwerk_theme_get_theme_strings( $lang );
	$fallback    = '<div class="contact-form-placeholder">' . esc_html( $strings['wpforms_missing'] ?? 'WPForms configuration missing.' ) . '</div>';

	if ( '' === $form_config ) {
		return $fallback;
	}

	if ( ! shortcode_exists( 'wpforms' ) ) {
		return $fallback;
	}

	$shortcode = leadwerk_theme_normalize_wpforms_shortcode( $form_config );
	if ( '' === $shortcode ) {
		return $fallback;
	}

	$markup = (string) do_shortcode( $shortcode );
	if ( '' === trim( wp_strip_all_tags( $markup ) ) && false === strpos( $markup, '<form' ) && false === strpos( $markup, 'wpforms' ) ) {
		return $fallback;
	}

	return $markup;
}

/**
 * Normalize a stored WPForms value into a valid shortcode.
 *
 * @param string $value Stored option value.
 * @return string
 */
function leadwerk_theme_normalize_wpforms_shortcode( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( 0 === stripos( $value, '[wpforms' ) ) {
		return $value;
	}

	if ( preg_match( '/^\d+$/', $value ) ) {
		return '[wpforms id="' . absint( $value ) . '" title="false" description="false"]';
	}

	return '';
}

/**
 * Get current language.
 *
 * @return string
 */
function leadwerk_theme_get_current_lang() {
	$post_id = get_queried_object_id();
	if ( $post_id && class_exists( 'Leadwerk_Translation_API' ) ) {
		return Leadwerk_Translation_API::get_post_language( $post_id );
	}

	if ( class_exists( 'Leadwerk_Translation_API' ) && method_exists( 'Leadwerk_Translation_API', 'get_current_request_language' ) ) {
		return Leadwerk_Translation_API::get_current_request_language();
	}

	return 'de';
}

/**
 * Get current source key.
 *
 * @return string
 */
function leadwerk_theme_get_current_source_key() {
	$post_id = get_queried_object_id();
	return $post_id ? (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) : '';
}

/**
 * Whether current page matches a source key.
 *
 * @param string $source_key Source key.
 * @return bool
 */
function leadwerk_theme_is_source_key( $source_key ) {
	return leadwerk_theme_get_current_source_key() === $source_key;
}

/**
 * Whether the current page is one of the service pages.
 *
 * @return bool
 */
function leadwerk_theme_is_service_page() {
	return false;
}

/**
 * Whether a source key belongs to a legal page.
 *
 * @param string $source_key Source key.
 * @return bool
 */
function leadwerk_theme_is_legal_source_key( $source_key ) {
	return in_array( $source_key, array( 'francis-impressum-v1', 'francis-datenschutz-v1' ), true );
}

/**
 * Kontexte mit „einfachem“ hellen Header: Impressum, Datenschutz,
 * sowie beliebige Seiten mit Post-Meta leadwerk_simple_header = 1.
 *
 * @return bool
 */
function leadwerk_theme_is_simple_header_context() {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return false;
	}
	if ( function_exists( 'leadwerk_theme_is_simple_content_page' ) && leadwerk_theme_is_simple_content_page( $post_id ) ) {
		return true;
	}
	$source_key = trim( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
	if ( leadwerk_theme_is_legal_source_key( $source_key ) ) {
		return true;
	}
	return '1' === (string) get_post_meta( $post_id, LEADWERK_THEME_SIMPLE_HEADER_META, true );
}

/**
 * Get alternate language URL for current page.
 *
 * @return string
 */
function leadwerk_theme_get_alternate_language_url() {
	$post_id = get_queried_object_id();
	if ( ! $post_id || ! class_exists( 'Leadwerk_Translation_API' ) ) {
		return home_url( '/en/' );
	}

	$target_lang = 'en' === leadwerk_theme_get_current_lang() ? 'de' : 'en';
	$fallback    = 'en' === $target_lang ? home_url( '/en/' ) : home_url( '/' );

	return Leadwerk_Translation_API::get_translation_url( $post_id, $target_lang, $fallback );
}

/**
 * Get a page ID by source key and language.
 *
 * @param string $source_key Source key.
 * @param string $lang       Language code.
 * @return int
 */
function leadwerk_theme_get_page_id( $source_key, $lang ) {
	if ( class_exists( 'Leadwerk_Translation_API' ) ) {
		return Leadwerk_Translation_API::get_post_by_source_key( $source_key, $lang );
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => 'leadwerk_source_key',
					'value' => $source_key,
				),
				array(
					'key'   => 'leadwerk_lang',
					'value' => $lang,
				),
			),
		)
	);

	$ids = $query->get_posts();
	return ! empty( $ids ) ? (int) $ids[0] : 0;
}

/**
 * Get a page URL by source key.
 *
 * @param string $source_key Source key.
 * @param string $lang       Language code.
 * @param string $fallback   Fallback URL.
 * @return string
 */
function leadwerk_theme_get_page_url( $source_key, $lang, $fallback = '#' ) {
	if ( class_exists( 'Leadwerk_Translation_API' ) ) {
		return Leadwerk_Translation_API::get_post_url_by_source_key( $source_key, $lang, $fallback );
	}

	$page_id = leadwerk_theme_get_page_id( $source_key, $lang );
	return $page_id ? get_permalink( $page_id ) : $fallback;
}

/**
 * DE/EN URLs for shell header/footer language links (aligned with Leadwerk translation URLs).
 *
 * @return array{de: string, en: string}
 */
function leadwerk_theme_get_header_footer_lang_pair_urls() {
	$de_fb = leadwerk_theme_get_page_url( 'francis-index-v1', 'de', home_url( '/' ) );
	$en_fb = leadwerk_theme_get_page_url( 'francis-index-v1', 'en', home_url( '/en/' ) );

	if ( class_exists( 'Leadwerk_Translation_API' ) ) {
		$post_id = get_queried_object_id();
		$post    = get_queried_object();
		if ( $post_id && $post instanceof WP_Post && is_singular( $post->post_type )
			&& Leadwerk_Translation_API::is_translatable_post_type( $post->post_type ) ) {
			return array(
				'de' => Leadwerk_Translation_API::get_translation_url( $post_id, 'de', $de_fb ),
				'en' => Leadwerk_Translation_API::get_translation_url( $post_id, 'en', $en_fb ),
			);
		}
	}

	$key = leadwerk_theme_get_current_source_key();

	return array(
		'de' => leadwerk_theme_get_page_url( $key, 'de', home_url( '/' ) ),
		'en' => leadwerk_theme_get_page_url( $key, 'en', home_url( '/en/' ) ),
	);
}

/**
 * Get a page title by source key.
 *
 * @param string $source_key Source key.
 * @param string $lang       Language code.
 * @param string $fallback   Fallback label.
 * @return string
 */
function leadwerk_theme_get_page_title( $source_key, $lang, $fallback = '' ) {
	$page_id = leadwerk_theme_get_page_id( $source_key, $lang );
	if ( ! $page_id ) {
		return $fallback;
	}

	$title = trim( (string) get_the_title( $page_id ) );
	if ( '' === $title ) {
		return $fallback;
	}
	$decoded = trim( html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

	return '' !== $decoded ? $decoded : $fallback;
}

/**
 * Get an option value through Leadwerk Fields.
 *
 * @param string $field_name Field name.
 * @param string $default    Default.
 * @return string
 */
function leadwerk_theme_get_option_value( $field_name, $default = '' ) {
	$value = null;
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		$value = Leadwerk_Fields_API::get_field( $field_name, 'option' );
	} elseif ( function_exists( 'get_field' ) ) {
		$value = get_field( $field_name, 'option' );
	}
	if ( null !== $value && '' !== trim( (string) $value ) ) {
		return (string) $value;
	}

	return $default;
}

/**
 * Get an option image URL.
 *
 * @param string $field_name   Field name.
 * @param string $default_path Theme-relative fallback path.
 * @return string
 */
function leadwerk_theme_get_option_image_url( $field_name, $default_path ) {
	$value = null;
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		$value = Leadwerk_Fields_API::get_field( $field_name, 'option' );
	} elseif ( function_exists( 'get_field' ) ) {
		$value = get_field( $field_name, 'option' );
	}
	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );
		if ( $url ) {
			return $url;
		}
	}

	if ( function_exists( 'leadwerk_theme_resolve_source_asset_url' ) ) {
		$url = leadwerk_theme_resolve_source_asset_url( $default_path );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * Attachment ID for an image option (Leadwerk Fields / ACF option).
 *
 * @param string $field_name Field name.
 * @return int
 */
function leadwerk_theme_get_option_image_id( $field_name ) {
	$value = null;
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		$value = Leadwerk_Fields_API::get_field( $field_name, 'option' );
	} elseif ( function_exists( 'get_field' ) ) {
		$value = get_field( $field_name, 'option' );
	}
	return is_numeric( $value ) ? (int) $value : 0;
}

/**
 * Format multiline address for safe HTML (line breaks only).
 *
 * @param string $raw Raw textarea.
 * @return string
 */
function leadwerk_theme_format_address_lines_html( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}

	$lines = preg_split( '/\r\n|\r|\n/', $raw );
	$parts = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$parts[] = esc_html( $line );
		}
	}

	return implode( '<br />', $parts );
}

/**
 * Merge only non-empty translated string values into defaults.
 *
 * @param array<string,string> $defaults Base strings.
 * @param array<string,mixed>  $translations Candidate translations.
 * @return array<string,string>
 */
function leadwerk_theme_merge_non_empty_strings( $defaults, $translations ) {
	$merged = is_array( $defaults ) ? $defaults : array();

	foreach ( (array) $translations as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key ) {
			continue;
		}

		$value = (string) $value;
		if ( '' === trim( $value ) ) {
			continue;
		}

		$merged[ $key ] = $value;
	}

	return $merged;
}

/**
 * Francis-specific theme string defaults (override legacy ACM values).
 *
 * @param string $lang Language code.
 * @return array<string,string>
 */
function leadwerk_theme_get_francis_theme_string_defaults( $lang ) {
	if ( 'en' === $lang ) {
		return array(
			'header_open_menu_label'   => 'Open menu',
			'header_contact_cta_label' => 'Trial lesson',
			'header_logo_alt'          => 'Atelier Francis',
			'header_logo_link_aria_label' => 'Atelier Francis Home',
			'footer_tagline'           => 'Ballet · Dance · Movement',
			'footer_copyright'         => '© ' . gmdate( 'Y' ) . ' Atelier Francis, Francis Meylemans & Andrea Schlaile GbR',
			'footer_nav_heading'       => 'Navigation',
			'footer_courses_heading'   => 'Classes',
			'footer_contact_heading'   => 'Contact',
			'footer_legal_heading'     => 'Legal',
			'footer_logo_alt'          => 'Atelier Francis',
			'footer_wordmark_alt'      => 'Atelier Francis',
			'wpforms_missing'          => 'Please connect an English WPForms form ID or shortcode in Leadwerk options.',
			'wpforms_name_label'       => 'Name',
			'wpforms_email_label'      => 'Email address',
			'wpforms_message_label'    => 'Your message',
			'wpforms_submit_label'     => 'Send message',
			'wpforms_consent_prefix'   => 'I have read the ',
			'wpforms_consent_link_label' => 'privacy policy',
			'wpforms_consent_suffix'   => ' and agree.',
		);
	}

	return array(
		'header_open_menu_label'   => 'Menü öffnen',
		'header_contact_cta_label' => 'Probestunde',
		'header_logo_alt'          => 'Atelier Francis',
		'header_logo_link_aria_label' => 'Atelier Francis Startseite',
		'footer_tagline'           => 'Ballett · Tanz · Bewegung',
		'footer_copyright'         => '© ' . gmdate( 'Y' ) . ' Atelier Francis, Francis Meylemans & Andrea Schlaile GbR',
		'footer_nav_heading'       => 'Navigation',
		'footer_courses_heading'   => 'Kurse',
		'footer_contact_heading'   => 'Kontakt',
		'footer_legal_heading'     => 'Rechtliches',
		'footer_logo_alt'          => 'Atelier Francis',
		'footer_wordmark_alt'      => 'Atelier Francis',
		'wpforms_missing'          => 'Bitte WPForms-Formular-ID oder Shortcode in den Leadwerk-Optionen hinterlegen.',
		'wpforms_name_label'       => 'Name',
		'wpforms_email_label'      => 'E-Mail-Adresse',
		'wpforms_message_label'    => 'Ihre Nachricht',
		'wpforms_submit_label'     => 'Nachricht senden',
		'wpforms_consent_prefix'   => 'Ich habe die ',
		'wpforms_consent_link_label' => 'Datenschutzerklärung',
		'wpforms_consent_suffix'   => ' gelesen und stimme zu.',
	);
}

/**
 * Whether a stored theme string still contains ACM legacy copy.
 *
 * @param string $value Stored value.
 * @return bool
 */
function leadwerk_theme_string_looks_like_acm_legacy( $value ) {
	$value = (string) $value;
	if ( '' === trim( $value ) ) {
		return false;
	}
	return (bool) preg_match( '/ACM|AIR CHARTER|Business Aviation|Rheinmünster|Charter, Management/i', $value );
}

/**
 * Replace ACM theme strings in Leadwerk options with Francis defaults (one-time).
 *
 * @return void
 */
function leadwerk_theme_maybe_migrate_francis_theme_strings() {
	if ( '1' === (string) get_option( 'leadwerk_francis_theme_strings_migrated', '' ) ) {
		return;
	}

	foreach ( array( 'de', 'en' ) as $lang ) {
		$field   = 'theme_strings_' . $lang;
		$raw     = leadwerk_theme_get_option_value( $field, '' );
		$decoded = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}

		$needs_migration = empty( $decoded );
		if ( ! $needs_migration ) {
			foreach ( array( 'footer_tagline', 'footer_copyright', 'header_logo_alt', 'footer_logo_alt' ) as $key ) {
				if ( leadwerk_theme_string_looks_like_acm_legacy( $decoded[ $key ] ?? '' ) ) {
					$needs_migration = true;
					break;
				}
			}
		}

		if ( ! $needs_migration ) {
			continue;
		}

		$merged = array_merge( $decoded, leadwerk_theme_get_francis_theme_string_defaults( $lang ) );
		$json   = wp_json_encode( $merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( class_exists( 'Leadwerk_Fields_API' ) ) {
			Leadwerk_Fields_API::update_field( $field, $json, 'option' );
		} elseif ( function_exists( 'update_field' ) ) {
			update_field( $field, $json, 'option' );
		}
	}

	update_option( 'leadwerk_francis_theme_strings_migrated', '1', false );
}
add_action( 'after_setup_theme', 'leadwerk_theme_maybe_migrate_francis_theme_strings', 20 );

/**
 * WPForms, Complianz and generic UI string defaults (language-aware).
 *
 * @param string $lang Language code.
 * @return array<string,string>
 */
function leadwerk_theme_get_support_theme_string_defaults( $lang ) {
	if ( 'en' === $lang ) {
		return array(
			'header_language_group_label' => 'Choose language',
			'header_language_button_label' => 'Change language',
			'header_language_option_de' => 'Deutsch',
			'header_language_option_en' => 'English',
			'contact_privacy_link_label' => 'Privacy policy',
			'structured_open_link_label' => 'Open link',
			'ui_learn_more_label' => 'Learn more',
			'ui_close_label' => 'Close',
			'news_read_more_label' => 'Read more',
			'wpforms_first_name_placeholder' => 'First name',
			'wpforms_last_name_placeholder' => 'Last name',
			'wpforms_email_placeholder' => 'your@email.com',
			'wpforms_message_placeholder' => 'What is it about? What is on your mind right now?',
			'cmplz_title' => 'Manage consent',
			'cmplz_message' => 'To provide the best experience, we use technologies like cookies to store and/or access device information. If you consent to these technologies, we may process data such as browsing behavior or unique IDs on this site. If you do not consent or withdraw your consent, certain features and functions may be affected.',
			'cmplz_category_functional_title' => 'Functional',
			'cmplz_category_functional_desc' => 'The technical storage or access is strictly necessary for the legitimate purpose of enabling the use of a specific service explicitly requested by the subscriber or user, or for the sole purpose of carrying out the transmission of a communication over an electronic communications network.',
			'cmplz_category_preferences_title' => 'Preferences',
			'cmplz_category_preferences_desc' => 'The technical storage or access is necessary for the legitimate purpose of storing preferences that are not requested by the subscriber or user.',
			'cmplz_category_statistics_title' => 'Statistics',
			'cmplz_category_statistics_desc' => 'The technical storage or access that is used exclusively for statistical purposes.',
			'cmplz_category_statistics_anonymous_desc' => 'Without a subpoena, the voluntary consent of your Internet service provider, or additional records from third parties, the information stored or accessed for this purpose alone usually cannot be used to identify you.',
			'cmplz_category_marketing_title' => 'Marketing',
			'cmplz_category_marketing_desc' => 'The technical storage or access is required to create user profiles, send advertising, or track the user across one website or across several websites for similar marketing purposes.',
			'cmplz_always_active' => 'Always active',
			'cmplz_manage_options' => 'Manage options',
			'cmplz_manage_services' => 'Manage services',
			'cmplz_manage_vendors' => 'Manage {vendor_count} vendors',
			'cmplz_read_more_purposes' => 'Read more about these purposes',
			'cmplz_accept' => 'Accept',
			'cmplz_deny' => 'Deny',
			'cmplz_view_preferences' => 'View preferences',
			'cmplz_save_preferences' => 'Save preferences',
			'cmplz_placeholder_accept' => 'Click here to accept {category} cookies and enable this content',
		);
	}

	return array(
		'header_language_group_label' => 'Sprache wählen',
		'header_language_button_label' => 'Sprache wechseln',
		'header_language_option_de' => 'Deutsch',
		'header_language_option_en' => 'English',
		'contact_privacy_link_label' => 'Datenschutz',
		'structured_open_link_label' => 'Link öffnen',
		'ui_learn_more_label' => 'Mehr erfahren',
		'ui_close_label' => 'Schließen',
		'news_read_more_label' => 'Weiterlesen',
		'wpforms_first_name_placeholder' => 'Vorname',
		'wpforms_last_name_placeholder' => 'Nachname',
		'wpforms_email_placeholder' => 'deine@email.de',
		'wpforms_message_placeholder' => 'Worum geht es? Was beschaeftigt dich gerade?',
		'cmplz_title' => 'Zustimmung verwalten',
		'cmplz_message' => 'Um dir ein optimales Erlebnis zu bieten, verwenden wir Technologien wie Cookies, um Geräteinformationen zu speichern und/oder darauf zuzugreifen. Wenn du diesen Technologien zustimmst, können wir Daten wie das Surfverhalten oder eindeutige IDs auf dieser Website verarbeiten. Wenn du deine Zustimmung nicht erteilst oder zurückziehst, können bestimmte Merkmale und Funktionen beeinträchtigt werden.',
		'cmplz_category_functional_title' => 'Funktional',
		'cmplz_category_functional_desc' => 'Die technische Speicherung oder der Zugang ist unbedingt erforderlich für den rechtmäßigen Zweck, die Nutzung eines bestimmten Dienstes zu ermöglichen, der vom Teilnehmer oder Nutzer ausdrücklich gewünscht wird, oder für den alleinigen Zweck, die Übertragung einer Nachricht über ein elektronisches Kommunikationsnetz durchzuführen.',
		'cmplz_category_preferences_title' => 'Preferences',
		'cmplz_category_preferences_desc' => 'The technical storage or access is necessary for the legitimate purpose of storing preferences that are not requested by the subscriber or user.',
		'cmplz_category_statistics_title' => 'Statistiken',
		'cmplz_category_statistics_desc' => 'The technical storage or access that is used exclusively for statistical purposes.',
		'cmplz_category_statistics_anonymous_desc' => 'Die technische Speicherung oder der Zugriff, der ausschließlich zu anonymen statistischen Zwecken verwendet wird. Ohne eine Vorladung, die freiwillige Zustimmung deines Internetdienstanbieters oder zusätzliche Aufzeichnungen von Dritten können die zu diesem Zweck gespeicherten oder abgerufenen Informationen allein in der Regel nicht dazu verwendet werden, dich zu identifizieren.',
		'cmplz_category_marketing_title' => 'Marketing',
		'cmplz_category_marketing_desc' => 'Die technische Speicherung oder der Zugriff ist erforderlich, um Nutzerprofile zu erstellen, um Werbung zu versenden oder um den Nutzer auf einer Website oder über mehrere Websites hinweg zu ähnlichen Marketingzwecken zu verfolgen.',
		'cmplz_always_active' => 'Immer aktiv',
		'cmplz_manage_options' => 'Optionen verwalten',
		'cmplz_manage_services' => 'Dienste verwalten',
		'cmplz_manage_vendors' => 'Verwalten von {vendor_count}-Lieferanten',
		'cmplz_read_more_purposes' => 'Lese mehr über diese Zwecke',
		'cmplz_accept' => 'Akzeptieren',
		'cmplz_deny' => 'Ablehnen',
		'cmplz_view_preferences' => 'Einstellungen ansehen',
		'cmplz_save_preferences' => 'Einstellungen speichern',
		'cmplz_placeholder_accept' => 'Klicke hier, um {category}-Cookies zu akzeptieren und diesen Inhalt zu aktivieren',
	);
}

/**
 * Get language-aware theme strings.
 *
 * @param string|null $lang Optional language code.
 * @return array<string,string>
 */
function leadwerk_theme_get_theme_strings( $lang = null ) {
	$lang = $lang ?: leadwerk_theme_get_current_lang();
	$defaults = array_merge(
		leadwerk_theme_get_support_theme_string_defaults( $lang ),
		leadwerk_theme_get_francis_theme_string_defaults( $lang )
	);
	$package_strings = class_exists( 'Leadwerk_Translation_API' )
		? Leadwerk_Translation_API::get_package_strings( 'theme_strings', $lang, array() )
		: array();

	if ( ! empty( $package_strings ) ) {
		return leadwerk_theme_merge_non_empty_strings( $defaults, $package_strings );
	}

	$raw = class_exists( 'Leadwerk_Translation_API' )
		? Leadwerk_Translation_API::get_localized_option( 'theme_strings', $lang, '' )
		: leadwerk_theme_get_option_value( 'en' === $lang ? 'theme_strings_en' : 'theme_strings_de', '' );

	if ( is_array( $raw ) ) {
		return leadwerk_theme_merge_non_empty_strings( $defaults, $raw );
	}

	if ( '' === trim( $raw ) ) {
		return $defaults;
	}

	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? leadwerk_theme_merge_non_empty_strings( $defaults, $decoded ) : $defaults;
}

/**
 * Get one translated theme string with a fallback.
 *
 * @param string      $key      String key.
 * @param string      $fallback Fallback label.
 * @param string|null $lang     Optional language code.
 * @return string
 */
function leadwerk_theme_get_string( $key, $fallback = '', $lang = null ) {
	$strings = leadwerk_theme_get_theme_strings( $lang );
	$value   = isset( $strings[ $key ] ) ? trim( (string) $strings[ $key ] ) : '';

	return '' !== $value ? $value : (string) $fallback;
}

/**
 * Get one translated string list split by pipes or line breaks.
 *
 * @param string        $key      String key.
 * @param array<string> $fallback Fallback items.
 * @param string|null   $lang     Optional language code.
 * @return array<string>
 */
function leadwerk_theme_get_string_list( $key, $fallback = array(), $lang = null ) {
	$raw = leadwerk_theme_get_string( $key, '', $lang );
	if ( '' === trim( $raw ) ) {
		return array_values( array_filter( array_map( 'trim', (array) $fallback ) ) );
	}

	$items = preg_split( '/\r\n|\r|\n|\|/', $raw );
	return array_values( array_filter( array_map( 'trim', (array) $items ) ) );
}

/**
 * Get WPForms contact-form translations for the active language.
 *
 * @param string|null $lang Optional language code.
 * @return array<string,string>
 */
function leadwerk_theme_get_wpforms_translations( $lang = null ) {
	$strings = leadwerk_theme_get_theme_strings( $lang );

	return array(
		'nameLabel'            => (string) ( $strings['wpforms_name_label'] ?? '' ),
		'firstNamePlaceholder' => (string) ( $strings['wpforms_first_name_placeholder'] ?? '' ),
		'lastNamePlaceholder'  => (string) ( $strings['wpforms_last_name_placeholder'] ?? '' ),
		'emailLabel'           => (string) ( $strings['wpforms_email_label'] ?? '' ),
		'emailPlaceholder'     => (string) ( $strings['wpforms_email_placeholder'] ?? '' ),
		'messageLabel'         => (string) ( $strings['wpforms_message_label'] ?? '' ),
		'messagePlaceholder'   => (string) ( $strings['wpforms_message_placeholder'] ?? '' ),
		'submitLabel'          => (string) ( $strings['wpforms_submit_label'] ?? '' ),
		'consentPrefix'        => (string) ( $strings['wpforms_consent_prefix'] ?? '' ),
		'consentLinkLabel'     => (string) ( $strings['wpforms_consent_link_label'] ?? '' ),
		'consentSuffix'        => (string) ( $strings['wpforms_consent_suffix'] ?? '' ),
	);
}

/**
 * Get visible Complianz banner translations for the active language.
 *
 * @param string|null $lang Optional language code.
 * @return array<string,string>
 */
function leadwerk_theme_get_complianz_banner_translations( $lang = null ) {
	$lang         = $lang ?: leadwerk_theme_get_current_lang();
	$default_lang = class_exists( 'Leadwerk_Translation_API' ) ? Leadwerk_Translation_API::get_default_language() : 'de';
	if ( $lang === $default_lang ) {
		return array();
	}

	$strings = leadwerk_theme_get_theme_strings( $lang );

	return array(
		'title'                          => (string) ( $strings['cmplz_title'] ?? '' ),
		'message'                        => (string) ( $strings['cmplz_message'] ?? '' ),
		'functionalTitle'                => (string) ( $strings['cmplz_category_functional_title'] ?? '' ),
		'functionalDescription'          => (string) ( $strings['cmplz_category_functional_desc'] ?? '' ),
		'preferencesTitle'               => (string) ( $strings['cmplz_category_preferences_title'] ?? '' ),
		'preferencesDescription'         => (string) ( $strings['cmplz_category_preferences_desc'] ?? '' ),
		'statisticsTitle'                => (string) ( $strings['cmplz_category_statistics_title'] ?? '' ),
		'statisticsDescription'          => (string) ( $strings['cmplz_category_statistics_desc'] ?? '' ),
		'statisticsAnonymousDescription' => (string) ( $strings['cmplz_category_statistics_anonymous_desc'] ?? '' ),
		'marketingTitle'                 => (string) ( $strings['cmplz_category_marketing_title'] ?? '' ),
		'marketingDescription'           => (string) ( $strings['cmplz_category_marketing_desc'] ?? '' ),
		'alwaysActive'                   => (string) ( $strings['cmplz_always_active'] ?? '' ),
		'manageOptions'                  => (string) ( $strings['cmplz_manage_options'] ?? '' ),
		'manageServices'                 => (string) ( $strings['cmplz_manage_services'] ?? '' ),
		'manageVendors'                  => (string) ( $strings['cmplz_manage_vendors'] ?? '' ),
		'readMorePurposes'               => (string) ( $strings['cmplz_read_more_purposes'] ?? '' ),
		'accept'                         => (string) ( $strings['cmplz_accept'] ?? '' ),
		'deny'                           => (string) ( $strings['cmplz_deny'] ?? '' ),
		'viewPreferences'                => (string) ( $strings['cmplz_view_preferences'] ?? '' ),
		'savePreferences'                => (string) ( $strings['cmplz_save_preferences'] ?? '' ),
		'placeholderAccept'             => (string) ( $strings['cmplz_placeholder_accept'] ?? '' ),
	);
}
