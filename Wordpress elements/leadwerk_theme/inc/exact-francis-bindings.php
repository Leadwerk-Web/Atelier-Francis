<?php
/**
 * Atelier Francis exact shell field binders.
 *
 * @package Leadwerk_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function leadwerk_francis_image_id( $field ) {
	if ( is_array( $field ) ) {
		return (int) ( $field['id'] ?? 0 );
	}
	return (int) $field;
}

function leadwerk_francis_image_alt( $field, $fallback = '' ) {
	if ( is_array( $field ) && '' !== trim( (string) ( $field['alt'] ?? '' ) ) ) {
		return (string) $field['alt'];
	}
	return (string) $fallback;
}

function leadwerk_francis_bind_heading( $xpath, $context, $query, $html ) {
	$node = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( ! $node instanceof DOMElement ) {
		return;
	}
	$clean = class_exists( 'Leadwerk_Content_Schema' )
		? Leadwerk_Content_Schema::sanitize_heading_html( (string) $html )
		: wp_kses_post( (string) $html );
	if ( '' === trim( wp_strip_all_tags( $clean ) ) ) {
		return;
	}
	leadwerk_theme_dom_set_inner_html( $node, $clean );
}

function leadwerk_francis_bind_editor( $xpath, $context, $query, $html ) {
	$node = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( $node instanceof DOMElement && '' !== trim( (string) $html ) ) {
		leadwerk_theme_dom_set_trusted_inner_html( $node, (string) $html );
	}
}

function leadwerk_francis_bind_label( $xpath, $context, $query, $text ) {
	$node = leadwerk_theme_dom_first( $xpath, $query, $context );
	if ( $node instanceof DOMElement && '' !== trim( (string) $text ) ) {
		leadwerk_theme_dom_set_text( $node, (string) $text );
	}
}

/**
 * XPath for an exact CSS class token (avoids partial matches like class-card__title-row).
 *
 * @param string $class_name Class name.
 * @return string
 */
function leadwerk_francis_exact_class_xpath( $class_name ) {
	$class_name = trim( (string) $class_name );
	if ( '' === $class_name ) {
		return './/*';
	}
	return './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")]';
}

function leadwerk_theme_bind_francis_hero_video( $xpath, $section, $value ) {
	leadwerk_francis_bind_heading( $xpath, $section, './/h1[contains(@class,"hero__title")][1]', $value['title'] ?? '' );
	$video_id = leadwerk_francis_image_id( $value['video'] ?? 0 );
	$source   = leadwerk_theme_dom_first( $xpath, './/video/source[1]', $section );
	if ( $source instanceof DOMElement ) {
		$url = $video_id ? leadwerk_theme_resolve_media_url( $video_id ) : '';
		if ( '' === $url ) {
			$url = leadwerk_theme_resolve_francis_theme_asset_url( (string) $source->getAttribute( 'src' ) );
		}
		if ( '' !== $url ) {
			leadwerk_theme_dom_set_attr( $source, 'src', $url );
		}
	}
	leadwerk_theme_bind_exact_button( $xpath, $section, './/a[contains(@class,"btn")][1]', (string) ( $value['cta_label'] ?? '' ), (string) ( $value['cta_page_key'] ?? '' ), (string) ( $value['cta_url'] ?? '' ) );
}

function leadwerk_theme_bind_francis_hero_subpage( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"hero__label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/h1[contains(@class,"hero__title")][1]', $value['title'] ?? '' );
	leadwerk_theme_bind_exact_image( $xpath, $section, './/img[contains(@class,"hero__video")][1]', leadwerk_francis_image_id( $value['image'] ?? 0 ), leadwerk_francis_image_alt( $value['image'] ?? array(), (string) ( $value['image_alt'] ?? '' ) ) );
}

