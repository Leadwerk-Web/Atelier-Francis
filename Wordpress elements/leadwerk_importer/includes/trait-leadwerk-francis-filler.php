<?php
/**
 * Atelier Francis HTML parsers for Leadwerk_ACF_Filler.
 *
 * @package Leadwerk_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Leadwerk_Francis_Filler {

	/**
	 * Francis href → source_key map.
	 *
	 * @return array<string,string>
	 */
	protected function get_francis_href_map() {
		return array(
			''                      => 'francis-index-v1',
			'index.html'            => 'francis-index-v1',
			'ueber-uns.html'        => 'francis-ueber-uns-v1',
			'stundenplan.html'      => 'francis-stundenplan-v1',
			'kursuebersicht.html'   => 'francis-kursuebersicht-v1',
			'kurs-kindertanz.html'  => 'francis-kurs-kindertanz-v1',
			'kurs-ballett-kinder.html' => 'francis-kurs-ballett-kinder-v1',
			'kurs-ballett-erwachsene.html' => 'francis-kurs-ballett-erwachsene-v1',
			'kurs-modern.html'      => 'francis-kurs-modern-v1',
			'kurs-hip-hop.html'     => 'francis-kurs-hip-hop-v1',
			'kurs-pilates.html'     => 'francis-kurs-pilates-v1',
			'kurs-choreo-fit.html'  => 'francis-kurs-choreo-fit-v1',
			'kurs-latino.html'      => 'francis-kurs-latino-v1',
			'aktuelles.html'        => 'francis-aktuelles-v1',
			'eindruecke.html'       => 'francis-eindruecke-v1',
			'kontakt.html'          => 'francis-kontakt-v1',
			'danke.html'            => 'francis-danke-v1',
			'impressum.html'        => 'francis-impressum-v1',
			'datenschutz.html'      => 'francis-datenschutz-v1',
			'en/index.html'         => 'francis-index-v1',
			'en/ueber-uns.html'     => 'francis-ueber-uns-v1',
			'en/stundenplan.html'   => 'francis-stundenplan-v1',
			'en/kursuebersicht.html'=> 'francis-kursuebersicht-v1',
			'en/kurs-kindertanz.html' => 'francis-kurs-kindertanz-v1',
			'en/kurs-ballett-kinder.html' => 'francis-kurs-ballett-kinder-v1',
			'en/kurs-ballett-erwachsene.html' => 'francis-kurs-ballett-erwachsene-v1',
			'en/kurs-modern.html'   => 'francis-kurs-modern-v1',
			'en/kurs-hip-hop.html'  => 'francis-kurs-hip-hop-v1',
			'en/kurs-pilates.html'  => 'francis-kurs-pilates-v1',
			'en/kurs-choreo-fit.html' => 'francis-kurs-choreo-fit-v1',
			'en/kurs-latino.html'   => 'francis-kurs-latino-v1',
			'en/aktuelles.html'     => 'francis-aktuelles-v1',
			'en/eindruecke.html'    => 'francis-eindruecke-v1',
			'en/kontakt.html'       => 'francis-kontakt-v1',
			'en/impressum.html'     => 'francis-impressum-v1',
			'en/datenschutz.html'   => 'francis-datenschutz-v1',
		);
	}

	/**
	 * Selector fallbacks for Francis field groups.
	 *
	 * @param string $field_name Field group.
	 * @return array<string,string>
	 */
	protected function get_francis_selector_fallbacks( $field_name ) {
		$maps = array(
			'francis_index_sections' => array(
				'hero'         => '#hero',
				'about'        => '#about',
				'classes'      => '#classes',
				'statement'    => '#contact',
				'team'         => '#team',
				'impressions'  => '#impressions',
				'testimonials' => '#testimonials',
				'promo'        => '.promo',
				'pricing'      => '.pricing',
			),
			'francis_ueber_uns_sections' => array(
				'hero'          => '.hero--subpage',
				'philosophy'    => '#philosophy',
				'values'        => '.values',
				'team_showcase' => '#team',
				'statement'     => '.statement',
				'testimonials'  => '#testimonials',
			),
			'francis_course_sections' => array(
				'hero'    => '.hero--subpage',
				'content' => '.page-section',
			),
			'francis_stundenplan_sections' => array(
				'hero'     => '.hero--subpage',
				'schedule' => '.page-section',
			),
			'francis_kursuebersicht_sections' => array(
				'hero'    => '.hero--subpage',
				'classes' => '.classes--kurse',
			),
			'francis_aktuelles_sections' => array(
				'hero'   => '.hero--subpage',
				'events' => '.page-section--aktuelles',
			),
			'francis_eindruecke_sections' => array(
				'hero'    => '.hero--subpage',
				'gallery' => '#gallery',
				'promo'   => '.page-section--warm',
			),
			'francis_kontakt_sections' => array(
				'hero'    => '.hero--subpage',
				'contact' => '.page-section--reach',
			),
			'francis_danke_sections' => array(
				'hero'   => '.hero--subpage',
				'thanks' => '.utility-page--thanks',
			),
		);
		return $maps[ $field_name ] ?? array();
	}

	protected function francis_exact_class_xpath( $class_name ) {
		$class_name = trim( (string) $class_name );
		if ( '' === $class_name ) {
			return './/*';
		}
		return './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")]';
	}

	protected function francis_section_title_xpath() {
		return $this->francis_exact_class_xpath( 'section-title' ) . '[1]';
	}

	protected function parse_francis_hero_video( $section_node ) {
		$video_src = $this->attr( $section_node, './/video/source[1]', 'src' );
		$video_id  = $this->resolve_image_field( $video_src )['id'];
		$button    = $this->parse_button( $section_node, './/a[contains(@class,"btn")][1]' );
		return array_merge(
			array(
				'title' => $this->html( $section_node, './/h1[contains(@class,"hero__title")][1]' ),
				'video' => $video_id,
				'video_poster' => 0,
			),
			$button
		);
	}

	protected function parse_francis_hero_subpage( $section_node ) {
		$img = $this->parse_image_fields( $section_node, './/img[contains(@class,"hero__video")][1]' );
		return array(
			'label'     => $this->text( $section_node, './/span[contains(@class,"hero__label")][1]' ),
			'title'     => $this->html( $section_node, './/h1[contains(@class,"hero__title")][1]' ),
			'image'     => $img['id'],
			'image_alt' => $img['alt'],
		);
	}

	protected function parse_francis_about_split( $section_node ) {
		$img    = $this->parse_image_fields( $section_node, './/img[1]' );
		$button = $this->parse_button( $section_node, './/a[contains(@class,"btn")][1]' );
		$kpis   = array();
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"about__detail")]' ) as $node ) {
			$kpis[] = array(
				'number' => $this->text( $node, './/*[contains(@class,"about__detail-number")][1]' ),
				'label'  => $this->text( $node, './/*[contains(@class,"about__detail-label")][1]' ),
			);
		}
		return array_merge(
			array(
				'label'     => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
				'title'     => $this->html( $section_node, $this->francis_section_title_xpath() ),
				'body'      => $this->editor_html( $section_node, './/*[contains(@class,"about__text")][1]' ),
				'image'     => $img['id'],
				'image_alt' => $img['alt'],
				'kpis'      => $kpis,
			),
			$button
		);
	}

	protected function parse_francis_classes_grid( $section_node ) {
		$items = array();
		foreach ( $this->query_nodes( $section_node, './/article[contains(@class,"class-card")]' ) as $card ) {
			$img  = $this->parse_image_fields( $card, './/img[1]' );
			$link = $this->parse_link_target( $this->attr( $card, './/a[1]', 'href' ) );
			$desc = $this->text( $card, './/*[contains(@class,"class-card__level")][1]' );
			if ( '' === $desc ) {
				$desc = $this->text( $card, './/*[contains(@class,"class-card__desc")][1]' );
			}
			$items[] = array(
				'title'       => $this->text( $card, './/h3[contains(@class,"class-card__title")][1]' ),
				'description' => $desc,
				'image'       => $img['id'],
				'image_alt'   => $img['alt'],
				'page_key'    => $link['page_key'],
				'url'         => $link['url'],
			);
		}
		return array(
			'label' => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title' => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'items' => $items,
		);
	}

	protected function parse_francis_contact_statement( $section_node ) {
		$background = $this->parse_image_fields( $section_node, './/*[contains(@class,"statement__bg")]//img[1]' );
		$buttons    = $this->query_nodes( $section_node, './/*[contains(@class,"statement__actions")]//a[contains(@class,"btn")]' );
		$primary    = isset( $buttons[0] ) ? $this->parse_button( $buttons[0], '.' ) : $this->parse_button( $section_node, './/a[contains(@class,"btn")][1]' );
		$secondary  = isset( $buttons[1] ) ? $this->parse_button( $buttons[1], '.' ) : array(
			'cta_label'    => '',
			'cta_page_key' => '',
			'cta_url'      => '',
		);
		return array_merge(
			array(
				'label'                => $this->text( $section_node, './/*[contains(@class,"statement__label")][1]' ),
				'title'                => $this->html( $section_node, './/h2[contains(@class,"statement__title")][1] | ' . $this->francis_section_title_xpath() . ' | .//h2[1]' ),
				'body'                 => $this->editor_html( $section_node, './/*[contains(@class,"statement__text")][1] | .//*[contains(@class,"about__text")][1]' ),
				'background_image'     => $background['id'],
				'background_image_alt' => $background['alt'],
				'secondary_label'      => $secondary['cta_label'] ?? '',
				'secondary_page_key'   => $secondary['cta_page_key'] ?? '',
				'secondary_url'        => $secondary['cta_url'] ?? '',
			),
			$primary
		);
	}

	protected function parse_francis_team_grid( $section_node ) {
		$items = array();
		$card_queries = array(
			'.//article[contains(@class,"team-card")]',
			'.//article[contains(@class,"team-showcase__member")]',
		);
		$cards = array();
		foreach ( $card_queries as $query ) {
			$found = $this->query_nodes( $section_node, $query );
			if ( ! empty( $found ) ) {
				$cards = $found;
				break;
			}
		}
		foreach ( $cards as $card ) {
			$img = $this->parse_image_fields( $card, './/img[1]' );
			$items[] = array(
				'name'      => $this->text( $card, './/*[contains(@class,"team-card__name") or contains(@class,"team-showcase__name")][1]' ),
				'role'      => $this->text( $card, './/*[contains(@class,"team-card__role") or contains(@class,"team-showcase__role")][1]' ),
				'bio'       => $this->editor_html( $card, './/*[contains(@class,"team-card__bio") or contains(@class,"team-showcase__quote")][1]' ),
				'image'     => $img['id'],
				'image_alt' => $img['alt'],
			);
		}
		return array(
			'label' => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title' => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'intro' => $this->editor_html( $section_node, './/*[contains(@class,"team__header-text")][1]' ),
			'items' => $items,
		);
	}

	protected function parse_francis_impressions_slider( $section_node ) {
		$images = array();
		$item_xpath = $this->francis_gallery_item_xpath();
		foreach ( $this->query_nodes( $section_node, $item_xpath ) as $item ) {
			$src = $this->attr( $item, './/img[1]', 'src' );
			if ( '' === trim( (string) $src ) ) {
				continue;
			}
			$img = $this->parse_image_fields( $item, './/img[1]' );
			$alt = trim( (string) $img['alt'] );
			if ( '' === $alt ) {
				$alt = $this->text( $item, './/*[contains(@class,"impressions__caption")]//span[1]' );
			}
			$images[] = array(
				'image'     => (int) $img['id'],
				'image_alt' => $alt,
			);
		}
		return array(
			'label'  => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title'  => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'images' => $images,
		);
	}

	protected function parse_francis_testimonials( $section_node ) {
		$items = array();
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"testimonials__slide")]' ) as $slide ) {
			$items[] = array(
				'quote'  => $this->editor_html( $slide, './/*[contains(@class,"testimonials__quote")][1]' ),
				'author' => $this->text( $slide, './/*[contains(@class,"testimonials__author")][1]' ),
				'role'   => $this->text( $slide, './/*[contains(@class,"testimonials__role")][1]' ),
			);
		}
		return array(
			'label' => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title' => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'items' => $items,
		);
	}

	protected function parse_francis_promo_banner( $section_node ) {
		$img = $this->parse_image_fields( $section_node, './/*[contains(@class,"promo__image")]//img[1] | .//img[1]' );
		$btns = $this->query_nodes( $section_node, './/a[contains(@class,"btn")]' );
		$primary   = isset( $btns[0] ) ? $this->parse_button( $btns[0], '.' ) : array();
		$secondary = isset( $btns[1] ) ? $this->parse_button( $btns[1], '.' ) : array();
		return array(
			'title'              => $this->html( $section_node, './/h2[contains(@class,"promo__title")][1]' ),
			'body'               => $this->editor_html( $section_node, './/*[contains(@class,"promo__text")][1]' ),
			'image'              => $img['id'],
			'image_alt'          => $img['alt'],
			'primary_label'      => $primary['cta_label'] ?? '',
			'primary_page_key'   => $primary['cta_page_key'] ?? '',
			'primary_url'        => $primary['cta_url'] ?? '',
			'secondary_label'    => $secondary['cta_label'] ?? '',
			'secondary_page_key' => $secondary['cta_page_key'] ?? '',
			'secondary_url'      => $secondary['cta_url'] ?? '',
		);
	}

	protected function parse_francis_pricing( $section_node ) {
		$items = array();
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"pricing__card")]' ) as $card ) {
			$btn = $this->parse_button( $card, './/a[contains(@class,"btn")][1]' );
			$items[] = array(
				'label'        => $this->text( $card, './/*[contains(@class,"pricing__card-label")][1]' ),
				'price'        => $this->text( $card, './/*[contains(@class,"pricing__card-price")][1]' ),
				'period'       => $this->text( $card, './/*[contains(@class,"pricing__card-period")][1]' ),
				'description'  => $this->text( $card, './/*[contains(@class,"pricing__card-desc")][1]' ),
				'featured'     => false !== strpos( (string) $this->attr( $card, '.', 'class' ), 'pricing__card--featured' ),
				'cta_label'    => $btn['cta_label'] ?? '',
				'cta_page_key' => $btn['cta_page_key'] ?? '',
				'cta_url'      => $btn['cta_url'] ?? '',
			);
		}
		$notes = '';
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"pricing__note")]' ) as $note ) {
			$notes .= '<p>' . esc_html( trim( $note->textContent ) ) . '</p>';
		}
		return array(
			'label' => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title' => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'items' => $items,
			'notes' => $notes,
		);
	}

	protected function parse_francis_values_grid( $section_node ) {
		$items = array();
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"values__item")]' ) as $item ) {
			$items[] = array(
				'title'   => $this->text( $item, './/h3[1]' ),
				'content' => $this->editor_html( $item, './/p[1]' ),
			);
		}
		return array(
			'label' => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title' => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'items' => $items,
		);
	}

	protected function parse_francis_statement( $section_node ) {
		return array(
			'quote'  => $this->editor_html( $section_node, './/*[contains(@class,"statement__quote")][1]' ),
			'author' => $this->text( $section_node, './/*[contains(@class,"statement__author")][1]' ),
		);
	}

	protected function parse_francis_course_content( $section_node ) {
		$gallery = array();
		foreach ( $this->query_nodes( $section_node, './/figure//img | .//*[contains(@class,"course-page__mini-gallery")]//img' ) as $img_node ) {
			if ( ! $img_node instanceof DOMElement ) {
				continue;
			}
			$gallery[] = array(
				'image'     => $this->resolve_image_field( (string) $img_node->getAttribute( 'src' ) )['id'],
				'image_alt' => (string) $img_node->getAttribute( 'alt' ),
			);
		}
		return array(
			'label'   => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title'   => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'subline' => $this->text( $section_node, './/*[contains(@class,"course-page__subline")][1]' ),
			'body'    => $this->editor_html( $section_node, './/*[contains(@class,"course-page__prose")][1]' ),
			'gallery' => $gallery,
		);
	}

	protected function parse_francis_schedule_table( $section_node ) {
		$img    = $this->parse_image_fields( $section_node, './/img[contains(@class,"schedule-plan__img")][1]' );
		$button = $this->parse_button( $section_node, './/a[contains(@class,"btn")][1]' );
		return array_merge(
			array(
				'label'              => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
				'title'              => $this->html( $section_node, $this->francis_section_title_xpath() ),
				'intro'              => $this->editor_html( $section_node, './/*[contains(@class,"schedule-page__header")][1]' ),
				'schedule_image'     => $img['id'],
				'schedule_image_alt' => $img['alt'],
				'body'               => $this->editor_html( $section_node, './/*[contains(@class,"schedule-next")][1]' ),
			),
			$button
		);
	}

	protected function parse_francis_event_schedule( $section_node ) {
		$items = array();
		foreach ( $this->query_nodes( $section_node, './/*[contains(@class,"event-row")]' ) as $row ) {
			$img = $this->parse_image_fields( $row, './/img[1]' );
			$items[] = array(
				'day'         => $this->text( $row, './/*[contains(@class,"event-row__day")][1]' ),
				'month'       => $this->text( $row, './/*[contains(@class,"event-row__month")][1]' ),
				'title'       => $this->text( $row, './/h3[contains(@class,"event-row__title")][1]' ),
				'location'    => $this->text( $row, './/*[contains(@class,"event-row__location")][1]' ),
				'description' => $this->editor_html( $row, './/*[contains(@class,"event-row__text")][1]' ),
				'image'       => $img['id'],
				'image_alt'   => $img['alt'],
			);
		}
		return array(
			'label' => $this->text( $section_node, './/*[contains(@class,"event-schedule__eyebrow")][1]' ),
			'title' => $this->html( $section_node, './/*[contains(@class,"event-schedule__title")][1]' ),
			'intro' => $this->editor_html( $section_node, './/*[contains(@class,"event-schedule__intro")][1]' ),
			'items' => $items,
		);
	}

	protected function francis_gallery_item_xpath() {
		return './/*[contains(@class,"impressions__item")] | .//figure | .//*[contains(@class,"gallery__item")]';
	}

	protected function parse_francis_gallery_grid( $section_node ) {
		$images = array();
		foreach ( $this->query_nodes( $section_node, $this->francis_gallery_item_xpath() ) as $item ) {
			$src = $this->attr( $item, './/img[1]', 'src' );
			if ( '' === trim( (string) $src ) ) {
				continue;
			}
			$img = $this->parse_image_fields( $item, './/img[1]' );
			$alt = trim( (string) $img['alt'] );
			if ( '' === $alt ) {
				$alt = $this->text( $item, './/figcaption[1]' );
			}
			$images[] = array(
				'image'     => (int) $img['id'],
				'image_alt' => $alt,
				'caption'   => $this->text( $item, './/figcaption[1]' ) ?: $alt,
			);
		}
		return array(
			'label'  => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title'  => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'images' => $images,
		);
	}

	protected function parse_francis_contact_form( $section_node ) {
		return array(
			'label'        => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title'        => $this->html( $section_node, $this->francis_section_title_xpath() ),
			'intro'        => $this->editor_html( $section_node, './/*[contains(@class,"team__header-text")][1]' ),
			'form_hint'    => $this->text( $section_node, './/*[contains(@class,"reach-form__hint")][1]' ),
			'address_html' => $this->editor_html( $section_node, './/*[contains(@class,"reach-aside")][1]' ),
		);
	}

	protected function parse_francis_thank_you( $section_node ) {
		$primary   = $this->parse_button( $section_node, './/div[contains(@class,"utility-page__actions")]//a[contains(@class,"btn")][1]' );
		$secondary = $this->parse_button( $section_node, './/div[contains(@class,"utility-page__actions")]//a[contains(@class,"btn")][2]' );

		return array(
			'label'                => $this->text( $section_node, './/span[contains(@class,"section-label")][1]' ),
			'title_line_1'         => $this->html( $section_node, './/*[contains(@class,"utility-page__title-main")][1]' ),
			'title_line_2'         => $this->html( $section_node, './/*[contains(@class,"utility-page__title-accent")][1]' ),
			'body'                 => $this->editor_html( $section_node, './/*[contains(@class,"utility-page__lead")][1]' ),
			'cta_primary_label'    => $primary['cta_label'] ?? '',
			'cta_primary_page_key' => $primary['cta_page_key'] ?? '',
			'cta_primary_url'      => $primary['cta_url'] ?? '',
			'cta_secondary_label'    => $secondary['cta_label'] ?? '',
			'cta_secondary_page_key' => $secondary['cta_page_key'] ?? '',
			'cta_secondary_url'      => $secondary['cta_url'] ?? '',
		);
	}

	/**
	 * Editor HTML from inner nodes.
	 *
	 * @param DOMNode $context Context.
	 * @param string  $query   XPath.
	 * @return string
	 */
	protected function editor_html( $context, $query ) {
		$node = $this->first_node( $context, $query );
		if ( ! $node instanceof DOMNode ) {
			return '';
		}
		return $this->save_inner_html( $node );
	}

	/**
	 * Parse Francis layout by template key.
	 *
	 * @param string              $template     Template key.
	 * @param DOMNode             $section_node Section node.
	 * @return array<string,mixed>|null
	 */
	protected function parse_francis_layout( $template, $section_node ) {
		switch ( $template ) {
			case 'francis_hero_video':
				return $this->parse_francis_hero_video( $section_node );
			case 'francis_hero_subpage':
				return $this->parse_francis_hero_subpage( $section_node );
			case 'francis_about_split':
				return $this->parse_francis_about_split( $section_node );
			case 'francis_classes_grid':
				return $this->parse_francis_classes_grid( $section_node );
			case 'francis_contact_statement':
				return $this->parse_francis_contact_statement( $section_node );
			case 'francis_team_grid':
			case 'francis_team_showcase':
				return $this->parse_francis_team_grid( $section_node );
			case 'francis_impressions_slider':
				return $this->parse_francis_impressions_slider( $section_node );
			case 'francis_testimonials':
				return $this->parse_francis_testimonials( $section_node );
			case 'francis_promo_banner':
			case 'francis_promo_cta':
				return $this->parse_francis_promo_banner( $section_node );
			case 'francis_pricing':
				return $this->parse_francis_pricing( $section_node );
			case 'francis_values_grid':
				return $this->parse_francis_values_grid( $section_node );
			case 'francis_statement':
				return $this->parse_francis_statement( $section_node );
			case 'francis_course_content':
				return $this->parse_francis_course_content( $section_node );
			case 'francis_schedule_table':
				return $this->parse_francis_schedule_table( $section_node );
			case 'francis_event_schedule':
				return $this->parse_francis_event_schedule( $section_node );
			case 'francis_gallery_grid':
				return $this->parse_francis_gallery_grid( $section_node );
			case 'francis_contact_form':
				return $this->parse_francis_contact_form( $section_node );
			case 'francis_thank_you':
				return $this->parse_francis_thank_you( $section_node );
		}
		return null;
	}
}