function leadwerk_theme_bind_francis_about_split( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"about__text")][1]', $value['body'] ?? '' );
	leadwerk_theme_bind_exact_image( $xpath, $section, './/img[1]', leadwerk_francis_image_id( $value['image'] ?? 0 ), leadwerk_francis_image_alt( $value['image'] ?? array(), (string) ( $value['image_alt'] ?? '' ) ) );
	$details = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"about__detail")]', $section );
	$kpis    = is_array( $value['kpis'] ?? null ) ? array_values( $value['kpis'] ) : array();
	foreach ( $details as $idx => $detail ) {
		if ( ! isset( $kpis[ $idx ] ) ) {
			break;
		}
		leadwerk_francis_bind_label( $xpath, $detail, './/*[contains(@class,"about__detail-number")][1]', $kpis[ $idx ]['number'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $detail, './/*[contains(@class,"about__detail-label")][1]', $kpis[ $idx ]['label'] ?? '' );
	}
	leadwerk_theme_bind_exact_button( $xpath, $section, './/a[contains(@class,"btn")][1]', (string) ( $value['cta_label'] ?? '' ), (string) ( $value['cta_page_key'] ?? '' ), (string) ( $value['cta_url'] ?? '' ) );
}

function leadwerk_theme_bind_francis_classes_grid( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$cards = leadwerk_theme_dom_query( $xpath, './/article[contains(@class,"class-card")]', $section );
	$items = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $cards as $idx => $card ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		$item = $items[ $idx ];
		leadwerk_francis_bind_label( $xpath, $card, './/h3[contains(@class,"class-card__title")][1]', $item['title'] ?? '' );
		$desc = trim( (string) ( $item['description'] ?? '' ) );
		if ( '' !== $desc ) {
			if ( leadwerk_theme_dom_first( $xpath, './/*[contains(@class,"class-card__level")][1]', $card ) instanceof DOMElement ) {
				leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"class-card__level")][1]', $desc );
			} else {
				leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"class-card__desc")][1]', $desc );
			}
		}
		leadwerk_theme_bind_exact_image( $xpath, $card, './/img[1]', leadwerk_francis_image_id( $item['image'] ?? 0 ), leadwerk_francis_image_alt( $item['image'] ?? array(), (string) ( $item['image_alt'] ?? '' ) ) );
		$link = leadwerk_theme_dom_first( $xpath, './/a[1]', $card );
		if ( $link instanceof DOMElement ) {
			leadwerk_theme_dom_set_attr( $link, 'href', leadwerk_theme_resolve_exact_href( (string) ( $item['page_key'] ?? '' ), (string) ( $item['url'] ?? '' ) ) );
		}
	}
}

function leadwerk_theme_bind_francis_contact_statement( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/*[contains(@class,"statement__label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"statement__title")][1] | .//*[contains(@class,"section-title")][1] | .//h2[1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"statement__text")][1] | .//*[contains(@class,"about__text")][1]', $value['body'] ?? '' );
	leadwerk_theme_bind_exact_image(
		$xpath,
		$section,
		'.//*[contains(@class,"statement__bg")]//img[1]',
		leadwerk_francis_image_id( $value['background_image'] ?? 0 ),
		leadwerk_francis_image_alt( $value['background_image'] ?? array(), (string) ( $value['background_image_alt'] ?? '' ) )
	);
	$buttons = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"statement__actions")]//a[contains(@class,"btn")]', $section );
	if ( isset( $buttons[0] ) ) {
		leadwerk_theme_bind_exact_button( $xpath, $buttons[0], '.', (string) ( $value['cta_label'] ?? '' ), (string) ( $value['cta_page_key'] ?? '' ), (string) ( $value['cta_url'] ?? '' ) );
	} else {
		leadwerk_theme_bind_exact_button( $xpath, $section, './/a[contains(@class,"btn")][1]', (string) ( $value['cta_label'] ?? '' ), (string) ( $value['cta_page_key'] ?? '' ), (string) ( $value['cta_url'] ?? '' ) );
	}
	if ( isset( $buttons[1] ) ) {
		leadwerk_theme_bind_exact_button( $xpath, $buttons[1], '.', (string) ( $value['secondary_label'] ?? '' ), (string) ( $value['secondary_page_key'] ?? '' ), (string) ( $value['secondary_url'] ?? '' ) );
	}
}

function leadwerk_theme_bind_francis_team_grid( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"team__header-text")][1]', $value['intro'] ?? '' );
	$cards = leadwerk_theme_dom_query( $xpath, './/article[contains(@class,"team-card")] | .//article[contains(@class,"team-showcase__member")]', $section );
	$items = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $cards as $idx => $card ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		$item = $items[ $idx ];
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"team-card__name") or contains(@class,"team-showcase__name")][1]', $item['name'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"team-card__role") or contains(@class,"team-showcase__role")][1]', $item['role'] ?? '' );
		leadwerk_francis_bind_editor( $xpath, $card, './/*[contains(@class,"team-card__bio") or contains(@class,"team-showcase__quote")][1]', $item['bio'] ?? '' );
		leadwerk_theme_bind_exact_image( $xpath, $card, './/img[1]', leadwerk_francis_image_id( $item['image'] ?? 0 ), leadwerk_francis_image_alt( $item['image'] ?? array(), (string) ( $item['image_alt'] ?? '' ) ) );
	}
}

function leadwerk_theme_bind_francis_impressions_slider( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$items = leadwerk_theme_dom_query( $xpath, leadwerk_francis_gallery_item_xpath(), $section );
	$data  = is_array( $value['images'] ?? null ) ? array_values( $value['images'] ) : array();
	foreach ( $items as $idx => $item ) {
		if ( ! isset( $data[ $idx ] ) ) {
			break;
		}
		leadwerk_theme_bind_exact_image( $xpath, $item, './/img[1]', leadwerk_francis_image_id( $data[ $idx ]['image'] ?? 0 ), leadwerk_francis_image_alt( $data[ $idx ]['image'] ?? array(), (string) ( $data[ $idx ]['image_alt'] ?? '' ) ) );
	}
}

function leadwerk_theme_bind_francis_testimonials( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$slides = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"testimonials__slide")]', $section );
	$items  = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $slides as $idx => $slide ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		leadwerk_francis_bind_editor( $xpath, $slide, './/*[contains(@class,"testimonials__quote")][1]', $items[ $idx ]['quote'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $slide, './/*[contains(@class,"testimonials__author")][1]', $items[ $idx ]['author'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $slide, './/*[contains(@class,"testimonials__role")][1]', $items[ $idx ]['role'] ?? '' );
	}
}

function leadwerk_theme_bind_francis_promo_banner( $xpath, $section, $value ) {
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"promo__title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"promo__text")][1]', $value['body'] ?? '' );
	leadwerk_theme_bind_exact_image( $xpath, $section, './/*[contains(@class,"promo__image")]//img[1] | .//img[1]', leadwerk_francis_image_id( $value['image'] ?? 0 ), leadwerk_francis_image_alt( $value['image'] ?? array(), (string) ( $value['image_alt'] ?? '' ) ) );
	$btns = leadwerk_theme_dom_query( $xpath, './/a[contains(@class,"btn")]', $section );
	if ( isset( $btns[0] ) ) {
		leadwerk_theme_bind_exact_button( $xpath, $btns[0], '.', (string) ( $value['primary_label'] ?? '' ), (string) ( $value['primary_page_key'] ?? '' ), (string) ( $value['primary_url'] ?? '' ) );
	}
	if ( isset( $btns[1] ) ) {
		leadwerk_theme_bind_exact_button( $xpath, $btns[1], '.', (string) ( $value['secondary_label'] ?? '' ), (string) ( $value['secondary_page_key'] ?? '' ), (string) ( $value['secondary_url'] ?? '' ) );
	}
}

function leadwerk_theme_bind_francis_pricing( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$cards = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"pricing__card")]', $section );
	$items = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $cards as $idx => $card ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		$item = $items[ $idx ];
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"pricing__card-label")][1]', $item['label'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"pricing__card-price")][1]', $item['price'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"pricing__card-period")][1]', $item['period'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $card, './/*[contains(@class,"pricing__card-desc")][1]', $item['description'] ?? '' );
		leadwerk_theme_bind_exact_button( $xpath, $card, './/a[contains(@class,"btn")][1]', (string) ( $item['cta_label'] ?? '' ), (string) ( $item['cta_page_key'] ?? '' ), (string) ( $item['cta_url'] ?? '' ) );
	}
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"pricing__notes")][1]', $value['notes'] ?? '' );
}

function leadwerk_theme_bind_francis_values_grid( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$nodes = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"values__item")]', $section );
	$items = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $nodes as $idx => $node ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		leadwerk_francis_bind_label( $xpath, $node, './/h3[1]', $items[ $idx ]['title'] ?? '' );
		leadwerk_francis_bind_editor( $xpath, $node, './/p[1]', $items[ $idx ]['content'] ?? '' );
	}
}

function leadwerk_theme_bind_francis_statement( $xpath, $section, $value ) {
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"statement__quote")][1]', $value['quote'] ?? '' );
	leadwerk_francis_bind_label( $xpath, $section, './/*[contains(@class,"statement__author")][1]', $value['author'] ?? '' );
}

function leadwerk_theme_bind_francis_course_content( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_label( $xpath, $section, './/*[contains(@class,"course-page__subline")][1]', $value['subline'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"course-page__prose")][1]', $value['body'] ?? '' );
	$images = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"course-page__mini-gallery")]//img | .//figure//img', $section );
	$gallery = is_array( $value['gallery'] ?? null ) ? array_values( $value['gallery'] ) : array();
	foreach ( $images as $idx => $img ) {
		if ( ! isset( $gallery[ $idx ] ) ) {
			break;
		}
		leadwerk_theme_bind_exact_image( $xpath, $img, '.', leadwerk_francis_image_id( $gallery[ $idx ]['image'] ?? 0 ), leadwerk_francis_image_alt( $gallery[ $idx ]['image'] ?? array(), (string) ( $gallery[ $idx ]['image_alt'] ?? '' ) ) );
	}
}

function leadwerk_theme_bind_francis_schedule_table( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"schedule-page__header")][1]', $value['intro'] ?? '' );
	leadwerk_theme_bind_exact_image( $xpath, $section, './/img[contains(@class,"schedule-plan__img")][1]', leadwerk_francis_image_id( $value['schedule_image'] ?? 0 ), leadwerk_francis_image_alt( $value['schedule_image'] ?? array(), (string) ( $value['schedule_image_alt'] ?? '' ) ) );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"schedule-next")][1]', $value['body'] ?? '' );
	leadwerk_theme_bind_exact_button( $xpath, $section, './/a[contains(@class,"btn")][1]', (string) ( $value['cta_label'] ?? '' ), (string) ( $value['cta_page_key'] ?? '' ), (string) ( $value['cta_url'] ?? '' ) );
}

function leadwerk_theme_bind_francis_event_schedule( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/*[contains(@class,"event-schedule__eyebrow")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"event-schedule__title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"event-schedule__intro")][1]', $value['intro'] ?? '' );
	$rows  = leadwerk_theme_dom_query( $xpath, './/*[contains(@class,"event-row")]', $section );
	$items = is_array( $value['items'] ?? null ) ? array_values( $value['items'] ) : array();
	foreach ( $rows as $idx => $row ) {
		if ( ! isset( $items[ $idx ] ) ) {
			break;
		}
		$item = $items[ $idx ];
		leadwerk_francis_bind_label( $xpath, $row, './/*[contains(@class,"event-row__day")][1]', $item['day'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $row, './/*[contains(@class,"event-row__month")][1]', $item['month'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $row, './/h3[contains(@class,"event-row__title")][1]', $item['title'] ?? '' );
		leadwerk_francis_bind_label( $xpath, $row, './/*[contains(@class,"event-row__location")][1]', $item['location'] ?? '' );
		leadwerk_francis_bind_editor( $xpath, $row, './/*[contains(@class,"event-row__text")][1]', $item['description'] ?? '' );
		leadwerk_theme_bind_exact_image( $xpath, $row, './/img[1]', leadwerk_francis_image_id( $item['image'] ?? 0 ), leadwerk_francis_image_alt( $item['image'] ?? array(), (string) ( $item['image_alt'] ?? '' ) ) );
	}
}

function leadwerk_francis_gallery_item_xpath() {
	return './/*[contains(@class,"impressions__item")] | .//figure | .//*[contains(@class,"gallery__item")]';
}

function leadwerk_theme_bind_francis_gallery_grid( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	$figures = leadwerk_theme_dom_query( $xpath, leadwerk_francis_gallery_item_xpath(), $section );
	$images  = is_array( $value['images'] ?? null ) ? array_values( $value['images'] ) : array();
	foreach ( $figures as $idx => $figure ) {
		if ( ! isset( $images[ $idx ] ) ) {
			break;
		}
		leadwerk_theme_bind_exact_image( $xpath, $figure, './/img[1]', leadwerk_francis_image_id( $images[ $idx ]['image'] ?? 0 ), leadwerk_francis_image_alt( $images[ $idx ]['image'] ?? array(), (string) ( $images[ $idx ]['image_alt'] ?? '' ) ) );
		leadwerk_francis_bind_label( $xpath, $figure, './/figcaption[1]', $images[ $idx ]['caption'] ?? '' );
	}
}

function leadwerk_theme_bind_francis_contact_form( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"section-title")][1]', $value['title'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"team__header-text")][1]', $value['intro'] ?? '' );
	leadwerk_francis_bind_label( $xpath, $section, './/*[contains(@class,"reach-form__hint")][1]', $value['form_hint'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"reach-aside")][1]', $value['address_html'] ?? '' );
}

function leadwerk_theme_bind_francis_thank_you( $xpath, $section, $value ) {
	leadwerk_francis_bind_label( $xpath, $section, './/span[contains(@class,"section-label")][1]', $value['label'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"utility-page__title-main")][1]', $value['title_line_1'] ?? '' );
	leadwerk_francis_bind_heading( $xpath, $section, './/*[contains(@class,"utility-page__title-accent")][1]', $value['title_line_2'] ?? '' );
	leadwerk_francis_bind_editor( $xpath, $section, './/*[contains(@class,"utility-page__lead")][1]', $value['body'] ?? '' );
	leadwerk_theme_bind_exact_button(
		$xpath,
		$section,
		'.//div[contains(@class,"utility-page__actions")]//a[contains(@class,"btn")][1]',
		(string) ( $value['cta_primary_label'] ?? '' ),
		(string) ( $value['cta_primary_page_key'] ?? '' ),
		(string) ( $value['cta_primary_url'] ?? '' )
	);
	leadwerk_theme_bind_exact_button(
		$xpath,
		$section,
		'.//div[contains(@class,"utility-page__actions")]//a[contains(@class,"btn")][2]',
		(string) ( $value['cta_secondary_label'] ?? '' ),
		(string) ( $value['cta_secondary_page_key'] ?? '' ),
		(string) ( $value['cta_secondary_url'] ?? '' )
	);
}

function leadwerk_theme_bind_francis_layout_section( $xpath, $section_node, $template, $value ) {
	switch ( $template ) {
		case 'francis_hero_video':
			leadwerk_theme_bind_francis_hero_video( $xpath, $section_node, $value );
			break;
		case 'francis_hero_subpage':
			leadwerk_theme_bind_francis_hero_subpage( $xpath, $section_node, $value );
			break;
		case 'francis_about_split':
			leadwerk_theme_bind_francis_about_split( $xpath, $section_node, $value );
			break;
		case 'francis_classes_grid':
			leadwerk_theme_bind_francis_classes_grid( $xpath, $section_node, $value );
			break;
		case 'francis_contact_statement':
			leadwerk_theme_bind_francis_contact_statement( $xpath, $section_node, $value );
			break;
		case 'francis_team_grid':
		case 'francis_team_showcase':
			leadwerk_theme_bind_francis_team_grid( $xpath, $section_node, $value );
			break;
		case 'francis_impressions_slider':
			leadwerk_theme_bind_francis_impressions_slider( $xpath, $section_node, $value );
			break;
		case 'francis_testimonials':
			leadwerk_theme_bind_francis_testimonials( $xpath, $section_node, $value );
			break;
		case 'francis_promo_banner':
		case 'francis_promo_cta':
			leadwerk_theme_bind_francis_promo_banner( $xpath, $section_node, $value );
			break;
		case 'francis_pricing':
			leadwerk_theme_bind_francis_pricing( $xpath, $section_node, $value );
			break;
		case 'francis_values_grid':
			leadwerk_theme_bind_francis_values_grid( $xpath, $section_node, $value );
			break;
		case 'francis_statement':
			leadwerk_theme_bind_francis_statement( $xpath, $section_node, $value );
			break;
		case 'francis_course_content':
			leadwerk_theme_bind_francis_course_content( $xpath, $section_node, $value );
			break;
		case 'francis_schedule_table':
			leadwerk_theme_bind_francis_schedule_table( $xpath, $section_node, $value );
			break;
		case 'francis_event_schedule':
			leadwerk_theme_bind_francis_event_schedule( $xpath, $section_node, $value );
			break;
		case 'francis_gallery_grid':
			leadwerk_theme_bind_francis_gallery_grid( $xpath, $section_node, $value );
			break;
		case 'francis_contact_form':
			leadwerk_theme_bind_francis_contact_form( $xpath, $section_node, $value );
			break;
		case 'francis_thank_you':
			leadwerk_theme_bind_francis_thank_you( $xpath, $section_node, $value );
			break;
	}
}
